<?php
namespace Ipf\Bib\View;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
 *      Goettingen State Library
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

use \Ipf\Bib\Exception\DataException;
use \Ipf\Bib\Utility\Utility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use \TYPO3\CMS\Core\Messaging\FlashMessage;

class EditorView {

	/**
	 * @var \tx_bib_pi1
	 */
	public $pi1;

	/**
	 * @var string
	 */
	protected $buttonClass;

	/**
	 * @var array
	 */
	public $conf;

	/**
	 * @var \Ipf\Bib\Utility\ReferenceReader
	 */
	public $referenceReader;

	/**
	 * @var \Ipf\Bib\Utility\ReferenceWriter
	 */
	public $referenceWriter;
	/**
	 * Database Utility
	 *
	 * @var \Ipf\Bib\Utility\DbUtility
	 */
	public $databaseUtility;

	/**
	 * @var string
	 */
	public $LLPrefix = 'editor_';

	/**
	 * @var \Ipf\Bib\Utility\Generator\CiteIdGenerator|bool
	 */
	public $idGenerator = FALSE;

	/**
	 * @var bool
	 */
	public $isNew = FALSE;

	/**
	 * @var bool
	 */
	public $isFirstEdit = FALSE;

	/**
	 * @var int
	 */
	protected $widgetMode;

	/**
	 * Show and pass value
	 */
	const WIDGET_SHOW = 0;

	/**
	 * Edit and pass value
	 */
	const WIDGET_EDIT = 1;

	/**
	 * Don't show but pass value
	 */
	const WIDGET_SILENT = 2;

	/**
	 * Don't show and don't pass value
	 */
	const WIDGET_HIDDEN = 3;


	/**
	 * Initializes this class
	 *
	 * @param \tx_bib_pi1 $pi1
	 * @return void
	 */
	public function initialize($pi1) {

		/** @var \tx_bib_pi1 pi1 */
		$this->pi1 =& $pi1;
		$this->conf =& $pi1->conf['editor.'];
		$this->referenceReader =& $pi1->referenceReader;
		$this->referenceReader->setClearCache($this->pi1->extConf['editor']['clear_page_cache']);
		// Load editor language data
		$this->pi1->extend_ll('EXT:' . $this->pi1->extKey . '/Resources/Private/Language/locallang.xml');

		// setup db_utility
		/** @var \Ipf\Bib\Utility\DbUtility $databaseUtility */
		$databaseUtility = GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\DbUtility');
		$databaseUtility->initialize($pi1->referenceReader);
		$databaseUtility->charset = $pi1->extConf['charset']['upper'];
		$databaseUtility->readFullTextGenerationConfiguration($this->conf['full_text.']);

		$this->databaseUtility = $databaseUtility;

		// Create an instance of the citeid generator
		if (isset ($this->conf['citeid_generator_file'])) {
			$ext_file = $GLOBALS['TSFE']->tmpl->getFileName($this->conf['citeid_generator_file']);
			if (file_exists($ext_file)) {
				require_once($ext_file);
				$this->idGenerator = GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\Generator\\AuthorsCiteIdGenerator');
			}
		} else {
			$this->idGenerator = GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\Generator\\CiteIdGenerator');
		}
		$this->idGenerator->initialize($pi1);
	}


	/**
	 * Get the string in the local language to a given key from
	 * the database language file
	 *
	 * @todo find out why we should need that - just a wrapper for pi1->get_ll
	 *
	 * @param String $key
	 * @param String $alt
	 * @param bool $hsc
	 *
	 * @return String The string in the local language
	 */
	protected function get_ll($key, $alt = '', $hsc = FALSE) {
		return $this->pi1->get_ll($key, $alt, $hsc);
	}

	/**
	 * @param string $key
	 * @param string $alt
	 * @param bool $hsc
	 * @return string
	 */
	protected function get_db_ll($key, $alt = '', $hsc = FALSE) {
		$key = str_replace('LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:', '', $key);
		return $this->pi1->get_ll($key, $alt, $hsc);
	}


