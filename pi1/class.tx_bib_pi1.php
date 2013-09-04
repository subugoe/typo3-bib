<?php

use \TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Sebastian Holtermann (sebholt@web.de)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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
 ***************************************************************/
/**
 * Plugin 'Publication List' for the 'bib' extension.
 *
 * @author Sebastian Holtermann <sebholt@web.de>
 * @author Ingo Pfennigstorf <i.pfennigstorf@gmail.com>
 *
 */

class tx_bib_pi1 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {

	public $prefixId = 'tx_bib_pi1';
	public $scriptRelPath = 'pi1/class.tx_bib_pi1.php';
	public $extKey = 'bib'; // The extension key.

	// http://forum.typo3.org/index.php/t/152665/
	public $pi_checkCHash = FALSE;

	public $prefixShort = 'tx_bib';
	public $prefix_pi1 = 'tx_bib_pi1';

	// Enumeration for list modes
	const D_SIMPLE = 0;
	const D_Y_SPLIT = 1;
	const D_Y_NAV = 2;

	// Enumeration for view modes
	const VIEW_LIST = 0;
	const VIEW_SINGLE = 1;
	const VIEW_EDITOR = 2;
	const VIEW_DIALOG = 3;

	// Editor view modes
	const EDIT_SHOW = 0;
	const EDIT_EDIT = 1;
	const EDIT_NEW = 2;
	const EDIT_CONFIRM_SAVE = 3;
	const EDIT_CONFIRM_DELETE = 4;
	const EDIT_CONFIRM_ERASE = 5;

	// Various dialog modes
	const DIALOG_SAVE_CONFIRMED = 1;
	const DIALOG_DELETE_CONFIRMED = 2;
	const DIALOG_ERASE_CONFIRMED = 3;
	const DIALOG_EXPORT = 4;
	const DIALOG_IMPORT = 5;

	// Enumeration style in the list view
	const ENUM_PAGE = 1;
	const ENUM_ALL = 2;
	const ENUM_BULLET = 3;
	const ENUM_EMPTY = 4;
	const ENUM_FILE_ICON = 5;

	// Import modes
	const IMP_BIBTEX = 1;
	const IMP_XML = 2;

	// Statistic modes
	const STAT_NONE = 0;
	const STAT_TOTAL = 1;
	const STAT_YEAR_TOTAL = 2;

	// citeid generation modes
	const AUTOID_OFF = 0;
	const AUTOID_HALF = 1;
	const AUTOID_FULL = 2;

	// Sorting modes
	const SORT_DESC = 0;
	const SORT_ASC = 1;

	public $template; // HTML templates
	public $itemTemplate; // HTML templates

	/**
	 * These are derived/extra configuration values
	 *
	 * @var array
	 */
	public $extConf;

	/**
	 * The reference database reader
	 *
	 * @var \Ipf\Bib\Utility\ReferenceReader
	 */
	public $referenceReader;

	/**
	 * @var array
	 */
	public $icon_src = array();

	// Statistics
	public $stat;

	/**
	 * @var array
	 */
	public $labelTranslator = array();

	/**
	 * @var array
	 */
	protected $flexFormData;

	/**
	 * @var array
	 */
	protected $pidList;

	/**
	 * The main function merges all configuration options and
	 * switches to the appropriate request handler
	 *
	 * @param String $content
	 * @param array $conf
	 *
	 * @return String The plugin HTML content
	 */
	function main($content, $conf) {
		$this->conf = $conf;
		$this->extConf = array();
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->extend_ll('EXT:' . $this->extKey . '/Resources/Private/Language/locallang_db.xml');
		$this->pi_initPIflexForm();

		$this->flexFormData = $this->cObj->data['pi_flexform'];

		// Create some configuration shortcuts
		$extConf =& $this->extConf;
		$this->referenceReader = GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\ReferenceReader');
		$this->referenceReader->set_cObj($this->cObj);

		$extConf = GeneralUtility::array_merge_recursive_overrule($this->getExtensionConfiguration(), $extConf);

		// Configuration by TypoScript selected
		if (intval($extConf['d_mode']) < 0)
			$extConf['d_mode'] = intval($this->conf['display_mode']);
		if (intval($extConf['enum_style']) < 0)
			$extConf['enum_style'] = intval($this->conf['enum_style']);
		if (intval($extConf['date_sorting']) < 0)
			$extConf['date_sorting'] = intval($this->conf['date_sorting']);
		if (intval($extConf['stat_mode']) < 0)
			$extConf['stat_mode'] = intval($this->conf['statNav.']['mode']);

		if (intval($extConf['sub_page']['ipp']) < 0) {
			$extConf['sub_page']['ipp'] = intval($this->conf['items_per_page']);
		}
		if (intval($extConf['max_authors']) < 0) {
			$extConf['max_authors'] = intval($this->conf['max_authors']);
		}

		// Character set
		$extConf['charset'] = array('upper' => 'UTF-8', 'lower' => 'utf-8');
		if (strlen($this->conf['charset']) > 0) {
			$extConf['charset']['upper'] = strtoupper($this->conf['charset']);
			$extConf['charset']['lower'] = strtolower($this->conf['charset']);
		}

		// Frontend editor configuration
		$this->getFrontendEditorConfiguration();

		// Get storage page(s)
		$this->getStoragePid();

		// Remove doubles and zero
		$pidList = array_unique($this->pidList);
		if (in_array(0, $pidList)) {
			unset ($pidList[array_search(0, $pidList)]);
		}

		if (sizeof($pidList) > 0) {
			// Determine the recursive depth
			$extConf['recursive'] = $this->cObj->data['recursive'];
			if (isset ($this->conf['recursive'])) {
				$extConf['recursive'] = $this->conf['recursive'];
			}
			$extConf['recursive'] = intval($extConf['recursive']);

			$pidList = $this->pi_getPidList(implode(',', $pidList), $extConf['recursive']);

			$pidList = GeneralUtility::intExplode(',', $pidList);

			// Due to how recursive prepends the folders
			$pidList = array_reverse($pidList);

			$extConf['pid_list'] = $pidList;
		} else {
			// Use current page as storage
			$extConf['pid_list'] = array(intval($GLOBALS['TSFE']->id));
		}
		$this->referenceReader->pid_list = $extConf['pid_list'];

		//
		// Adjustments
		//
		switch ($extConf['d_mode']) {
			case self::D_SIMPLE:
			case self::D_Y_SPLIT:
			case self::D_Y_NAV:
				break;
			default:
				$extConf['d_mode'] = self::D_SIMPLE; // emergency default
		}
		switch ($extConf['enum_style']) {
			case self::ENUM_PAGE:
			case self::ENUM_ALL:
			case self::ENUM_BULLET:
			case self::ENUM_EMPTY:
			case self::ENUM_FILE_ICON:
				break;
			default:
				$extConf['enum_style'] = self::ENUM_ALL; // emergency default
		}
		switch ($extConf['date_sorting']) {
			case self::SORT_DESC:
			case self::SORT_ASC:
				break;
			default:
				$extConf['date_sorting'] = self::SORT_DESC; // emergency default
		}
		switch ($extConf['stat_mode']) {
			case self::STAT_NONE:
			case self::STAT_TOTAL:
			case self::STAT_YEAR_TOTAL:
				break;
			default:
				$extConf['stat_mode'] = self::STAT_TOTAL; // emergency default
		}
		$extConf['sub_page']['ipp'] = max(intval($extConf['sub_page']['ipp']), 0);
		$extConf['max_authors'] = max(intval($extConf['max_authors']), 0);


		//
		// Search navi
		//
		if ($extConf['show_nav_search']) {
			$extConf['dynamic'] = TRUE;
			$extConf['search_navi'] = array();
			$searchNavigationConfiguration =& $extConf['search_navi'];
			$searchNavigationConfiguration['obj'] =& $this->get_navi_instance('SearchNavigation');
			$searchNavigationConfiguration['obj']->hook_init();
		}

		//
		// Year navi
		//
		if ($extConf['d_mode'] == self::D_Y_NAV) {
			$extConf['show_nav_year'] = TRUE;
		}

		//
		// Author navi
		//
		if ($extConf['show_nav_author']) {
			$extConf['dynamic'] = TRUE;
			$extConf['author_navi'] = array();
			$searchNavigationConfiguration =& $extConf['author_navi'];
			$searchNavigationConfiguration['obj'] =& $this->get_navi_instance('AuthorNavigation');
			$searchNavigationConfiguration['obj']->hook_init();
		}


		//
		// Preference navi
		//
		if ($extConf['show_nav_pref']) {
			$extConf['pref_navi'] = array();
			$searchNavigationConfiguration =& $extConf['pref_navi'];
			$searchNavigationConfiguration['obj'] =& $this->get_navi_instance('PreferenceNavigation');
			$searchNavigationConfiguration['obj']->hook_init();
		}


		//
		// Statistic navi
		//
		if (intval($this->extConf['stat_mode']) != self::STAT_NONE) {
			$extConf['show_nav_stat'] = TRUE;
		}

		// Export navigation
		if ($extConf['show_nav_export']) {
			$this->getExportNavigation();
		}

		// Enable Enable the edit mode
		$validBackendUser = $this->isValidBackendUser();

		// allow FE-user editing from special groups (set via TS)
		$validFrontendUser = $this->isValidFrontendUser($validBackendUser);

		$extConf['edit_mode'] = (($validBackendUser || $validFrontendUser) && $extConf['editor']['enabled']);

		// Set the enumeration mode
		$extConf['has_enum'] = TRUE;
		if (($extConf['enum_style'] == self::ENUM_EMPTY)) {
			$extConf['has_enum'] = FALSE;
		}

		// Initialize data display restrictions
		$this->init_restrictions();

		// Initialize icons
		$this->init_list_icons();

		// Initialize the default filter
		$this->init_filters();

		// Don't show hidden entries
		$extConf['show_hidden'] = FALSE;
		if ($extConf['edit_mode']) {
			$extConf['show_hidden'] = TRUE;
		}
		$this->referenceReader->show_hidden = $extConf['show_hidden'];


		//
		// Edit mode specific !!!
		//
		if ($extConf['edit_mode']) {

			// Disable caching in edit mode
			$GLOBALS['TSFE']->set_no_cache();

			// Load edit labels
			$this->extend_ll('EXT:' . $this->extKey . '/Resources/Private/Language/locallang_editor.xml');

			// Do an action type evaluation
			if (is_array($this->piVars['action'])) {
				$actionName = implode('', array_keys($this->piVars['action']));

				switch ($actionName) {
					case 'new':
						$extConf['view_mode'] = self::VIEW_EDITOR;
						$extConf['editor_mode'] = self::EDIT_NEW;
						break;
					case 'edit':
						$extConf['view_mode'] = self::VIEW_EDITOR;
						$extConf['editor_mode'] = self::EDIT_EDIT;
						break;
					case 'confirm_save':
						$extConf['view_mode'] = self::VIEW_EDITOR;
						$extConf['editor_mode'] = self::EDIT_CONFIRM_SAVE;
						break;
					case 'save':
						$extConf['view_mode'] = self::VIEW_DIALOG;
						$extConf['dialog_mode'] = self::DIALOG_SAVE_CONFIRMED;
						break;
					case 'confirm_delete':
						$extConf['view_mode'] = self::VIEW_EDITOR;
						$extConf['editor_mode'] = self::EDIT_CONFIRM_DELETE;
						break;
					case 'delete':
						$extConf['view_mode'] = self::VIEW_DIALOG;
						$extConf['dialog_mode'] = self::DIALOG_DELETE_CONFIRMED;
						break;
					case 'confirm_erase':
						$extConf['view_mode'] = self::VIEW_EDITOR;
						$extConf['editor_mode'] = self::EDIT_CONFIRM_ERASE;
						break;
					case 'erase':
						$extConf['view_mode'] = self::VIEW_DIALOG;
						$extConf['dialog_mode'] = self::DIALOG_ERASE_CONFIRMED;
					case 'hide':
						$this->hidePublication(TRUE);
						break;
					case 'reveal':
						$this->hidePublication(FALSE);
						break;
					default:
				}
			}

			// Set unset extConf and piVars editor mode
			if ($extConf['view_mode'] == self::VIEW_DIALOG) {
				unset ($this->piVars['editor_mode']);
			}

			if (isset ($extConf['editor_mode'])) {
				$this->piVars['editor_mode'] = $extConf['editor_mode'];
			} else if (isset ($this->piVars['editor_mode'])) {
				$extConf['view_mode'] = self::VIEW_EDITOR;
				$extConf['editor_mode'] = $this->piVars['editor_mode'];
			}

			// Initialize edit icons
			$this->init_edit_icons();

			// Switch to an import view on demand
			$allImport = intval(self::IMP_BIBTEX | self::IMP_XML);
			if (isset($this->piVars['import']) &&
					(intval($this->piVars['import']) & $allImport)
			) {
				$extConf['view_mode'] = self::VIEW_DIALOG;
				$extConf['dialog_mode'] = self::DIALOG_IMPORT;
			}

		}

		// Switch to an export view on demand
		if (is_string($extConf['export_navi']['do'])) {
			$extConf['view_mode'] = self::VIEW_DIALOG;
			$extConf['dialog_mode'] = self::DIALOG_EXPORT;
		}

		// Switch to a single view on demand
		if (is_numeric($this->piVars['show_uid'])) {
			$extConf['view_mode'] = self::VIEW_SINGLE;
			$extConf['single_view']['uid'] = intval($this->piVars['show_uid']);
			unset ($this->piVars['editor_mode']);
			unset ($this->piVars['dialog_mode']);
		}


		//
		// Search navigation setup
		//
		if ($extConf['show_nav_search']) {
			$extConf['search_navi']['obj']->hook_filter();
		}


		//
		// Fetch publication statistics
		//
		$this->stat = array();
		$this->referenceReader->set_filters($extConf['filters']);

		//
		// Author navigation hook
		//
		if ($extConf['show_nav_author']) {
			$extConf['author_navi']['obj']->hook_filter();
		}


		//
		// Year navigation
		//
		if ($extConf['show_nav_year']) {
			// Fetch a year histogram
			$histogram = $this->referenceReader->fetch_histogram('year');
			$this->stat['year_hist'] = $histogram;
			$this->stat['years'] = array_keys($histogram);
			sort($this->stat['years']);

			$this->stat['num_all'] = array_sum($histogram);
			$this->stat['num_page'] = $this->stat['num_all'];

			//
			// Determine the year to display
			//
			$extConf['year'] = intval(date('Y')); // System year
			//$extConf['year'] = 'all'; // All years
			$yearNavigationConfiguration =& $extConf['year'];

			$exportPluginVariables = strtolower($this->piVars['year']);
			if (is_numeric($exportPluginVariables)) {
				$yearNavigationConfiguration = intval($exportPluginVariables);
			} else {
				if ($exportPluginVariables == 'all') {
					$yearNavigationConfiguration = $exportPluginVariables;
				}
			}

			if ($yearNavigationConfiguration == 'all') {
				if ($this->conf['yearNav.']['selection.']['all_year_split']) {
					$extConf['split_years'] = TRUE;
				}
			}


			// The selected year has no publications so select the closest year
			if (($this->stat['num_all'] > 0) && is_numeric($yearNavigationConfiguration)) {
				$yearNavigationConfiguration = \Ipf\Bib\Utility\Utility::find_nearest_int(
					$yearNavigationConfiguration, $this->stat['years']);
			}
			// Append default link variable
			$extConf['link_vars']['year'] = $yearNavigationConfiguration;

			if (is_numeric($yearNavigationConfiguration)) {
				// Adjust num_page
				$this->stat['num_page'] = $this->stat['year_hist'][$yearNavigationConfiguration];

				// Adjust year filter
				$extConf['filters']['br_year'] = array();
				$br_filter =& $extConf['filters']['br_year'];
				$br_filter['year'] = array();
				$br_filter['year']['years'] = array($yearNavigationConfiguration);
			}

		}

		//
		// Determine number of publications
		//
		if (!is_numeric($this->stat['num_all'])) {
			$this->stat['num_all'] = $this->referenceReader->fetch_num();
			$this->stat['num_page'] = $this->stat['num_all'];
		}

		//
		// Page navigation
		//
		$subPage =& $extConf['sub_page'];
		$subPage['max'] = 0;
		$subPage['current'] = 0;
		$iPP = $subPage['ipp'];

		if ($iPP > 0) {
			$subPage['max'] = floor(($this->stat['num_page'] - 1) / $iPP);
			$subPage['current'] = \Ipf\Bib\Utility\Utility::crop_to_range(
				$this->piVars['page'], 0, $subPage['max']);
		}

		if ($subPage['max'] > 0) {
			$extConf['show_nav_page'] = TRUE;

			$extConf['filters']['br_page'] = array();
			$br_filter =& $extConf['filters']['br_page'];

			// Adjust the browse filter limit
			$br_filter['limit'] = array();
			$br_filter['limit']['start'] = $subPage['current'] * $iPP;
			$br_filter['limit']['num'] = $iPP;
		}


		//
		// The sort filter
		//
		$extConf['filters']['sort'] = array();
		$extConf['filters']['sort']['sorting'] = array();
		$sortFilter =& $extConf['filters']['sort']['sorting'];

		// Default sorting
		$defaultSorting = 'DESC';
		if ($this->extConf['date_sorting'] == self::SORT_ASC) {
			$defaultSorting = 'ASC';
		}
		$referenceTableAlias =& $this->referenceReader->referenceTableAlias;
		$sortFilter = array(
			array('field' => $referenceTableAlias . '.year', 'dir' => $defaultSorting),
			array('field' => $referenceTableAlias . '.month', 'dir' => $defaultSorting),
			array('field' => $referenceTableAlias . '.day', 'dir' => $defaultSorting),
			array('field' => $referenceTableAlias . '.bibtype', 'dir' => 'ASC'),
			array('field' => $referenceTableAlias . '.state', 'dir' => 'ASC'),
			array('field' => $referenceTableAlias . '.sorting', 'dir' => 'ASC'),
			array('field' => $referenceTableAlias . '.title', 'dir' => 'ASC')
		);

		// Adjust sorting for bibtype split
		if ($extConf['split_bibtypes']) {
			if ($extConf['d_mode'] == self::D_SIMPLE) {
				$sortFilter = array(
					array('field' => $referenceTableAlias . '.bibtype', 'dir' => 'ASC'),
					array('field' => $referenceTableAlias . '.year', 'dir' => $defaultSorting),
					array('field' => $referenceTableAlias . '.month', 'dir' => $defaultSorting),
					array('field' => $referenceTableAlias . '.day', 'dir' => $defaultSorting),
					array('field' => $referenceTableAlias . '.state', 'dir' => 'ASC'),
					array('field' => $referenceTableAlias . '.sorting', 'dir' => 'ASC'),
					array('field' => $referenceTableAlias . '.title', 'dir' => 'ASC')
				);
			} else {
				$sortFilter = array(
					array('field' => $referenceTableAlias . '.year', 'dir' => $defaultSorting),
					array('field' => $referenceTableAlias . '.bibtype', 'dir' => 'ASC'),
					array('field' => $referenceTableAlias . '.month', 'dir' => $defaultSorting),
					array('field' => $referenceTableAlias . '.day', 'dir' => $defaultSorting),
					array('field' => $referenceTableAlias . '.state', 'dir' => 'ASC'),
					array('field' => $referenceTableAlias . '.sorting', 'dir' => 'ASC'),
					array('field' => $referenceTableAlias . '.title', 'dir' => 'ASC')
				);
			}
		}

		// Setup reference reader
		$this->referenceReader->set_filters($extConf['filters']);

		//
		// Disable navigations om demand
		//
		if ($this->stat['num_all'] == 0)
			$extConf['show_nav_export'] = FALSE;
		if ($this->stat['num_page'] == 0)
			$extConf['show_nav_stat'] = FALSE;

		//
		// Initialize the html templates
		//
		$error = $this->initializeHtmlTemplate();
		if (sizeof($error) > 0) {
			$bad = '';
			foreach ($error as $msg)
				$bad .= $this->errorMessage($msg);
			return $this->finalize($bad);
		}

		//
		// Switch to requested view mode
		//
		switch ($extConf['view_mode']) {
			case self::VIEW_LIST :
				return $this->finalize($this->list_view());
				break;
			case self::VIEW_SINGLE :
				return $this->finalize($this->singleView());
				break;
			case self::VIEW_EDITOR :
				return $this->finalize($this->editorView());
				break;
			case self::VIEW_DIALOG :
				return $this->finalize($this->dialogView());
				break;
		}

		return $this->finalize($this->errorMessage('An illegal view mode occured'));
	}


