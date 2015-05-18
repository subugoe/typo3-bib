<?php
namespace Ipf\Bib\View;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
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
use Ipf\Bib\Navigation\AuthorNavigation;
use Ipf\Bib\Navigation\SearchNavigation;
use Ipf\Bib\Navigation\YearNavigation;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use Ipf\Bib\Utility\Utility;
use Ipf\Bib\Utility\Importer\Importer;

/**
 * List view
 */
class ListView extends View implements ViewInterface {

	// Enumeration style in the list view
	const ENUM_PAGE = 1;
	const ENUM_ALL = 2;
	const ENUM_BULLET = 3;
	const ENUM_EMPTY = 4;
	const ENUM_FILE_ICON = 5;

	/**
	 * @var array
	 */
	protected $extConf;

	/**
	 * @var array
	 */
	protected $conf;

	/**
	 * @var array
	 */
	protected $template;

	/**
	 * @var ContentObjectRenderer
	 */
	protected $cObj;

	/**
	 * @var \tx_bib_pi1
	 */
	protected $pi1;

	/**
	 * @param \tx_bib_pi1 $pi1
	 */
	public function initialize($pi1) {
		$this->extConf = $pi1->extConf;
		$this->conf = $pi1->conf;
		$this->template = $pi1->template;
		$this->cObj = $pi1->cObj;
		$this->pi1 = $pi1;
	}

	/**
	 * @return mixed
	 */
	public function render() {
		// Setup navigation elements
		$this->setupSearchNavigation();
		$this->setupYearNavigation();
		$this->setupAuthorNavigation();
		$this->setupPreferenceNavigation();
		$this->setupPageNavigation();

		$this->setupNewEntryNavigation();

		$this->setupExportNavigation();
		$this->setupImportNavigation();
		$this->setupStatisticsNavigation();

		$this->setupSpacer();
		$this->setupTopNavigation();

		// Setup all publication items
		$this->setupItems();

		return $this->template['LIST_VIEW'];
	}

	/**
	 * Returns the year navigation bar
	 *
	 * @return string An HTML string with the year navigation bar
	 */
	protected function setupSearchNavigation() {
		$trans = [];
		$hasStr = '';

		if ($this->extConf['show_nav_search']) {
			$trans = $this->extConf['search_navi']['obj']->translator();
			$hasStr = ['', ''];

			if (strlen($trans['###SEARCH_NAVI_TOP###']) > 0) {
				$this->extConf['has_top_navi'] = TRUE;
			}
		}

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_SEARCH_NAVI###', $hasStr);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $trans);
	}

	/**
	 * Returns the year navigation bar
	 *
	 * @return string An HTML string with the year navigation bar
	 */
	protected function setupYearNavigation() {
		$trans = [];
		$hasStr = '';

		if ($this->extConf['show_nav_year']) {
			/** @var YearNavigation $yearNavigation */
			$yearNavigation = Utility::getAndInitializeNavigationInstance('YearNavigation', $this->pi1);

			$trans = $yearNavigation->translator();
			$hasStr = ['', ''];

			if (strlen($trans['###YEAR_NAVI_TOP###']) > 0) {
				$this->extConf['has_top_navi'] = TRUE;
			}
		}

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_YEAR_NAVI###', $hasStr);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $trans);
	}

	/**
	 * Sets up the author navigation bar
	 *
	 * @return void
	 */
	protected function setupAuthorNavigation() {
		$trans = [];
		$hasStr = '';

		if ($this->extConf['show_nav_author']) {
			$trans = $this->extConf['author_navi']['obj']->translator();
			$hasStr = ['', ''];

			if (strlen($trans['###AUTHOR_NAVI_TOP###']) > 0) {
				$this->extConf['has_top_navi'] = TRUE;
			}
		}

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_AUTHOR_NAVI###', $hasStr);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $trans);
	}