	/**
	 * The editor shows a single publication entry
	 * and allows to edit, delete or save it.
	 *
	 * @throws \Exception
	 * @return string A publication editor
	 */
	public function editor_view() {
		$content = '';

		// check whether the BE user is authorized
		if (!$this->pi1->extConf['edit_mode']) {
			throw new \Exception('You are not authorized to edit the publication database.', 1379074809);
		}

		$pub_http = $this->getPublicationDataFromHttpRequest();
		$pub_db = array();
		$publicationData = array();
		$preContent = '';
		$uid = -1;
		$dataValid = TRUE;
		$this->buttonClass = $this->pi1->prefixShort . '-editor_button';

		// Determine widget mode
		switch ($this->pi1->extConf['editor_mode']) {
			case \tx_bib_pi1::EDIT_SHOW :
				$this->widgetMode = self::WIDGET_SHOW;
				break;
			case \tx_bib_pi1::EDIT_EDIT :
			case \tx_bib_pi1::EDIT_NEW :
				$this->widgetMode = self::WIDGET_EDIT;
				break;
			case \tx_bib_pi1::EDIT_CONFIRM_SAVE :
			case \tx_bib_pi1::EDIT_CONFIRM_DELETE :
			case \tx_bib_pi1::EDIT_CONFIRM_ERASE :
				$this->widgetMode = self::WIDGET_SHOW;
				break;
			default :
				$this->widgetMode = self::WIDGET_SHOW;
		}

		// determine entry uid
		if (array_key_exists('uid', $this->pi1->piVars)) {
			if (is_numeric($this->pi1->piVars['uid'])) {
				$uid = intval($this->pi1->piVars['uid']);
			}
		}

		$this->isNew = TRUE;
		if ($uid >= 0) {
			$this->isNew = FALSE;
			$pub_http['uid'] = $uid;
		}

		$this->isFirstEdit = TRUE;
		if (is_array($this->pi1->piVars['DATA']['pub'])) {
			$this->isFirstEdit = FALSE;
		}

		$title = $this->LLPrefix;
		switch ($this->pi1->extConf['editor_mode']) {
			case \tx_bib_pi1::EDIT_SHOW :
				$title .= 'title_view';
				break;
			case \tx_bib_pi1::EDIT_EDIT :
				$title .= 'title_edit';
				break;
			case \tx_bib_pi1::EDIT_NEW :
				$title .= 'title_new';
				break;
			case \tx_bib_pi1::EDIT_CONFIRM_DELETE :
				$title .= 'title_confirm_delete';
				break;
			case \tx_bib_pi1::EDIT_CONFIRM_ERASE :
				$title .= 'title_confirm_erase';
				break;
			default:
				$title .= 'title_edit';
				break;
		}
		$title = $this->get_ll($title);

		// Load default data
		if ($this->isFirstEdit) {
			if ($this->isNew) {
				// Load defaults for a new publication 
				$publicationData = $this->getDefaultPublicationData();
			} else {
				if ($uid < 0) {
					return $this->pi1->errorMessage('No publication id given');
				}

				// Load publication data from database
				$pub_db = $this->referenceReader->getPublicationDetails($uid);
				if ($pub_db) {
					$publicationData = array_merge($publicationData, $pub_db);
				} else {
					return $this->pi1->errorMessage('No publication with uid: ' . $uid);
				}
			}
		}

		// Merge in data from HTTP request
		$publicationData = array_merge($publicationData, $pub_http);

		// Generate cite id if requested
		$generateCiteId = FALSE;
		$generateCiteIdRequest = FALSE;

		// Evaluate actions
		if (is_array($this->pi1->piVars['action'])) {

			// Generate cite id
			if (array_key_exists('generate_id', $this->pi1->piVars['action'])) {
				$generateCiteIdRequest = TRUE;
			}

			// Raise author
			if (is_numeric($this->pi1->piVars['action']['raise_author'])) {
				$num = intval($this->pi1->piVars['action']['raise_author']);
				if (($num > 0) && ($num < sizeof($publicationData['authors']))) {
					$tmp = $publicationData['authors'][$num - 1];
					$publicationData['authors'][$num - 1] = $publicationData['authors'][$num];
					$publicationData['authors'][$num] = $tmp;
				}
			}

			// Lower author
			if (is_numeric($this->pi1->piVars['action']['lower_author'])) {
				$num = intval($this->pi1->piVars['action']['lower_author']);
				if (($num >= 0) && ($num < (sizeof($publicationData['authors']) - 1))) {
					$tmp = $publicationData['authors'][$num + 1];
					$publicationData['authors'][$num + 1] = $publicationData['authors'][$num];
					$publicationData['authors'][$num] = $tmp;
				}
			}

			if (isset($this->pi1->piVars['action']['more_authors'])) {
				$this->pi1->piVars['editor']['numAuthors'] += 1;
			}
			if (isset($this->pi1->piVars['action']['less_authors'])) {
				$this->pi1->piVars['editor']['numAuthors'] -= 1;
			}
		}

		// Generate cite id on demand
		if ($this->isNew) {
			// Generate cite id for new entries
			switch ($this->pi1->extConf['editor']['citeid_gen_new']) {
				case \tx_bib_pi1::AUTOID_FULL:
					$generateCiteId = TRUE;
					break;
				case \tx_bib_pi1::AUTOID_HALF:
					if ($generateCiteIdRequest) {
						$generateCiteId = TRUE;
					}
					break;
				default:
					break;
			}
		} else {
			// Generate cite id for already existing (old) entries
			$auto_id = $this->pi1->extConf['editor']['citeid_gen_old'];
			if (($generateCiteIdRequest && ($auto_id == \tx_bib_pi1::AUTOID_HALF))
					|| (strlen($publicationData['citeid']) == 0)
			) {
				$generateCiteId = TRUE;
			}
		}
		if ($generateCiteId) {
			$publicationData['citeid'] = $this->idGenerator->generateId($publicationData);
		}

		// Determine the number of authors
		$this->pi1->piVars['editor']['numAuthors'] = max(
			$this->pi1->piVars['editor']['numAuthors'],
			$this->conf['numAuthors'],
			sizeof($publicationData['authors']),
			1
		);

		// Edit button
		$editButton = $this->getEditButton();

		// Syntax help button
		$helpButton = $this->getSyntaxHelpButton();

		$fields = $this->getEditFields($publicationData['bibtype']);

		// Data validation
		if ($this->pi1->extConf['editor_mode'] == \tx_bib_pi1::EDIT_CONFIRM_SAVE) {
			$d_err = $this->validatePublicationData($publicationData);
			$title = $this->get_ll($this->LLPrefix . 'title_confirm_save');

			if (sizeof($d_err) > 0) {
				$dataValid = FALSE;
				$cfg =& $this->conf['warn_box.'];
				$txt = $this->get_ll($this->LLPrefix . 'error_title');
				$box = $this->pi1->cObj->stdWrap($txt, $cfg['title.']) . "\n";
				$box .= $this->validationErrorMessage($d_err);
				$box .= $editButton;
				$box = $this->pi1->cObj->stdWrap($box, $cfg['all_wrap.']) . "\n";
				$preContent .= $box;
			}
		}

		// Buttons
		$cancelButton = $this->getCancelButton();
		$citeIdeGeneratorButton = $this->getCiteIdGeneratorButton();
		$updateButton = $this->getUpdateButton();
		$saveButton = $this->getSaveButton();
		$deleteButton = $this->getDeleteButton();

		// Write title
		$content .= '<h2>' . $title . '</h2>' . "\n";

		// Write initial form tag
		$formName = $this->pi1->prefix_pi1 . '_ref_data_form';
		$content .= '<form name="' . $formName . '"';
		$content .= ' action="' . $this->pi1->get_edit_link_url() . '" method="post"';
		$content .= '>' . "\n";
		$content .= $preContent;

		// Javascript for automatic submitting
		$content .= '<script type="text/javascript">' . "\n";
		$content .= '/* <![CDATA[ */' . "\n";
		$content .= 'function click_update_button() {' . "\n";
		$content .= "  var btn = document.getElementsByName('" . $this->pi1->prefix_pi1 . '[action][update_form]' . "')[0];" . "\n";
		$content .= '  btn.click();' . "\n";
		$content .= '  return;' . "\n";
		$content .= '}' . "\n";
		$content .= '/* ]]> */' . "\n";
		$content .= '</script>' . "\n";

		// Begin of the editor box
		$content .= '<div class="' . $this->pi1->prefixShort . '-editor">' . "\n";

		// Top buttons
		$content .= '<div class="' . $this->pi1->prefixShort . '-editor_button_box">' . "\n";
		$content .= '<span class="' . $this->pi1->prefixShort . '-box_right">';
		$content .= $deleteButton;
		$content .= '</span>' . "\n";
		$content .= '<span class="' . $this->pi1->prefixShort . '-box_left">';
		$content .= $saveButton . $editButton . $cancelButton . $helpButton;
		$content .= '</span>' . "\n";
		$content .= '</div>' . "\n";

		$fieldGroups = array(
			'required',
			'optional',
			'other',
			'library',
			'typo3');
		array_unshift($fields['required'], 'bibtype');

		$bib_str = $this->referenceReader->allBibTypes[$publicationData['bibtype']];

		foreach ($fieldGroups as $fg) {
			$class_str = ' class="' . $this->pi1->prefixShort . '-editor_' . $fg . '"';

			$rows_vis = '';
			$rows_silent = '';
			$rows_hidden = '';
			foreach ($fields[$fg] as $ff) {

				// Field label
				$label = $this->fieldLabel($ff, $bib_str);

				// Adjust the widget mode on demand
				$wm = $this->getWidgetMode($ff, $this->widgetMode);

				// Field value widget
				$widget = '';
				switch ($ff) {
					case 'citeid':
						if ($this->pi1->extConf['editor']['citeid_gen_new'] == \tx_bib_pi1::AUTOID_FULL) {
							$widget .= $this->getWidget($ff, $publicationData[$ff], $wm);
						} else {
							$widget .= $this->getWidget($ff, $publicationData[$ff], $wm);
						}
						// Add the id generation button
						if ($this->isNew) {
							if ($this->pi1->extConf['editor']['citeid_gen_new'] == \tx_bib_pi1::AUTOID_HALF) {
								$widget .= $citeIdeGeneratorButton;
							}
						} else {
							if ($this->pi1->extConf['editor']['citeid_gen_old'] == \tx_bib_pi1::AUTOID_HALF) {
								$widget .= $citeIdeGeneratorButton;
							}
						}
						break;
					case 'year':
						$widget .= $this->getWidget('year', $publicationData['year'], $wm);
						$widget .= ' - ';
						$widget .= $this->getWidget('month', $publicationData['month'], $wm);
						$widget .= ' - ';
						$widget .= $this->getWidget('day', $publicationData['day'], $wm);
						break;
					case 'month':
					case 'day':
						break;
					default:
						$widget .= $this->getWidget($ff, $publicationData[$ff], $wm);
				}
				if ($ff == 'bibtype') {
					$widget .= $updateButton;
				}

				if (strlen($widget) > 0) {
					if ($wm == self::WIDGET_SILENT) {
						$rows_silent .= $widget . "\n";
					} else if ($wm == self::WIDGET_HIDDEN) {
						$rows_hidden .= $widget . "\n";
					} else {
						$label = $this->pi1->cObj->stdWrap($label, $this->conf['field_labels.']);
						$widget = $this->pi1->cObj->stdWrap($widget, $this->conf['field_widgets.']);
						$rows_vis .= '<tr>' . "\n";
						$rows_vis .= '<th' . $class_str . '>' . $label . '</th>' . "\n";
						$rows_vis .= '<td' . $class_str . '>' . $widget . '</td>' . "\n";
						$rows_vis .= '</tr>' . "\n";
					}
				}

			}

			// Append header and table if there are rows
			if (strlen($rows_vis) > 0) {
				$content .= '<h3>';
				$content .= $this->get_ll($this->LLPrefix . 'fields_' . $fg);
				$content .= '</h3>';

				$content .= '<table class="' . $this->pi1->prefixShort . '-editor_fields">' . "\n";
				$content .= '<tbody>' . "\n";

				$content .= $rows_vis . "\n";

				$content .= '</tbody>' . "\n";
				$content .= '</table>' . "\n";
			}

			if (strlen($rows_silent) > 0) {
				$content .= "\n";
				$content .= $rows_silent . "\n";
			}

			if (strlen($rows_hidden) > 0) {
				$content .= "\n";
				$content .= $rows_hidden . "\n";
			}
		}

		// Invisible 'uid' and 'mod_key' field
		if (!$this->isNew) {
			if (isset ($publicationData['mod_key'])) {
				$content .= Utility::html_hidden_input(
					$this->pi1->prefix_pi1 . '[DATA][pub][mod_key]',
					htmlspecialchars($publicationData['mod_key'], ENT_QUOTES)
				);
				$content .= "\n";
			}
		}

		// Footer Buttons
		$content .= '<div class="' . $this->pi1->prefixShort . '-editor_button_box">' . "\n";
		$content .= '<span class="' . $this->pi1->prefixShort . '-box_right">';
		$content .= $deleteButton;
		$content .= '</span>' . "\n";
		$content .= '<span class="' . $this->pi1->prefixShort . '-box_left">';
		$content .= $saveButton . $editButton . $cancelButton;
		$content .= '</span>' . "\n";
		$content .= '</div>' . "\n";

		$content .= '</div>' . "\n";
		$content .= '</form>' . "\n";

		return $content;
	}

