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

use \TYPO3\CMS\Core\Utility\GeneralUtility;

class EditorView {

	/**
	 * @var \tx_bib_pi1
	 */
	public $pi1;

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
	public $dbUtility;

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
	function initialize($pi1) {

		/** @var \tx_bib_pi1 pi1 */
		$this->pi1 =& $pi1;
		$this->conf =& $pi1->conf['editor.'];
		$this->referenceReader =& $pi1->referenceReader;
		$this->referenceReader->clear_cache = $this->pi1->extConf['editor']['clear_page_cache'];
		// Load editor language data
		$this->pi1->extend_ll('EXT:' . $this->pi1->extKey . '/Resources/Private/Language/locallang_editor.xml');

		// setup db_utility
		$this->dbUtility = GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\DbUtility');
		$this->dbUtility->initialize($pi1->referenceReader);
		$this->dbUtility->charset = $pi1->extConf['charset']['upper'];
		$this->dbUtility->read_full_text_conf($this->conf['full_text.']);

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
	 * @param String $key
	 * @param String $alt
	 * @param bool $hsc
	 *
	 * @return String The string in the local language
	 */
	function get_ll($key, $alt = '', $hsc = FALSE) {
		return $this->pi1->get_ll($key, $alt, $hsc);
	}

	/**
	 * @param string $key
	 * @param string $alt
	 * @param bool $hsc
	 * @return string
	 */
	function get_db_ll($key, $alt = '', $hsc = FALSE) {
		$key = str_replace('LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:', '', $key);
		return $this->pi1->get_ll($key, $alt, $hsc);
	}


	/**
	 * The editor shows a single publication entry
	 * and allows to edit, delete or save it.
	 *
	 * @return string A publication editor
	 */
	function editor_view() {
		$content = '';

		// check whether the BE user is authorized
		if (!$this->pi1->extConf['edit_mode']) {
			$content .= 'ERROR: You are not authorized to edit the publication database.';
			return $content;
		}

		/** @var tx_bib_pi1 $pi1 */
		$pi1 =& $this->pi1;
		$editorMode = $pi1->extConf['editor_mode'];
		$prefixId =& $pi1->prefix_pi1;
		$prefixShort =& $pi1->prefixShort;
		$edConf =& $this->conf;
		$editorConfiguration =& $pi1->extConf['editor'];

		$pub_http = $this->get_ref_http();
		$pub_db = array();
		$publicationData = array();
		$preContent = '';
		$uid = -1;
		$dataValid = TRUE;
		$buttonClass = $prefixShort . '-editor_button';
		$buttonDeleteClass = $prefixShort . '-delete_button';

		// Determine widget mode
		switch ($editorMode) {
			case $pi1::EDIT_SHOW :
				$widgetMode = self::WIDGET_SHOW;
				break;
			case $pi1::EDIT_EDIT :
			case $pi1::EDIT_NEW :
				$widgetMode = self::WIDGET_EDIT;
				break;
			case $pi1::EDIT_CONFIRM_SAVE :
			case $pi1::EDIT_CONFIRM_DELETE :
			case $pi1::EDIT_CONFIRM_ERASE :
				$widgetMode = self::WIDGET_SHOW;
				break;
			default :
				$widgetMode = self::WIDGET_SHOW;
		}

		// determine entry uid
		if (array_key_exists('uid', $pi1->piVars)) {
			if (is_numeric($pi1->piVars['uid'])) {
				$uid = intval($pi1->piVars['uid']);
			}
		}

		$this->isNew = TRUE;
		if ($uid >= 0) {
			$this->isNew = FALSE;
			$pub_http['uid'] = $uid;
		}

		$this->isFirstEdit = TRUE;
		if (is_array($pi1->piVars['DATA']['pub'])) {
			$this->isFirstEdit = FALSE;
		}

		$title = $this->LLPrefix;
		switch ($editorMode) {
			case $pi1::EDIT_SHOW :
				$title .= 'title_view';
				break;
			case $pi1::EDIT_EDIT :
				$title .= 'title_edit';
				break;
			case $pi1::EDIT_NEW :
				$title .= 'title_new';
				break;
			case $pi1::EDIT_CONFIRM_DELETE :
				$title .= 'title_confirm_delete';
				break;
			case $pi1::EDIT_CONFIRM_ERASE :
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
				$publicationData = $this->get_ref_default();
			} else {
				if ($uid < 0) {
					return $pi1->errorMessage('No publication id given');
				}

				// Load publication data from database
				$pub_db = $this->referenceReader->fetch_db_pub($uid);
				if ($pub_db) {
					$publicationData = array_merge($publicationData, $pub_db);
				} else {
					return $pi1->errorMessage('No publication with uid: ' . $uid);
				}
			}
		}

		// Merge in data from HTTP request
		$publicationData = array_merge($publicationData, $pub_http);

		// Generate cite id if requested
		$generateCiteId = FALSE;
		$generateCiteIdRequest = FALSE;

		// Evaluate actions
		if (is_array($pi1->piVars['action'])) {
			$actions =& $pi1->piVars['action'];

			// Generate cite id
			if (array_key_exists('generate_id', $actions)) {
				$generateCiteIdRequest = TRUE;
			}

			// Raise author
			if (is_numeric($actions['raise_author'])) {
				$num = intval($actions['raise_author']);
				if (($num > 0) && ($num < sizeof($publicationData['authors']))) {
					$tmp = $publicationData['authors'][$num - 1];
					$publicationData['authors'][$num - 1] = $publicationData['authors'][$num];
					$publicationData['authors'][$num] = $tmp;
				}
			}

			// Lower author
			if (is_numeric($actions['lower_author'])) {
				$num = intval($actions['lower_author']);
				if (($num >= 0) && ($num < (sizeof($publicationData['authors']) - 1))) {
					$tmp = $publicationData['authors'][$num + 1];
					$publicationData['authors'][$num + 1] = $publicationData['authors'][$num];
					$publicationData['authors'][$num] = $tmp;
				}
			}

			if (isset($pi1->piVars['action']['more_authors'])) {
				$pi1->piVars['editor']['numAuthors'] += 1;
			}
			if (isset($pi1->piVars['action']['less_authors'])) {
				$pi1->piVars['editor']['numAuthors'] -= 1;
			}
		}

		// Generate cite id on demand
		if ($this->isNew) {
			// Generate cite id for new entries
			switch ($editorConfiguration['citeid_gen_new']) {
				case $pi1::AUTOID_FULL:
					$generateCiteId = TRUE;
					break;
				case $pi1::AUTOID_HALF:
					if ($generateCiteIdRequest)
						$generateCiteId = TRUE;
					break;
				default:
					break;
			}
		} else {
			// Generate cite id for already existing (old) entries
			$auto_id = $editorConfiguration['citeid_gen_old'];
			if (($generateCiteIdRequest && ($auto_id == $pi1::AUTOID_HALF))
					|| (strlen($publicationData['citeid']) == 0)
			) {
				$generateCiteId = TRUE;
			}
		}
		if ($generateCiteId) {
			$publicationData['citeid'] = $this->idGenerator->generateId($publicationData);
		}

		// Determine the number of authors
		$pi1->piVars['editor']['numAuthors'] = max(
			$pi1->piVars['editor']['numAuthors'], $edConf['numAuthors'],
			sizeof($publicationData['authors']), 1);

		// Edit button
		$editButton = '';
		if ($editorMode == $pi1::EDIT_CONFIRM_SAVE) {
			$editButton = '<input type="submit" ';
			if ($this->isNew)
				$editButton .= 'name="' . $prefixId . '[action][new]" ';
			else
				$editButton .= 'name="' . $prefixId . '[action][edit]" ';
			$editButton .= 'value="' . $this->get_ll($this->LLPrefix . 'btn_edit') .
					'" class="' . $buttonClass . '"/>';
		}

		// Syntax help button
		$helpButton = '';
		if ($widgetMode == self::WIDGET_EDIT) {
			$url = $GLOBALS['TSFE']->tmpl->getFileName(
				'EXT:bib/Resources/Public/Html/Syntax.html');
			$helpButton = '<span class="' . $buttonClass . '">' .
					'<a href="' . $url . '" target="_blank" class="button-help">' .
					$this->get_ll($this->LLPrefix . 'btn_syntax_help') . '</a></span>';
		}

		$fields = $this->get_edit_fields($publicationData['bibtype']);

		// Data validation
		if ($editorMode == $pi1::EDIT_CONFIRM_SAVE) {
			$d_err = $this->validate_data($publicationData);
			$title = $this->get_ll($this->LLPrefix . 'title_confirm_save');

			if (sizeof($d_err) > 0) {
				$dataValid = FALSE;
				$cfg =& $edConf['warn_box.'];
				$txt = $this->get_ll($this->LLPrefix . 'error_title');
				$box = $pi1->cObj->stdWrap($txt, $cfg['title.']) . "\n";
				$box .= $this->validation_error_string($d_err);
				$box .= $editButton;
				$box = $pi1->cObj->stdWrap($box, $cfg['all_wrap.']) . "\n";
				$preContent .= $box;
			}
		}

		// Cancel button
		$cancelButton = '<span class="' . $buttonClass . '">' . $pi1->get_link(
					$this->get_ll($this->LLPrefix . 'btn_cancel')) . '</span>';

		// Generate Citeid button
		$citeIdeGeneratorButton = '';
		if ($widgetMode == self::WIDGET_EDIT) {
			$citeIdeGeneratorButton = '<input type="submit" ' .
					'name="' . $prefixId . '[action][generate_id]" ' .
					'value="' . $this->get_ll($this->LLPrefix . 'btn_generate_id') .
					'" class="' . $buttonClass . '"/>';
		}

		// Update button
		$updateButton = '';
		$updateButtonName = $prefixId . '[action][update_form]';
		$updateButtonValue = $this->get_ll($this->LLPrefix . 'btn_update_form');
		if ($widgetMode == self::WIDGET_EDIT) {
			$updateButton = '<input type="submit"' .
					' name="' . $updateButtonName . '"' .
					' value="' . $updateButtonValue . '"' .
					' class="' . $buttonClass . '"/>';
		}

		// Save button
		$saveButton = '';
		if ($widgetMode == self::WIDGET_EDIT)
			$saveButton = '[action][confirm_save]';
		if ($editorMode == $pi1::EDIT_CONFIRM_SAVE)
			$saveButton = '[action][save]';
		if (strlen($saveButton) > 0) {
			$saveButton = '<input type="submit" name="' . $prefixId . $saveButton . '" ' .
					'value="' . $this->get_ll($this->LLPrefix . 'btn_save') .
					'" class="' . $buttonClass . '"/>';
		}

		// Delete button
		$deleteButton = '';
		if (!$this->isNew) {
			if (($editorMode != $pi1::EDIT_SHOW) &&
					($editorMode != $pi1::EDIT_CONFIRM_SAVE)
			)
				$deleteButton = '[action][confirm_delete]';
			if ($editorMode == $pi1::EDIT_CONFIRM_DELETE)
				$deleteButton = '[action][delete]';
			if (strlen($deleteButton)) {
				$deleteButton = '<input type="submit" name="' . $prefixId . $deleteButton . '" ' .
						'value="' . $this->get_ll($this->LLPrefix . 'btn_delete') .
						'" class="' . $buttonClass . ' ' . $buttonDeleteClass . '"/>';
			}
		}

		// Write title
		$content .= '<h2>' . $title . '</h2>' . "\n";

		// Write initial form tag
		$formName = $prefixId . '_ref_data_form';
		$content .= '<form name="' . $formName . '"';
		$content .= ' action="' . $pi1->get_edit_link_url() . '" method="post"';
		$content .= '>' . "\n";
		$content .= $preContent;

		// Javascript for automatic submitting
		$content .= '<script type="text/javascript">' . "\n";
		$content .= '/* <![CDATA[ */' . "\n";
		$content .= 'function click_update_button() {' . "\n";
		//$content .= "  alert('click_update_button');" . "\n";
		$content .= "  var btn = document.getElementsByName('" . $updateButtonName . "')[0];" . "\n";
		//$content .= "  alert(btn);" . "\n";
		$content .= '  btn.click();' . "\n";
		$content .= '  return;' . "\n";
		$content .= '}' . "\n";
		$content .= '/* ]]> */' . "\n";
		$content .= '</script>' . "\n";

		// Begin of the editor box
		$content .= '<div class="' . $prefixShort . '-editor">' . "\n";

		// Top buttons
		$content .= '<div class="' . $prefixShort . '-editor_button_box">' . "\n";
		$content .= '<span class="' . $prefixShort . '-box_right">';
		$content .= $deleteButton;
		$content .= '</span>' . "\n";
		$content .= '<span class="' . $prefixShort . '-box_left">';
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
			$class_str = ' class="' . $prefixShort . '-editor_' . $fg . '"';

			$rows_vis = "";
			$rows_silent = "";
			$rows_hidden = "";
			foreach ($fields[$fg] as $ff) {

				// Field label
				$label = $this->field_label($ff, $bib_str);

				// Adjust the widget mode on demand
				$wm = $this->get_widget_mode($ff, $widgetMode);

				// Field value widget
				$widget = '';
				switch ($ff) {
					case 'citeid':
						if ($editorConfiguration['citeid_gen_new'] == $pi1::AUTOID_FULL) {
							$widget .= $this->get_widget($ff, $publicationData[$ff], $wm);
						} else {
							$widget .= $this->get_widget($ff, $publicationData[$ff], $wm);
						}
						// Add the id generation button
						if ($this->isNew) {
							if ($editorConfiguration['citeid_gen_new'] == $pi1::AUTOID_HALF)
								$widget .= $citeIdeGeneratorButton;
						} else {
							if ($editorConfiguration['citeid_gen_old'] == $pi1::AUTOID_HALF)
								$widget .= $citeIdeGeneratorButton;
						}
						break;
					case 'year':
						$widget .= $this->get_widget('year', $publicationData['year'], $wm);
						$widget .= ' - ';
						$widget .= $this->get_widget('month', $publicationData['month'], $wm);
						$widget .= ' - ';
						$widget .= $this->get_widget('day', $publicationData['day'], $wm);
						break;
					case 'month':
					case 'day':
						break;
					default:
						$widget .= $this->get_widget($ff, $publicationData[$ff], $wm);
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
						$label = $pi1->cObj->stdWrap($label, $edConf['field_labels.']);
						$widget = $pi1->cObj->stdWrap($widget, $edConf['field_widgets.']);
						$rows_vis .= '<tr>' . "\n";
						$rows_vis .= '<th' . $class_str . '>' . $label . '</th>' . "\n";
						$rows_vis .= '<td' . $class_str . '>' . $widget . '</td>' . "\n";
						$rows_vis .= '</tr>' . "\n";
					}
				}

			}