	/**
	 * This is the last function called before ouptput
	 *
	 * @param String $pluginContent
	 * @return The input string with some extra data
	 */
	function finalize($pluginContent) {
		if ($this->extConf['debug']) {
			$pluginContent .= GeneralUtiliy::view_array(
				array(
					'extConf' => $this->extConf,
					'conf' => $this->conf,
					'piVars' => $this->piVars,
					'stat' => $this->stat,
					'HTTP_POST_VARS' => $GLOBALS['HTTP_POST_VARS'],
					'HTTP_GET_VARS' => $GLOBALS['HTTP_GET_VARS'],
				)
			);
		}
		return $this->pi_wrapInBaseClass($pluginContent);
	}

	/**
	 * Retrieve and optimize Extension configuration
	 *
	 * @return array
	 */
	protected function getExtensionConfiguration() {
		$extConf = array();
		// Initialize current configuration
		$extConf['link_vars'] = array();
		$extConf['sub_page'] = array();

		$extConf['view_mode'] = self::VIEW_LIST;
		$extConf['debug'] = $this->conf['debug'] ? TRUE : FALSE;
		$extConf['ce_links'] = $this->conf['ce_links'] ? TRUE : FALSE;


		//
		// Retrieve general FlexForm values
		//
		$fSheet = 'sDEF';
		$extConf['d_mode'] = $this->pi_getFFvalue($this->flexFormData, 'display_mode', $fSheet);
		$extConf['enum_style'] = $this->pi_getFFvalue($this->flexFormData, 'enum_style', $fSheet);
		$extConf['show_nav_search'] = $this->pi_getFFvalue($this->flexFormData, 'show_search', $fSheet);
		$extConf['show_nav_author'] = $this->pi_getFFvalue($this->flexFormData, 'show_authors', $fSheet);
		$extConf['show_nav_pref'] = $this->pi_getFFvalue($this->flexFormData, 'show_pref', $fSheet);
		$extConf['sub_page']['ipp'] = $this->pi_getFFvalue($this->flexFormData, 'items_per_page', $fSheet);
		$extConf['max_authors'] = $this->pi_getFFvalue($this->flexFormData, 'max_authors', $fSheet);
		$extConf['split_bibtypes'] = $this->pi_getFFvalue($this->flexFormData, 'split_bibtypes', $fSheet);
		$extConf['stat_mode'] = $this->pi_getFFvalue($this->flexFormData, 'stat_mode', $fSheet);
		$extConf['show_nav_export'] = $this->pi_getFFvalue($this->flexFormData, 'export_mode', $fSheet);
		$extConf['date_sorting'] = $this->pi_getFFvalue($this->flexFormData, 'date_sorting', $fSheet);

		$show_fields = $this->pi_getFFvalue($this->flexFormData, 'show_textfields', $fSheet);
		$show_fields = explode(',', $show_fields);

		$extConf['hide_fields'] = array(
			'abstract' => 1,
			'annotation' => 1,
			'note' => 1,
			'keywords' => 1,
			'tags' => 1
		);

		foreach ($show_fields as $f) {
			$field = FALSE;
			switch ($f) {
				case 1:
					$field = 'abstract';
					break;
				case 2:
					$field = 'annotation';
					break;
				case 3:
					$field = 'note';
					break;
				case 4:
					$field = 'keywords';
					break;
				case 5:
					$field = 'tags';
					break;
			}
			if ($field) {
				$extConf['hide_fields'][$field] = 0;
			}
		}

		return $extConf;
	}

	/**
	 * Get configuration from FlexForms
	 */
	protected function getFrontendEditorConfiguration() {
		$ecEditor =& $this->extConf['editor'];
		$flexFormSheet = 's_fe_editor';
		$ecEditor['enabled'] = $this->pi_getFFvalue($this->flexFormData, 'enable_editor', $flexFormSheet);
		$ecEditor['citeid_gen_new'] = $this->pi_getFFvalue($this->flexFormData, 'citeid_gen_new', $flexFormSheet);
		$ecEditor['citeid_gen_old'] = $this->pi_getFFvalue($this->flexFormData, 'citeid_gen_old', $flexFormSheet);
		$ecEditor['clear_page_cache'] = $this->pi_getFFvalue($this->flexFormData, 'clear_cache', $flexFormSheet);

		// Overwrite editor configuration from TSsetup
		if (is_array($this->conf['editor.'])) {
			$editorOverride =& $this->conf['editor.'];
			if (array_key_exists('enabled', $editorOverride))
				$extConf['editor']['enabled'] = $editorOverride['enabled'] ? TRUE : FALSE;
			if (array_key_exists('citeid_gen_new', $editorOverride))
				$extConf['editor']['citeid_gen_new'] = $editorOverride['citeid_gen_new'] ? TRUE : FALSE;
			if (array_key_exists('citeid_gen_old', $editorOverride))
				$extConf['editor']['citeid_gen_old'] = $editorOverride['citeid_gen_old'] ? TRUE : FALSE;
		}
		$this->referenceReader->clear_cache = $extConf['editor']['clear_page_cache'];
	}