	/**
	 * @return string
	 */
	protected function getSyntaxHelpButton() {
		$helpButton = '';
		if ($this->widgetMode == self::WIDGET_EDIT) {
			$url = $GLOBALS['TSFE']->tmpl->getFileName(	'EXT:bib/Resources/Public/Html/Syntax.html');

			$helpButton = '<span class="' . $this->buttonClass . '">' .
					'<a href="' . $url . '" target="_blank" class="button-help">' .
					$this->get_ll($this->LLPrefix . 'btn_syntax_help') . '</a></span>';
		}
		return $helpButton;
	}

	/**
	 * @return string
	 */
	protected function getEditButton() {
		$editButton = '';
		if ($this->pi1->extConf['editor_mode'] == \tx_bib_pi1::EDIT_CONFIRM_SAVE) {
			$editButton = '<input type="submit" ';
			if ($this->isNew) {
				$editButton .= 'name="' . $this->pi1->prefix_pi1 . '[action][new]" ';
			} else {
				$editButton .= 'name="' . $this->pi1->prefix_pi1 . '[action][edit]" ';
			}
			$editButton .= 'value="' . $this->get_ll($this->LLPrefix . 'btn_edit') . '" class="' . $this->buttonClass . '"/>';
		}
		return $editButton;
	}