			# Append header and table it there're rows
			if (strlen($rows_vis) > 0) {
				$content .= '<h3>';
				$content .= $this->get_ll($this->LLPrefix . 'fields_' . $fg);
				$content .= '</h3>';

				$content .= '<table class="' . $prefixShort . '-editor_fields">' . "\n";
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
				$content .= \Ipf\Bib\Utility\Utility::html_hidden_input(
					$prefixId . '[DATA][pub][mod_key]',
					htmlspecialchars($publicationData['mod_key'], ENT_QUOTES));
				$content .= "\n";
			}
		}

		// Footer Buttons
		$content .= '<div class="' . $prefixShort . '-editor_button_box">' . "\n";
		$content .= '<span class="' . $prefixShort . '-box_right">';
		$content .= $deleteButton;
		$content .= '</span>' . "\n";
		$content .= '<span class="' . $prefixShort . '-box_left">';
		$content .= $saveButton . $editButton . $cancelButton;
		$content .= '</span>' . "\n";
		$content .= '</div>' . "\n";

		$content .= '</div>' . "\n";
		$content .= '</form>' . "\n";

		return $content;
	}


	/**
	 * Depending on the bibliography type this function returns
	 * The label for a field
	 * @param string $field The field
	 * @param string $bib_str The bibtype identifier string
	 * @return string $label
	 */
	function field_label($field, $bib_str) {
		$label = $this->referenceReader->referenceTable . '_' . $field;

		switch ($field) {
			case 'authors':
				$label = $this->referenceReader->authorTable . '_' . $field;
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
	 * @return array An array with subarrays with field lists for
	 */
	function get_edit_fields($bibType) {
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
					$ff = \Ipf\Bib\Utility\Utility::multi_explode_trim(
						array(',', '|'), $cfg_arr[$type], TRUE);
					$cfg_fields[$group][$type] = $ff;
				}
			}
		}

		// Merge field lists
		$pubFields = $this->referenceReader->pubFields;
		unset ($pubFields[array_search('bibtype', $pubFields)]);
		foreach ($all_types as $type) {
			$fields[$type] = array();
			$cur =& $fields[$type];
			if (is_array($cfg_fields[$bib_str][$type]))
				$cur = $cfg_fields[$bib_str][$type];
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
	 * @return The widget mode
	 */
	function get_widget_mode($field, $mode) {
		$edConf =& $this->conf;

		$widgetMode = $mode;

		if (($widgetMode == self::WIDGET_EDIT) && $edConf['no_edit.'][$field]) {
			$widgetMode = self::WIDGET_SHOW;
		}
		if ($edConf['no_show.'][$field]) {
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
	 * @return The field widget
	 */
	function get_widget($field, $value, $mode) {
		$content = ''; // Content
		$pi1 =& $this->pi1;

		switch ($field) {
			case 'authors':
				$content .= $this->get_authors_widget($value, $mode);
				break;
			case 'pid':
				$content .= $this->get_pid_widget($value, $mode);
				break;
			default:
				if ($mode == self::WIDGET_EDIT) {
					$content .= $this->get_default_edit_widget($field, $value, $mode);
				} else {
					$content .= $this->get_default_static_widget($field, $value, $mode);
				}
		}
		return $content;
	}


	function get_default_edit_widget($field, $value, $mode) {
		$cfg =& $GLOBALS['TCA'][$this->referenceReader->referenceTable]['columns'][$field]['config'];
		$pi1 =& $this->pi1;
		$cclass = $pi1->prefixShort . '-editor_input';
		$Iclass = ' class="' . $cclass . '"';
		$content = ''; // Content

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
		$htmlValue = $pi1->filter_pub_html($value, TRUE);

		$attrs = array();
		$attrs['class'] = $cclass;

		switch ($widgetType) {
			case 'input' :
				if ($cfg['max'])
					$attrs['maxlength'] = $cfg['max'];
				$size = intval($cfg['size']);
				if ($size > 40)
					$size = $isize;
				$attrs['size'] = strval($size);

				$content .= \Ipf\Bib\Utility\Utility::html_text_input(
					$fieldAttr, $htmlValue, $attrs);

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
				$attrs['name'] = $fieldAttr;
				if ($field == 'bibtype') {
					$attrs['onchange'] = 'click_update_button()';
				}

				$pairs = array();
				for ($ii = 0; $ii < sizeof($cfg['items']); $ii++) {
					$p_desc = $this->get_db_ll($cfg['items'][$ii][0], $cfg['items'][$ii][0]);
					$p_val = $cfg['items'][$ii][1];
					$pairs[$p_val] = $p_desc;
				}

				$content .= \Ipf\Bib\Utility\Utility::html_select_input(
					$pairs, $value, $attrs);

				break;

			case 'check' :
				$content .= \Ipf\Bib\Utility\Utility::html_check_input(
					$fieldAttr,
					'1',
					($value == 1),
					$attrs
				);

				break;

			default :
				$content .= 'Unknown edit widget: ' . $widgetType;
		}

		return $content;
	}


	function get_default_static_widget($field, $value, $mode) {
		$configuration =& $GLOBALS['TCA'][$this->referenceReader->referenceTable]['columns'][$field]['config'];
		$pi1 =& $this->pi1;
		$Iclass = ' class="' . $pi1->prefixShort . '-editor_input' . '"';

		// Default widget
		$widgetType = $configuration['type'];
		$fieldAttributes = $pi1->prefix_pi1 . '[DATA][pub][' . $field . ']';
		$htmlValue = $pi1->filter_pub_html($value, TRUE);

		$content = '';
		if ($mode == self::WIDGET_SHOW) {
			$content .= \Ipf\Bib\Utility\Utility::html_hidden_input(
				$fieldAttributes, $htmlValue);

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

			$content .= \Ipf\Bib\Utility\Utility::html_hidden_input(
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
	function get_authors_widget($value, $mode) {
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
		$edOpts['numAuthors'] = max($edOpts['numAuthors'],
			sizeof($authors), $pi1->extConf['editor']['numAuthors'], 1);

		if (($mode == self::WIDGET_SHOW) || ($mode == self::WIDGET_EDIT)) {
			$au_con = array();
			for ($i = 0; $i < $edOpts['numAuthors']; $i++) {
				if ($i > ($aNum - 1) && ($mode != self::WIDGET_EDIT))
					break;

				$row_con = array();

				$fn = $pi1->filter_pub_html($authors[$i]['forename'], TRUE);
				$sn = $pi1->filter_pub_Html($authors[$i]['surname'], TRUE);

				$row_con[0] = strval($i + 1);
				if ($mode == self::WIDGET_SHOW) {
					$row_con[1] = \Ipf\Bib\Utility\Utility::html_hidden_input(
						$key_data . '[' . $i . '][forename]', $fn);
					$row_con[1] .= $fn;

					$row_con[2] = \Ipf\Bib\Utility\Utility::html_hidden_input(
						$key_data . '[' . $i . '][surname]', $sn);
					$row_con[2] .= $sn;

				} else if ($mode == self::WIDGET_EDIT) {

					$lowerBtn = \Ipf\Bib\Utility\Utility::html_image_input(
						$key_action . '[lower_author]',
						strval($i), $pi1->icon_src['down']);
					$raiseBtn = \Ipf\Bib\Utility\Utility::html_image_input(
						$key_action . '[raise_author]',
						strval($i), $pi1->icon_src['up']);

					$row_con[1] = \Ipf\Bib\Utility\Utility::html_text_input(
						$key_data . '[' . $i . '][forename]', $fn,
						array('size' => $isize, 'maxlength' => 255, 'class' => $cclass));

					$row_con[2] .= \Ipf\Bib\Utility\Utility::html_text_input(
						$key_data . '[' . $i . '][surname]', $sn,
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
			$content .= $this->get_ll($this->referenceReader->authorTable . '_forename');
			$content .= '</th>';
			$content .= '<th>';
			$content .= $this->get_ll($this->referenceReader->authorTable . '_surname');
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
				$content .= \Ipf\Bib\Utility\Utility::html_submit_input(
					$key_action . '[more_authors]', '+'
				);
				$content .= ' ';
				$content .= \Ipf\Bib\Utility\Utility::html_submit_input(
					$key_action . '[less_authors]', '-'
				);
				$content .= '</div>' . "\n";
			}

		} else if ($mode == self::WIDGET_SILENT) {

			for ($i = 0; $i < sizeof($authors); $i++) {
				$fn = $pi1->filter_pub_html($authors[$i]['forename'], TRUE);
				$sn = $pi1->filter_pub_Html($authors[$i]['surname'], TRUE);
				$content .= \Ipf\Bib\Utility\Utility::html_hidden_input(
					$key_data . '[' . $i . '][forename]', $fn);
				$content .= \Ipf\Bib\Utility\Utility::html_hidden_input(
					$key_data . '[' . $i . '][surname]', $sn);
			}

		}

		return $content;
	}


	/**
	 * Get the pid (storage folder) widget
	 *
	 * @return string The pid widget
	 */
	function get_pid_widget($value, $mode) {

		$content = '';

		// Pid
		$pi1 =& $this->pi1;
		$pids = $pi1->extConf['pid_list'];
		$value = intval($value);
		$fieldAttr = $pi1->prefix_pi1 . '[DATA][pub][pid]';

		$attrs = array();
		$attrs['class'] = $pi1->prefixShort . '-editor_input';

		// Fetch page titles
		$pages = \Ipf\Bib\Utility\Utility::get_page_titles($pids);

		if ($mode == self::WIDGET_SHOW) {
			$content .= \Ipf\Bib\Utility\Utility::html_hidden_input(
				$fieldAttr, $value);
			$content .= strval($pages[$value]);

		} else if ($mode == self::WIDGET_EDIT) {
			$attrs['name'] = $fieldAttr;
			$content .= \Ipf\Bib\Utility\Utility::html_select_input(
				$pages, $value, $attrs);

		} else if ($mode == self::WIDGET_SILENT) {
			$content .= \Ipf\Bib\Utility\Utility::html_hidden_input(
				$fieldAttr, $value);

		}

		return $content;
	}


	/**
	 * Returns the default storage uid
	 *
	 * @return int The parent id pid
	 */
	function get_default_pid() {
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
	function get_ref_default() {
		$pub = array();

		if (is_array($this->conf['field_default.'])) {
			foreach ($this->referenceReader->refFields as $field) {
				if (array_key_exists($field, $this->conf['field_default.']))
					$pub[$field] = strval($this->conf['field_default.'][$field]);
			}
		}

		if ($pub['bibtype'] == 0) {
			$pub['bibtype'] = array_search('article', $this->referenceReader->allBibTypes);
		}

		if ($pub['year'] == 0) {
			if (is_numeric($this->pi1->extConf['year']))
				$pub['year'] = intval($this->pi1->extConf['year']);
			else
				$pub['year'] = intval(date('Y'));
		}

		if (!in_array($pub['pid'], $this->referenceReader->pid_list)) {
			$pub['pid'] = $this->get_default_pid();
		}

		return $pub;
	}


	/**
	 * Returns the publication data that was encoded in the
	 * HTTP request
	 *
	 * @param bool $htmlSpecialChars
	 * @return array An array containing the formatted publication
	 *         data that was found in the HTTP request
	 */
	function get_ref_http($htmlSpecialChars = FALSE) {
		$Publication = array();
		$charset = $this->pi1->extConf['charset']['upper'];
		$fields = $this->referenceReader->pubFields;
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
	function post_db_write() {
		$events = array();
		$errors = array();
		if ($this->conf['delete_no_ref_authors']) {
			$count = $this->dbUtility->deleteAuthorsWithoutPublications();
			if ($count > 0) {
				$message = $this->get_ll('msg_deleted_authors');
				$message = str_replace('%d', strval($count), $message);
				$events[] = $message;
			}
		}
		if ($this->conf['full_text.']['update']) {
			$stat = $this->dbUtility->update_full_text_all();

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
	 * @return string The html message string
	 */
	function post_db_write_message($messages) {
		$content = '';
		if (count($messages[0]) > 0) {
			$content .= '<h4>' . $this->get_ll('msg_title_events') . '</h4>' . "\n";
			$content .= $this->post_db_write_message_items($messages[0]);
		}
		if (count($messages[1]) > 0) {
			$content .= '<h4>' . $this->get_ll('msg_title_errors') . '</h4>' . "\n";
			$content .= $this->post_db_write_message_items($messages[1]);
		}
		return $content;
	}


	/**
	 * Creates a html text from a post db write event
	 *
	 * @return string The html message string
	 */
	function post_db_write_message_items($messages) {
		$content = '';
		$messages = \Ipf\Bib\Utility\Utility::string_counter($messages);
		$content .= '<ul>' . "\n";
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
	function dialog_view() {
		$content = '';

		$pi1 =& $this->pi1;

		$this->referenceWriter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\ReferenceWriter');
		$this->referenceWriter->initialize($this->referenceReader);

		switch ($pi1->extConf['dialog_mode']) {

			case $pi1::DIALOG_SAVE_CONFIRMED :
				$publication = $this->get_ref_http();

				// Unset fields that should not be edited
				$checkFields = $this->referenceReader->refFields;
				$checkFields[] = 'pid';
				$checkFields[] = 'hidden';
				foreach ($checkFields as $ff) {
					if ($publication[$ff]) {
						if ($this->conf['no_edit.'][$ff] ||
								$this->conf['no_show.'][$ff]
						) {
							unset ($publication[$ff]);
						}
					}
				}

				if ($this->referenceWriter->save_publication($publication)) {
					$content .= '<div class="' . $pi1->prefixShort . '-warning_box">' . "\n";
					$content .= '<p>' . $this->get_ll('msg_save_fail') . '</p>';
					$content .= '<p>' . $this->referenceWriter->html_error_message() . '</p>';
					$content .= '</div>' . "\n";
				} else {
					$content .= '<p>' . $this->get_ll('msg_save_success') . '</p>';
					$messages = $this->post_db_write();
					$content .= $this->post_db_write_message($messages);
				}
				break;

			case $pi1::DIALOG_DELETE_CONFIRMED :
				$publication = $this->get_ref_http();
				if ($this->referenceWriter->delete_publication($pi1->piVars['uid'], $publication['mod_key'])) {
					$content .= '<div class="' . $pi1->prefixShort . '-warning_box">' . "\n";
					$content .= '<p>' . $this->get_ll('msg_delete_fail') . '</p>';
					$content .= '<p>' . $this->referenceWriter->html_error_message() . '</p>';
					$content .= '</div>' . "\n";
				} else {
					$content .= '<p>' . $this->get_ll('msg_delete_success') . '</p>';
					$messages = $this->post_db_write();
					$content .= $this->post_db_write_message($messages);
				}
				break;

			case $pi1::DIALOG_ERASE_CONFIRMED :
				if ($this->referenceWriter->erase_publication($pi1->piVars['uid'])) {
					$content .= '<p>' . $this->get_ll('msg_erase_fail') . '</p>';
				} else {
					$content .= '<p>' . $this->get_ll('msg_erase_success') . '</p>';
					$messages = $this->post_db_write();
					$content .= $this->post_db_write_message($messages);
				}
				break;

			default :
				$content .= 'Unknown dialog mode: ' .
						$pi1->extConf['dialog_mode'];
		}
		return $content;
	}


	/**
	 * Validates the data in a publication
	 *
	 * @return array An array with error messages
	 */
	function validate_data($pub) {
		$d_err = array();
		$title = $this->get_ll($this->LLPrefix . 'title_confirm_save');

		$bib_str = $this->referenceReader->allBibTypes[$pub['bibtype']];

		$fields = $this->get_edit_fields($bib_str, TRUE);

		$cond = array();
		$parts = \Ipf\Bib\Utility\Utility::explode_trim(',', $this->conf['groups.'][$bib_str . '.']['required']);
		foreach ($parts as $part) {
			if (!(strpos($part, '|') === FALSE)) {
				$cond[] = \Ipf\Bib\Utility\Utility::explode_trim('|', $part);
			}
		}

		$warn =& $this->conf['warnings.'];

		//
		// Find empty required fields
		//
		$type = 'empty_fields';
		if ($warn[$type]) {
			$empty = array();
			// Find empty fields
			foreach ($fields['required'] as $ff) {
				if (!$this->conf['no_edit.'][$ff] &&
						!$this->conf['no_show.'][$ff]
				) {
					switch ($ff) {
						case 'authors':
							if (!is_array($pub[$ff]) ||
									(sizeof($pub[$ff]) == 0)
							) {
								$empty[] = $ff;
							}
							break;
						default:
							if (strlen(trim($pub[$ff])) == 0)
								$empty[] = $ff;
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
						if ($ok) break;
					}
				}
				if ($ok) $clear[] = $em;
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
							$str = $this->field_label($field, $bib_str);
							break;
						default:
							$str = $this->field_label($field, $bib_str);
					}
					$err['list'][] = array('msg' => $str);
				}
				$d_err[] = $err;
			}
		}

		// Local file does not exist
		$type = 'file_nexist';
		if ($warn[$type]) {
			$file = $pub['file_url'];
			if (\Ipf\Bib\Utility\Utility::check_file_nexist($file)) {
				$message = $this->get_ll('editor_error_file_nexist');
				$message = str_replace('%f', $file, $message);
				$d_err[] = array('type' => $type, 'msg' => $message);
			}
		}

		// Cite id doubles
		$type = 'double_citeid';
		if ($warn[$type] && !$this->conf['no_edit.']['citeid'] &&
				!$this->conf['no_show.']['citeid']
		) {
			if ($this->referenceReader->citeid_exists($pub['citeid'], $pub['uid'])) {
				$err = array('type' => $type);
				$err['msg'] = $this->get_ll($this->LLPrefix . 'error_id_exists');
				$d_err[] = $err;
			}
		}

		return $d_err;
	}


	/**
	 * Makes some html out of the return array of
	 * validate_data()
	 *
	 * @param array $errors
	 * @param int $level
	 * @return array An array with error messages
	 */
	function validation_error_string($errors, $level = 0) {

		if (!is_array($errors) || (sizeof($errors) == 0)) {
			return '';
		}

		$charset = $this->pi1->extConf['charset']['upper'];

		$content = '<ul>';
		foreach ($errors as $error) {
			$errorIterator = '<li>';
			$msg = htmlspecialchars($error['msg'], ENT_QUOTES, $charset);
			$errorIterator .= $this->pi1->cObj->stdWrap($msg,
						$this->conf['warn_box.']['msg.']) . "\n";

			$list =& $error['list'];
			if (is_array($list) && (sizeof($list) > 0)) {
				$errorIterator .= '<ul>';
				$errorIterator .= $this->validation_error_string($list, $level + 1);
				$errorIterator .= '</ul>' . "\n";
			}

			$errorIterator .= '</li>';
			$content .= $errorIterator;
		}
		$content .= '</ul>';

		return $content;
	}


}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/Classes/View/EditorView.php"]) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/Classes/View/EditorView.php"]);
}

?>