	/**
	 * Sets up the preferences navigation bar
	 *
	 * @return void
	 */
	protected function setupPreferenceNavigation() {
		$trans = [];
		$hasStr = '';

		if ($this->extConf['show_nav_pref']) {
			$trans = $this->extConf['pref_navi']['obj']->translator();
			$hasStr = ['', ''];

			if (strlen($trans['###PREF_NAVI_TOP###']) > 0)
				$this->extConf['has_top_navi'] = TRUE;
		}

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_PREF_NAVI###', $hasStr);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $trans);
	}

	/**
	 * Sets up the page navigation bar
	 *
	 * @return void
	 */
	protected function setupPageNavigation() {
		$trans = [];
		$hasStr = '';

		if ($this->extConf['show_nav_page']) {
			$obj = Utility::getAndInitializeNavigationInstance('PageNavigation', $this->pi1);

			$trans = $obj->translator();
			$hasStr = ['', ''];

			if (strlen($trans['###PAGE_NAVI_TOP###']) > 0)
				$this->extConf['has_top_navi'] = TRUE;
		}

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_PAGE_NAVI###', $hasStr);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $trans);
	}

	/**
	 * Setup the add-new-entry element
	 *
	 * @return void
	 */
	protected function setupNewEntryNavigation() {
		$linkStr = '';
		$hasStr = '';

		if ($this->extConf['edit_mode']) {
			$template = $this->setupEnumerationConditionBlock($this->template['NEW_ENTRY_NAVI_BLOCK']);
			$linkStr = $this->getNewManipulator();
			$linkStr = $this->cObj->substituteMarker($template, '###NEW_ENTRY###', $linkStr);
			$hasStr = ['', ''];
			$this->extConf['has_top_navi'] = TRUE;
		}

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_NEW_ENTRY###', $hasStr);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarker($this->template['LIST_VIEW'], '###NEW_ENTRY###', $linkStr);
	}

	/**
	 * Setup the export-link element
	 *
	 * @return void
	 */
	protected function setupExportNavigation() {
		$hasStr = '';

		if ($this->extConf['show_nav_export']) {

			$cfg = [];
			if (is_array($this->conf['export.'])) {
				$cfg =& $this->conf['export.'];
			}

			$exports = [];

			// Export label
			$label = $this->pi1->get_ll($cfg['label']);
			$label = $this->cObj->stdWrap($label, $cfg['label.']);

			$exportModes = ['bibtex', 'xml'];

			foreach ($exportModes as $mode) {
				if (in_array($mode, $this->extConf['export_navi']['modes'])) {
					$title = $this->pi1->get_ll('export_' . $mode . 'LinkTitle', $mode, TRUE);
					$txt = $this->pi1->get_ll('export_' . $mode);
					$link = $this->pi1->get_link(
							$txt,
							['export' => $mode],
							FALSE,
							['title' => $title]
					);
					$link = $this->cObj->stdWrap($link, $cfg[$mode . '.']);
					$exports[] = $link;
				}
			}

			$sep = '&nbsp;';
			if (array_key_exists('separator', $cfg)) {
				$sep = $this->cObj->stdWrap($cfg['separator'], $cfg['separator.']);
			}

			// Export string
			$exports = implode($sep, $exports);

			// The translator
			$trans = [];
			$trans['###LABEL###'] = $label;
			$trans['###EXPORTS###'] = $exports;

			$block = $this->setupEnumerationConditionBlock($this->template['EXPORT_NAVI_BLOCK']);
			$block = $this->cObj->substituteMarkerArrayCached($block, $trans, []);
			$hasStr = ['', ''];
		}

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_EXPORT###', $hasStr);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarker($this->template['LIST_VIEW'], '###EXPORT###', $block);
	}