	/**
	 * @return string
	 */
	protected function getCancelButton() {
		$cancelButton = '<span class="' . $this->buttonClass . '">' .
				$this->pi1->get_link(
							$this->get_ll($this->LLPrefix . 'btn_cancel')
				) .
				'</span>';

		return $cancelButton;
	}

	/**
	 * @return string
	 */
	protected function getCiteIdGeneratorButton() {
		$citeIdeGeneratorButton = '';
		if ($this->widgetMode == self::WIDGET_EDIT) {
			$citeIdeGeneratorButton = '<input type="submit" ' .
					'name="' . $this->pi1->prefix_pi1 . '[action][generate_id]" ' .
					'value="' . $this->get_ll($this->LLPrefix . 'btn_generate_id') .
					'" class="' . $this->buttonClass . '"/>';
		}

		return $citeIdeGeneratorButton;
	}

	/**
	 * @return string
	 */
	protected function getUpdateButton() {
		$updateButton = '';
		$updateButtonName = $this->pi1->prefix_pi1 . '[action][update_form]';
		$updateButtonValue = $this->get_ll($this->LLPrefix . 'btn_update_form');
		if ($this->widgetMode == self::WIDGET_EDIT) {
			$updateButton = '<input type="submit"' .
					' name="' . $updateButtonName . '"' .
					' value="' . $updateButtonValue . '"' .
					' class="' . $this->buttonClass . '"/>';
		}
		return $updateButton;
	}

	/**
	 * @return string
	 */
	protected function getSaveButton() {
		$saveButton = '';
		if ($this->widgetMode == self::WIDGET_EDIT) {
			$saveButton = '[action][confirm_save]';
		}
		if ($this->pi1->extConf['editor_mode'] == \tx_bib_pi1::EDIT_CONFIRM_SAVE) {
			$saveButton = '[action][save]';
		}
		if (strlen($saveButton) > 0) {
			$saveButton = '<input type="submit" name="' . $this->pi1->prefix_pi1 . $saveButton . '" ' .
					'value="' . $this->get_ll($this->LLPrefix . 'btn_save') .
					'" class="' . $this->buttonClass . '"/>';
		}
		return $saveButton;
	}

	/**
	 * @return string
	 */
	protected function getDeleteButton() {
		$deleteButton = '';
		$buttonDeleteClass = $this->pi1->prefixShort . '-delete_button';

		if (!$this->isNew) {
			if (($this->pi1->extConf['editor_mode'] != \tx_bib_pi1::EDIT_SHOW) &&
					($this->pi1->extConf['editor_mode'] != \tx_bib_pi1::EDIT_CONFIRM_SAVE)
			) {
				$deleteButton = '[action][confirm_delete]';
			}
			if ($this->pi1->extConf['editor_mode'] == \tx_bib_pi1::EDIT_CONFIRM_DELETE) {
				$deleteButton = '[action][delete]';
			}
			if (strlen($deleteButton)) {
				$deleteButton = '<input type="submit" name="' . $this->pi1->prefix_pi1 . $deleteButton . '" ' .
						'value="' . $this->get_ll($this->LLPrefix . 'btn_delete') .
						'" class="' . $this->buttonClass . ' ' . $buttonDeleteClass . '"/>';
			}
		}
		return $deleteButton;
	}

	/**
	 * Depending on the bibliography type this function returns
	 * The label for a field
	 * @param string $field The field
	 * @param string $bib_str The bibtype identifier string
	 * @return string $label
	 */
	protected function fieldLabel($field, $bib_str) {
		$label = $this->referenceReader->getReferenceTable() . '_' . $field;

		switch ($field) {
			case 'authors':
				$label = $this->referenceReader->getAuthorTable() . '_' . $field;
				break;
			case 'year':
				$label = 'olabel_year_month_day';
				break;
			case 'month':
			case 'day':
				$label = '';
				break;
		}

		$over = array(
			$this->conf['olabel.']['all.'][$field],
			$this->conf['olabel.'][$bib_str . '.'][$field]
		);

		foreach ($over as $lvar) {
			if (is_string($lvar)) $label = $lvar;
		}

		$label = trim($label);
		if (strlen($label) > 0) {
			$label = $this->get_ll($label, $label, TRUE);
		}
		return $label;
	}


	/**
	 * Depending on the bibliography type this function returns what fields
	 * are required and what are optional according to BibTeX
	 *
	 * @param $bibType
	 * @return array An array with subarrays with field lists for
	 */
	protected function getEditFields($bibType) {
		$fields = array();
		$bib_str = $bibType;
		if (is_numeric($bib_str)) {
			$bib_str = $this->referenceReader->allBibTypes[$bibType];
		}

		$all_groups = array('all', $bib_str);
		$all_types = array('required', 'optional', 'library');

		// Read field list from TS configuration
		$cfg_fields = array();
		foreach ($all_groups as $group) {
			$cfg_fields[$group] = array();
			$cfg_arr =& $this->conf['groups.'][$group . '.'];
			if (is_array($cfg_arr)) {
				foreach ($all_types as $type) {
					$cfg_fields[$group][$type] = array();
					$ff = Utility::multi_explode_trim(
						array(',', '|'), $cfg_arr[$type], TRUE
					);
					$cfg_fields[$group][$type] = $ff;
				}
			}
		}

		// Merge field lists
		$pubFields = $this->referenceReader->getPublicationFields();
		unset ($pubFields[array_search('bibtype', $pubFields)]);
		foreach ($all_types as $type) {
			$fields[$type] = array();
			$cur =& $fields[$type];
			if (is_array($cfg_fields[$bib_str][$type])) {
				$cur = $cfg_fields[$bib_str][$type];
			}
			if (is_array($cfg_fields['all'][$type])) {
				foreach ($cfg_fields['all'][$type] as $field) {
					$cur[] = $field;
				}
			}
			$cur = array_unique($cur);

			$cur = array_intersect($cur, $pubFields);
			$pubFields = array_diff($pubFields, $cur);
		}

		// Calculate the remaining 'other' fields
		$fields['other'] = $pubFields;
		$fields['typo3'] = array('uid', 'hidden', 'pid');

		return $fields;
	}