	/**
	 * Get storage pages
	 *
	 * @return void
	 */
	public function getStoragePid() {
		$pidList = array();
		if (isset ($this->conf['pid_list'])) {
			$this->pidList = \Ipf\Bib\Utility\Utility::explode_intval(',', $this->conf['pid_list']);
		}
		if (isset ($this->cObj->data['pages'])) {
			$tmp = \Ipf\Bib\Utility\Utility::explode_intval(',', $this->cObj->data['pages']);
			$this->pidList = array_merge($pidList, $tmp);
		}
	}

	/**
	 * Builds the export navigation
	 */
	protected function getExportNavigation() {
		$this->extConf['export_navi'] = array();

		// Check group restrictions
		$groups = $this->conf['export.']['FE_groups_only'];
		$validFrontendUser = TRUE;
		if (strlen($groups) > 0) {
			$validFrontendUser = \Ipf\Bib\Utility\Utility::check_fe_user_groups($groups);
		}

		// Acquire export modes
		$modes = $this->conf['export.']['enable_export'];
		if (strlen($modes) > 0) {
			$modes = \Ipf\Bib\Utility\Utility::explode_trim_lower(
				',',
				$modes,
				TRUE
			);
		}

		// Add export modes
		$this->extConf['export_navi']['modes'] = array();
		$exportModules =& $this->extConf['export_navi']['modes'];
		if (is_array($modes) && $validFrontendUser) {
			$availableExportModes = array('bibtex', 'xml');
			$exportModules = array_intersect($availableExportModes, $modes);
		}

		if (sizeof($exportModules) == 0) {
			$extConf['show_nav_export'] = FALSE;
		} else {
			$exportPluginVariables = trim($this->piVars['export']);
			if ((strlen($exportPluginVariables) > 0) && in_array($exportPluginVariables, $exportModules)) {
				$this->extConf['export_navi']['do'] = $exportPluginVariables;
			}
		}
	}

	/**
	 * Determine whether a valid backend user with write access to the reference table is logged in
	 *
	 * @return bool
	 */
	protected function isValidBackendUser() {
		if (is_object($GLOBALS['BE_USER'])) {
			if ($GLOBALS['BE_USER']->isAdmin())
				return TRUE;
			else {
				return $GLOBALS['BE_USER']->check('tables_modify', $this->referenceReader->referenceTable);
			}
		}
	}

	protected function isValidFrontendUser($validBackendUser) {
		if (!$validBackendUser && isset ($this->conf['FE_edit_groups'])) {
			$groups = $this->conf['FE_edit_groups'];
			if (\Ipf\Bib\Utility\Utility::check_fe_user_groups($groups))
				return TRUE;
		}
	}


	/**
	 * Returns the error message wrapped into a mesage container
	 *
	 * @param String $errorString
	 * @return String The wrapper error message
	 */
	function errorMessage($errorString) {
		$errorMessage = '<div class="' . $this->prefixShort . '-warning_box">' . "\n";
		$errorMessage .= '<h3>' . $this->prefix_pi1 . ' error</h3>' . "\n";
		$errorMessage .= '<div>' . $errorString . '</div>' . "\n";
		$errorMessage .= '</div>' . "\n";
		return $errorMessage;
	}


	/**
	 * This initializes field restrictions
	 *
	 * @return void
	 */
	function init_restrictions() {
		$this->extConf['restrict'] = array();
		$restrictions =& $this->extConf['restrict'];

		$restrictionConfiguration =& $this->conf['restrictions.'];
		if (!is_array($restrictionConfiguration)) {
			return;
		}

		// This is a nested array containing fields
		// that may have restrictions
		$fields = array(
			'ref' => array(),
			'author' => array()
		);
		$allFields = array();
		// Acquire field configurations
		foreach ($restrictionConfiguration as $table => $data) {
			if (is_array($data)) {
				$t_fields = array();
				$table = substr($table, 0, -1);

				switch ($table) {
					case 'ref':
						$allFields =& $this->referenceReader->refFields;
						break;
					case 'authors':
						$allFields =& $this->referenceReader->authorFields;
						break;
					default:
						continue;
				}

				foreach ($data as $t_field => $t_data) {
					if (is_array($t_data)) {
						$t_field = substr($t_field, 0, -1);
						if (in_array($t_field, $allFields)) {
							$fields[$table][] = $t_field;
						}
					}
				}
			}
		}

		// Process restriction requests
		foreach ($fields as $table => $fields) {
			$restrictions[$table] = array();
			$d_table = $table . '.';
			foreach ($fields as $field) {
				$d_field = $field . '.';
				$rcfg = $restrictionConfiguration[$d_table][$d_field];

				// Hide all
				$all = ($rcfg['hide_all'] != 0);

				// Hide on string extensions
				$ext = \Ipf\Bib\Utility\Utility::explode_trim_lower(
					',', $rcfg['hide_file_ext'], TRUE);

				// Reveal on FE user groups
				$groups = strtolower($rcfg['FE_user_groups']);
				if (strpos($groups, 'all') === FALSE) {
					$groups = \Ipf\Bib\Utility\Utility::explode_intval(',', $groups);
				} else {
					$groups = 'all';
				}

				if ($all || (sizeof($ext) > 0)) {
					$restrictions[$table][$field] = array(
						'hide_all' => $all,
						'hide_ext' => $ext,
						'fe_groups' => $groups
					);
				}
			}
		}

		//GeneralUtiliy::debug ( $rest );
	}


	/**
	 * This initializes all filters before the browsing filter
	 *
	 * @return FALSE or an error message
	 */
	function init_filters() {
		$this->extConf['filters'] = array();
		$this->init_flexform_filter();
		$this->init_selection_filter();
	}


	/**
	 * This initializes filter array from the flexform
	 *
	 * @return FALSE or an error message
	 */
	function init_flexform_filter() {
		// Create and select the flexform filter
		$this->extConf['filters']['flexform'] = array();
		$filter =& $this->extConf['filters']['flexform'];

		// Flexform helpers
		$flexForm =& $this->cObj->data['pi_flexform'];
		$flexFormSheet = 's_filter';

		// Pid filter
		$filter['pid'] = $this->extConf['pid_list'];

		// Year filter
		if ($this->pi_getFFvalue($flexForm, 'enable_year', $flexFormSheet) > 0) {
			$flexFormFilter = array();
			$flexFormFilter['years'] = array();
			$flexFormFilter['ranges'] = array();
			$ffStr = $this->pi_getFFvalue($flexForm, 'years', $flexFormSheet);
			$arr = \Ipf\Bib\Utility\Utility::multi_explode_trim(array(',', "\r", "\n"), $ffStr, TRUE);
			foreach ($arr as $y) {
				if (strpos($y, '-') === FALSE) {
					if (is_numeric($y))
						$flexFormFilter['years'][] = intval($y);
				} else {
					$range = array();
					$elms = \Ipf\Bib\Utility\Utility::explode_trim('-', $y, FALSE);
					if (is_numeric($elms[0]))
						$range['from'] = intval($elms[0]);
					if (is_numeric($elms[1]))
						$range['to'] = intval($elms[1]);
					if (sizeof($range) > 0)
						$flexFormFilter['ranges'][] = $range;
				}
			}
			if ((sizeof($flexFormFilter['years']) + sizeof($flexFormFilter['ranges'])) > 0) {
				$filter['year'] = $flexFormFilter;
			}
		}

		// Author filter
		$this->extConf['highlight_authors'] = $this->pi_getFFvalue($flexForm, 'highlight_authors', $flexFormSheet);

		if ($this->pi_getFFvalue($flexForm, 'enable_author', $flexFormSheet) != 0) {
			$flexFormFilter = array();;
			$flexFormFilter['authors'] = array();
			$flexFormFilter['rule'] = $this->pi_getFFvalue($flexForm, 'author_rule', $flexFormSheet);
			$flexFormFilter['rule'] = intval($flexFormFilter['rule']);

			$authors = $this->pi_getFFvalue($flexForm, 'authors', $flexFormSheet);
			$authors = \Ipf\Bib\Utility\Utility::multi_explode_trim(array("\r", "\n"), $authors, TRUE);
			foreach ($authors as $a) {
				$parts = GeneralUtiliy::trimExplode(',', $a);
				$author = array();
				if (strlen($parts[0]) > 0)
					$author['surname'] = $parts[0];
				if (strlen($parts[1]) > 0)
					$author['forename'] = $parts[1];
				if (sizeof($author) > 0)
					$flexFormFilter['authors'][] = $author;
			}
			if (sizeof($flexFormFilter['authors']) > 0)
				$filter['author'] = $flexFormFilter;
		}

		// State filter
		if ($this->pi_getFFvalue($flexForm, 'enable_state', $flexFormSheet) != 0) {
			$flexFormFilter = array();
			$flexFormFilter['states'] = array();
			$states = intval($this->pi_getFFvalue($flexForm, 'states', $flexFormSheet));

			$j = 1;
			for ($i = 0; $i < sizeof($this->referenceReader->allStates); $i++) {
				if ($states & $j)
					$flexFormFilter['states'][] = $i;
				$j = $j * 2;
			}
			if (sizeof($flexFormFilter['states']) > 0)
				$filter['state'] = $flexFormFilter;
		}

		// Bibtype filter
		if ($this->pi_getFFvalue($flexForm, 'enable_bibtype', $flexFormSheet) != 0) {
			$flexFormFilter = array();
			$flexFormFilter['types'] = array();
			$types = $this->pi_getFFvalue($flexForm, 'bibtypes', $flexFormSheet);
			$types = explode(',', $types);
			foreach ($types as $v) {
				$v = intval($v);
				if (($v >= 0) && ($v < sizeof($this->referenceReader->allBibTypes)))
					$flexFormFilter['types'][] = $v;
			}
			if (sizeof($flexFormFilter['types']) > 0)
				$filter['bibtype'] = $flexFormFilter;
		}

		// Origin filter
		if ($this->pi_getFFvalue($flexForm, 'enable_origin', $flexFormSheet) != 0) {
			$flexFormFilter = array();
			$flexFormFilter['origin'] = $this->pi_getFFvalue($flexForm, 'origins', $flexFormSheet);
			if ($flexFormFilter['origin'] == 1)
				$flexFormFilter['origin'] = 0; // Legacy value
			else if ($flexFormFilter['origin'] == 2)
				$flexFormFilter['origin'] = 1; // Legacy value
			$filter['origin'] = $flexFormFilter;
		}

		// Reviewed filter
		if ($this->pi_getFFvalue($flexForm, 'enable_reviewes', $flexFormSheet) != 0) {
			$flexFormFilter = array();
			$flexFormFilter['value'] = $this->pi_getFFvalue($flexForm, 'reviewes', $flexFormSheet);
			$filter['reviewed'] = $flexFormFilter;
		}

		// In library filter
		if ($this->pi_getFFvalue($flexForm, 'enable_in_library', $flexFormSheet) != 0) {
			$flexFormFilter = array();
			$flexFormFilter['value'] = $this->pi_getFFvalue($flexForm, 'in_library', $flexFormSheet);
			$filter['in_library'] = $flexFormFilter;
		}

		// Borrowed filter
		if ($this->pi_getFFvalue($flexForm, 'enable_borrowed', $flexFormSheet) != 0) {
			$flexFormFilter = array();
			$flexFormFilter['value'] = $this->pi_getFFvalue($flexForm, 'borrowed', $flexFormSheet);
			$filter['borrowed'] = $flexFormFilter;
		}

		// Citeid filter
		if ($this->pi_getFFvalue($flexForm, 'enable_citeid', $flexFormSheet) != 0) {
			$flexFormFilter = array();
			$ids = $this->pi_getFFvalue($flexForm, 'citeids', $flexFormSheet);
			if (strlen($ids) > 0) {
				$ids = \Ipf\Bib\Utility\Utility::multi_explode_trim(array(',', "\r", "\n"), $ids, TRUE);
				$flexFormFilter['ids'] = array_unique($ids);
				$filter['citeid'] = $flexFormFilter;
			}
		}

		// Tags filter
		if ($this->pi_getFFvalue($flexForm, 'enable_tags', $flexFormSheet)) {
			$flexFormFilter = array();
			$flexFormFilter['rule'] = $this->pi_getFFvalue($flexForm, 'tags_rule', $flexFormSheet);
			$flexFormFilter['rule'] = intval($flexFormFilter['rule']);
			$kw = $this->pi_getFFvalue($flexForm, 'tags', $flexFormSheet);
			if (strlen($kw) > 0) {
				$words = \Ipf\Bib\Utility\Utility::multi_explode_trim(array(',', "\r", "\n"), $kw, TRUE);
				foreach ($words as &$word) {
					$word = $this->referenceReader->search_word($word, $this->extConf['charset']['upper']);
				}
				$flexFormFilter['words'] = $words;
				$filter['tags'] = $flexFormFilter;
			}
		}

		// Keywords filter
		if ($this->pi_getFFvalue($flexForm, 'enable_keywords', $flexFormSheet)) {
			$flexFormFilter = array();
			$flexFormFilter['rule'] = $this->pi_getFFvalue($flexForm, 'keywords_rule', $flexFormSheet);
			$flexFormFilter['rule'] = intval($flexFormFilter['rule']);
			$kw = $this->pi_getFFvalue($flexForm, 'keywords', $flexFormSheet);
			if (strlen($kw) > 0) {
				$words = \Ipf\Bib\Utility\Utility::multi_explode_trim(array(',', "\r", "\n"), $kw, TRUE);
				foreach ($words as &$word) {
					$word = $this->referenceReader->search_word($word, $this->extConf['charset']['upper']);
				}
				$flexFormFilter['words'] = $words;
				$filter['keywords'] = $flexFormFilter;
			}
		}

		// General keyword search
		if ($this->pi_getFFvalue($flexForm, 'enable_search_all', $flexFormSheet)) {
			$flexFormFilter = array();
			$flexFormFilter['rule'] = $this->pi_getFFvalue($flexForm, 'search_all_rule', $flexFormSheet);
			$flexFormFilter['rule'] = intval($flexFormFilter['rule']);
			$kw = $this->pi_getFFvalue($flexForm, 'search_all', $flexFormSheet);
			if (strlen($kw) > 0) {
				$words = \Ipf\Bib\Utility\Utility::multi_explode_trim(array(',', "\r", "\n"), $kw, TRUE);
				foreach ($words as &$word) {
					$word = $this->referenceReader->search_word($word, $this->extConf['charset']['upper']);
				}
				$flexFormFilter['words'] = $words;
				$filter['all'] = $flexFormFilter;
			}
		}
	}