	/**
	 * Setup the import-link element in the
	 * HTML-template
	 *
	 * @return void
	 */
	protected function setupImportNavigation() {
		$str = '';
		$hasStr = '';

		if ($this->extConf['edit_mode']) {

			$cfg = [];
			if (is_array($this->conf['import.']))
				$cfg =& $this->conf['import.'];

			$str = $this->setupEnumerationConditionBlock($this->template['IMPORT_NAVI_BLOCK']);
			$translator = [];
			$imports = [];

			// Import bibtex
			$title = $this->pi1->get_ll('import_bibtexLinkTitle', 'bibtex', TRUE);
			$link = $this->pi1->get_link($this->pi1->get_ll('import_bibtex'), ['import' => Importer::IMP_BIBTEX],
					FALSE, ['title' => $title]);
			$imports[] = $this->cObj->stdWrap($link, $cfg['bibtex.']);

			// Import xml
			$title = $this->pi1->get_ll('import_xmlLinkTitle', 'xml', TRUE);
			$link = $this->pi1->get_link($this->pi1->get_ll('import_xml'), ['import' => Importer::IMP_XML],
					FALSE, ['title' => $title]);
			$imports[] = $this->cObj->stdWrap($link, $cfg['xml.']);

			$sep = '&nbsp;';
			if (array_key_exists('separator', $cfg)) {
				$sep = $this->cObj->stdWrap($cfg['separator'], $cfg['separator.']);
			}

			// Import label
			$translator['###LABEL###'] = $this->cObj->stdWrap($this->pi1->get_ll($cfg['label']), $cfg['label.']);
			$translator['###IMPORTS###'] = implode($sep, $imports);

			$str = $this->cObj->substituteMarkerArrayCached($str, $translator, []);
			$hasStr = ['', ''];
		}

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_IMPORT###', $hasStr);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarker($this->template['LIST_VIEW'], '###IMPORT###', $str);
	}

	/**
	 * Setup the statistic element
	 *
	 * @return void
	 */
	protected function setupStatisticsNavigation() {
		$trans = [];
		$hasStr = '';

		if ($this->extConf['show_nav_stat']) {
			$obj = Utility::getAndInitializeNavigationInstance('StatisticsNavigation', $this->pi1);

			$trans = $obj->translator();
			$hasStr = ['', ''];

			if (strlen($trans['###STAT_NAVI_TOP###']) > 0) {
				$this->extConf['has_top_navi'] = TRUE;
			}
		}

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_STAT_NAVI###', $hasStr);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $trans);
	}

	/**
	 * Setup the a spacer block
	 *
	 * @return void
	 */
	protected function setupSpacer() {
		$spacerBlock = $this->setupEnumerationConditionBlock($this->template['SPACER_BLOCK']);
		$listViewTemplate =& $this->template['LIST_VIEW'];
		$listViewTemplate = $this->cObj->substituteMarker($listViewTemplate, '###SPACER###', $spacerBlock);
	}

	/**
	 * Setup the top navigation block
	 *
	 * @return void
	 */
	protected function setupTopNavigation() {
		$hasStr = '';
		if ($this->extConf['has_top_navi']) {
			$hasStr = ['', ''];
		}
		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_TOP_NAVI###', $hasStr);
	}