	/**
	 * Get the widget mode for an edit widget
	 *
	 * @param string $field
	 * @param int $widgetMode
	 * @return int The widget mode
	 */
	protected function getWidgetMode($field, $widgetMode) {

		if (($widgetMode == self::WIDGET_EDIT) && $this->conf['no_edit.'][$field]) {
			$widgetMode = self::WIDGET_SHOW;
		}
		if ($this->conf['no_show.'][$field]) {
			$widgetMode = self::WIDGET_HIDDEN;
		}

		if ($field == 'uid') {
			if ($widgetMode == self::WIDGET_EDIT) {
				$widgetMode = self::WIDGET_SHOW;
			} else if ($widgetMode == self::WIDGET_HIDDEN) {
				// uid must be passed always
				$widgetMode = self::WIDGET_SILENT;
			}
		} else if ($field == 'pid') {
			// pid must be passed always
			if ($widgetMode == self::WIDGET_HIDDEN) {
				$widgetMode = self::WIDGET_SILENT;
			}
		}

		return $widgetMode;
	}


	/**
	 * Get the edit widget for a row field
	 *
	 * @param string $field
	 * @param string $value
	 * @param int $mode
	 * @return string The field widget
	 */
	protected function getWidget($field, $value, $mode) {
		$content = '';

		switch ($field) {
			case 'authors':
				$content .= $this->getAuthorsWidget($value, $mode);
				break;
			case 'pid':
				$content .= $this->getPidWidget($value, $mode);
				break;
			default:
				if ($mode == self::WIDGET_EDIT) {
					$content .= $this->getDefaultEditWidget($field, $value, $mode);
				} else {
					$content .= $this->getDefaultStaticWidget($field, $value, $mode);
				}
		}
		return $content;
	}

	/**
	 * @param string $field
	 * @param string $value
	 * @param int $mode
	 * @return string
	 */
	protected function getDefaultEditWidget($field, $value, $mode) {
		$cfg =& $GLOBALS['TCA'][$this->referenceReader->getReferenceTable()]['columns'][$field]['config'];
		$pi1 =& $this->pi1;
		$cclass = $pi1->prefixShort . '-editor_input';
		$Iclass = ' class="' . $cclass . '"';
		$content = '';

		$isize = 60;
		$all_size = array(
			$this->conf['input_size.']['default'],
			$this->conf['input_size.'][$field]
		);
		foreach ($all_size as $ivar) {
			if (is_numeric($ivar)) $isize = intval($ivar);
		}

		// Default widget
		$widgetType = $cfg['type'];
		$fieldAttr = $pi1->prefix_pi1 . '[DATA][pub][' . $field . ']';
		$nameAttr = ' name="' . $fieldAttr . '"';
		$htmlValue = Utility::filter_pub_html($value, TRUE, $pi1->extConf['charset']['upper']);

		$attributes = array();
		$attributes['class'] = $cclass;

		switch ($widgetType) {
			case 'input' :
				if ($cfg['max']) {
					$attributes['maxlength'] = $cfg['max'];
				}
				$size = intval($cfg['size']);
				if ($size > 40) {
					$size = $isize;
				}
				$attributes['size'] = strval($size);

				$content .= Utility::html_text_input(
					$fieldAttr,
					$htmlValue,
					$attributes
				);

				break;

			case 'text' :
				$content .= '<textarea' . $nameAttr;
				$content .= ' rows="' . $cfg['rows'] . '"';
				$content .= ' cols="' . strval($isize) . '"';
				$content .= $Iclass . '>';
				$content .= $htmlValue;
				$content .= '</textarea>';
				$content .= "\n";

				break;

			case 'select' :
				$attributes['name'] = $fieldAttr;
				if ($field == 'bibtype') {
					$attributes['onchange'] = 'click_update_button()';
				}

				$pairs = array();
				$itemConfigurationSize = sizeof($cfg['items']);
				for ($ii = 0; $ii < $itemConfigurationSize; $ii++) {
					$p_desc = $this->get_db_ll($cfg['items'][$ii][0], $cfg['items'][$ii][0]);
					$p_val = $cfg['items'][$ii][1];
					$pairs[$p_val] = $p_desc;
				}

				$content .= Utility::html_select_input(
					$pairs,
					$value,
					$attributes
				);

				break;

			case 'check' :
				$content .= Utility::html_check_input(
					$fieldAttr,
					'1',
					($value == 1),
					$attributes
				);

				break;

			default :
				$content .= 'Unknown edit widget: ' . $widgetType;
		}

		return $content;
	}

	/**
	 * @param string $field
	 * @param string $value
	 * @param int $mode
	 * @return string
	 */
	protected function getDefaultStaticWidget($field, $value, $mode) {
		$configuration =& $GLOBALS['TCA'][$this->referenceReader->getReferenceTable()]['columns'][$field]['config'];
		$pi1 =& $this->pi1;
		$Iclass = ' class="' . $pi1->prefixShort . '-editor_input' . '"';

		// Default widget
		$widgetType = $configuration['type'];
		$fieldAttributes = $pi1->prefix_pi1 . '[DATA][pub][' . $field . ']';
		$htmlValue = Utility::filter_pub_html($value, TRUE, $pi1->extConf['charset']['upper']);

		$content = '';
		if ($mode == self::WIDGET_SHOW) {
			$content .= Utility::html_hidden_input($fieldAttributes, $htmlValue);

			switch ($widgetType) {
				case 'select':
					$name = '';
					foreach ($configuration['items'] as $it) {
						if (strtolower($it[1]) == strtolower($value)) {
							$name = $this->get_db_ll($it[0], $it[0]);
							break;
						}
					}
					$content .= $name;
					break;

				case 'check':
					$content .= $this->get_ll(
						($value == 0) ? 'editor_no' : 'editor_yes');
					break;

				default:
					$content .= $htmlValue;
			}

		} else if ($mode == self::WIDGET_SILENT) {

			$content .= Utility::html_hidden_input(
				$fieldAttributes,
				$htmlValue
			);

		}

		return $content;
	}


