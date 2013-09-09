<?php

use \TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Sebastian Holtermann (sebholt@web.de)
 *  (c) 2013 Ingo Pfennigstorf <i.pfennigstorf@gmail.com>
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

	/**
	 * @var string
	 */
	public $template;

	/**
	 * @var string
	 */
	public $itemTemplate;

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

	/**
	 * Statistices
	 *
	 * @var array
	 */
	public $stat = array();

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

	protected $flexForm;

	/**
	 * @var string
	 */
	protected $flexFormFilterSheet;

	/**
	 * The main function merges all configuration options and
	 * switches to the appropriate request handler
	 *
	 * @param string $content
	 * @param array $conf
	 *
	 * @return string The plugin HTML content
	 */
	public function main($content, $conf) {
		$this->conf = $conf;
		$this->extConf = array();
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->extend_ll('EXT:' . $this->extKey . '/Resources/Private/Language/locallang_db.xml');
		$this->pi_initPIflexForm();

		$this->flexFormData = $this->cObj->data['pi_flexform'];

		$this->includeJavaScript();

		$this->initializeReferenceReader();

		$this->getExtensionConfiguration();

		$this->getTypoScriptConfiguration();

		$this->getCharacterSet();

		$this->getFrontendEditorConfiguration();

		$this->getStoragePid();

		$this->getPidList();

		$this->makeAdjustments();

		$this->setupNavigations();

		// Enable the edit mode
		$validBackendUser = $this->isValidBackendUser();

		// allow FE-user editing from special groups (set via TS)
		$validFrontendUser = $this->isValidFrontendUser($validBackendUser);

		$this->extConf['edit_mode'] = (($validBackendUser || $validFrontendUser) && $this->extConf['editor']['enabled']);

		$this->setEnumerationMode();

		$this->initializeRestrictions();

		$this->initializeListViewIcons();

		$this->initializeFilters();

		$this->showHiddenEntries();

		$this->getEditMode();

		$this->switchToExportView();

		$this->switchToSingleView();

		$this->callSearchNavigationHook();

		$this->referenceReader->set_filters($this->extConf['filters']);

		$this->callAuthorNavigationHook();

		$this->getYearNavigation();

		$this->determineNumberOfPublications();

		$this->getPageNavigation();

		$this->getSortFilter();

		$this->disableNavigationOnDemand();

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

		// Switch to requested view mode
		return $this->finalize($this->switchToRequestedViewMode());
	}

	/**
	 * Calls the hook_filter in the author navigation instance
	 *
	 * @return void
	 */
	protected function callAuthorNavigationHook() {
		if ($this->extConf['show_nav_author']) {
			$this->extConf['author_navi']['obj']->hook_filter();
		}
	}

	/**
	 * Calls the hook_filter in the search navigation instance
	 *
	 * @return void
	 */
	protected function callSearchNavigationHook() {
		if ($this->extConf['show_nav_search']) {
			$this->extConf['search_navi']['obj']->hook_filter();
		}
	}

	/**
	 * Disable navigations om demand
	 *
	 * @return void
	 */
	protected function disableNavigationOnDemand() {

		if ($this->stat['num_all'] == 0) {
			$this->extConf['show_nav_export'] = FALSE;
		}

		if ($this->stat['num_page'] == 0) {
			$this->extConf['show_nav_stat'] = FALSE;
		}
	}

	/**
	 * Initialize a ReferenceReader instance and pass it to the class variable
	 * @return void
	 */
	protected function initializeReferenceReader() {
		/** @var \Ipf\Bib\Utility\ReferenceReader $referenceReader */
		$referenceReader = GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\ReferenceReader');
		$referenceReader->set_cObj($this->cObj);
		$this->referenceReader = $referenceReader;
	}

	/**
	 * Determines and applies sorting filters to the ReferenceReader
	 *
	 * @return void
	 */
	protected function getSortFilter() {
		$this->extConf['filters']['sort'] = array();
		$this->extConf['filters']['sort']['sorting'] = array();

		// Default sorting
		$defaultSorting = 'DESC';

		if ($this->extConf['date_sorting'] == self::SORT_ASC) {
			$defaultSorting = 'ASC';
		}

		$this->extConf['filters']['sort']['sorting'] = array(
			array('field' => $this->referenceReader->referenceTableAlias . '.year', 'dir' => $defaultSorting),
			array('field' => $this->referenceReader->referenceTableAlias . '.month', 'dir' => $defaultSorting),
			array('field' => $this->referenceReader->referenceTableAlias . '.day', 'dir' => $defaultSorting),
			array('field' => $this->referenceReader->referenceTableAlias . '.bibtype', 'dir' => 'ASC'),
			array('field' => $this->referenceReader->referenceTableAlias . '.state', 'dir' => 'ASC'),
			array('field' => $this->referenceReader->referenceTableAlias . '.sorting', 'dir' => 'ASC'),
			array('field' => $this->referenceReader->referenceTableAlias . '.title', 'dir' => 'ASC')
		);

		// Adjust sorting for bibtype split
		if ($this->extConf['split_bibtypes']) {
			if ($this->extConf['d_mode'] == self::D_SIMPLE) {
				$this->extConf['filters']['sort']['sorting'] = array(
					array('field' => $this->referenceReader->referenceTableAlias . '.bibtype', 'dir' => 'ASC'),
					array('field' => $this->referenceReader->referenceTableAlias . '.year', 'dir' => $defaultSorting),
					array('field' => $this->referenceReader->referenceTableAlias . '.month', 'dir' => $defaultSorting),
					array('field' => $this->referenceReader->referenceTableAlias . '.day', 'dir' => $defaultSorting),
					array('field' => $this->referenceReader->referenceTableAlias . '.state', 'dir' => 'ASC'),
					array('field' => $this->referenceReader->referenceTableAlias . '.sorting', 'dir' => 'ASC'),
					array('field' => $this->referenceReader->referenceTableAlias . '.title', 'dir' => 'ASC')
				);
			} else {
				$this->extConf['filters']['sort']['sorting'] = array(
					array('field' => $this->referenceReader->referenceTableAlias . '.year', 'dir' => $defaultSorting),
					array('field' => $this->referenceReader->referenceTableAlias . '.bibtype', 'dir' => 'ASC'),
					array('field' => $this->referenceReader->referenceTableAlias . '.month', 'dir' => $defaultSorting),
					array('field' => $this->referenceReader->referenceTableAlias . '.day', 'dir' => $defaultSorting),
					array('field' => $this->referenceReader->referenceTableAlias . '.state', 'dir' => 'ASC'),
					array('field' => $this->referenceReader->referenceTableAlias . '.sorting', 'dir' => 'ASC'),
					array('field' => $this->referenceReader->referenceTableAlias . '.title', 'dir' => 'ASC')
				);
			}
		}
		$this->referenceReader->set_filters($this->extConf['filters']);
	}

	/**
	 * Determines the number of publications
	 *
	 * @return void
	 */
	protected function determineNumberOfPublications() {
		if (!is_numeric($this->stat['num_all'])) {
			$this->stat['num_all'] = $this->referenceReader->getNumberOfPublications();
			$this->stat['num_page'] = $this->stat['num_all'];
		}
	}

	/**
	 * Switch to single view on demand
	 *
	 * @return void
	 */
	protected function switchToSingleView() {
		if (is_numeric($this->piVars['show_uid'])) {
			$this->extConf['view_mode'] = self::VIEW_SINGLE;
			$this->extConf['single_view']['uid'] = intval($this->piVars['show_uid']);
			unset ($this->piVars['editor_mode']);
			unset ($this->piVars['dialog_mode']);
		}
	}

	/**
	 * Switch to export mode on demand
	 *
	 * @return void
	 */
	protected function switchToExportView() {
		if (is_string($this->extConf['export_navi']['do'])) {
			$this->extConf['view_mode'] = self::VIEW_DIALOG;
			$this->extConf['dialog_mode'] = self::DIALOG_EXPORT;
		}
	}

	/**
	 * Determines whether hidden entries are displayed or not
	 *
	 * @return void
	 */
	protected function showHiddenEntries() {
		$this->extConf['show_hidden'] = FALSE;
		if ($this->extConf['edit_mode']) {
			$this->extConf['show_hidden'] = TRUE;
		}
		$this->referenceReader->setShowHidden($this->extConf['show_hidden']);
	}

	/**
	 * Set the enumeration mode
	 * @return void
	 */
	protected function setEnumerationMode() {
		$this->extConf['has_enum'] = TRUE;
		if (($this->extConf['enum_style'] == self::ENUM_EMPTY)) {
			$this->extConf['has_enum'] = FALSE;
		}
	}

	/**
	 * Retrieves and optimizes the pid list and passes it to the referenceReader
	 *
	 * @return void
	 */
	protected function getPidList() {
		$pidList = array_unique($this->pidList);
		if (in_array(0, $pidList)) {
			unset ($pidList[array_search(0, $pidList)]);
		}

		if (sizeof($pidList) > 0) {
			// Determine the recursive depth
			$this->extConf['recursive'] = $this->cObj->data['recursive'];
			if (isset ($this->conf['recursive'])) {
				$this->extConf['recursive'] = $this->conf['recursive'];
			}
			$this->extConf['recursive'] = intval($this->extConf['recursive']);

			$pidList = $this->pi_getPidList(implode(',', $pidList), $this->extConf['recursive']);

			$pidList = GeneralUtility::intExplode(',', $pidList);

			// Due to how recursive prepends the folders
			$pidList = array_reverse($pidList);

			$this->extConf['pid_list'] = $pidList;
		} else {
			// Use current page as storage
			$this->extConf['pid_list'] = array(intval($GLOBALS['TSFE']->id));
		}
		$this->referenceReader->setPidList($this->extConf['pid_list']);
	}

	/**
	 * Get the character set and write it to the configuration
	 *
	 * @return void
	 */
	protected function getCharacterSet() {
		$this->extConf['charset'] = array('upper' => 'UTF-8', 'lower' => 'utf-8');
		if (strlen($this->conf['charset']) > 0) {
			$this->extConf['charset']['upper'] = strtoupper($this->conf['charset']);
			$this->extConf['charset']['lower'] = strtolower($this->conf['charset']);
		}
	}

	/**
	 * @return void
	 */
	protected function getTypoScriptConfiguration() {

		if (intval($this->extConf['d_mode']) < 0) {
			$this->extConf['d_mode'] = intval($this->conf['display_mode']);
		}

		if (intval($this->extConf['enum_style']) < 0) {
			$this->extConf['enum_style'] = intval($this->conf['enum_style']);
		}

		if (intval($this->extConf['date_sorting']) < 0) {
			$this->extConf['date_sorting'] = intval($this->conf['date_sorting']);
		}

		if (intval($this->extConf['stat_mode']) < 0) {
			$this->extConf['stat_mode'] = intval($this->conf['statNav.']['mode']);
		}

		if (intval($this->extConf['sub_page']['ipp']) < 0) {
			$this->extConf['sub_page']['ipp'] = intval($this->conf['items_per_page']);
		}

		if (intval($this->extConf['max_authors']) < 0) {
			$this->extConf['max_authors'] = intval($this->conf['max_authors']);
		}
	}

	/**
	 * Determine the requested view mode (List, Single, Editor, Dialog)
	 *
	 * @return string
	 */
	protected function switchToRequestedViewMode() {

		switch ($this->extConf['view_mode']) {
			case self::VIEW_LIST :
				return $this->list_view();
				break;
			case self::VIEW_SINGLE :
				return $this->singleView();
				break;
			case self::VIEW_EDITOR :
				return $this->editorView();
				break;
			case self::VIEW_DIALOG :
				return $this->dialogView();
				break;
			default:
				return $this->errorMessage('An illegal view mode occured');
		}
	}

	/**
	 * Setup and initialize Navigation types
	 *
	 * Search Navigation
	 * Year Navigation
	 * Author Navigation
	 * Preference Navigation
	 * Statistic Navigation
	 * Export Navigation
	 *
	 * @return void
	 */
	protected function setupNavigations() {
		// Search Navigation
		if ($this->extConf['show_nav_search']) {
			$this->initializeSearchNavigation();
		}

		// Year Navigation
		if ($this->extConf['d_mode'] == self::D_Y_NAV) {
			$this->enableYearNavigation();
		}

		// Author Navigation
		if ($this->extConf['show_nav_author']) {
			$this->initializeAuthorNavigation();
		}

		// Preference Navigation
		if ($this->extConf['show_nav_pref']) {
			$this->initializePreferenceNavigation();
		}

		// Statistic Navigation
		if (intval($this->extConf['stat_mode']) != self::STAT_NONE) {
			$this->enableStatisticsNavigation(TRUE);
		}

		// Export navigation
		if ($this->extConf['show_nav_export']) {
			$this->getExportNavigation();
		}
	}

	/**
	 * Make adjustments to different modes
	 * @todo find a better method name or split up
	 *
	 * @return void
	 */
	protected function makeAdjustments() {
		switch ($this->extConf['d_mode']) {
			case self::D_SIMPLE:
			case self::D_Y_SPLIT:
			case self::D_Y_NAV:
				break;
			default:
				$this->extConf['d_mode'] = self::D_SIMPLE;
		}
		switch ($this->extConf['enum_style']) {
			case self::ENUM_PAGE:
			case self::ENUM_ALL:
			case self::ENUM_BULLET:
			case self::ENUM_EMPTY:
			case self::ENUM_FILE_ICON:
				break;
			default:
				$this->extConf['enum_style'] = self::ENUM_ALL;
		}
		switch ($this->extConf['date_sorting']) {
			case self::SORT_DESC:
			case self::SORT_ASC:
				break;
			default:
				$this->extConf['date_sorting'] = self::SORT_DESC;
		}
		switch ($this->extConf['stat_mode']) {
			case self::STAT_NONE:
			case self::STAT_TOTAL:
			case self::STAT_YEAR_TOTAL:
				break;
			default:
				$this->extConf['stat_mode'] = self::STAT_TOTAL;
		}
		$this->extConf['sub_page']['ipp'] = max(intval($this->extConf['sub_page']['ipp']), 0);
		$this->extConf['max_authors'] = max(intval($this->extConf['max_authors']), 0);
	}

	/**
	 * This is the last function called before ouptput
	 *
	 * @param string $pluginContent
	 * @return string The input string with some extra data
	 */
	protected function finalize($pluginContent) {
		if ($this->extConf['debug']) {
			$pluginContent .= GeneralUtility::view_array(
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
	 * Insert extension specific JavaScript
	 *
	 * @return void
	 */
	protected function includeJavaScript() {

		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('t3jquery') && T3JQUERY === TRUE) {
			// add t3query generated JavaScript
			tx_t3jquery::addJqJS();

			/** @var \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer */
			$pageRenderer = $GLOBALS['TSFE']->getPageRenderer();
			$pageRenderer->addJsFooterFile('typo3conf/ext/' . $this->extKey . '/Resources/Public/JavaScript/bib.js');
		} else {
			throw new \Exception('The TYPO3 extension t3jquery has to be installed and configured', 1378366263);
		}
	}

	/**
	 * @return void
	 */
	protected function enableYearNavigation() {
		$this->extConf['show_nav_year'] = TRUE;
	}

	/**
	 * @return void
	 */
	protected function enableStatisticsNavigation() {
		$this->extConf['show_nav_stat'] = TRUE;
	}

	/**
	 * @return void
	 */
	protected function initializePreferenceNavigation() {
		$this->extConf['pref_navi'] = array();
		$this->extConf['pref_navi']['obj'] =& $this->getAndInitializeNavigationInstance('PreferenceNavigation');
		$this->extConf['pref_navi']['obj']->hook_init();
	}

	/**
	 * @return void
	 */
	protected function initializeAuthorNavigation() {
		$this->extConf['dynamic'] = TRUE;
		$this->extConf['author_navi'] = array();
		$this->extConf['author_navi']['obj'] =& $this->getAndInitializeNavigationInstance('AuthorNavigation');
		$this->extConf['author_navi']['obj']->hook_init();
	}

	/**
	 * @return void
	 */
	protected function getEditMode() {
		if ($this->extConf['edit_mode']) {

			// Disable caching in edit mode
			$GLOBALS['TSFE']->set_no_cache();

			// Load edit labels
			$this->extend_ll('EXT:' . $this->extKey . '/Resources/Private/Language/locallang_editor.xml');

			// Do an action type evaluation
			if (is_array($this->piVars['action'])) {
				$actionName = implode('', array_keys($this->piVars['action']));

				switch ($actionName) {
					case 'new':
						$this->extConf['view_mode'] = self::VIEW_EDITOR;
						$this->extConf['editor_mode'] = self::EDIT_NEW;
						break;
					case 'edit':
						$this->extConf['view_mode'] = self::VIEW_EDITOR;
						$this->extConf['editor_mode'] = self::EDIT_EDIT;
						break;
					case 'confirm_save':
						$this->extConf['view_mode'] = self::VIEW_EDITOR;
						$this->extConf['editor_mode'] = self::EDIT_CONFIRM_SAVE;
						break;
					case 'save':
						$this->extConf['view_mode'] = self::VIEW_DIALOG;
						$this->extConf['dialog_mode'] = self::DIALOG_SAVE_CONFIRMED;
						break;
					case 'confirm_delete':
						$this->extConf['view_mode'] = self::VIEW_EDITOR;
						$this->extConf['editor_mode'] = self::EDIT_CONFIRM_DELETE;
						break;
					case 'delete':
						$this->extConf['view_mode'] = self::VIEW_DIALOG;
						$this->extConf['dialog_mode'] = self::DIALOG_DELETE_CONFIRMED;
						break;
					case 'confirm_erase':
						$this->extConf['view_mode'] = self::VIEW_EDITOR;
						$this->extConf['editor_mode'] = self::EDIT_CONFIRM_ERASE;
						break;
					case 'erase':
						$this->extConf['view_mode'] = self::VIEW_DIALOG;
						$this->extConf['dialog_mode'] = self::DIALOG_ERASE_CONFIRMED;
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
			if ($this->extConf['view_mode'] == self::VIEW_DIALOG) {
				unset ($this->piVars['editor_mode']);
			}

			if (isset ($this->extConf['editor_mode'])) {
				$this->piVars['editor_mode'] = $this->extConf['editor_mode'];
			} else if (isset ($this->piVars['editor_mode'])) {
				$this->extConf['view_mode'] = self::VIEW_EDITOR;
				$this->extConf['editor_mode'] = $this->piVars['editor_mode'];
			}

			// Initialize edit icons
			$this->initializeEditIcons();

			// Switch to an import view on demand
			$allImport = intval(self::IMP_BIBTEX | self::IMP_XML);
			if (isset($this->piVars['import']) && (intval($this->piVars['import']) & $allImport)) {
				$this->extConf['view_mode'] = self::VIEW_DIALOG;
				$this->extConf['dialog_mode'] = self::DIALOG_IMPORT;
			}
		}
	}

	/**
	 * @return void
	 */
	protected function getYearNavigation() {
		if ($this->extConf['show_nav_year']) {

			// Fetch a year histogram
			$histogram = $this->referenceReader->getHistogram('year');
			$this->stat['year_hist'] = $histogram;
			$this->stat['years'] = array_keys($histogram);
			sort($this->stat['years']);

			$this->stat['num_all'] = array_sum($histogram);
			$this->stat['num_page'] = $this->stat['num_all'];

			// Determine the year to display
			$this->extConf['year'] = intval(date('Y')); // System year

			$exportPluginVariables = strtolower($this->piVars['year']);
			if (is_numeric($exportPluginVariables)) {
				$this->extConf['year'] = intval($exportPluginVariables);
			} else {
				if ($exportPluginVariables == 'all') {
					$this->extConf['year'] = $exportPluginVariables;
				}
			}

			if ($this->extConf['year'] == 'all') {
				if ($this->conf['yearNav.']['selection.']['all_year_split']) {
					$this->extConf['split_years'] = TRUE;
				}
			}


			// The selected year has no publications so select the closest year
			if (($this->stat['num_all'] > 0) && is_numeric($this->extConf['year'])) {
				$this->extConf['year'] = \Ipf\Bib\Utility\Utility::find_nearest_int(
					$this->extConf['year'], $this->stat['years']);
			}
			// Append default link variable
			$this->extConf['link_vars']['year'] = $this->extConf['year'];

			if (is_numeric($this->extConf['year'])) {
				// Adjust num_page
				$this->stat['num_page'] = $this->stat['year_hist'][$this->extConf['year']];

				// Adjust year filter
				$this->extConf['filters']['br_year'] = array();
				$this->extConf['filters']['br_year']['year'] = array();
				$this->extConf['filters']['br_year']['year']['years'] = array($this->extConf['year']);
			}
		}
	}

	/**
	 * @return void
	 */
	protected function initializeSearchNavigation() {
		$this->extConf['dynamic'] = TRUE;
		$this->extConf['search_navi'] = array();
		$this->extConf['search_navi']['obj'] =& $this->getAndInitializeNavigationInstance('SearchNavigation');
		$this->extConf['search_navi']['obj']->hook_init();
	}

	/**
	 * @return void
	 */
	protected function getPageNavigation() {
		$this->extConf['sub_page']['max'] = 0;
		$this->extConf['sub_page']['current'] = 0;

		if ($this->extConf['sub_page']['ipp'] > 0) {
			$this->extConf['sub_page']['max'] = floor(($this->stat['num_page'] - 1) / $this->extConf['sub_page']['ipp']);
			$this->extConf['sub_page']['current'] = \Ipf\Bib\Utility\Utility::crop_to_range(
				$this->piVars['page'],
				0,
				$this->extConf['sub_page']['max']
			);
		}

		if ($this->extConf['sub_page']['max'] > 0) {
			$this->extConf['show_nav_page'] = TRUE;

			$this->extConf['filters']['br_page'] = array();

			// Adjust the browse filter limit
			$this->extConf['filters']['br_page']['limit'] = array();
			$this->extConf['filters']['br_page']['limit']['start'] = $this->extConf['sub_page']['current'] * $this->extConf['sub_page']['ipp'];
			$this->extConf['filters']['br_page']['limit']['num'] = $this->extConf['sub_page']['ipp'];
		}
	}

	/**
	 * Retrieve and optimize Extension configuration
	 *
	 * @return void
	 */
	protected function getExtensionConfiguration() {
		$this->extConf = array();
		// Initialize current configuration
		$this->extConf['link_vars'] = array();
		$this->extConf['sub_page'] = array();

		$this->extConf['view_mode'] = self::VIEW_LIST;
		$this->extConf['debug'] = $this->conf['debug'] ? TRUE : FALSE;
		$this->extConf['ce_links'] = $this->conf['ce_links'] ? TRUE : FALSE;

		// Retrieve general FlexForm values
		$fSheet = 'sDEF';
		$this->extConf['d_mode'] = $this->pi_getFFvalue($this->flexFormData, 'display_mode', $fSheet);
		$this->extConf['enum_style'] = $this->pi_getFFvalue($this->flexFormData, 'enum_style', $fSheet);
		$this->extConf['show_nav_search'] = $this->pi_getFFvalue($this->flexFormData, 'show_search', $fSheet);
		$this->extConf['show_nav_author'] = $this->pi_getFFvalue($this->flexFormData, 'show_authors', $fSheet);
		$this->extConf['show_nav_pref'] = $this->pi_getFFvalue($this->flexFormData, 'show_pref', $fSheet);
		$this->extConf['sub_page']['ipp'] = $this->pi_getFFvalue($this->flexFormData, 'items_per_page', $fSheet);
		$this->extConf['max_authors'] = $this->pi_getFFvalue($this->flexFormData, 'max_authors', $fSheet);
		$this->extConf['split_bibtypes'] = $this->pi_getFFvalue($this->flexFormData, 'split_bibtypes', $fSheet);
		$this->extConf['stat_mode'] = $this->pi_getFFvalue($this->flexFormData, 'stat_mode', $fSheet);
		$this->extConf['show_nav_export'] = $this->pi_getFFvalue($this->flexFormData, 'export_mode', $fSheet);
		$this->extConf['date_sorting'] = $this->pi_getFFvalue($this->flexFormData, 'date_sorting', $fSheet);

		$show_fields = $this->pi_getFFvalue($this->flexFormData, 'show_textfields', $fSheet);
		$show_fields = explode(',', $show_fields);

		$this->extConf['hide_fields'] = array(
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
				$this->extConf['hide_fields'][$field] = 0;
			}
		}
	}

	/**
	 * Get configuration from FlexForms
	 *
	 * @return void
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
				$this->extConf['editor']['enabled'] = $editorOverride['enabled'] ? TRUE : FALSE;
			if (array_key_exists('citeid_gen_new', $editorOverride))
				$this->extConf['editor']['citeid_gen_new'] = $editorOverride['citeid_gen_new'] ? TRUE : FALSE;
			if (array_key_exists('citeid_gen_old', $editorOverride))
				$this->extConf['editor']['citeid_gen_old'] = $editorOverride['citeid_gen_old'] ? TRUE : FALSE;
		}
		$this->referenceReader->clear_cache = $this->extConf['editor']['clear_page_cache'];
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
	 *
	 * @return void
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

	/**
	 * @param bool $validBackendUser
	 * @return bool
	 */
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
	public function errorMessage($errorString) {
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
	protected function initializeRestrictions() {
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
	}


	/**
	 * This initializes all filters before the browsing filter
	 *
	 * @return void
	 */
	protected function initializeFilters() {
		$this->extConf['filters'] = array();
		$this->initializeFlexformFilter();
		$this->initializeSelectionFilter();
	}

	/**
	 * @return void
	 */
	protected function initializeYearFilter() {
		if ($this->pi_getFFvalue($this->flexForm, 'enable_year', $this->flexFormFilterSheet) > 0) {
			$flexFormFilter = array();
			$flexFormFilter['years'] = array();
			$flexFormFilter['ranges'] = array();
			$ffStr = $this->pi_getFFvalue($this->flexForm, 'years', $this->flexFormFilterSheet);
			$arr = \Ipf\Bib\Utility\Utility::multi_explode_trim(
				array(',', "\r", "\n"),
				$ffStr,
				TRUE
			);

			foreach ($arr as $year) {
				if (strpos($year, '-') === FALSE) {
					if (is_numeric($year)) {
						$flexFormFilter['years'][] = intval($year);
					}
				} else {
					$range = array();
					$elms = \Ipf\Bib\Utility\Utility::explode_trim('-', $year, FALSE);
					if (is_numeric($elms[0])) {
						$range['from'] = intval($elms[0]);
					}
					if (is_numeric($elms[1])) {
						$range['to'] = intval($elms[1]);
					}
					if (sizeof($range) > 0) {
						$flexFormFilter['ranges'][] = $range;
					}
				}
			}
			if ((sizeof($flexFormFilter['years']) + sizeof($flexFormFilter['ranges'])) > 0) {
				$this->extConf['filters']['flexform']['year'] = $flexFormFilter;
			}
		}
	}

	/**
	 * @return void
	 */
	protected function initializeAuthorFilter() {
		$this->extConf['highlight_authors'] = $this->pi_getFFvalue($this->flexForm, 'highlight_authors', $this->flexFormFilterSheet);

		if ($this->pi_getFFvalue($this->flexForm, 'enable_author', $this->flexFormFilterSheet) != 0) {
			$flexFormFilter = array();;
			$flexFormFilter['authors'] = array();
			$flexFormFilter['rule'] = $this->pi_getFFvalue($this->flexForm, 'author_rule', $this->flexFormFilterSheet);
			$flexFormFilter['rule'] = intval($flexFormFilter['rule']);

			$authors = $this->pi_getFFvalue($this->flexForm, 'authors', $this->flexFormFilterSheet);
			$authors = \Ipf\Bib\Utility\Utility::multi_explode_trim(
				array("\r", "\n"),
				$authors,
				TRUE
			);

			foreach ($authors as $a) {
				$parts = GeneralUtility::trimExplode(',', $a);
				$author = array();
				if (strlen($parts[0]) > 0) {
					$author['surname'] = $parts[0];
				}
				if (strlen($parts[1]) > 0) {
					$author['forename'] = $parts[1];
				}
				if (sizeof($author) > 0) {
					$flexFormFilter['authors'][] = $author;
				}
			}
			if (sizeof($flexFormFilter['authors']) > 0) {
				$this->extConf['filters']['flexform']['author'] = $flexFormFilter;
			}
		}
	}

	/**
	 * @return void
	 */
	protected function initializeStateFilter() {
		if ($this->pi_getFFvalue($this->flexForm, 'enable_state', $this->flexFormFilterSheet) != 0) {
			$flexFormFilter = array();
			$flexFormFilter['states'] = array();
			$states = intval($this->pi_getFFvalue($this->flexForm, 'states', $this->flexFormFilterSheet));

			$j = 1;
			for ($i = 0; $i < sizeof($this->referenceReader->allStates); $i++) {
				if ($states & $j) {
					$flexFormFilter['states'][] = $i;
				}
				$j = $j * 2;
			}
			if (sizeof($flexFormFilter['states']) > 0) {
				$this->extConf['filters']['flexform']['state'] = $flexFormFilter;
			}
		}
	}

	/**
	 * @return void
	 */
	protected function initializeBibliographyTypeFilter() {
		if ($this->pi_getFFvalue($this->flexForm, 'enable_bibtype', $this->flexFormFilterSheet) != 0) {
			$flexFormFilter = array();
			$flexFormFilter['types'] = array();
			$types = $this->pi_getFFvalue($this->flexForm, 'bibtypes', $this->flexFormFilterSheet);
			$types = explode(',', $types);
			foreach ($types as $type) {
				$type = intval($type);
				if (($type >= 0) && ($type < sizeof($this->referenceReader->allBibTypes))) {
					$flexFormFilter['types'][] = $type;
				}
			}
			if (sizeof($flexFormFilter['types']) > 0) {
				$this->extConf['filters']['flexform']['bibtype'] = $flexFormFilter;
			}
		}
	}

	/**
	 * @return void
	 */
	protected function initializeOriginFilter() {
		if ($this->pi_getFFvalue($this->flexForm, 'enable_origin', $this->flexFormFilterSheet) != 0) {
			$flexFormFilter = array();
			$flexFormFilter['origin'] = $this->pi_getFFvalue($this->flexForm, 'origins', $this->flexFormFilterSheet);

			if ($flexFormFilter['origin'] == 1) {
				// Legacy value
				$flexFormFilter['origin'] = 0;
			} else if ($flexFormFilter['origin'] == 2) {
				// Legacy value
				$flexFormFilter['origin'] = 1;
			}

			$this->extConf['filters']['flexform']['origin'] = $flexFormFilter;
		}
	}

	/**
	 * @return void
	 */
	protected function initializePidFilter() {
		$this->extConf['filters']['flexform']['pid'] = $this->extConf['pid_list'];
	}

	/**
	 * @return void
	 */
	protected function initializeReviewFilter() {
		if ($this->pi_getFFvalue($this->flexForm, 'enable_reviewes', $this->flexFormFilterSheet) != 0) {
			$flexFormFilter = array();
			$flexFormFilter['value'] = $this->pi_getFFvalue($this->flexForm, 'reviewes', $this->flexFormFilterSheet);
			$this->extConf['filters']['flexform']['reviewed'] = $flexFormFilter;
		}
	}

	/**
	 * @return void
	 */
	protected function initializeInLibraryFilter() {
		if ($this->pi_getFFvalue($this->flexForm, 'enable_in_library', $this->flexFormFilterSheet) != 0) {
			$flexFormFilter = array();
			$flexFormFilter['value'] = $this->pi_getFFvalue($this->flexForm, 'in_library', $this->flexFormFilterSheet);
			$this->extConf['filters']['flexform']['in_library'] = $flexFormFilter;
		}
	}

	/**
	 * @return void
	 */
	protected function initializeBorrowedFilter() {
		if ($this->pi_getFFvalue($this->flexForm, 'enable_borrowed', $this->flexFormFilterSheet) != 0) {
			$flexFormFilter = array();
			$flexFormFilter['value'] = $this->pi_getFFvalue($this->flexForm, 'borrowed', $this->flexFormFilterSheet);
			$this->extConf['filters']['flexform']['borrowed'] = $flexFormFilter;
		}
	}

	/**
	 * @return void
	 */
	protected function initializeCiteIdFilter() {
		if ($this->pi_getFFvalue($this->flexForm, 'enable_citeid', $this->flexFormFilterSheet) != 0) {
			$flexFormFilter = array();
			$ids = $this->pi_getFFvalue($this->flexForm, 'citeids', $this->flexFormFilterSheet);
			if (strlen($ids) > 0) {
				$ids = \Ipf\Bib\Utility\Utility::multi_explode_trim(
					array(
						',',
						"\r",
						"\n"
					),
					$ids,
					TRUE
				);
				$flexFormFilter['ids'] = array_unique($ids);
				$this->extConf['filters']['flexform']['citeid'] = $flexFormFilter;
			}
		}
	}

	/**
	 * @return void
	 */
	protected function initializeTagFilter() {
		if ($this->pi_getFFvalue($this->flexForm, 'enable_tags', $this->flexFormFilterSheet)) {
			$flexFormFilter = array();
			$flexFormFilter['rule'] = $this->pi_getFFvalue($this->flexForm, 'tags_rule', $this->flexFormFilterSheet);
			$flexFormFilter['rule'] = intval($flexFormFilter['rule']);
			$kw = $this->pi_getFFvalue($this->flexForm, 'tags', $this->flexFormFilterSheet);
			if (strlen($kw) > 0) {
				$words = \Ipf\Bib\Utility\Utility::multi_explode_trim(
					array(
						',',
						"\r",
						"\n"
					),
					$kw,
					TRUE
				);
				foreach ($words as &$word) {
					$word = $this->referenceReader->getSearchTerm($word, $this->extConf['charset']['upper']);
				}
				$flexFormFilter['words'] = $words;
				$this->extConf['filters']['flexform']['tags'] = $flexFormFilter;
			}
		}
	}

	/**
	 * @return void
	 */
	protected function initializeKeywordsFilter() {
		if ($this->pi_getFFvalue($this->flexForm, 'enable_keywords', $this->flexFormFilterSheet)) {
			$flexFormFilter = array();
			$flexFormFilter['rule'] = $this->pi_getFFvalue($this->flexForm, 'keywords_rule', $this->flexFormFilterSheet);
			$flexFormFilter['rule'] = intval($flexFormFilter['rule']);
			$kw = $this->pi_getFFvalue($this->flexForm, 'keywords', $this->flexFormFilterSheet);
			if (strlen($kw) > 0) {
				$words = \Ipf\Bib\Utility\Utility::multi_explode_trim(array(',', "\r", "\n"), $kw, TRUE);
				foreach ($words as &$word) {
					$word = $this->referenceReader->getSearchTerm($word, $this->extConf['charset']['upper']);
				}
				$flexFormFilter['words'] = $words;
				$this->extConf['filters']['flexform']['keywords'] = $flexFormFilter;
			}
		}
	}

	/**
	 * @return void
	 */
	protected function initializeGeneralKeywordSearch() {
		if ($this->pi_getFFvalue($this->flexForm, 'enable_search_all', $this->flexFormFilterSheet)) {
			$flexFormFilter = array();
			$flexFormFilter['rule'] = $this->pi_getFFvalue($this->flexForm, 'search_all_rule', $this->flexFormFilterSheet);
			$flexFormFilter['rule'] = intval($flexFormFilter['rule']);
			$kw = $this->pi_getFFvalue($this->flexForm, 'search_all', $this->flexFormFilterSheet);
			if (strlen($kw) > 0) {
				$words = \Ipf\Bib\Utility\Utility::multi_explode_trim(array(',', "\r", "\n"), $kw, TRUE);
				foreach ($words as &$word) {
					$word = $this->referenceReader->getSearchTerm($word, $this->extConf['charset']['upper']);
				}
				$flexFormFilter['words'] = $words;
				$this->extConf['filters']['flexform']['all'] = $flexFormFilter;
			}
		}
	}

	/**
	 * This initializes filter array from the flexform
	 *
	 * @return void
	 */
	protected function initializeFlexformFilter() {
		// Create and select the flexform filter
		$this->extConf['filters']['flexform'] = array();

		// Filtersheet and flexform data into variable
		$this->flexForm = $this->cObj->data['pi_flexform'];
		$this->flexFormFilterSheet = 's_filter';

		$this->initializePidFilter();

		$this->initializeYearFilter();

		$this->initializeAuthorFilter();

		$this->initializeStateFilter();

		$this->initializeBibliographyTypeFilter();

		$this->initializeOriginFilter();

		$this->initializeReviewFilter();

		$this->initializeInLibraryFilter();

		$this->initializeBorrowedFilter();

		$this->initializeCiteIdFilter();

		$this->initializeTagFilter();

		$this->initializeKeywordsFilter();

		$this->initializeGeneralKeywordSearch();

	}


	/**
	 * This initializes the selction filter array from the piVars
	 *
	 * @return string|bool FALSE or an error message
	 */
	protected function initializeSelectionFilter() {
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
	 * @return bool TRUE on error, FALSE otherwise
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
	protected function initializeEditIcons() {
		$list = array();
		$more = $this->conf['edit_icons.'];
		if (is_array($more)) {
			$list = array_merge($list, $more);
		}

		foreach ($list as $key => $val) {
			$this->icon_src[$key] = $GLOBALS['TSFE']->tmpl->getFileName($base . $val);
		}
	}


	/**
	 * Initialize the list view icons
	 *
	 * @return void
	 */
	protected function initializeListViewIcons() {
		$list = array(
			'default' => 'EXT:cms/tslib/media/fileicons/default.gif');
		$more = $this->conf['file_icons.'];
		if (is_array($more)) {
			$list = array_merge($list, $more);
		}

		$this->icon_src['files'] = array();

		foreach ($list as $key => $val) {
			$this->icon_src['files']['.' . $key] = $GLOBALS['TSFE']->tmpl->getFileName($val);
		}
	}


	/**
	 * Extend the $this->LOCAL_LANG label with another language set
	 *
	 * @param string $file
	 * @return void
	 */
	public function extend_ll($file) {
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
	 * @param string $key
	 * @param string $alt
	 * @param bool $hsc
	 * @return string The string in the local language
	 */
	public function get_ll($key, $alt = '', $hsc = FALSE) {
		return $this->pi_getLL($key, $alt, $hsc);
	}


	/**
	 * Composes a link of an url an some attributes
	 *
	 * @param string $url
	 * @param string $content
	 * @param array $attributes
	 * @return string The link (HTML <a> element)
	 */
	protected function composeLink($url, $content, $attributes = NULL) {
		$linkString = '<a href="' . $url . '"';
		if (is_array($attributes)) {
			foreach ($attributes as $k => $v) {
				$linkString .= ' ' . $k . '="' . $v . '"';
			}
		}
		$linkString .= '>' . $content . '</a>';
		return $linkString;
	}


	/**
	 * Wraps the content into a link to the current page with
	 * extra link arguments given in the array $linkVariables
	 *
	 * @param string $content
	 * @param array $linkVariables
	 * @param bool $autoCache
	 * @param array $attributes
	 * @return string The link to the current page
	 */
	public function get_link($content, $linkVariables = array(), $autoCache = TRUE, $attributes = NULL) {
		$url = $this->get_link_url($linkVariables, $autoCache);
		return $this->composeLink($url, $content, $attributes);
	}


	/**
	 * Same as get_link but returns just the URL
	 *
	 * @param array $linkVariables
	 * @param bool $autoCache
	 * @param bool $currentRecord
	 * @return string The url
	 */
	public function get_link_url($linkVariables = array(), $autoCache = TRUE, $currentRecord = TRUE) {
		if ($this->extConf['edit_mode']) $autoCache = FALSE;

		$linkVariables = array_merge($this->extConf['link_vars'], $linkVariables);
		$linkVariables = array($this->prefix_pi1 => $linkVariables);

		$record = '';
		if ($this->extConf['ce_links'] && $currentRecord) {
			$record = "#c" . strval($this->cObj->data['uid']);
		}

		$this->pi_linkTP('x', $linkVariables, $autoCache);
		$url = $this->cObj->lastTypoLinkUrl . $record;

		$url = preg_replace('/&([^;]{8})/', '&amp;\\1', $url);
		return $url;
	}

	/**
	 * Same as get_link_url() but for edit mode urls
	 *
	 * @param array $linkVariables
	 * @param bool $autoCache
	 * @param bool $currentRecord
	 * @return string The url
	 */
	public function get_edit_link_url($linkVariables = array(), $autoCache = TRUE, $currentRecord = TRUE) {
		$parametersToBeKept = array('uid', 'editor_mode', 'editor');
		foreach ($parametersToBeKept as $parameter) {
			if (is_string($this->piVars[$parameter]) || is_array($this->piVars[$parameter]) || is_numeric($this->piVars[$parameter])) {
				$linkVariables[$parameter] = $this->piVars[$parameter];
			}
		}
		return $this->get_link_url($linkVariables, $autoCache, $currentRecord);
	}


	/**
	 * Returns an instance of a navigation bar class
	 *
	 * @param string $type
	 * @return \Ipf\Bib\Navigation\Navigation Instance of the navigation object
	 */
	protected function getAndInitializeNavigationInstance($type) {
		$navigationInstance = GeneralUtility::makeInstance('Ipf\\Bib\\Navigation\\' . $type);
		$navigationInstance->initialize($this);
		return $navigationInstance;
	}


	/**
	 * This function prepares database content fot HTML output
	 *
	 * @param string $content
	 * @param bool $htmlSpecialChars
	 * @return string The string filtered for html output
	 */
	public function filter_pub_html($content, $htmlSpecialChars = FALSE) {
		$charset = $this->extConf['charset']['upper'];
		if ($htmlSpecialChars) {
			$content = htmlspecialchars($content, ENT_QUOTES, $charset);
		}
		return $content;
	}


	/**
	 * This replaces unneccessary tags and prepares the argument string
	 * for html output
	 *
	 * @param string $content
	 * @param bool $htmlSpecialChars
	 * @return string The string filtered for html output
	 */
	protected function filter_pub_html_display($content, $htmlSpecialChars = FALSE) {
		$rand = strval(rand()) . strval(rand());
		$content = str_replace(array('<prt>', '</prt>'), '', $content);

		$LE = '#LE' . $rand . 'LE#';
		$GE = '#GE' . $rand . 'GE#';

		foreach ($this->referenceReader->allowedTags as $tag) {
			$content = str_replace('<' . $tag . '>', $LE . $tag . $GE, $content);
			$content = str_replace('</' . $tag . '>', $LE . '/' . $tag . $GE, $content);
		}

		$content = str_replace('<', '&lt;', $content);
		$content = str_replace('>', '&gt;', $content);

		$content = str_replace($LE, '<', $content);
		$content = str_replace($GE, '>', $content);

		$content = str_replace(array('<prt>', '</prt>'), '', $content);

		// End of remove not allowed tags

		// Handle illegal ampersands
		if (!(strpos($content, '&') === FALSE)) {
			$content = \Ipf\Bib\Utility\Utility::fix_html_ampersand($content);
		}

		$content = $this->filter_pub_html($content, $htmlSpecialChars);
		return $content;
	}


	/**
	 * This function composes the html-view of a set of publications
	 *
	 * @return string The list view
	 */
	protected function list_view() {
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
		$trans = array();
		$hasStr = '';

		if ($this->extConf['show_nav_search']) {
			$trans = $this->extConf['search_navi']['obj']->translator();
			$hasStr = array('', '');

			if (strlen($trans['###SEARCH_NAVI_TOP###']) > 0)
				$this->extConf['has_top_navi'] = TRUE;
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
		$trans = array();
		$hasStr = '';

		if ($this->extConf['show_nav_year']) {
			$obj = $this->getAndInitializeNavigationInstance('YearNavigation');

			$trans = $obj->translator();
			$hasStr = array('', '');

			if (strlen($trans['###YEAR_NAVI_TOP###']) > 0)
				$this->extConf['has_top_navi'] = TRUE;
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
		$trans = array();
		$hasStr = '';

		if ($this->extConf['show_nav_author']) {
			$trans = $this->extConf['author_navi']['obj']->translator();
			$hasStr = array('', '');

			if (strlen($trans['###AUTHOR_NAVI_TOP###']) > 0)
				$this->extConf['has_top_navi'] = TRUE;
		}

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_AUTHOR_NAVI###', $hasStr);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $trans);
	}


	/**
	 * Sets up the page navigation bar
	 *
	 * @return void
	 */
	protected function setupPageNavigation() {
		$trans = array();
		$hasStr = '';

		if ($this->extConf['show_nav_page']) {
			$obj = $this->getAndInitializeNavigationInstance('PageNavigation');

			$trans = $obj->translator();
			$hasStr = array('', '');

			if (strlen($trans['###PAGE_NAVI_TOP###']) > 0)
				$this->extConf['has_top_navi'] = TRUE;
		}

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_PAGE_NAVI###', $hasStr);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $trans);
	}


	/**
	 * Sets up the preferences navigation bar
	 *
	 * @return void
	 */
	protected function setupPreferenceNavigation() {
		$trans = array();
		$hasStr = '';

		if ($this->extConf['show_nav_pref']) {
			$trans = $this->extConf['pref_navi']['obj']->translator();
			$hasStr = array('', '');

			if (strlen($trans['###PREF_NAVI_TOP###']) > 0)
				$this->extConf['has_top_navi'] = TRUE;
		}

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_PREF_NAVI###', $hasStr);
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
			$hasStr = array('', '');
			$this->extConf['has_top_navi'] = TRUE;
		}

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_NEW_ENTRY###', $hasStr);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarker($this->template['LIST_VIEW'], '###NEW_ENTRY###', $linkStr);
	}


	/**
	 * Setup the statistic element
	 *
	 * @return void
	 */
	protected function setupStatisticsNavigation() {
		$trans = array();
		$hasStr = '';

		if ($this->extConf['show_nav_stat']) {
			$obj = $this->getAndInitializeNavigationInstance('StatisticsNavigation');

			$trans = $obj->translator();
			$hasStr = array('', '');

			if (strlen($trans['###STAT_NAVI_TOP###']) > 0) {
				$this->extConf['has_top_navi'] = TRUE;
			}
		}

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_STAT_NAVI###', $hasStr);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $trans);
	}

	/**
	 * Setup the export-link element
	 *
	 * @return void
	 */
	protected function setupExportNavigation() {
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

			$exportModes = array('bibtex', 'xml');

			foreach ($exportModes as $mode) {
				if (in_array($mode, $extConf['modes'])) {
					$title = $this->get_ll('export_' . $mode . 'LinkTitle', $mode, TRUE);
					$txt = $this->get_ll('export_' . $mode);
					$link = $this->get_link(
						$txt,
						array('export' => $mode),
						FALSE,
						array('title' => $title)
					);
					$link = $this->cObj->stdWrap($link, $cfg[$mode . '.']);
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

		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_IMPORT###', $hasStr);
		$this->template['LIST_VIEW'] = $this->cObj->substituteMarker($this->template['LIST_VIEW'], '###IMPORT###', $str);
	}


	/**
	 * Setup the top navigation block
	 *
	 * @return void
	 */
	protected function setupTopNavigation() {
		$hasStr = '';
		if ($this->extConf['has_top_navi']) {
			$hasStr = array('', '');
		}
		$this->template['LIST_VIEW'] = $this->cObj->substituteSubpart($this->template['LIST_VIEW'], '###HAS_TOP_NAVI###', $hasStr);
	}


	/**
	 * Prepares database publication data for displaying
	 *
	 * @param array $pub
	 * @param array $warnings
	 * @param bool $showHidden
	 * @return array The processed publication data array
	 */
	public function prepare_pub_display($pub, &$warnings = array(), $showHidden = FALSE) {

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

		// Copy field values
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
				}
			}
		}

		// Format the author string
		$pdata['authors'] = $this->getItemAuthorsHtml($pdata['authors']);

		// Format the author string
		$pdata['authors'] = $this->getItemAuthorsHtml($pdata['authors']);

		// store editor's data before processing it
		$cleanEditors = $pdata['editor'];

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

			// reset processed data @todo check if the above block may be removed
			$pdata['editor'] = $cleanEditors;

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

		$warnings = $d_err;;

		return $pdata;
	}


	/**
	 * Prepares the cObj->data array for a reference
	 *
	 * @param array $pdata
	 * @return array The procesed publication data array
	 */
	public function prepare_pub_cObj_data($pdata) {
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
	 * @param array $publicationData
	 * @param string $template
	 * @return string HTML string for a single item in the list view
	 */
	protected function getItemHtml($publicationData, $template) {

		$translator = array();

		$bib_str = $publicationData['bibtype_short'];
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

		foreach ($fields as $field) {
			$upStr = strtoupper($field);
			$tkey = '###' . $upStr . '###';
			$hasStr = '';
			$translator[$tkey] = '';

			$val = strval($publicationData[$field]);

			if (strlen($val) > 0) {
				// Wrap default or by bibtype
				$stdWrap = array();
				$stdWrap = $this->conf['field.'][$field . '.'];

				if (is_array($this->conf['field.'][$bib_str . '.'][$field . '.'])) {
					$stdWrap = $this->conf['field.'][$bib_str . '.'][$field . '.'];
				}

				if (isset ($stdWrap['single_view_link'])) {
					$val = $this->get_link($val, array('show_uid' => strval($publicationData['uid'])));
				}
				$val = $this->cObj->stdWrap($val, $stdWrap);

				if (strlen($val) > 0) {
					$hasStr = array('', '');
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
		$template = $this->cObj->substituteMarkerArrayCached($template, $this->labelTranslator);

		// Wrap elements with an anchor
		$url_wrap = array('', '');
		if (strlen($publicationData['file_url']) > 0) {
			$url_wrap = $this->cObj->typolinkWrap(array('parameter' => $publicationData['auto_url']));
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


	/**
	 * Returns the authors string for a publication
	 *
	 * @param array $authors
	 * @return void
	 */
	protected function getItemAuthorsHtml(& $authors) {

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
			$author = $authors[$i_a];

			// Init cObj data
			$cObj->data = $author;
			$cObj->data['url'] = htmlspecialchars_decode($author['url'], ENT_QUOTES);

			// The forename
			$authorForename = trim($author['forename']);
			if (strlen($authorForename) > 0) {
				$authorForename = $this->filter_pub_html_display($authorForename);
				$authorForename = $this->cObj->stdWrap($authorForename, $this->conf['authors.']['forename.']);
			}

			// The surname
			$authorSurname = trim($author['surname']);
			if (strlen($authorSurname) > 0) {
				$authorSurname = $this->filter_pub_html_display($authorSurname);
				$authorSurname = $this->cObj->stdWrap($authorSurname, $this->conf['authors.']['surname.']);
			}

			// The link icon
			$cr_link = FALSE;
			$authorIcon = '';
			foreach ($this->extConf['author_lfields'] as $field) {
				$val = trim(strval($author[$field]));
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

			// Wrap the filtered authors with a highlighting class on demand
			if ($highlightAuthors) {
				foreach ($filter_authors as $fa) {
					if ($author['surname'] == $fa['surname']) {
						if (!$fa['forename'] || ($author['forename'] == $fa['forename'])) {
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
		$this->referenceReader->initializeReferenceFetching();

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
		while ($pub = $this->referenceReader->getReference()) {
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
					$manip_all[] = $this->getEditManipulator($pub);
					$manip_all[] = $this->getHideManipulator($pub);
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
			$items[] = $this->getItemHtml($pdata, $listViewTemplate);

			// Update counters
			$i_page += $i_page_delta;
			$i_subpage++;
			$i_bibtype++;

			$prevBibType = $pub['bibtype'];
			$prevYear = $pub['year'];
		}

		// clean up
		$this->referenceReader->finalizeReferenceFetching();

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
	 *
	 * @return string
	 */
	protected function getNewManipulator() {
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
	 *
	 * @param array $publication
	 * @return string
	 */
	protected function getEditManipulator($publication) {
		// The edit button
		$label = $this->get_ll('manipulators_edit', 'Edit', TRUE);
		$imgSrc = 'src="' . $this->icon_src['edit'] . '"';
		$img = '<img ' . $imgSrc . ' alt="' . $label . '" ' .
				'class="' . $this->prefixShort . '-edit_icon" />';

		$res = $this->get_link($img,
			array('action' => array('edit' => 1), 'uid' => $publication['uid']),
			TRUE, array('title' => $label));

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
			array('action' => $action, 'uid' => $publication['uid']),
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
	 * @return stirng The generated url
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
	 * @return string The html icon img tag
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
	 * @return string
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
				/** @var \Ipf\Bib\View\EditorView $editorView */
				$editorView = GeneralUtility::makeInstance('Ipf\\Bib\\View\\EditorView');
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

		$label = '';
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

			if ($this->extConf['dynamic']) {
				$dynamic = TRUE;
			}

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
					$link = $this->cObj->getTypoLink(
						$exporter->file_name,
						$exporter->getRelativeFilePath()
					);
					$content .= '<ul><li><div>';
					$content .= $link;
					if ($exporter->file_new) {
						$content .= ' (' . $this->get_ll('export_file_new') . ')';
					}
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

		$title = $this->get_ll('import_title');
		$content = '<h2>' . $title . '</h2>' . "\n";
		$mode = $this->piVars['import'];

		if (($mode == self::IMP_BIBTEX) || ($mode == self::IMP_XML)) {

			/** @var \Ipf\Bib\Utility\Importer\Importer $importer */
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
	 * @return bool TRUE (allowed) FALSE (restricted)
	 */
	protected function checkFEauthorRestriction($publicationId) {
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