	/**
	 * Setup items in the html-template
	 *
	 * @return void
	 */
	protected function setupItems() {
		$items = [];

		// Store cObj data
		$contentObjectBackup = $this->cObj->data;

		$this->pi1->prepareItemSetup();

		// Initialize the label translator
		$this->labelTranslator = [];
		$labels = [
				'abstract',
				'annotation',
				'chapter',
				'doc_number',
				'doi',
				'edition',
				'editor',
				'ISBN',
				'ISSN',
				'keywords',
				'tags',
				'note',
				'of_series',
				'page',
				'publisher',
				'references',
				'report_number',
				'volume',
		];

		foreach ($labels as $label) {
			$upperCaseLabel = strtoupper($label);
			$labelValue = $this->pi1->get_ll('label_' . $label);
			$labelValue = $this->cObj->stdWrap($labelValue, $this->conf['label.'][$label . '.']);
			$this->pi1->labelTranslator['###LABEL_' . $upperCaseLabel . '###'] = $labelValue;
		}

		// block templates
		$itemTemplate = [];
		$itemBlockTemplate = $this->setupEnumerationConditionBlock($this->template['ITEM_BLOCK']);
		$yearBlockTemplate = $this->setupEnumerationConditionBlock($this->template['YEAR_BLOCK']);
		$bibliographyTypeBlockTemplate = $this->setupEnumerationConditionBlock($this->template['BIBTYPE_BLOCK']);

		// Initialize the enumeration template
		$enumerationIdentifier = 'page';
		switch (intval($this->extConf['enum_style'])) {
			case self::ENUM_ALL:
				$enumerationIdentifier = 'all';
				break;
			case self::ENUM_BULLET:
				$enumerationIdentifier = 'bullet';
				break;
			case self::ENUM_EMPTY:
				$enumerationIdentifier = 'empty';
				break;
			case self::ENUM_FILE_ICON:
				$enumerationIdentifier = 'file_icon';
				break;
		}
		$enumerationBase = strval($this->conf['enum.'][$enumerationIdentifier]);
		$enumerationWrap = $this->conf['enum.'][$enumerationIdentifier . '.'];

		if ($this->extConf['d_mode'] == \tx_bib_pi1::D_Y_SPLIT) {
			$this->extConf['split_years'] = TRUE;
		}

		// Database reading initialization
		$this->pi1->referenceReader->initializeReferenceFetching();

		// Determine publication numbers
		$publicationsBefore = 0;
		if (($this->extConf['d_mode'] == \tx_bib_pi1::D_Y_NAV) && is_numeric($this->extConf['year'])) {
			foreach ($this->pi1->stat['year_hist'] as $y => $n) {
				if ($y == $this->extConf['year']) {
					break;
				}
				$publicationsBefore += $n;
			}
		}

		$prevBibType = -1;
		$prevYear = -1;

		// Initialize counters
		$limit_start = intval($this->extConf['filters']['br_page']['limit']['start']);
		$i_page = $this->pi1->stat['num_page'] - $limit_start;
		$i_page_delta = -1;
		if ($this->extConf['date_sorting'] == \tx_bib_pi1::SORT_ASC) {
			$i_page = $limit_start + 1;
			$i_page_delta = 1;
		}

		$i_subpage = 1;
		$i_bibtype = 1;

		// Start the fetch loop
		while ($pub = $this->pi1->referenceReader->getReference()) {
			// Get prepared publication data
			$warnings = [];
			$pdata = $this->pi1->preparePublicationData($pub, $warnings);

			// Item data
			$this->pi1->prepare_pub_cObj_data($pdata);

			// All publications counter
			$i_all = $publicationsBefore + $i_page;

			// Determine evenOdd
			if ($this->extConf['split_bibtypes']) {
				if ($pub['bibtype'] != $prevBibType) {
					$i_bibtype = 1;
				}
				$evenOdd = $i_bibtype % 2;
			} else {
				$evenOdd = $i_subpage % 2;
			}

			// Setup the item template
			$listViewTemplate = $itemTemplate[$pdata['bibtype']];
			if (strlen($listViewTemplate) == 0) {
				$key = strtoupper($pdata['bibtype_short']) . '_DATA';
				$listViewTemplate = $this->template[$key];

				if (strlen($listViewTemplate) == 0) {
					$data_block = $this->template['DEFAULT_DATA'];
				}

				$listViewTemplate = $this->cObj->substituteMarker(
						$itemBlockTemplate,
						'###ITEM_DATA###',
						$listViewTemplate
				);
				$itemTemplate[$pdata['bibtype']] = $listViewTemplate;
			}

			// Initialize the translator
			$translator = [];

			$enum = $enumerationBase;
			$enum = str_replace('###I_ALL###', strval($i_all), $enum);
			$enum = str_replace('###I_PAGE###', strval($i_page), $enum);
			if (!(strpos($enum, '###FILE_URL_ICON###') === FALSE)) {
				$repl = $this->pi1->getFileUrlIcon($pub, $pdata);
				$enum = str_replace('###FILE_URL_ICON###', $repl, $enum);
			}
			$translator['###ENUM_NUMBER###'] = $this->cObj->stdWrap($enum, $enumerationWrap);

			// Row classes
			$eo = $evenOdd ? 'even' : 'odd';

			$translator['###ROW_CLASS###'] = $this->conf['classes.'][$eo];

			$translator['###NUMBER_CLASS###'] = $this->pi1->prefixShort . '-enum';

			// Manipulators
			$translator['###MANIPULATORS###'] = '';
			$manip_all = [];
			$subst_sub = '';
			if ($this->extConf['edit_mode']) {
				if ($this->checkFEauthorRestriction($pub['uid'])) {
					$subst_sub = ['', ''];
					$manip_all[] = $this->getEditManipulator($pub);
					$manip_all[] = $this->getHideManipulator($pub);
					$manip_all = Utility::html_layout_table([$manip_all]);

					$translator['###MANIPULATORS###'] = $this->cObj->stdWrap(
							$manip_all, $this->conf['editor.']['list.']['manipulators.']['all.']
					);
				}
			}

			$listViewTemplate = $this->cObj->substituteSubpart($listViewTemplate, '###HAS_MANIPULATORS###', $subst_sub);

			// Year separator label
			if ($this->extConf['split_years'] && ($pub['year'] != $prevYear)) {
				$yearStr = $this->cObj->stdWrap(strval($pub['year']), $this->conf['label.']['year.']);
				$items[] = $this->cObj->substituteMarker($yearBlockTemplate, '###YEAR###', $yearStr);
				$prevBibType = -1;
			}

			// Bibtype separator label
			if ($this->extConf['split_bibtypes'] && ($pub['bibtype'] != $prevBibType)) {
				$bibStr = $this->cObj->stdWrap(
						$this->pi1->get_ll('bibtype_plural_' . $pub['bibtype'], $pub['bibtype'], TRUE),
					$this->conf['label.']['bibtype.']
				);
				$items[] = $this->cObj->substituteMarker($bibliographyTypeBlockTemplate, '###BIBTYPE###', $bibStr);
			}

			// Append string for item data
			$append = '';
			if ((sizeof($warnings) > 0) && $this->extConf['edit_mode']) {
				$charset = $this->extConf['charset']['upper'];
				foreach ($warnings as $err) {
					$msg = htmlspecialchars($err['msg'], ENT_QUOTES, $charset);
					$append .= $this->cObj->stdWrap($msg, $this->conf['editor.']['list.']['warn_box.']['msg.']);
				}
				$append = $this->cObj->stdWrap($append, $this->conf['editor.']['list.']['warn_box.']['all_wrap.']);
			}
			$translator['###ITEM_APPEND###'] = $append;

			// Apply translator
			$listViewTemplate = $this->cObj->substituteMarkerArrayCached($listViewTemplate, $translator);

			// Pass to item processor
			$items[] = $this->getItemHtml($pdata, $listViewTemplate);

			// Update counters
			$i_page += $i_page_delta;
			$i_subpage++;
			$i_bibtype++;

			$prevBibType = $pub['bibtype'];
			$prevYear = $pub['year'];
		}

		// clean up
		$this->pi1->referenceReader->finalizeReferenceFetching();

		// Restore cObj data
		$this->cObj->data = $contentObjectBackup;

		$items = implode('', $items);

		$hasStr = '';
		$no_items = '';
		if (strlen($items) > 0) {
			$hasStr = ['', ''];
		} else {
			$no_items = strval($this->extConf['post_items']);
			if (strlen($no_items) == 0) {
				$no_items = $this->pi1->get_ll('label_no_items');
			}
			$no_items = $this->cObj->stdWrap($no_items, $this->conf['label.']['no_items.']);
		}

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_ITEMS###', $hasStr);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $this->pi1->labelTranslator);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarker($this->template['LIST_VIEW'], '###NO_ITEMS###', $no_items);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarker($this->template['LIST_VIEW'], '###ITEMS###', $items);
	}