	/**
	 * Get the authors widget
	 *
	 * @param array $value
	 * @param int $mode
	 * @return string The authors widget
	 */
	protected function getAuthorsWidget($value, $mode) {
		$content = '';
		$cclass = $this->pi1->prefixShort . '-editor_input';

		/** @var \tx_bib_pi1 $pi1 */
		$pi1 =& $this->pi1;

		$isize = 25;
		$ivar = $this->conf['input_size.']['author'];
		if (is_numeric($ivar)) $isize = intval($ivar);

		$key_action = $pi1->prefix_pi1 . '[action]';
		$key_data = $pi1->prefix_pi1 . '[DATA][pub][authors]';

		// Author widget
		$authors = is_array($value) ? $value : array();
		$aNum = sizeof($authors);
		$edOpts =& $pi1->piVars['editor'];
		$edOpts['numAuthors'] = max($edOpts['numAuthors'], sizeof($authors), $pi1->extConf['editor']['numAuthors'], 1);

		if (($mode == self::WIDGET_SHOW) || ($mode == self::WIDGET_EDIT)) {
			$au_con = array();
			for ($i = 0; $i < $edOpts['numAuthors']; $i++) {
				if ($i > ($aNum - 1) && ($mode != self::WIDGET_EDIT)) {
					break;
				}
				$row_con = array();

				$foreName = Utility::filter_pub_html($authors[$i]['forename'], TRUE, $pi1->extConf['charset']['upper']);
				$surName = Utility::filter_pub_Html($authors[$i]['surname'], TRUE, $pi1->extConf['charset']['upper']);

				$row_con[0] = strval($i + 1);
				if ($mode == self::WIDGET_SHOW) {
					$row_con[1] = Utility::html_hidden_input(
						$key_data . '[' . $i . '][forename]', $foreName);
					$row_con[1] .= $foreName;

					$row_con[2] = Utility::html_hidden_input(
						$key_data . '[' . $i . '][surname]', $surName);
					$row_con[2] .= $surName;

				} else if ($mode == self::WIDGET_EDIT) {

					$lowerBtn = Utility::html_image_input(
						$key_action . '[lower_author]',
						strval($i), $pi1->icon_src['down']);

					$raiseBtn = Utility::html_image_input(
						$key_action . '[raise_author]',
						strval($i), $pi1->icon_src['up']);

					$row_con[1] = Utility::html_text_input(
						$key_data . '[' . $i . '][forename]', $foreName,
						array('size' => $isize, 'maxlength' => 255, 'class' => $cclass));

					$row_con[2] .= Utility::html_text_input(
						$key_data . '[' . $i . '][surname]', $surName,
						array('size' => $isize, 'maxlength' => 255, 'class' => $cclass));

					$row_con[3] = ($i < ($aNum - 1)) ? $lowerBtn : '';
					$row_con[4] = (($i > 0) && ($i < ($aNum))) ? $raiseBtn : '';
				}

				$au_con[] = $row_con;
			}

			$content .= '<table class="' . $pi1->prefixShort . '-editor_author">' . "\n";
			$content .= '<tbody>' . "\n";

			// Head rows
			$content .= '<tr><th></th>';
			$content .= '<th>';
			$content .= $this->get_ll($this->referenceReader->getAuthorTable() . '_forename');
			$content .= '</th>';
			$content .= '<th>';
			$content .= $this->get_ll($this->referenceReader->getAuthorTable() . '_surname');
			$content .= '</th>';
			if ($mode == self::WIDGET_EDIT) {
				$content .= '<th></th><th></th>';
			}
			$content .= '</tr>' . "\n";

			// Author data rows
			foreach ($au_con as $row_con) {
				$content .= '<tr>';
				$content .= '<th class="' . $pi1->prefixShort . '-editor_author_num">';
				$content .= $row_con[0];
				$content .= '</th><td>';
				$content .= $row_con[1];
				$content .= '</td><td>';
				$content .= $row_con[2];
				if (sizeof($row_con) > 3) {
					$content .= '</td><td style="padding: 1px;">';
					$content .= $row_con[3];
					$content .= '</td><td style="padding: 1px;">';
					$content .= $row_con[4];
				}
				$content .= '</td>';
				$content .= '</tr>' . "\n";
			}

			$content .= '</tbody>';
			$content .= '</table>' . "\n";

			// Bottom buttons
			if ($mode == self::WIDGET_EDIT) {
				$content .= '<div style="padding-top: 0.5ex; padding-bottom: 0.5ex;">' . "\n";
				$content .= Utility::html_submit_input(
					$key_action . '[more_authors]', '+'
				);
				$content .= ' ';
				$content .= Utility::html_submit_input(
					$key_action . '[less_authors]', '-'
				);
				$content .= '</div>' . "\n";
			}

		} else if ($mode == self::WIDGET_SILENT) {
			$authorsSize = sizeof($authors);
			for ($i = 0; $i < $authorsSize; $i++) {
				$foreName = Utility::filter_pub_html($authors[$i]['forename'], TRUE, $pi1->extConf['charset']['upper']);
				$surName = Utility::filter_pub_Html($authors[$i]['surname'], TRUE, $pi1->extConf['charset']['upper']);

				$content .= Utility::html_hidden_input(
					$key_data . '[' . $i . '][forename]',
					$foreName
				);

				$content .= Utility::html_hidden_input(
					$key_data . '[' . $i . '][surname]',
					$surName
				);
			}

		}

		return $content;
	}


	/**
	 * Get the pid (storage folder) widget
	 *
	 * @param string $value
	 * @param int $mode
	 * @return string The pid widget
	 */
	protected function getPidWidget($value, $mode) {

		$content = '';

		// Pid
		$pi1 =& $this->pi1;
		$pids = $pi1->extConf['pid_list'];
		$value = intval($value);
		$fieldAttr = $pi1->prefix_pi1 . '[DATA][pub][pid]';

		$attributes = array();
		$attributes['class'] = $pi1->prefixShort . '-editor_input';

		// Fetch page titles
		$pages = Utility::get_page_titles($pids);

		if ($mode == self::WIDGET_SHOW) {
			$content .= Utility::html_hidden_input(
				$fieldAttr,
				$value
			);
			$content .= strval($pages[$value]);

		} else if ($mode == self::WIDGET_EDIT) {
			$attributes['name'] = $fieldAttr;
			$content .= Utility::html_select_input(
				$pages,
				$value,
				$attributes
			);

		} else if ($mode == self::WIDGET_SILENT) {
			$content .= Utility::html_hidden_input(
				$fieldAttr,
				$value
			);

		}

		return $content;
	}


	/**
	 * Returns the default storage uid
	 *
	 * @return int The parent id pid
	 */
	protected function getDefaultPid() {
		$pid = 0;
		if (is_numeric($this->conf['default_pid'])) {
			$pid = intval($this->conf['default_pid']);
		}
		if (!in_array($pid, $this->referenceReader->pid_list)) {
			$pid = intval($this->referenceReader->pid_list[0]);
		}
		return $pid;
	}