	/**
	 * This initializes the selction filter array from the piVars
	 *
	 * @return string|bool FALSE or an error message
	 */
	function init_selection_filter() {
		if (!$this->conf['allow_selection']) {
			return FALSE;
		}

		$this->extConf['filters']['selection'] = array();
		$filter =& $this->extConf['filters']['selection'];

		// Publication ids
		if (is_string($this->piVars['search']['ref_ids'])) {
			$ids = $this->piVars['search']['ref_ids'];
			$ids = \Ipf\Bib\Utility\Utility::explode_intval(',', $ids);

			if (sizeof($ids) > 0) {
				$filter['uid'] = $ids;
			}
		}

		// General search
		if (is_string($this->piVars['search']['all'])) {
			$words = $this->piVars['search']['all'];
			$words = \Ipf\Bib\Utility\Utility::explode_trim(',', $words, TRUE);
			if (sizeof($words) > 0) {
				$filter['all']['words'] = $words;
				$filter['all']['rule'] = 1; // AND
				$rule = strtoupper(trim($this->piVars['search']['all_rule']));
				if (strpos($rule, 'AND') === FALSE) {
					$filter['all']['rule'] = 0; // OR
				}
			}
		}
	}


	/**
	 * Initializes an array which contains subparts of the
	 * html templates.
	 *
	 * @return TRUE on error, FALSE otherwise
	 */
	protected function initializeHtmlTemplate() {
		$error = array();

		// Allready initialized?
		if (isset ($this->template['LIST_VIEW']))
			return $error;

		$this->template = array();
		$this->itemTemplate = array();

		// List blocks
		$list_blocks = array(
			'YEAR_BLOCK', 'BIBTYPE_BLOCK', 'SPACER_BLOCK'
		);

		// Bibtype data blocks
		$bib_types = array();
		foreach ($this->referenceReader->allBibTypes as $val) {
			$bib_types[] = strtoupper($val) . '_DATA';
		}
		$bib_types[] = 'DEFAULT_DATA';
		$bib_types[] = 'ITEM_BLOCK';

		// Misc navigation blocks
		$navi_blocks = array(
			'EXPORT_NAVI_BLOCK',
			'IMPORT_NAVI_BLOCK',
			'NEW_ENTRY_NAVI_BLOCK'
		);

		// Fetch the template file list
		$tlist =& $this->conf['templates.'];
		if (!is_array($tlist)) {
			$error[] = 'HTML templates are not set in TypoScript';
			return $error;
		}

		$info = array(
			'main' => array(
				'file' => $tlist['main'],
				'parts' => array('LIST_VIEW')
			),
			'list_blocks' => array(
				'file' => $tlist['list_blocks'],
				'parts' => $list_blocks
			),
			'list_items' => array(
				'file' => $tlist['list_items'],
				'parts' => $bib_types,
				'no_warn' => TRUE
			),
			'navi_misc' => array(
				'file' => $tlist['navi_misc'],
				'parts' => $navi_blocks,
			)
		);

		foreach ($info as $key => $val) {
			if (strlen($val['file']) == 0) {
				$error[] = 'HTML template file for \'' . $key . '\' is not set';
				continue;
			}
			$tmpl = $this->cObj->fileResource($val['file']);
			if (strlen($tmpl) == 0) {
				$error[] = 'The HTML template file \'' . $val['file'] . '\' for \'' . $key .
						'\' is not readable or empty';
				continue;
			}
			foreach ($val['parts'] as $part) {
				$ptag = '###' . $part . '###';
				$pstr = $this->cObj->getSubpart($tmpl, $ptag);
				// Error message
				if ((strlen($pstr) == 0) && !$val['no_warn']) {
					$error[] = 'The subpart \'' . $ptag . '\' in the HTML template file \''
							. $val['file'] . '\' is empty';
				}
				$this->template[$part] = $pstr;
			}
		}

		return $error;
	}


	/**
	 * Initialize the edit icons
	 *
	 * @return void
	 */
	function init_edit_icons() {
		$list = array();
		$more = $this->conf['edit_icons.'];
		if (is_array($more))
			$list = array_merge($list, $more);

		$tmpl =& $GLOBALS['TSFE']->tmpl;
		foreach ($list as $key => $val) {
			$this->icon_src[$key] = $tmpl->getFileName($base . $val);
		}
	}


	/**
	 * Initialize the list view icons
	 *
	 * @return void
	 */
	function init_list_icons() {
		$list = array(
			'default' => 'EXT:cms/tslib/media/fileicons/default.gif');
		$more = $this->conf['file_icons.'];
		if (is_array($more)) {
			$list = array_merge($list, $more);
		}

		$tmpl =& $GLOBALS['TSFE']->tmpl;
		$this->icon_src['files'] = array();
		$ic =& $this->icon_src['files'];
		foreach ($list as $key => $val) {
			$ic['.' . $key] = $tmpl->getFileName($val);
		}
	}


	/**
	 * Extend the $this->LOCAL_LANG label with another language set
	 *
	 * @return void
	 */
	function extend_ll($file) {
		if (!is_array($this->extConf['LL_ext']))
			$this->extConf['LL_ext'] = array();
		if (!in_array($file, $this->extConf['LL_ext'])) {

			$tmpLang = GeneralUtility::readLLfile($file, $this->LLkey);
			foreach ($this->LOCAL_LANG as $lang => $list) {
				foreach ($list as $key => $word) {
					$tmpLang[$lang][$key] = $word;
				}
			}
			$this->LOCAL_LANG = $tmpLang;

			if ($this->altLLkey) {
				$tmpLang = GeneralUtility::readLLfile($file, $this->altLLkey);
				foreach ($this->LOCAL_LANG as $lang => $list) {
					foreach ($list as $key => $word) {
						$tmpLang[$lang][$key] = $word;
					}
				}
				$this->LOCAL_LANG = $tmpLang;
			}

			$this->extConf['LL_ext'][] = $file;
		}
	}


	/**
	 * Get the string in the local language to a given key .
	 *
	 * @return The string in the local language
	 */
	function get_ll($key, $alt = '', $hsc = FALSE) {
		return $this->pi_getLL($key, $alt, $hsc);
	}


	/**
	 * Composes a link of an url an some attributes
	 *
	 * @return The link (HTML <a> element)
	 */
	function compose_link($url, $content, $attribs = NULL) {
		$lstr = '<a href="' . $url . '"';
		if (is_array($attribs)) {
			foreach ($attribs as $k => $v) {
				$lstr .= ' ' . $k . '="' . $v . '"';
			}
		}
		$lstr .= '>' . $content . '</a>';
		return $lstr;
	}


	/**
	 * Wraps the content into a link to the current page with
	 * extra link arguments given in the array $vars
	 *
	 * @return The link to the current page
	 */
	function get_link($content, $vars = array(), $auto_cache = TRUE, $attribs = NULL) {
		$url = $this->get_link_url($vars, $auto_cache);
		return $this->compose_link($url, $content, $attribs);
	}


	/**
	 * Same as get_link but returns just the URL
	 *
	 * @return The url
	 */
	function get_link_url($vars = array(), $auto_cache = TRUE, $current_record = TRUE) {
		if ($this->extConf['edit_mode']) $auto_cache = FALSE;

		$vars = array_merge($this->extConf['link_vars'], $vars);
		$vars = array($this->prefix_pi1 => $vars);

		$record = '';
		if ($this->extConf['ce_links'] && $current_record)
			$record = "#c" . strval($this->cObj->data['uid']);

		$this->pi_linkTP('x', $vars, $auto_cache);
		$url = $this->cObj->lastTypoLinkUrl . $record;

		$url = preg_replace('/&([^;]{8})/', '&amp;\\1', $url);
		return $url;
	}


	/**
	 * Same as get_link() but for edit mode links
	 *
	 * @return The link to the current page
	 */
	function get_edit_link($content, $vars = array(), $auto_cache = TRUE, $attribs = array()) {
		$url = $this->get_edit_link_url($vars, $auto_cache);
		return $this->compose_link($url, $content, $attribs);
	}


	/**
	 * Same as get_link_url() but for edit mode urls
	 *
	 * @return The url
	 */
	function get_edit_link_url($vars = array(), $auto_cache = TRUE, $current_record = TRUE) {
		$pv =& $this->piVars;
		$keep = array('uid', 'editor_mode', 'editor');
		foreach ($keep as $k) {
			$pvar =& $pv[$k];
			if (is_string($pvar) || is_array($pvar) || is_numeric($pvar)) {
				$vars[$k] = $pvar;
			}
		}
		return $this->get_link_url($vars, $auto_cache, $current_record);
	}


	/**
	 * Returns an instance of a navigation bar class
	 *
	 * @return The url
	 */
	function get_navi_instance($type) {
		$obj = GeneralUtility::makeInstance('Ipf\\Bib\\Navigation\\' . $type);
		$obj->initialize($this);
		return $obj;
	}


	/**
	 * This function prepares database content fot HTML output
	 *
	 * @return The string filtered for html output
	 */
	function filter_pub_html($str, $hsc = FALSE) {
		$charset = $this->extConf['charset']['upper'];
		if ($hsc)
			$str = htmlspecialchars($str, ENT_QUOTES, $charset);

		return $str;
	}


	/**
	 * This replaces unneccessary tags and prepares the argument string
	 * for html output
	 *
	 * @return The string filtered for html output
	 */
	function filter_pub_html_display($str, $hsc = FALSE) {
		$rand = strval(rand()) . strval(rand());
		$str = str_replace(array('<prt>', '</prt>'), '', $str);

		// Remove not allowed tags
		// Keep the following tags
		$tags =& $this->referenceReader->allowedTags;

		$LE = '#LE' . $rand . 'LE#';
		$GE = '#GE' . $rand . 'GE#';

		foreach ($tags as $tag) {
			$str = str_replace('<' . $tag . '>', $LE . $tag . $GE, $str);
			$str = str_replace('</' . $tag . '>', $LE . '/' . $tag . $GE, $str);
		}

		$str = str_replace('<', '&lt;', $str);
		$str = str_replace('>', '&gt;', $str);

		$str = str_replace($LE, '<', $str);
		$str = str_replace($GE, '>', $str);

		$str = str_replace(array('<prt>', '</prt>'), '', $str);

		// End of remove not allowed tags

		// Handle illegal ampersands
		if (!(strpos($str, '&') === FALSE)) {
			$str = \Ipf\Bib\Utility\Utility::fix_html_ampersand($str);
		}

		$str = $this->filter_pub_html($str, $hsc);
		return $str;
	}