	/**
	 * Removes the enumeration condition block
	 * or just the block markers
	 *
	 * @param String $template
	 * @return string
	 */
	protected function setupEnumerationConditionBlock($template) {
		$sub = $this->extConf['has_enum'] ? [] : '';
		$template = $this->cObj->substituteSubpart($template, '###HAS_ENUM###', $sub);
		return $template;
	}

	/**
	 * Returns the new entry button
	 *
	 * @return string
	 */
	protected function getNewManipulator() {
		$label = $this->pi1->get_ll('manipulators_new', 'New', TRUE);
		$res = $this->pi1->get_link(
				'',
				[
						'action' => [
								'new' => 1
						]
				],
				TRUE,
				[
						'title' => $label,
						'class' => 'new-record'
				]
		);
		return $this->cObj->stdWrap($res, $this->conf['editor.']['list.']['manipulators.']['new.']);
	}

	/**
	 * Returns the edit button
	 *
	 * @param array $publication
	 * @return string
	 */
	protected function getEditManipulator($publication) {
		$label = $this->pi1->get_ll('manipulators_edit', 'Edit', TRUE);
		$res = $this->pi1->get_link(
				'',
				[
						'action' => [
								'edit' => 1
						],
						'uid' => $publication['uid']
				],
				TRUE,
				[
						'title' => $label,
						'class' => 'edit-record'
				]
		);

		$res = $this->cObj->stdWrap($res, $this->conf['editor.']['list.']['manipulators.']['edit.']);

		return $res;
	}