	/**
	 * Returns the default publication data
	 *
	 * @return array An array containing the default publication data
	 */
	protected function getDefaultPublicationData() {
		$publication = array();

		if (is_array($this->conf['field_default.'])) {
			foreach ($this->referenceReader->getReferenceFields() as $field) {
				if (array_key_exists($field, $this->conf['field_default.']))
					$publication[$field] = strval($this->conf['field_default.'][$field]);
			}
		}

		if ($publication['bibtype'] == 0) {
			$publication['bibtype'] = array_search('article', $this->referenceReader->allBibTypes);
		}

		if ($publication['year'] == 0) {
			if (is_numeric($this->pi1->extConf['year'])) {
				$publication['year'] = intval($this->pi1->extConf['year']);
			} else
				$publication['year'] = intval(date('Y'));
		}

		if (!in_array($publication['pid'], $this->referenceReader->pid_list)) {
			$publication['pid'] = $this->getDefaultPid();
		}

		return $publication;
	}


	/**
	 * Returns the publication data that was encoded in the
	 * HTTP request
	 *
	 * @param bool $htmlSpecialChars
	 * @return array An array containing the formatted publication
	 *         data that was found in the HTTP request
	 */
	protected function getPublicationDataFromHttpRequest($htmlSpecialChars = FALSE) {
		$Publication = array();
		$charset = $this->pi1->extConf['charset']['upper'];
		$fields = $this->referenceReader->getPublicationFields();
		$fields[] = 'uid';
		$fields[] = 'pid';
		$fields[] = 'hidden';
		$fields[] = 'mod_key'; // Gets generated on loading from the database
		$data =& $this->pi1->piVars['DATA']['pub'];
		if (is_array($data)) {
			foreach ($fields as $ff) {
				switch ($ff) {

					case 'authors':
						if (is_array($data[$ff])) {
							$Publication['authors'] = array();
							foreach ($data[$ff] as $v) {
								$foreName = trim($v['forename']);
								$sureName = trim($v['surname']);
								if ($htmlSpecialChars) {
									$foreName = htmlspecialchars($foreName, ENT_QUOTES, $charset);
									$sureName = htmlspecialchars($sureName, ENT_QUOTES, $charset);
								}
								if (strlen($foreName) || strlen($sureName)) {
									$Publication['authors'][] = array('forename' => $foreName, 'surname' => $sureName);
								}
							}
						}

						break;

					default:

						if (array_key_exists($ff, $data)) {
							$Publication[$ff] = $data[$ff];
							if ($htmlSpecialChars) {
								$Publication[$ff] = htmlspecialchars($Publication[$ff], ENT_QUOTES, $charset);
							}
						}
				}
			}
		}
		return $Publication;
	}


	/**
	 * Performs actions after Database write access (save/delete)
	 *
	 * @return array The requested dialog
	 */
	protected function postDatabaseWriteActions() {
		$events = array();
		$errors = array();
		if ($this->conf['delete_no_ref_authors']) {
			$count = $this->databaseUtility->deleteAuthorsWithoutPublications();
			if ($count > 0) {
				$message = $this->get_ll('msg_deleted_authors');
				$message = str_replace('%d', strval($count), $message);
				$events[] = $message;
			}
		}
		if ($this->conf['full_text.']['update']) {
			$stat = $this->databaseUtility->update_full_text_all();

			$count = sizeof($stat['updated']);
			if ($count > 0) {
				$message = $this->get_ll('msg_updated_full_text');
				$message = str_replace('%d', strval($count), $message);
				$events[] = $message;
			}

			if (sizeof($stat['errors']) > 0) {
				foreach ($stat['errors'] as $err) {
					$message = $err[1]['msg'];
					$errors[] = $message;
				}
			}

			if ($stat['limit_num']) {
				$message = $this->get_ll('msg_warn_ftc_limit') . ' - ';
				$message .= $this->get_ll('msg_warn_ftc_limit_num');
				$errors[] = $message;
			}

			if ($stat['limit_time']) {
				$message = $this->get_ll('msg_warn_ftc_limit') . ' - ';
				$message .= $this->get_ll('msg_warn_ftc_limit_time');
				$errors[] = $message;
			}

		}
		return array($events, $errors);
	}


	/**
	 * Creates a html text from a post db write event
	 *
	 * @param array $messages
	 * @return string The html message string
	 */
	protected function createHtmlTextFromPostDatabaseWrite($messages) {
		$content = '';
		if (count($messages[0]) > 0) {
			$content .= '<h4>' . $this->get_ll('msg_title_events') . '</h4>' . "\n";
			$content .= $this->createHtmlTextFromPostDatabaseWriteEvent($messages[0]);
		}
		if (count($messages[1]) > 0) {
			$content .= '<h4>' . $this->get_ll('msg_title_errors') . '</h4>' . "\n";
			$content .= $this->createHtmlTextFromPostDatabaseWriteEvent($messages[1]);
		}
		return $content;
	}


	/**
	 * Creates a html text from a post db write event
	 *
	 * @param array $messages
	 * @return string The html message string
	 */
	protected function createHtmlTextFromPostDatabaseWriteEvent($messages) {
		$messages = Utility::string_counter($messages);
		$content = '<ul>' . "\n";
		foreach ($messages as $msg => $count) {
			$msg = htmlspecialchars($msg, ENT_QUOTES, $this->pi1->extConf['charset']['upper']);
			$content .= '<li>';
			$content .= $msg;
			if ($count > 1) {
				$app = str_replace('%d', strval($count), $this->get_ll('msg_times'));
				$content .= '(' . $app . ')';
			}
			$content .= '</li>' . "\n";
		}
		$content .= '</ul>' . "\n";
		return $content;
	}