	/**
	 * This function composes the html-view of a set of publications
	 *
	 * @return The list view
	 */
	function list_view() {
		// Setup navigation elements
		$this->setup_search_navi();
		$this->setup_year_navi();
		$this->setup_author_navi();
		$this->setup_pref_navi();
		$this->setup_page_navi();

		$this->setup_new_entry_navi();

		$this->setup_export_navi();
		$this->setup_import_navi();
		$this->setup_statistic_navi();

		$this->setupSpacer();
		$this->setup_top_navigation();

		// Setup all publication items
		$this->setupItems();

		return $this->template['LIST_VIEW'];
	}


	/**
	 * Returns the year navigation bar
	 *
	 * @return A HTML string with the year navigation bar
	 */
	function setup_search_navi() {
		$trans = array();
		$hasStr = '';
		$cObj =& $this->cObj;

		if ($this->extConf['show_nav_search']) {
			$trans = $this->extConf['search_navi']['obj']->translator();
			$hasStr = array('', '');

			if (strlen($trans['###SEARCH_NAVI_TOP###']) > 0)
				$this->extConf['has_top_navi'] = TRUE;
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $cObj->substituteSubpart($tmpl, '###HAS_SEARCH_NAVI###', $hasStr);
		$tmpl = $cObj->substituteMarkerArrayCached($tmpl, $trans);
	}


	/**
	 * Returns the year navigation bar
	 *
	 * @return A HTML string with the year navigation bar
	 */
	function setup_year_navi() {
		$trans = array();
		$hasStr = '';
		$cObj =& $this->cObj;

		if ($this->extConf['show_nav_year']) {
			$obj = $this->get_navi_instance('YearNavigation');

			$trans = $obj->translator();
			$hasStr = array('', '');

			if (strlen($trans['###YEAR_NAVI_TOP###']) > 0)
				$this->extConf['has_top_navi'] = TRUE;
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $cObj->substituteSubpart($tmpl, '###HAS_YEAR_NAVI###', $hasStr);
		$tmpl = $cObj->substituteMarkerArrayCached($tmpl, $trans);
	}


	/**
	 * Sets up the author navigation bar
	 *
	 * @return void
	 */
	function setup_author_navi() {
		$trans = array();
		$hasStr = '';
		$cObj =& $this->cObj;

		if ($this->extConf['show_nav_author']) {
			$trans = $this->extConf['author_navi']['obj']->translator();
			$hasStr = array('', '');

			if (strlen($trans['###AUTHOR_NAVI_TOP###']) > 0)
				$this->extConf['has_top_navi'] = TRUE;
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $cObj->substituteSubpart($tmpl, '###HAS_AUTHOR_NAVI###', $hasStr);
		$tmpl = $cObj->substituteMarkerArrayCached($tmpl, $trans);
	}


	/**
	 * Sets up the page navigation bar
	 *
	 * @return void
	 */
	function setup_page_navi() {
		$trans = array();
		$hasStr = '';
		$cObj =& $this->cObj;

		if ($this->extConf['show_nav_page']) {
			$obj = $this->get_navi_instance('PageNavigation');

			$trans = $obj->translator();
			$hasStr = array('', '');

			if (strlen($trans['###PAGE_NAVI_TOP###']) > 0)
				$this->extConf['has_top_navi'] = TRUE;
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $cObj->substituteSubpart($tmpl, '###HAS_PAGE_NAVI###', $hasStr);
		$tmpl = $cObj->substituteMarkerArrayCached($tmpl, $trans);
	}


	/**
	 * Sets up the preferences navigation bar
	 *
	 * @return void
	 */
	function setup_pref_navi() {
		$trans = array();
		$hasStr = '';
		$cObj =& $this->cObj;

		if ($this->extConf['show_nav_pref']) {
			$trans = $this->extConf['pref_navi']['obj']->translator();
			$hasStr = array('', '');

			if (strlen($trans['###PREF_NAVI_TOP###']) > 0)
				$this->extConf['has_top_navi'] = TRUE;
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $cObj->substituteSubpart($tmpl, '###HAS_PREF_NAVI###', $hasStr);
		$tmpl = $cObj->substituteMarkerArrayCached($tmpl, $trans);
	}


	/**
	 * Setup the add-new-entry element
	 *
	 * @return void
	 */
	function setup_new_entry_navi() {
		$linkStr = '';
		$hasStr = '';

		if ($this->extConf['edit_mode']) {
			$tmpl = $this->setupEnumerationConditionBlock($this->template['NEW_ENTRY_NAVI_BLOCK']);
			$linkStr = $this->get_new_manipulator();
			$linkStr = $this->cObj->substituteMarker($tmpl, '###NEW_ENTRY###', $linkStr);
			$hasStr = array('', '');
			//GeneralUtiliy::debug ( $linkStr );
			$this->extConf['has_top_navi'] = TRUE;
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $this->cObj->substituteSubpart($tmpl, '###HAS_NEW_ENTRY###', $hasStr);
		$tmpl = $this->cObj->substituteMarker($tmpl, '###NEW_ENTRY###', $linkStr);
	}


	/**
	 * Setup the statistic element
	 *
	 * @return void
	 */
	function setup_statistic_navi() {
		$trans = array();
		$hasStr = '';
		$cObj =& $this->cObj;

		if ($this->extConf['show_nav_stat']) {
			$obj = $this->get_navi_instance('StatisticsNavigation');

			$trans = $obj->translator();
			$hasStr = array('', '');

			if (strlen($trans['###STAT_NAVI_TOP###']) > 0)
				$this->extConf['has_top_navi'] = TRUE;
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $this->cObj->substituteSubpart($tmpl, '###HAS_STAT_NAVI###', $hasStr);
		$tmpl = $cObj->substituteMarkerArrayCached($tmpl, $trans);
	}


	/**
	 * Setup the export-link element
	 *
	 * @return void
	 */
	function setup_export_navi() {
		$str = '';
		$hasStr = '';

		if ($this->extConf['show_nav_export']) {

			$cfg = array();
			if (is_array($this->conf['export.']))
				$cfg =& $this->conf['export.'];
			$extConf =& $this->extConf['export_navi'];

			$exports = array();

			// Export label
			$label = $this->get_ll($cfg['label']);
			$label = $this->cObj->stdWrap($label, $cfg['label.']);

			$mod_all = array('bibtex', 'xml');

			foreach ($mod_all as $mod) {
				if (in_array($mod, $extConf['modes'])) {
					$title = $this->get_ll('export_' . $mod . 'LinkTitle', $mod, TRUE);
					$txt = $this->get_ll('export_' . $mod);
					$link = $this->get_link(
						$txt,
						array('export' => $mod),
						FALSE,
						array('title' => $title)
					);
					$link = $this->cObj->stdWrap($link, $cfg[$mod . '.']);
					$exports[] = $link;
				}
			}

			$sep = '&nbsp;';
			if (array_key_exists('separator', $cfg))
				$sep = $this->cObj->stdWrap($cfg['separator'], $cfg['separator.']);

			// Export string
			$exports = implode($sep, $exports);

			// The translator
			$trans = array();
			$trans['###LABEL###'] = $label;
			$trans['###EXPORTS###'] = $exports;

			$block = $this->setupEnumerationConditionBlock($this->template['EXPORT_NAVI_BLOCK']);
			$block = $this->cObj->substituteMarkerArrayCached($block, $trans, array());
			$hasStr = array('', '');
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $this->cObj->substituteSubpart($tmpl, '###HAS_EXPORT###', $hasStr);
		$tmpl = $this->cObj->substituteMarker($tmpl, '###EXPORT###', $block);
	}


	/**
	 * Setup the import-link element in the
	 * HTML-template
	 *
	 * @return void
	 */
	function setup_import_navi() {
		$str = '';
		$hasStr = '';

		if ($this->extConf['edit_mode']) {

			$cfg = array();
			if (is_array($this->conf['import.']))
				$cfg =& $this->conf['import.'];

			$str = $this->setupEnumerationConditionBlock($this->template['IMPORT_NAVI_BLOCK']);
			$translator = array();
			$imports = array();

			// Import bibtex
			$title = $this->get_ll('import_bibtexLinkTitle', 'bibtex', TRUE);
			$link = $this->get_link($this->get_ll('import_bibtex'), array('import' => self::IMP_BIBTEX),
				FALSE, array('title' => $title));
			$imports[] = $this->cObj->stdWrap($link, $cfg['bibtex.']);

			// Import xml
			$title = $this->get_ll('import_xmlLinkTitle', 'xml', TRUE);
			$link = $this->get_link($this->get_ll('import_xml'), array('import' => self::IMP_XML),
				FALSE, array('title' => $title));
			$imports[] = $this->cObj->stdWrap($link, $cfg['xml.']);

			$sep = '&nbsp;';
			if (array_key_exists('separator', $cfg))
				$sep = $this->cObj->stdWrap($cfg['separator'], $cfg['separator.']);

			// Import label
			$translator['###LABEL###'] = $this->cObj->stdWrap(
				$this->get_ll($cfg['label']), $cfg['label.']);
			$translator['###IMPORTS###'] = implode($sep, $imports);

			$str = $this->cObj->substituteMarkerArrayCached($str, $translator, array());
			$hasStr = array('', '');
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $this->cObj->substituteSubpart($tmpl, '###HAS_IMPORT###', $hasStr);
		$tmpl = $this->cObj->substituteMarker($tmpl, '###IMPORT###', $str);
	}


	/**
	 * Setup the top navigation block
	 *
	 * @return void
	 */
	function setup_top_navigation() {
		$hasStr = '';
		if ($this->extConf['has_top_navi']) {
			$hasStr = array('', '');
		}
		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $this->cObj->substituteSubpart($tmpl, '###HAS_TOP_NAVI###', $hasStr);
	}


	/**
	 * Prepares database publication data for displaying
	 *
	 * @return array The procesed publication data array
	 */
	function prepare_pub_display($pub, &$warnings = array(), $show_hidden = false) {

		// The error list
		$d_err = array();

		// Prepare processed row data
		$pdata = $pub;
		foreach ($this->referenceReader->refFields as $f) {
			$pdata[$f] = $this->filter_pub_html_display($pdata[$f]);
		}

		// Preprocess some data

		// File url
		// Check file existance
		$file_url = trim(strval($pub['file_url']));
		if (\Ipf\Bib\Utility\Utility::check_file_nexist($file_url)) {
			$pdata['file_url'] = '';
			$pdata['_file_nexist'] = TRUE;
		} else {
			$pdata['_file_nexist'] = FALSE;
		}

		// Bibtype
		$pdata['bibtype_short'] = $this->referenceReader->allBibTypes[$pdata['bibtype']];
		$pdata['bibtype'] = $this->get_ll(
			$this->referenceReader->referenceTable . '_bibtype_I_' . $pdata['bibtype'],
				'Unknown bibtype: ' . $pdata['bibtype'], TRUE);

		// Extern
		$pdata['extern'] = ($pub['extern'] == 0 ? '' : 'extern');

		// Day
		if (($pub['day'] > 0) && ($pub['day'] <= 31)) {
			$pdata['day'] = strval($pub['day']);
		} else {
			$pdata['day'] = '';
		}

		// Month
		if (($pub['month'] > 0) && ($pub['month'] <= 12)) {
			$tme = mktime(0, 0, 0, intval($pub['month']), 15, 2008);
			$pdata['month'] = $tme;
		} else {
			$pdata['month'] = '';
		}

		// State
		switch ($pdata['state']) {
			case 0 :
				$pdata['state'] = '';
				break;
			default :
				$pdata['state'] = $this->get_ll(
					$this->referenceReader->referenceTable . '_state_I_' . $pdata['state'],
						'Unknown state: ' . $pdata['state'], TRUE);
		}

		// Bool strings
		$b_yes = $this->get_ll('label_yes', 'Yes', TRUE);
		$b_no = $this->get_ll('label_no', 'No', TRUE);

		// Bool fields
		$pdata['reviewed'] = ($pub['reviewed'] > 0) ? $b_yes : $b_no;
		$pdata['in_library'] = ($pub['in_library'] > 0) ? $b_yes : $b_no;

		//
		// Copy field values
		//
		$charset = $this->extConf['charset']['upper'];
		$url_max = 40;
		if (is_numeric($this->conf['max_url_string_length'])) {
			$url_max = intval($this->conf['max_url_string_length']);
		}

		// Iterate through reference fields
		foreach ($this->referenceReader->refFields as $f) {
			// Trim string
			$val = trim(strval($pdata[$f]));

			if (strlen($val) == 0) {
				$pdata[$f] = $val;
				continue;
			}

			// Check restrictions
			/*
			if ( $this->check_field_restriction ( 'ref', $f, $val, $show_hidden ) ) {
				$pdata[$f] = '';
				continue;
			}
*/
			// Treat some fields
			switch ($f) {
				case 'file_url':
				case 'web_url':
				case 'web_url2':
					$pdata[$f] = \Ipf\Bib\Utility\Utility::fix_html_ampersand($val);
					$val = \Ipf\Bib\Utility\Utility::crop_middle($val, $url_max, $charset);
					$pdata[$f . '_short'] = \Ipf\Bib\Utility\Utility::fix_html_ampersand($val);
					break;
				case 'DOI':
					$pdata[$f] = $val;
					$pdata['DOI_url'] = 'http://dx.doi.org/' . $val;
				default:
					$pdata[$f] = $val;
			}
		}

		// Multi fields
		$multi = array(
			'authors' => $this->referenceReader->authorFields
		);
		foreach ($multi as $table => $fields) {
			$elms =& $pdata[$table];
			if (!is_array($elms)) {
				continue;
			}
			foreach ($elms as &$elm) {
				foreach ($fields as $field) {
					$val = $elm[$field];
					// Check restrictions
					if (strlen($val) > 0) {
						if ($this->checkFieldRestriction($table, $field, $val)) {
							$val = '';
							$elm[$field] = $val;
						}
					}
					//GeneralUtiliy::debug ( array ( 'field' => $field, 'value' => $val ) );
					//GeneralUtiliy::debug ( array ( 'elm' => $elm ) );
				}
			}
		}

		// Format the author string
		$pdata['authors'] = $this->get_item_authors_html($pdata['authors']);

#echo '<pre>';
#print_r($pdata['authors']);
#echo '</pre>';

		// Format the author string
		$pdata['authors'] = $this->get_item_authors_html($pdata['authors']);

		#nkw
		/*
			echo "<pre>";
			print_r($pdata['editor']);
			echo "</pre>";
*/

		#nkw
		# hier merken wir uns wie die editoren aussehen, bevor die ext damit sachen macht, die wir nicht wollen
		# siehe auch: https://develop.sub.uni-goettingen.de/jira/browse/ADWD-631
		$cleanEditors = $pdata['editor'];
		#nkw end

		// Editors
		if (strlen($pdata['editor']) > 0) {
			$editors = \Ipf\Bib\Utility\Utility::explode_author_str($pdata['editor']);
			$lst = array();
			foreach ($editors as $ed) {
				$app = '';
				if (strlen($ed['forename']) > 0) $app .= $ed['forename'] . ' ';
				if (strlen($ed['surname']) > 0) $app .= $ed['surname'];
				$app = $this->cObj->stdWrap($app, $this->conf['field.']['editor_each.']);
				$lst[] = $app;
			}

			$and = ' ' . $this->get_ll('label_and', 'and', TRUE) . ' ';
			$pdata['editor'] = \Ipf\Bib\Utility\Utility::implode_and_last(
				$lst, ', ', $and);

			#nkw
			# nachdem die ext hier dinge mit den editoren macht, die wir nicht brauchen setzen wir das einfach mal wieder zur�ck.
			# siehe auch: https://develop.sub.uni-goettingen.de/jira/browse/ADWD-631
			$pdata['editor'] = $cleanEditors;
			#nkw end

		}

		// Automatic url
		$order = \Ipf\Bib\Utility\Utility::explode_trim(',', $this->conf['auto_url_order'], TRUE);
		$pdata['auto_url'] = $this->getAutoUrl($pdata, $order);
		$pdata['auto_url_short'] = \Ipf\Bib\Utility\Utility::crop_middle(
			$pdata['auto_url'], $url_max, $charset);

		// Do data checks
		if ($this->extConf['edit_mode']) {
			$w_cfg =& $this->conf['editor.']['list.']['warnings.'];

			// Local file does not exist
			$type = 'file_nexist';
			if ($w_cfg[$type]) {
				if ($pdata['_file_nexist']) {
					$msg = $this->get_ll('editor_error_file_nexist');
					$msg = str_replace('%f', $file_url, $msg);
					$d_err[] = array('type' => $type, 'msg' => $msg);
				}
			}

		}

		$warnings = $d_err;
		//GeneralUtiliy::debug ( $warnings );
		//GeneralUtiliy::debug ( $pdata );

		return $pdata;
	}


	/**
	 * Prepares the cObj->data array for a reference
	 *
	 * @return The procesed publication data array
	 */
	function prepare_pub_cObj_data($pdata) {
		// Item data
		$this->cObj->data = $pdata;
		$data =& $this->cObj->data;
		// Needed since stdWrap/Typolink applies htmlspecialchars to url data
		$data['file_url'] = htmlspecialchars_decode($pdata['file_url'], ENT_QUOTES);
		$data['web_url'] = htmlspecialchars_decode($pdata['web_url'], ENT_QUOTES);
		$data['web_url2'] = htmlspecialchars_decode($pdata['web_url2'], ENT_QUOTES);
		$data['DOI_url'] = htmlspecialchars_decode($pdata['DOI_url'], ENT_QUOTES);
		$data['auto_url'] = htmlspecialchars_decode($pdata['auto_url'], ENT_QUOTES);
	}


	/**
	 * Returns the html interpretation of the publication
	 * item as it is defined in the html template
	 *
	 * @return HTML string for a single item in the list view
	 */
	function get_item_html($pdata, $templ) {
		//GeneralUtiliy::debug ( array ( 'get_item_html($pdata)' => $pdata ) );
		$translator = array();
		$cObj =& $this->cObj;
		$conf =& $this->conf;

		$bib_str = $pdata['bibtype_short'];
		$all_base = 'rnd' . strval(rand()) . 'rnd';
		$all_wrap = $all_base;

		// Prepare the translator
		// Remove empty field marker from the template
		$fields = $this->referenceReader->pubFields;
		$fields[] = 'file_url_short';
		$fields[] = 'web_url_short';
		$fields[] = 'web_url2_short';
		$fields[] = 'auto_url';
		$fields[] = 'auto_url_short';

		foreach ($fields as $f) {
			$upStr = strtoupper($f);
			$tkey = '###' . $upStr . '###';
			$hasStr = '';
			$translator[$tkey] = '';

			$val = strval($pdata[$f]);

			if (strlen($val) > 0) {
				// Wrap default or by bibtype
				$stdWrap = array();
				$stdWrap = $conf['field.'][$f . '.'];
				if (is_array($conf['field.'][$bib_str . '.'][$f . '.']))
					$stdWrap = $conf['field.'][$bib_str . '.'][$f . '.'];
				//GeneralUtiliy::debug ( $stdWrap );
				if (isset ($stdWrap['single_view_link'])) {
					$val = $this->get_link($val, array('show_uid' => strval($pdata['uid'])));
				}
				$val = $cObj->stdWrap($val, $stdWrap);

				if (strlen($val) > 0) {
					$hasStr = array('', '');
					$translator[$tkey] = $val;
				}
			}

			$templ = $cObj->substituteSubpart($templ, '###HAS_' . $upStr . '###', $hasStr);
		}

		// Reference wrap
		$all_wrap = $cObj->stdWrap($all_wrap, $conf['reference.']);

		// Embrace hidden references with wrap
		if (($pdata['hidden'] != 0) && is_array($conf['editor.']['list.']['hidden.'])) {
			$all_wrap = $cObj->stdWrap($all_wrap, $conf['editor.']['list.']['hidden.']);
		}

		$templ = $cObj->substituteMarkerArrayCached($templ, $translator);
		$templ = $cObj->substituteMarkerArrayCached($templ, $this->labelTranslator);

		// Wrap elements with an anchor
		$url_wrap = array('', '');
		if (strlen($pdata['file_url']) > 0) {
			$url_wrap = $cObj->typolinkWrap(array('parameter' => $pdata['auto_url']));
		}
		$templ = $cObj->substituteSubpart($templ, '###URL_WRAP###', $url_wrap);

		$all_wrap = explode($all_base, $all_wrap);
		$templ = $cObj->substituteSubpart($templ, '###REFERENCE_WRAP###', $all_wrap);

		// remove empty divs
		$templ = preg_replace("/<div[^>]*>[\s\r\n]*<\/div>/", "\n", $templ);
		// remove multiple line breaks
		$templ = preg_replace("/\n+/", "\n", $templ);
		//GeneralUtiliy::debug ( $templ );

		return $templ;
	}


	/**
	 * Returns the authors string for a publication
	 *
	 * @return void
	 */
	function get_item_authors_html(& $authors) {

		$res = '';
		$charset = $this->extConf['charset']['upper'];

		// Load publication data into cObj
		$cObj =& $this->cObj;
		$contentObjectBackup = $cObj->data;

		// Format the author string$this->
		$and = ' ' . $this->get_ll('label_and', 'and', TRUE) . ' ';

		$and = ';';

		$max_authors = abs(intval($this->extConf['max_authors']));
		$lastAuthor = sizeof($authors) - 1;
		$cutAuthors = FALSE;
		if (($max_authors > 0) && (sizeof($authors) > $max_authors)) {
			$cutAuthors = TRUE;
			if (sizeof($authors) == ($max_authors + 1)) {
				$lastAuthor = $max_authors - 2;
			} else {
				$lastAuthor = $max_authors - 1;
			}
			$and = '';
		}
		$lastAuthor = max($lastAuthor, 0);

		$highlightAuthors = $this->extConf['highlight_authors'] ? TRUE : FALSE;

		$link_fields = $this->extConf['author_sep'];
		$a_sep = $this->extConf['author_sep'];
		$authorTemplate = $this->extConf['author_tmpl'];

		$filter_authors = array();
		if ($highlightAuthors) {
			// Collect filter authors
			foreach ($this->extConf['filters'] as $filter) {
				if (is_array($filter['author']['authors'])) {
					$filter_authors = array_merge(
						$filter_authors, $filter['author']['authors']);
				}
			}
		}

		$icon_img =& $this->extConf['author_icon_img'];

		$elements = array();
		// Iterate through authors
		for ($i_a = 0; $i_a <= $lastAuthor; $i_a++) {
			$a = $authors[$i_a];
			// debug($a);

			// Init cObj data
			$cObj->data = $a;
			$cObj->data['url'] = htmlspecialchars_decode($a['url'], ENT_QUOTES);

			// The forename
			$authorForename = trim($a['forename']);
			if (strlen($authorForename) > 0) {
				$authorForename = $this->filter_pub_html_display($authorForename);
				$authorForename = $this->cObj->stdWrap($authorForename, $this->conf['authors.']['forename.']);
			}

			// The surname
			$authorSurname = trim($a['surname']);
			if (strlen($authorSurname) > 0) {
				$authorSurname = $this->filter_pub_html_display($authorSurname);
				$authorSurname = $this->cObj->stdWrap($authorSurname, $this->conf['authors.']['surname.']);
			}

			// The link icon
			$cr_link = FALSE;
			$authorIcon = '';
			foreach ($this->extConf['author_lfields'] as $field) {
				$val = trim(strval($a[$field]));
				if ((strlen($val) > 0) && ($val != '0')) {
					$cr_link = TRUE;
					break;
				}
			}
			if ($cr_link && (strlen($icon_img) > 0)) {
				$wrap = $this->conf['authors.']['url_icon.'];
				if (is_array($wrap)) {
					if (is_array($wrap['typolink.'])) {
						$title = $this->get_ll('link_author_info', 'Author info', TRUE);
						$wrap['typolink.']['title'] = $title;
					}
					$authorIcon = $this->cObj->stdWrap($icon_img, $wrap);
				}
			}

			// Compose names
			$a_str = str_replace(
				array('###SURNAME###', '###FORENAME###', '###URL_ICON###'),
				array($authorSurname, $authorForename, $authorIcon), $authorTemplate);

			// apply stdWrap
			$stdWrap = $this->conf['field.']['author.'];
			if (is_array($this->conf['field.'][$bib_str . '.']['author.'])) {
				$stdWrap = $this->conf['field.'][$bib_str . '.']['author.'];
			}
			$a_str = $this->cObj->stdWrap($a_str, $stdWrap);

			// Wrap the filtered authors with a highlightning class on demand
			if ($highlightAuthors) {
				foreach ($filter_authors as $fa) {
					if ($a['surname'] == $fa['surname']) {
						if (!$fa['forename'] || ($a['forename'] == $fa['forename'])) {
							$a_str = $this->cObj->stdWrap(
								$a_str, $this->conf['authors.']['highlight.']);
							break;
						}
					}
				}
			}

			// Append author name
			if (!empty($authorSurname)) {
				$elements[] = $authorSurname . ', ' . $authorForename;
			}


			// Append 'et al.'
			if ($cutAuthors && ($i_a == $lastAuthor)) {
				// Append et al.
				$etAl = $this->get_ll('label_et_al', 'et al.', TRUE);
				$etAl = (strlen($etAl) > 0) ? ' ' . $etAl : '';

				if (strlen($etAl) > 0) {
					$wrap = FALSE;

					// Highlight "et al." on demand
					if ($highlightAuthors) {
						for ($j = $lastAuthor + 1; $j < sizeof($authors); $j++) {
							$a_et = $authors[$j];
							foreach ($filter_authors as $fa) {
								if ($a_et['surname'] == $fa['surname']) {
									if (!$fa['forename']
											|| ($a_et['forename'] == $fa['forename'])
									) {
										$wrap = $this->conf['authors.']['highlight.'];
										$j = sizeof($authors);
										break;
									}
								}
							}
						}
					}

					if (is_array($wrap)) {
						$etAl = $this->cObj->stdWrap($app, $wrap);
					}
					$wrap = $this->conf['authors.']['et_al.'];
					$etAl = $this->cObj->stdWrap($etAl, $wrap);
					$elements[] = $etAl;
				}
			}
		}

		$res = \Ipf\Bib\Utility\Utility::implode_and_last($elements, $a_sep, $and);

		// Restore cObj data
		$cObj->data = $contentObjectBackup;

		return $res;
	}


	/**
	 * Setup items in the html-template
	 *
	 * @return void
	 */
	public function prepareItemSetup() {
		$cObj =& $this->cObj;
		$conf =& $this->conf;

		$charset = $this->extConf['charset']['upper'];

		// The author name template
		$this->extConf['author_tmpl'] = '###FORENAME### ###SURNAME###';
		if (isset ($conf['authors.']['template'])) {
			$this->extConf['author_tmpl'] = $cObj->stdWrap(
				$conf['authors.']['template'], $conf['authors.']['template.']
			);
		}
		$this->extConf['author_sep'] = ', ';
		if (isset ($conf['authors.']['separator'])) {
			$this->extConf['author_sep'] = $cObj->stdWrap(
				$conf['authors.']['separator'], $conf['authors.']['separator.']
			);
		}
		$this->extConf['author_lfields'] = 'url';
		if (isset ($conf['authors.']['url_icon_fields'])) {
			$this->extConf['author_lfields'] =
					\Ipf\Bib\Utility\Utility::explode_trim(',',
						$conf['authors.']['url_icon_fields'], TRUE);
		}

		// Acquire author url icon
		$authorsUrlIconFile = trim($this->conf['authors.']['url_icon_file']);
		$imageTag = '';
		if (strlen($authorsUrlIconFile) > 0) {
			$authorsUrlIconFile = $GLOBALS['TSFE']->tmpl->getFileName($authorsUrlIconFile);
			$authorsUrlIconFile = htmlspecialchars($authorsUrlIconFile, ENT_QUOTES, $charset);
			$alt = $this->get_ll('img_alt_person', 'Author image', TRUE);
			$imageTag = '<img';
			$imageTag .= ' src="' . $authorsUrlIconFile . '"';
			$imageTag .= ' alt="' . $alt . '"';
			$class =& $this->conf['authors.']['url_icon_class'];
			if (is_string($class)) {
				$imageTag .= ' class="' . $class . '"';
			}
			$imageTag .= '/>';
		}
		$this->extConf['author_icon_img'] = $imageTag;

	}


	/**
	 * Setup items in the html-template
	 *
	 * @return void
	 */
	protected function setupItems() {
		$items = array();

		// Aliases
		$conf =& $this->conf;
		$filters =& $this->extConf['filters'];

		// Store cObj data
		$contentObjectBackup = $this->cObj->data;

		$this->prepareItemSetup();

		// Initialize the label translator
		$this->labelTranslator = array();
		$labelTranslator =& $this->labelTranslator;
		$labels = array(
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
		);

		foreach ($labels as $label) {
			$upperCaseLabel = strtoupper($label);
			$labelValue = $this->get_ll('label_' . $label);
			$labelValue = $this->cObj->stdWrap($labelValue, $conf['label.'][$label . '.']);
			$labelTranslator['###LABEL_' . $upperCaseLabel . '###'] = $labelValue;
		}

		// block templates
		$itemTemplate = array();
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
		$enumerationBase = strval($conf['enum.'][$enumerationIdentifier]);
		$enumerationWrap = $conf['enum.'][$enumerationIdentifier . '.'];

		// Warning cfg
		$warningConfiguration =& $this->conf['editor.']['list.']['warn_box.'];
		$editMode = $this->extConf['edit_mode'];

		if ($this->extConf['d_mode'] == self::D_Y_SPLIT) {
			$this->extConf['split_years'] = TRUE;
		}

		// Database reading initialization
		$this->referenceReader->mFetch_initialize();

		// Determine publication numbers
		$publicationsBefore = 0;
		if (($this->extConf['d_mode'] == self::D_Y_NAV) &&
				is_numeric($this->extConf['year'])
		) {
			foreach ($this->stat['year_hist'] as $y => $n) {
				if ($y == $this->extConf['year'])
					break;
				$publicationsBefore += $n;
			}
		}

		$prevBibType = -1;
		$prevYear = -1;

		// Initialize counters
		$limit_start = intval($filters['br_page']['limit']['start']);
		$i_page = $this->stat['num_page'] - $limit_start;
		$i_page_delta = -1;
		if ($this->extConf['date_sorting'] == self::SORT_ASC) {
			$i_page = $limit_start + 1;
			$i_page_delta = 1;
		}

		$i_subpage = 1;
		$i_bibtype = 1;

		// Start the fetch loop
		while ($pub = $this->referenceReader->mFetch()) {
			// Get prepared publication data
			$warnings = array();
			$pdata = $this->prepare_pub_display($pub, $warnings);

			// Item data
			$this->prepare_pub_cObj_data($pdata);

			// All publications counter
			$i_all = $publicationsBefore + $i_page;

			// Determine evenOdd
			if ($this->extConf['split_bibtypes']) {
				if ($pub['bibtype'] != $prevBibType)
					$i_bibtype = 1;
				$evenOdd = $i_bibtype % 2;
			} else {
				$evenOdd = $i_subpage % 2;
			}

			// Setup the item template
			$listViewTemplate = $itemTemplate[$pdata['bibtype']];
			if (strlen($listViewTemplate) == 0) {
				$key = strtoupper($pdata['bibtype_short']) . '_DATA';
				$listViewTemplate = $this->template[$key];
				if (strlen($listViewTemplate) == 0)
					$data_block = $this->template['DEFAULT_DATA'];
				$listViewTemplate = $this->cObj->substituteMarker($itemBlockTemplate,
					'###ITEM_DATA###', $listViewTemplate);
				$itemTemplate[$pdata['bibtype']] = $listViewTemplate;
			}

			// Initialize the translator
			$translator = array();

			$enum = $enumerationBase;
			$enum = str_replace('###I_ALL###', strval($i_all), $enum);
			$enum = str_replace('###I_PAGE###', strval($i_page), $enum);
			if (!(strpos($enum, '###FILE_URL_ICON###') === FALSE)) {
				$repl = $this->getFileUrlIcon($pub, $pdata);
				$enum = str_replace('###FILE_URL_ICON###', $repl, $enum);
			}
			$translator['###ENUM_NUMBER###'] = $this->cObj->stdWrap($enum, $enumerationWrap);

			// Row classes
			$eo = $evenOdd ? 'even' : 'odd';

			$translator['###ROW_CLASS###'] = $conf['classes.'][$eo];

			$translator['###NUMBER_CLASS###'] = $this->prefixShort . '-enum';

			// Manipulators
			$translator['###MANIPULATORS###'] = '';
			$manip_edit = '';
			$manip_hide = '';
			$manip_all = array();
			$subst_sub = '';
			if ($editMode) {
				if ($this->checkFEauthorRestriction($pub['uid'])) {
					$subst_sub = array('', '');
					$manip_all[] = $this->get_edit_manipulator($pub);
					$manip_all[] = $this->get_hide_manipulator($pub);
					$manip_all = \Ipf\Bib\Utility\Utility::html_layout_table(array($manip_all));

					$translator['###MANIPULATORS###'] = $this->cObj->stdWrap(
						$manip_all, $conf['editor.']['list.']['manipulators.']['all.']
					);
				}
			}

			$listViewTemplate = $this->cObj->substituteSubpart($listViewTemplate, '###HAS_MANIPULATORS###', $subst_sub);

			// Year separator label
			if ($this->extConf['split_years'] && ($pub['year'] != $prevYear)) {
				$yearStr = $this->cObj->stdWrap(strval($pub['year']), $conf['label.']['year.']);
				$items[] = $this->cObj->substituteMarker($yearBlockTemplate, '###YEAR###', $yearStr);
				$prevBibType = -1;
			}

			// Bibtype separator label
			if ($this->extConf['split_bibtypes'] && ($pub['bibtype'] != $prevBibType)) {
				$bibStr = $this->cObj->stdWrap(
					$this->get_ll('bibtype_plural_' . $pub['bibtype'], $pub['bibtype'], TRUE),
					$conf['label.']['bibtype.']
				);
				$items[] = $this->cObj->substituteMarker($bibliographyTypeBlockTemplate, '###BIBTYPE###', $bibStr);
			}

			// Append string for item data
			$append = '';
			if ((sizeof($warnings) > 0) && $editMode) {
				$charset = $this->extConf['charset']['upper'];
				foreach ($warnings as $err) {
					$msg = htmlspecialchars($err['msg'], ENT_QUOTES, $charset);
					$append .= $this->cObj->stdWrap($msg, $warningConfiguration['msg.']);
				}
				$append = $this->cObj->stdWrap($append, $warningConfiguration['all_wrap.']);
			}
			$translator['###ITEM_APPEND###'] = $append;


			// Apply translator
			$listViewTemplate = $this->cObj->substituteMarkerArrayCached($listViewTemplate, $translator);

			// Pass to item processor
			$items[] = $this->get_item_html($pdata, $listViewTemplate);

			// Update counters
			$i_page += $i_page_delta;
			$i_subpage++;
			$i_bibtype++;

			$prevBibType = $pub['bibtype'];
			$prevYear = $pub['year'];
		}

		// clean up
		$this->referenceReader->mFetch_finish();

		// Restore cObj data
		$this->cObj->data = $contentObjectBackup;

		$items = implode('', $items);

		$hasStr = '';
		$no_items = '';
		if (strlen($items) > 0) {
			$hasStr = array('', '');
		} else {
			$no_items = strval($this->extConf['post_items']);
			if (strlen($no_items) == 0) {
				$no_items = $this->get_ll('label_no_items');
			}
			$no_items = $this->cObj->stdWrap($no_items, $conf['label.']['no_items.']);
		}

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_ITEMS###', $hasStr);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $this->labelTranslator);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarker($this->template['LIST_VIEW'], '###NO_ITEMS###', $no_items);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarker($this->template['LIST_VIEW'], '###ITEMS###', $items);
	}


	/**
	 * Returns the new entry button
	 */
	function get_new_manipulator() {
		$label = $this->get_ll('manipulators_new', 'New', TRUE);
		$imgSrc = 'src="' . $this->icon_src['new_record'] . '"';
		$img = '<img ' . $imgSrc . ' alt="' . $label . '" ' .
				'class="' . $this->prefixShort . '-new_icon" />';

		$res = $this->get_link($img, array('action' => array('new' => 1)), TRUE, array('title' => $label));
		$res . $this->cObj->stdWrap($res, $this->conf['editor.']['list.']['manipulators.']['new.']);
		return $res;
	}


	/**
	 * Returns the edit button
	 */
	function get_edit_manipulator($pub) {
		// The edit button
		$label = $this->get_ll('manipulators_edit', 'Edit', TRUE);
		$imgSrc = 'src="' . $this->icon_src['edit'] . '"';
		$img = '<img ' . $imgSrc . ' alt="' . $label . '" ' .
				'class="' . $this->prefixShort . '-edit_icon" />';

		$res = $this->get_link($img,
			array('action' => array('edit' => 1), 'uid' => $pub['uid']),
			TRUE, array('title' => $label));

		$res = $this->cObj->stdWrap($res, $this->conf['editor.']['list.']['manipulators.']['edit.']);

		return $res;
	}

	/**
	 * Returns the hide button
	 */
	function get_hide_manipulator($pub) {
		if ($pub['hidden'] == 0) {
			$label = $this->get_ll('manipulators_hide', 'Hide', TRUE);
			$imgSrc = 'src="' . $this->icon_src['hide'] . '"';
			$action = array('hide' => 1);
		} else {
			$label = $this->get_ll('manipulators_reveal', 'Reveal', TRUE);
			$imgSrc = 'src="' . $this->icon_src['reveal'] . '"';
			$action = array('reveal' => 1);
		}

		$img = '<img ' . $imgSrc . ' alt="' . $label . '" ' .
				'class="' . $this->prefixShort . '-hide_icon" />';
		$res = $this->get_link($img,
			array('action' => $action, 'uid' => $pub['uid']),
			TRUE, array('title' => $label));

		$res = $this->cObj->stdWrap($res, $this->conf['editor.']['list.']['manipulators.']['hide.']);

		return $res;
	}


	/**
	 * Returns TRUE if the field/value combination is restricted
	 * and should not be displayed
	 *
	 * @param String $table
	 * @param String $field
	 * @param String $value
	 * @param bool $showHidden
	 * @return bool TRUE (restricted) or FALSE (not restricted)
	 */
	protected function checkFieldRestriction($table, $field, $value, $showHidden = false) {
		// No value no restriction
		if (strlen($value) == 0) {
			return FALSE;
		}

		// Field is hidden
		if (!$showHidden && $this->extConf['hide_fields'][$field]) {
			return TRUE;
		}

		// Are there restrictions at all?
		$restrictions =& $this->extConf['restrict'][$table];
		if (!is_array($restrictions) || (sizeof($restrictions) == 0)) {
			return FALSE;
		}

		// Check Field restrictions
		if (is_array($restrictions[$field])) {
			$restrictionConfiguration =& $restrictions[$field];

			// Show by default
			$show = TRUE;

			// Hide on 'hide all'
			if ($restrictionConfiguration['hide_all']) {
				$show = FALSE;
			}

			// Hide if any extensions matches
			if ($show && is_array($restrictionConfiguration['hide_ext'])) {
				foreach ($restrictionConfiguration['hide_ext'] as $ext) {
					// Sanitize input
					$len = strlen($ext);
					if (($len > 0) && (strlen($value) >= $len)) {
						$uext = strtolower(substr($value, -$len));

						if ($uext == $ext) {
							$show = FALSE;
							break;
						}
					}
				}
			}

			// Enable if usergroup matches
			if (!$show && isset ($restrictionConfiguration['fe_groups'])) {
				$groups = $restrictionConfiguration['fe_groups'];
				if (\Ipf\Bib\Utility\Utility::check_fe_user_groups($groups))
					$show = TRUE;
			}

			// Restricted !
			if (!$show) {
				return TRUE;
			}
		}

		return FALSE;
	}


	/**
	 * Prepares the virtual auto_url from the data and field order
	 *
	 * @param array $processedPublicationData The processed publication data
	 * @param array $order
	 * @return The generated url
	 */
	protected function getAutoUrl($processedPublicationData, $order) {

		$url = '';

		foreach ($order as $field) {
			if (strlen($processedPublicationData[$field]) == 0) {
				continue;
			}
			if ($this->checkFieldRestriction('ref', $field, $processedPublicationData[$field])) {
				continue;
			}

			switch ($field) {
				case 'file_url':
					if (!$processedPublicationData['_file_nexist']) {
						$url = $processedPublicationData[$field];
					}
					break;
				case 'DOI':
					$url = $processedPublicationData['DOI_url'];
					break;
				default:
					$url = $processedPublicationData[$field];
			}

			if (strlen($url) > 0) {
				break;
			}
		}

		return $url;
	}


	/**
	 * Returns the file url icon
	 *
	 * @param array $unprocessedDatabaseData The unprocessed db data
	 * @param array $processedDatabaseData The processed db data
	 * @return The html icon img tag
	 */
	protected function getFileUrlIcon($unprocessedDatabaseData, $processedDatabaseData) {

		$fileSources =& $this->icon_src['files'];

		$src = strval($fileSources['.empty_default']);
		$alt = 'default';

		// Acquire file type
		$url = '';
		if (!$processedDatabaseData['_file_nexist']) {
			$url = $unprocessedDatabaseData['file_url'];
		}
		if (strlen($url) > 0) {
			$src = $fileSources['.default'];

			foreach ($fileSources as $ext => $file) {
				$len = strlen($ext);
				if (strlen($url) >= $len) {
					$sub = strtolower(substr($url, -$len));
					if ($sub == $ext) {
						$src = $file;
						$alt = substr($ext, 1);
						break;
					}
				}
			}
		}

		if (strlen($src) > 0) {
			$imageTag = '<img src="' . $src . '"';
			$imageTag .= ' alt="' . $alt . '"';
			$fileIconClass = $this->conf['enum.']['file_icon_class'];
			if (is_string($fileIconClass)) {
				$imageTag .= ' class="' . $fileIconClass . '"';
			}
			$imageTag .= '/>';
		} else {
			$imageTag = '&nbsp;';
		}

		$wrap = $this->conf['enum.']['file_icon_image.'];
		if (is_array($wrap)) {
			if (is_array($wrap['typolink.'])) {
				$title = $this->get_ll('link_get_file', 'Get file', TRUE);
				$wrap['typolink.']['title'] = $title;
			}
			$imageTag = $this->cObj->stdWrap($imageTag, $wrap);
		}

		return $imageTag;
	}


	/**
	 * Removes the enumeration condition block
	 * or just the block markers
	 *
	 * @param String $template
	 * @return void
	 */
	public function setupEnumerationConditionBlock($template) {
		$sub = $this->extConf['has_enum'] ? array() : '';
		$template = $this->cObj->substituteSubpart(
			$template, '###HAS_ENUM###', $sub);
		return $template;
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
	 * Hides or reveals a publication
	 *
	 * @param bool $hide
	 * @return void
	 */
	protected function hidePublication($hide = TRUE) {
		/** @var \Ipf\Bib\Utility\ReferenceWriter $referenceWriter */
		$referenceWriter = GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\ReferenceWriter');
		$referenceWriter->initialize($this->referenceReader);
		$referenceWriter->hide_publication($this->piVars['uid'], $hide);
	}


	/**
	 * This loads the single view
	 *
	 * @return String The single view
	 */
	protected function singleView() {
		/** @var \Ipf\Bib\View\SingleView $singleView */
		$singleView = GeneralUtility::makeInstance('Ipf\\Bib\\View\\SingleView');
		$singleView->initialize($this);

		return $singleView->single_view();
	}


	/**
	 * This loads the editor view
	 *
	 * @return String The editor view
	 */
	protected function editorView() {
		/** @var \Ipf\Bib\View\EditorView $editorView */
		$editorView = GeneralUtility::makeInstance('Ipf\\Bib\\View\\EditorView');
		$editorView->initialize($this);

		return $editorView->editor_view();
	}


	/**
	 * This switches to the requested dialog
	 *
	 * @return String The requested dialog
	 */
	protected function dialogView() {
		$content = '';
		switch ($this->extConf['dialog_mode']) {
			case self::DIALOG_EXPORT :
				$content .= $this->exportDialog();
				break;
			case self::DIALOG_IMPORT :
				$content .= $this->importDialog();
				break;
			default :
				/** @var \tx_bib_editor_view $editorView */
				$editorView = GeneralUtility::makeInstance('tx_bib_editor_view');
				$editorView->initialize($this);
				$content .= $editorView->dialog_view();
		}
		$content .= '<p>';
		$content .= $this->get_link($this->get_ll('link_back_to_list'));
		$content .= '</p>' . "\n";

		return $content;
	}


	/**
	 * The export dialog
	 *
	 * @return String The export dialog
	 */
	protected function exportDialog() {
		$content = '';
		$mode = $this->extConf['export_navi']['do'];
		$title = $this->get_ll('export_title');
		$content .= '<h2>' . $title . '</h2>' . "\n";

		$exporter = FALSE;
		$label = '';
		$exporterClass = '';
		switch ($mode) {
			case 'bibtex':
				$exporterClass = 'Ipf\\Bib\\Utility\\Exporter\\BibTexExporter';
				$label = 'export_bibtex';
				break;
			case 'xml':
				$exporterClass = 'Ipf\\Bib\\Utility\\Exporter\\XmlExporter';
				$label = 'export_xml';
				break;
			default:
				return $this->errorMessage('Unknown export mode');
		}

		/** @var \Ipf\Bib\Utility\Exporter\Exporter $exporter */
		$exporter = GeneralUtility::makeInstance($exporterClass);
		$label = $this->get_ll($label, $label, TRUE);

		if ($exporter instanceof \Ipf\Bib\Utility\Exporter\Exporter) {
			try {
				$exporter->initialize($this);
			} catch (\Exception $e) {
				$e->getMessage();
			}

			$dynamic = $this->conf['export.']['dynamic'] ? TRUE : FALSE;
			if ($this->extConf['dynamic'])
				$dynamic = TRUE;
			$exporter->dynamic = $dynamic;

			if ($exporter->export()) {
				$content .= $this->errorMessage($exporter->error);
			} else {
				if ($dynamic) {

					// Dump the export data and exit
					$exporterFileName = $exporter->file_name;
					header('Content-Type: text/plain');
					header('Content-Disposition: attachment; filename="' . $exporterFileName . '"');
					header('Cache-Control: no-cache, must-revalidate');
					echo $exporter->data;
					exit ();

				} else {
					// Create link to file
					$link = $this->cObj->getTypoLink($exporter->file_name,
						$exporter->get_file_rel());
					$content .= '<ul><li><div>';
					$content .= $link;
					if ($exporter->file_new)
						$content .= ' (' . $this->get_ll('export_file_new') . ')';
					$content .= '</div></li>';
					$content .= '</ul>' . "\n";
				}
			}
		}

		return $content;
	}


	/**
	 * The import dialog
	 *
	 * @return String The import dialog
	 */
	protected function importDialog() {
		$content = '';
		$title = $this->get_ll('import_title');
		$content .= '<h2>' . $title . '</h2>' . "\n";
		$mode = $this->piVars['import'];

		if (($mode == self::IMP_BIBTEX) || ($mode == self::IMP_XML)) {

			$importer = FALSE;
			switch ($mode) {
				case self::IMP_BIBTEX:
					$importer = GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\Importer\\BibTexImporter');
					break;
				case self::IMP_XML:
					$importer = GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\Importer\\XmlImporter');
					break;
			}
			$importer->initialize($this);
			$content .= $importer->import();
		} else {
			$content .= $this->errorMessage('Unknown import mode');
		}

		return $content;
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
	 * @return TRUE (allowed) FALSE (restricted)
	 */
	function checkFEauthorRestriction($publicationId) {
		// always allow BE users with sufficient rights
		if (is_object($GLOBALS['BE_USER'])) {
			if ($GLOBALS['BE_USER']->isAdmin())
				return true;
			else if ($GLOBALS['BE_USER']->check('tables_modify', $this->referenceReader->referenceTable))
				return true;
		}

		// Is FE-user editing only for own records enabled? (set via TS)
		if (isset ($this->conf['FE_edit_own_records']) && $this->conf['FE_edit_own_records'] != 0) {

			// query all authors of this publication
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				"fe_user_id",
				"tx_bib_domain_model_author as a,tx_bib_domain_model_authorships as m",
					"a.uid=m.author_id AND m.pub_id=" . $publicationId
			);

			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res)) {
				// check if author == FE user and allow editing
				if ($row[0] == $GLOBALS['TSFE']->fe_user->user[$GLOBALS['TSFE']->fe_user->userid_column])
					return true;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);

			return false;
		}

		// default behavior, FE user can edit all records
		return true;
	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/pi1/class.tx_bib_pi1.php"]) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/pi1/class.tx_bib_pi1.php"]);
}

?>