	/**
	 * Returns the hide button
	 *
	 * @param array $publication
	 * @return string
	 */
	protected function getHideManipulator($publication) {
		if ($publication['hidden'] == 0) {
			$label = $this->pi1->get_ll('manipulators_hide', 'Hide', TRUE);
			$class = 'hide';
		} else {
			$label = $this->pi1->get_ll('manipulators_reveal', 'Reveal', TRUE);
			$class = 'reveal';
		}

		$action = [$class => 1];

		$res = $this->pi1->get_link(
				'',
				[
						'action' => $action,
						'uid' => $publication['uid']
				],
				TRUE,
				[
						'title' => $label,
						'class' => $class . '-record'
				]
		);

		return $this->cObj->stdWrap($res, $this->conf['editor.']['list.']['manipulators.']['hide.']);

	}

	/**
	 *
	 * This method checks if the current FE user is allowed to edit
	 * this publication.
	 *
	 * The FE user is allowed to edit if he is an author of the publication.
	 * This check is only done if FE_edit_own_records is set to 1 in TS,
	 * otherwise all publications can be editited.
	 *
	 * @todo code duplication in here, better extend $extConf['edit_mode'] in some way
	 * @todo put conf['FE_edit_own_records'] check in extConf[], so it is not checked every time
	 * @todo make TS also a FlexForm value
	 *
	 * @param integer $publicationId
	 * @return bool TRUE (allowed) FALSE (restricted)
	 */
	protected function checkFEauthorRestriction($publicationId) {

		/** @var \TYPO3\CMS\Backend\FrontendBackendUserAuthentication $beUser */
		$beUser = $GLOBALS['BE_USER'];
		/** @var DatabaseConnection $database */
		$database = $GLOBALS['TYPO3_DB'];

		// always allow BE users with sufficient rights
		if (is_object($beUser)) {
			if ($beUser->isAdmin()) {
				return TRUE;
			} else if ($beUser->check('tables_modify', $this->pi1->referenceReader->getReferenceTable())) {
				return TRUE;
			}
		}

		// Is FE-user editing only for own records enabled? (set via TS)
		if (isset ($this->conf['FE_edit_own_records']) && $this->conf['FE_edit_own_records'] != 0) {

			// query all authors of this publication
			$res = $database->exec_SELECTquery(
					'fe_user_id',
					'tx_bib_domain_model_author as a, tx_bib_domain_model_authorships as m',
					'a.uid = m.author_id AND m.pub_id = ' . $publicationId
			);

			while ($row = $database->sql_fetch_row($res)) {
				// check if author == FE user and allow editing
				if ($row[0] == $GLOBALS['TSFE']->fe_user->user[$GLOBALS['TSFE']->fe_user->userid_column]) {
					return TRUE;
				}
			}
			$database->sql_free_result($res);

			return FALSE;
		}

		// default behavior, FE user can edit all records
		return TRUE;
	}