	/**
	 * This switches to the requested dialog
	 *
	 * @return string The requested dialog
	 */
	public function dialogView() {
		$pi1 =& $this->pi1;

		/** @var \Ipf\Bib\Utility\ReferenceWriter $referenceWriter */
		$referenceWriter = GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\ReferenceWriter');
		$referenceWriter->initialize($this->referenceReader);

		switch ($pi1->extConf['dialog_mode']) {

			case $pi1::DIALOG_SAVE_CONFIRMED :
				$publication = $this->getPublicationDataFromHttpRequest();

				// Unset fields that should not be edited
				$checkFields = $this->referenceReader->getReferenceFields();
				$checkFields[] = 'pid';
				$checkFields[] = 'hidden';
				foreach ($checkFields as $ff) {
					if ($publication[$ff]) {
						if ($this->conf['no_edit.'][$ff] || $this->conf['no_show.'][$ff]) {
							unset ($publication[$ff]);
						}
					}
				}

				try {
					$referenceWriter->savePublication($publication);
					/** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
					$message = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						$this->createHtmlTextFromPostDatabaseWrite($this->postDatabaseWriteActions()),
						$this->get_ll('msg_save_success'),
						FlashMessage::OK
					);
					FlashMessageQueue::addMessage($message);

				} catch (DataException $e) {
					$message = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						$e->getMessage(),
						$this->get_ll('msg_save_fail'),
						FlashMessage::ERROR
					);
					FlashMessageQueue::addMessage($message);
				}
				break;

			case $pi1::DIALOG_DELETE_CONFIRMED :
				$publication = $this->getPublicationDataFromHttpRequest();
				try {
					$referenceWriter->deletePublication($pi1->piVars['uid'], $publication['mod_key']);
					$message = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						$this->createHtmlTextFromPostDatabaseWrite($this->postDatabaseWriteActions()),
						$this->get_ll('msg_delete_success'),
						FlashMessage::OK
					);
					FlashMessageQueue::addMessage($message);
				} catch (DataException $e) {
					$message = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						$e->getMessage(),
						$this->get_ll('msg_delete_fail'),
						FlashMessage::ERROR
					);
					FlashMessageQueue::addMessage($message);
				}
				break;
			default :
				$message = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
					'Unknown dialog mode: ' . $pi1->extConf['dialog_mode'],
					$this->get_ll('msg_delete_fail'),
					FlashMessage::ERROR
				);
				FlashMessageQueue::addMessage($message);
			}

		$this->referenceWriter = $referenceWriter;

		return FlashMessageQueue::renderFlashMessages();
	}


	/**
	 * Validates the data in a publication
	 *
	 * @param array $pub
	 * @return array An array with error messages
	 */
	protected function validatePublicationData($pub) {
		$d_err = array();
		$title = $this->get_ll($this->LLPrefix . 'title_confirm_save');

		$bib_str = $this->referenceReader->allBibTypes[$pub['bibtype']];

		$fields = $this->getEditFields($bib_str, TRUE);

		$cond = array();
		$parts = GeneralUtility::trimExplode(',', $this->conf['groups.'][$bib_str . '.']['required']);
		foreach ($parts as $part) {
			if (!(strpos($part, '|') === FALSE)) {
				$cond[] = GeneralUtility::trimExplode('|', $part);
			}
		}

		// Find empty required fields
		$type = 'empty_fields';
		if ($this->conf['warnings.'][$type]) {
			$empty = array();
			// Find empty fields
			foreach ($fields['required'] as $ff) {
				if (!$this->conf['no_edit.'][$ff] && !$this->conf['no_show.'][$ff]) {
					switch ($ff) {
						case 'authors':
							if (!is_array($pub[$ff]) || (sizeof($pub[$ff]) == 0)) {
								$empty[] = $ff;
							}
							break;
						default:
							if (strlen(trim($pub[$ff])) == 0) {
								$empty[] = $ff;
							}
					}
				}
			}

			// Check conditions
			$clear = array();
			foreach ($empty as $em) {
				$ok = FALSE;
				foreach ($cond as $con_ored) {
					if (in_array($em, $con_ored)) {
						// Check if at least one field is not empty
						foreach ($con_ored as $ff) {
							if (!in_array($ff, $empty)) {
								$ok = TRUE;
								break;
							}
						}
						if ($ok) {
							break;
						}
					}
				}
				if ($ok) {
					$clear[] = $em;
				}
			}

			$empty = array_diff($empty, $clear);

			if (sizeof($empty)) {
				$err = array('type' => $type);
				$err['msg'] = $this->get_ll($this->LLPrefix . 'error_empty_fields');
				$err['list'] = array();
				$bib_str = $this->referenceReader->allBibTypes[$pub['bibtype']];
				foreach ($empty as $field) {
					switch ($field) {
						case 'authors':
							$str = $this->fieldLabel($field, $bib_str);
							break;
						default:
							$str = $this->fieldLabel($field, $bib_str);
					}
					$err['list'][] = array('msg' => $str);
				}
				$d_err[] = $err;
			}
		}

		// Local file does not exist
		$type = 'file_nexist';
		if ($this->conf['warnings.'][$type]) {
			$file = $pub['file_url'];
			if (Utility::check_file_nexist($file)) {
				$message = $this->get_ll('editor_error_file_nexist');
				$message = str_replace('%f', $file, $message);
				$d_err[] = array('type' => $type, 'msg' => $message);
			}
		}

		// Cite id doubles
		$type = 'double_citeid';
		if ($this->conf['warnings.'][$type] && !$this->conf['no_edit.']['citeid'] && !$this->conf['no_show.']['citeid']) {
			if ($this->referenceReader->citeIdExists($pub['citeid'], $pub['uid'])) {
				$err = array('type' => $type);
				$err['msg'] = $this->get_ll($this->LLPrefix . 'error_id_exists');
				$d_err[] = $err;
			}
		}

		return $d_err;
	}


	/**
	 * Makes some html out of the return array of
	 * validatePublicationData()
	 *
	 * @param array $errors
	 * @param int $level
	 * @return array An array with error messages
	 */
	protected function validationErrorMessage($errors, $level = 0) {

		if (!is_array($errors) || (sizeof($errors) == 0)) {
			return '';
		}

		$charset = $this->pi1->extConf['charset']['upper'];

		$content = '<ul>';
		foreach ($errors as $error) {
			$errorIterator = '<li>';
			$msg = htmlspecialchars($error['msg'], ENT_QUOTES, $charset);
			$errorIterator .= $this->pi1->cObj->stdWrap(
						$msg,
						$this->conf['warn_box.']['msg.']) . "\n";

			$list =& $error['list'];
			if (is_array($list) && (sizeof($list) > 0)) {
				$errorIterator .= '<ul>';
				$errorIterator .= $this->validationErrorMessage($list, $level + 1);
				$errorIterator .= '</ul>' . "\n";
			}

			$errorIterator .= '</li>';
			$content .= $errorIterator;
		}
		$content .= '</ul>';

		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/View/EditorView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/View/EditorView.php']);
}

?>