	/**
	 * Returns the html interpretation of the publication
	 * item as it is defined in the html template
	 *
	 * @param array $publicationData
	 * @param string $template
	 * @return string HTML string for a single item in the list view
	 */
	protected function getItemHtml($publicationData, $template) {

		$translator = [];

		$bib_str = $publicationData['bibtype_short'];
		$all_base = 'rnd' . strval(rand()) . 'rnd';
		$all_wrap = $all_base;

		// Prepare the translator
		// Remove empty field marker from the template
		$fields = $this->pi1->referenceReader->getPublicationFields();
		$fields[] = 'file_url_short';
		$fields[] = 'web_url_short';
		$fields[] = 'web_url2_short';
		$fields[] = 'auto_url';
		$fields[] = 'auto_url_short';

		foreach ($fields as $field) {
			$upStr = strtoupper($field);
			$tkey = '###' . $upStr . '###';
			$hasStr = '';
			$translator[$tkey] = '';

			$val = strval($publicationData[$field]);

			if (strlen($val) > 0) {
				// Wrap default or by bibtype
				$stdWrap = $this->conf['field.'][$field . '.'];

				if (is_array($this->conf['field.'][$bib_str . '.'][$field . '.'])) {
					$stdWrap = $this->conf['field.'][$bib_str . '.'][$field . '.'];
				}

				if (isset ($stdWrap['single_view_link'])) {
					$val = $this->pi1->get_link($val, ['show_uid' => strval($publicationData['uid'])]);
				}
				$val = $this->cObj->stdWrap($val, $stdWrap);

				if (strlen($val) > 0) {
					$hasStr = ['', ''];
					$translator[$tkey] = $val;
				}
			}

			$template = $this->cObj->substituteSubpart($template, '###HAS_' . $upStr . '###', $hasStr);
		}

		// Reference wrap
		$all_wrap = $this->cObj->stdWrap($all_wrap, $this->conf['reference.']);

		// Embrace hidden references with wrap
		if (($publicationData['hidden'] != 0) && is_array($this->conf['editor.']['list.']['hidden.'])) {
			$all_wrap = $this->cObj->stdWrap($all_wrap, $this->conf['editor.']['list.']['hidden.']);
		}

		$template = $this->cObj->substituteMarkerArrayCached($template, $translator);
		$template = $this->cObj->substituteMarkerArrayCached($template, $this->pi1->labelTranslator);

		// Wrap elements with an anchor
		$url_wrap = ['', ''];
		if (strlen($publicationData['file_url']) > 0) {
			$url_wrap = $this->cObj->typolinkWrap(['parameter' => $publicationData['auto_url']]);
		}
		$template = $this->cObj->substituteSubpart($template, '###URL_WRAP###', $url_wrap);

		$all_wrap = explode($all_base, $all_wrap);
		$template = $this->cObj->substituteSubpart($template, '###REFERENCE_WRAP###', $all_wrap);

		// remove empty divs
		$template = preg_replace("/<div[^>]*>[\s\r\n]*<\/div>/", "\n", $template);
		// remove multiple line breaks
		$template = preg_replace("/\n+/", "\n", $template);

		return $template;
	}

}
