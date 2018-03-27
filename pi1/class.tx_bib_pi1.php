<?php

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

use Ipf\Bib\Utility\Utility;
use Ipf\Bib\View\EditorView;
use Ipf\Bib\View\View;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Publication List' for the 'bib' extension.
 */
class tx_bib_pi1 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    const D_SIMPLE = 0;
    const D_Y_SPLIT = 1;
    const D_Y_NAV = 2; // The extension key.

    // http://forum.typo3.org/index.php/t/152665/
    const DIALOG_SAVE_CONFIRMED = 1;
    const DIALOG_DELETE_CONFIRMED = 2;
    const DIALOG_ERASE_CONFIRMED = 3;

    // Enumeration for list modes
    const DIALOG_EXPORT = 4;
    const DIALOG_IMPORT = 5;
    const ENUM_PAGE = 1;

    // Various dialog modes
    const ENUM_ALL = 2;
    const ENUM_BULLET = 3;
    const ENUM_EMPTY = 4;
    const ENUM_FILE_ICON = 5;
    const STAT_NONE = 0;

    // Enumeration style in the list view
    const STAT_TOTAL = 1;
    const STAT_YEAR_TOTAL = 2;
    const AUTOID_OFF = 0;
    const AUTOID_HALF = 1;
    const AUTOID_FULL = 2;

    // Statistic modes
    const SORT_DESC = 0;
    const SORT_ASC = 1;
    public $prefixId = 'tx_bib_pi1';

    // citeid generation modes
    public $scriptRelPath = 'pi1/class.tx_bib_pi1.php';
    public $extKey = 'bib';
    public $pi_checkCHash = false;

    // Sorting modes
    public $prefixShort = 'tx_bib';
    public $prefix_pi1 = 'tx_bib_pi1';
    /**
     * @var string
     */
    public $template;

    /**
     * @var string
     */
    public $itemTemplate;

    /**
     * These are derived/extra configuration values.
     *
     * @var array
     */
    public $extConf;

    /**
     * The reference database reader.
     *
     * @var \Ipf\Bib\Utility\ReferenceReader
     */
    public $referenceReader;

    /**
     * @var array
     */
    public $icon_src = [];

    /**
     * Statistices.
     *
     * @var array
     */
    public $stat = [];

    /**
     * @var array
     */
    public $labelTranslator = [];

    /**
     * @var array
     */
    protected $flexFormData;

    /**
     * @var array
     */
    protected $pidList;

    /**
     * @var array
     */
    protected $flexForm;

    /**
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected $view;

    /**
     * @var string
     */
    protected $flexFormFilterSheet;

    /**
     * The main function merges all configuration options and
     * switches to the appropriate request handler.
     *
     * @param string $content
     * @param array  $conf
     *
     * @return string The plugin HTML content
     */
    public function main($content, $conf)
    {
        $this->conf = $conf;
        $this->extConf = [];
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL();
        $this->extend_ll('EXT:'.$this->extKey.'/Resources/Private/Language/locallang_db.xml');
        $this->pi_initPIflexForm();

        $this->flexFormData = $this->cObj->data['pi_flexform'];

        $this
            ->initializeFluidTemplate()
            ->includeCss();

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

        $this->editAction();

        $this->exportAction();

        $this->detailAction();

        $this->callSearchNavigationHook();

        $this->referenceReader->set_filters($this->extConf['filters']);

        $this->referenceReader->set_searchFields($this->extConf['search_fields']);

        $this->referenceReader->set_editorStopWords($this->extConf['editor_stop_words']);

        $this->referenceReader->set_titleStopWords($this->extConf['title_stop_words']);

        $this->callAuthorNavigationHook();

        $this->getYearNavigation();

        $this->determineNumberOfPublications();

        $this->getPageNavigation();

        $this->getSortFilter();

        $this->disableNavigationOnDemand();

        // Initialize the html templates
        try {
            $this->initializeHtmlTemplate();
        } catch (\Exception $e) {
            return $this->finalize($e->getTraceAsString());
        }

        // Switch to requested view mode
        try {
            return $this->finalize($this->switchToRequestedViewMode());
        } catch (\Exception $e) {
            return $this->finalize($e->getMessage().'<br>'.$e->getTraceAsString());
        }
    }

    /**
     * Extend the $this->LOCAL_LANG label with another language set.
     *
     * @param string $file
     */
    public function extend_ll($file)
    {
        if (!is_array($this->extConf['LL_ext'])) {
            $this->extConf['LL_ext'] = [];
        }
        if (!in_array($file, $this->extConf['LL_ext'])) {
            $languageFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\LocalizationFactory::class);
            $tmpLang = $languageFactory->getParsedData($file, $this->LLkey);

            foreach ($this->LOCAL_LANG as $lang => $list) {
                foreach ($list as $key => $word) {
                    $tmpLang[$lang][$key] = $word;
                }
            }
            $this->LOCAL_LANG = $tmpLang;

            if ($this->altLLkey) {
                $tmpLang = $languageFactory->getParsedData($file, $this->LLkey);
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
     * @return \tx_bib_pi1
     */
    protected function includeCss()
    {
        /** @var \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        $pageRenderer->addCssFile(ExtensionManagementUtility::siteRelPath('bib').'/Resources/Public/Css/bib.css');

        return $this;
    }

    /**
     * @return \tx_bib_pi1
     */
    protected function initializeFluidTemplate()
    {
        /* @var \TYPO3\CMS\Fluid\View\StandaloneView $template */
        $view = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $view->setTemplatePathAndFilename(ExtensionManagementUtility::extPath($this->extKey).'/Resources/Private/Templates/');
        $this->view = $view;

        return $this;
    }

    /**
     * Initialize a ReferenceReader instance and pass it to the class variable.
     */
    protected function initializeReferenceReader()
    {
        /** @var \Ipf\Bib\Utility\ReferenceReader $referenceReader */
        $referenceReader = GeneralUtility::makeInstance(\Ipf\Bib\Utility\ReferenceReader::class);
        $referenceReader->set_cObj($this->cObj);
        $this->referenceReader = $referenceReader;
    }

    /**
     * Retrieve and optimize Extension configuration.
     */
    protected function getExtensionConfiguration()
    {
        $this->extConf = [];
        // Initialize current configuration
        $this->extConf['link_vars'] = [];
        $this->extConf['sub_page'] = [];

        $this->extConf['view_mode'] = View::VIEW_LIST;
        $this->extConf['debug'] = $this->conf['debug'] ? true : false;
        $this->extConf['ce_links'] = $this->conf['ce_links'] ? true : false;

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
        $this->extConf['sorting'] = $this->pi_getFFvalue($this->flexFormData, 'sorting', $fSheet);
        $this->extConf['search_fields'] = $this->pi_getFFvalue($this->flexFormData, 'search_fields', $fSheet);
        $this->extConf['separator'] = $this->pi_getFFvalue($this->flexFormData, 'separator', $fSheet);
        $this->extConf['editor_stop_words'] = $this->pi_getFFvalue($this->flexFormData, 'editor_stop_words', $fSheet);
        $this->extConf['title_stop_words'] = $this->pi_getFFvalue($this->flexFormData, 'title_stop_words', $fSheet);

        $show_fields = $this->pi_getFFvalue($this->flexFormData, 'show_textfields', $fSheet);
        $show_fields = explode(',', $show_fields);

        $this->extConf['hide_fields'] = [
            'abstract' => 1,
            'annotation' => 1,
            'note' => 1,
            'keywords' => 1,
            'tags' => 1,
        ];

        foreach ($show_fields as $f) {
            $field = false;
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

    protected function getTypoScriptConfiguration()
    {
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
     * Get the character set and write it to the configuration.
     */
    protected function getCharacterSet()
    {
        $this->extConf['charset'] = ['upper' => 'UTF-8', 'lower' => 'utf-8'];
        if (strlen($this->conf['charset']) > 0) {
            $this->extConf['charset']['upper'] = strtoupper($this->conf['charset']);
            $this->extConf['charset']['lower'] = strtolower($this->conf['charset']);
        }
    }

    /**
     * Get configuration from FlexForms.
     */
    protected function getFrontendEditorConfiguration()
    {
        $flexFormSheet = 's_fe_editor';
        $this->extConf['editor']['enabled'] = $this->pi_getFFvalue($this->flexFormData, 'enable_editor', $flexFormSheet);
        $this->extConf['editor']['citeid_gen_new'] = $this->pi_getFFvalue($this->flexFormData, 'citeid_gen_new', $flexFormSheet);
        $this->extConf['editor']['citeid_gen_old'] = $this->pi_getFFvalue($this->flexFormData, 'citeid_gen_old', $flexFormSheet);
        $this->extConf['editor']['clear_page_cache'] = $this->pi_getFFvalue($this->flexFormData, 'clear_cache', $flexFormSheet);

        // Overwrite editor configuration from TSsetup
        if (is_array($this->conf['editor.'])) {
            $editorOverride = &$this->conf['editor.'];
            if (array_key_exists('enabled', $editorOverride)) {
                $this->extConf['editor']['enabled'] = $editorOverride['enabled'] ? true : false;
            }
            if (array_key_exists('citeid_gen_new', $editorOverride)) {
                $this->extConf['editor']['citeid_gen_new'] = $editorOverride['citeid_gen_new'] ? true : false;
            }
            if (array_key_exists('citeid_gen_old', $editorOverride)) {
                $this->extConf['editor']['citeid_gen_old'] = $editorOverride['citeid_gen_old'] ? true : false;
            }
        }
        $this->referenceReader->setClearCache($this->extConf['editor']['clear_page_cache']);
    }

    /**
     * Get storage pages.
     */
    public function getStoragePid()
    {
        $pidList = [];
        if (isset($this->conf['pid_list'])) {
            $this->pidList = GeneralUtility::intExplode(',', $this->conf['pid_list']);
        }
        if (isset($this->cObj->data['pages'])) {
            $tmp = GeneralUtility::intExplode(',', $this->cObj->data['pages']);
            $this->pidList = array_merge($pidList, $tmp);
        }
    }

    /**
     * Retrieves and optimizes the pid list and passes it to the referenceReader.
     */
    protected function getPidList()
    {
        $pidList = array_unique($this->pidList);
        if (in_array(0, $pidList)) {
            unset($pidList[array_search(0, $pidList)]);
        }

        if (count($pidList) > 0) {
            // Determine the recursive depth
            $this->extConf['recursive'] = $this->cObj->data['recursive'];
            if (isset($this->conf['recursive'])) {
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
            $this->extConf['pid_list'] = [intval($GLOBALS['TSFE']->id)];
        }
        $this->referenceReader->setPidList($this->extConf['pid_list']);
    }

    /**
     * Make adjustments to different modes.
     *
     * @todo find a better method name or split up
     */
    protected function makeAdjustments()
    {
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
     * Setup and initialize Navigation types.
     *
     * Search Navigation
     * Year Navigation
     * Author Navigation
     * Preference Navigation
     * Statistic Navigation
     * Export Navigation
     */
    protected function setupNavigations()
    {
        // Search Navigation
        if ($this->extConf['show_nav_search']) {
            $this->initializeSearchNavigation();
        }

        // Year Navigation
        if (self::D_Y_NAV == $this->extConf['d_mode']) {
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
        if (self::STAT_NONE != intval($this->extConf['stat_mode'])) {
            $this->enableStatisticsNavigation();
        }

        // Export navigation
        if ($this->extConf['show_nav_export']) {
            $this->getExportNavigation();
        }
    }

    protected function initializeSearchNavigation()
    {
        $this->extConf['dynamic'] = true;
        $this->extConf['search_navi'] = [];
        $this->extConf['search_navi']['obj'] = $this->getAndInitializeNavigationInstance('SearchNavigation');
        $this->extConf['search_navi']['obj']->hook_init();
    }

    /**
     * Returns an instance of a navigation bar class.
     *
     * @param string $type
     *
     * @return \Ipf\Bib\Navigation\Navigation Instance of the navigation object
     */
    protected function getAndInitializeNavigationInstance($type)
    {
        $navigationInstance = GeneralUtility::makeInstance('Ipf\\Bib\\Navigation\\'.$type);
        $navigationInstance->initialize($this);

        return $navigationInstance;
    }

    protected function enableYearNavigation()
    {
        $this->extConf['show_nav_year'] = true;
    }

    protected function initializeAuthorNavigation()
    {
        $this->extConf['dynamic'] = true;
        $this->extConf['author_navi'] = [];
        $this->extConf['author_navi']['obj'] = $this->getAndInitializeNavigationInstance('AuthorNavigation');
        $this->extConf['author_navi']['obj']->hook_init();
    }

    protected function initializePreferenceNavigation()
    {
        $this->extConf['pref_navi'] = [];
        $this->extConf['pref_navi']['obj'] = $this->getAndInitializeNavigationInstance('PreferenceNavigation');
        $this->extConf['pref_navi']['obj']->hook_init();
    }

    protected function enableStatisticsNavigation()
    {
        $this->extConf['show_nav_stat'] = true;
    }

    /**
     * Builds the export navigation.
     */
    protected function getExportNavigation()
    {
        $this->extConf['export_navi'] = [];

        // Check group restrictions
        $groups = $this->conf['export.']['FE_groups_only'];
        $validFrontendUser = true;
        if (strlen($groups) > 0) {
            $validFrontendUser = \Ipf\Bib\Utility\Utility::check_fe_user_groups($groups);
        }

        // Acquire export modes
        $modes = $this->conf['export.']['enable_export'];
        if (strlen($modes) > 0) {
            $modes = \Ipf\Bib\Utility\Utility::explode_trim_lower(
                ',',
                $modes,
                true
            );
        }

        // Add export modes
        $this->extConf['export_navi']['modes'] = [];
        $exportModules = &$this->extConf['export_navi']['modes'];
        if (is_array($modes) && $validFrontendUser) {
            $availableExportModes = ['bibtex', 'xml'];
            $exportModules = array_intersect($availableExportModes, $modes);
        }

        if (0 == count($exportModules)) {
            $extConf['show_nav_export'] = false;
        } else {
            $exportPluginVariables = trim($this->piVars['export']);
            if ((strlen($exportPluginVariables) > 0) && in_array($exportPluginVariables, $exportModules)) {
                $this->extConf['export_navi']['do'] = $exportPluginVariables;
            }
        }
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Determine whether a valid backend user with write access to the reference table is logged in.
     *
     * @return bool
     */
    protected function isValidBackendUser()
    {
        $validBackendUser = false;

        if (is_object($this->getBackendUser())) {
            if ($this->getBackendUser()->isAdmin()) {
                $validBackendUser = true;
            } else {
                $validBackendUser = $this->getBackendUser()->check(
                    'tables_modify',
                    $this->referenceReader->getReferenceTable()
                );
            }
        }

        return $validBackendUser;
    }

    /**
     * @param bool $validBackendUser
     *
     * @return bool
     */
    protected function isValidFrontendUser($validBackendUser)
    {
        $validFrontendUser = false;

        if (!$validBackendUser && isset($this->conf['FE_edit_groups'])) {
            $groups = $this->conf['FE_edit_groups'];
            if (Utility::check_fe_user_groups($groups)) {
                $validFrontendUser = true;
            }
        }

        return $validFrontendUser;
    }

    /**
     * Set the enumeration mode.
     */
    protected function setEnumerationMode()
    {
        $this->extConf['has_enum'] = true;
        if ((self::ENUM_EMPTY == $this->extConf['enum_style'])) {
            $this->extConf['has_enum'] = false;
        }
    }

    /**
     * This initializes field restrictions.
     */
    protected function initializeRestrictions()
    {
        $this->extConf['restrict'] = [];
        $restrictions = &$this->extConf['restrict'];

        $restrictionConfiguration = &$this->conf['restrictions.'];
        if (!is_array($restrictionConfiguration)) {
            return;
        }

        // This is a nested array containing fields
        // that may have restrictions
        $fields = [
            'ref' => [],
            'author' => [],
        ];
        $allFields = [];
        // Acquire field configurations
        foreach ($restrictionConfiguration as $table => $data) {
            if (is_array($data)) {
                $table = substr($table, 0, -1);

                switch ($table) {
                    case 'ref':
                        $allFields = &$this->referenceReader->getReferenceFields();
                        break;
                    case 'authors':
                        $allFields = &$this->referenceReader->getAuthorFields();
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
        foreach ($fields as $table => $tableFields) {
            $restrictions[$table] = [];
            $d_table = $table.'.';
            foreach ($tableFields as $field) {
                $d_field = $field.'.';
                $rcfg = $restrictionConfiguration[$d_table][$d_field];

                // Hide all
                $all = (0 != $rcfg['hide_all']);

                // Hide on string extensions
                $ext = \Ipf\Bib\Utility\Utility::explode_trim_lower(
                    ',',
                    $rcfg['hide_file_ext'],
                    true
                );

                // Reveal on FE user groups
                $groups = strtolower($rcfg['FE_user_groups']);
                if (false === strpos($groups, 'all')) {
                    $groups = GeneralUtility::intExplode(',', $groups);
                } else {
                    $groups = 'all';
                }

                if ($all || (count($ext) > 0)) {
                    $restrictions[$table][$field] = [
                        'hide_all' => $all,
                        'hide_ext' => $ext,
                        'fe_groups' => $groups,
                    ];
                }
            }
        }
    }

    /**
     * Initialize the list view icons.
     */
    protected function initializeListViewIcons()
    {
        $list = [
            'default' => '/typo3/sysext/frontend/Resources/Public/Icons/FileIcons/default.gif',
        ];
        $more = $this->conf['file_icons.'];
        if (is_array($more)) {
            $list = array_merge($list, $more);
        }

        $this->icon_src['files'] = [];

        foreach ($list as $key => $val) {
            $this->icon_src['files']['.'.$key] = $GLOBALS['TSFE']->tmpl->getFileName($val);
        }
    }

    /**
     * This initializes all filters before the browsing filter.
     */
    protected function initializeFilters()
    {
        $this->extConf['filters'] = [];
        $this->initializeFlexformFilter();

        try {
            $this->initializeSelectionFilter();
        } catch (\Exception $e) {
            $flashMessageQueue = GeneralUtility::makeInstance(FlashMessageQueue::class);
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
            $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                $e->getMessage(),
                '',
                FlashMessage::ERROR
            );
            $flashMessageQueue->addMessage($message);
        }
    }

    /**
     * This initializes filter array from the flexform.
     */
    protected function initializeFlexformFilter()
    {
        // Create and select the flexform filter
        $this->extConf['filters']['flexform'] = [];

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

    protected function initializePidFilter()
    {
        $this->extConf['filters']['flexform']['pid'] = $this->extConf['pid_list'];
    }

    protected function initializeYearFilter()
    {
        if ($this->pi_getFFvalue($this->flexForm, 'enable_year', $this->flexFormFilterSheet) > 0) {
            $flexFormFilter = [];
            $flexFormFilter['years'] = [];
            $flexFormFilter['ranges'] = [];
            $ffStr = $this->pi_getFFvalue($this->flexForm, 'years', $this->flexFormFilterSheet);
            $arr = Utility::multi_explode_trim(
                [',', "\r", "\n"],
                $ffStr,
                true
            );

            foreach ($arr as $year) {
                if (false === strpos($year, '-')) {
                    if (is_numeric($year)) {
                        $flexFormFilter['years'][] = intval($year);
                    }
                } else {
                    $range = [];
                    $elms = GeneralUtility::trimExplode('-', $year, false);
                    if (is_numeric($elms[0])) {
                        $range['from'] = intval($elms[0]);
                    }
                    if (is_numeric($elms[1])) {
                        $range['to'] = intval($elms[1]);
                    }
                    if (count($range) > 0) {
                        $flexFormFilter['ranges'][] = $range;
                    }
                }
            }
            if ((count($flexFormFilter['years']) + count($flexFormFilter['ranges'])) > 0) {
                $this->extConf['filters']['flexform']['year'] = $flexFormFilter;
            }
        }
    }

    protected function initializeAuthorFilter()
    {
        $this->extConf['highlight_authors'] = $this->pi_getFFvalue(
            $this->flexForm,
            'highlight_authors',
            $this->flexFormFilterSheet
        );

        if (0 != $this->pi_getFFvalue($this->flexForm, 'enable_author', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['authors'] = [];
            $flexFormFilter['rule'] = $this->pi_getFFvalue($this->flexForm, 'author_rule', $this->flexFormFilterSheet);
            $flexFormFilter['rule'] = intval($flexFormFilter['rule']);

            $authors = $this->pi_getFFvalue($this->flexForm, 'authors', $this->flexFormFilterSheet);
            $authors = \Ipf\Bib\Utility\Utility::multi_explode_trim(
                ["\r", "\n"],
                $authors,
                true
            );

            foreach ($authors as $a) {
                $parts = GeneralUtility::trimExplode(',', $a);
                $author = [];
                if (strlen($parts[0]) > 0) {
                    $author['surname'] = $parts[0];
                }
                if (strlen($parts[1]) > 0) {
                    $author['forename'] = $parts[1];
                }
                if (count($author) > 0) {
                    $flexFormFilter['authors'][] = $author;
                }
            }
            if (count($flexFormFilter['authors']) > 0) {
                $this->extConf['filters']['flexform']['author'] = $flexFormFilter;
            }
        }
    }

    protected function initializeStateFilter()
    {
        if (0 != $this->pi_getFFvalue($this->flexForm, 'enable_state', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['states'] = [];
            $states = intval($this->pi_getFFvalue($this->flexForm, 'states', $this->flexFormFilterSheet));

            $j = 1;
            $referenceReaderStateSize = count($this->referenceReader->allStates);
            for ($i = 0; $i < $referenceReaderStateSize; ++$i) {
                if ($states & $j) {
                    $flexFormFilter['states'][] = $i;
                }
                $j = $j * 2;
            }
            if (count($flexFormFilter['states']) > 0) {
                $this->extConf['filters']['flexform']['state'] = $flexFormFilter;
            }
        }
    }

    protected function initializeBibliographyTypeFilter()
    {
        if (0 != $this->pi_getFFvalue($this->flexForm, 'enable_bibtype', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['types'] = [];
            $types = $this->pi_getFFvalue($this->flexForm, 'bibtypes', $this->flexFormFilterSheet);
            $types = explode(',', $types);
            foreach ($types as $type) {
                $type = intval($type);
                if (($type >= 0) && ($type < count($this->referenceReader->allBibTypes))) {
                    $flexFormFilter['types'][] = $type;
                }
            }
            if (count($flexFormFilter['types']) > 0) {
                $this->extConf['filters']['flexform']['bibtype'] = $flexFormFilter;
            }
        }
    }

    protected function initializeOriginFilter()
    {
        if (0 != $this->pi_getFFvalue($this->flexForm, 'enable_origin', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['origin'] = $this->pi_getFFvalue($this->flexForm, 'origins', $this->flexFormFilterSheet);

            if (1 == $flexFormFilter['origin']) {
                // Legacy value
                $flexFormFilter['origin'] = 0;
            } else {
                if (2 == $flexFormFilter['origin']) {
                    // Legacy value
                    $flexFormFilter['origin'] = 1;
                }
            }

            $this->extConf['filters']['flexform']['origin'] = $flexFormFilter;
        }
    }

    protected function initializeReviewFilter()
    {
        if (0 != $this->pi_getFFvalue($this->flexForm, 'enable_reviewes', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['value'] = $this->pi_getFFvalue($this->flexForm, 'reviewes', $this->flexFormFilterSheet);
            $this->extConf['filters']['flexform']['reviewed'] = $flexFormFilter;
        }
    }

    protected function initializeInLibraryFilter()
    {
        if (0 != $this->pi_getFFvalue($this->flexForm, 'enable_in_library', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['value'] = $this->pi_getFFvalue($this->flexForm, 'in_library', $this->flexFormFilterSheet);
            $this->extConf['filters']['flexform']['in_library'] = $flexFormFilter;
        }
    }

    protected function initializeBorrowedFilter()
    {
        if (0 != $this->pi_getFFvalue($this->flexForm, 'enable_borrowed', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['value'] = $this->pi_getFFvalue($this->flexForm, 'borrowed', $this->flexFormFilterSheet);
            $this->extConf['filters']['flexform']['borrowed'] = $flexFormFilter;
        }
    }

    protected function initializeCiteIdFilter()
    {
        if (0 != $this->pi_getFFvalue($this->flexForm, 'enable_citeid', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $ids = $this->pi_getFFvalue($this->flexForm, 'citeids', $this->flexFormFilterSheet);
            if (strlen($ids) > 0) {
                $ids = \Ipf\Bib\Utility\Utility::multi_explode_trim(
                    [
                        ',',
                        "\r",
                        "\n",
                    ],
                    $ids,
                    true
                );
                $flexFormFilter['ids'] = array_unique($ids);
                $this->extConf['filters']['flexform']['citeid'] = $flexFormFilter;
            }
        }
    }

    protected function initializeTagFilter()
    {
        if ($this->pi_getFFvalue($this->flexForm, 'enable_tags', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['rule'] = $this->pi_getFFvalue($this->flexForm, 'tags_rule', $this->flexFormFilterSheet);
            $flexFormFilter['rule'] = intval($flexFormFilter['rule']);
            $kw = $this->pi_getFFvalue($this->flexForm, 'tags', $this->flexFormFilterSheet);
            if (strlen($kw) > 0) {
                $words = \Ipf\Bib\Utility\Utility::multi_explode_trim(
                    [
                        ',',
                        "\r",
                        "\n",
                    ],
                    $kw,
                    true
                );
                foreach ($words as &$word) {
                    $word = $this->referenceReader->getSearchTerm($word, $this->extConf['charset']['upper']);
                }
                $flexFormFilter['words'] = $words;
                $this->extConf['filters']['flexform']['tags'] = $flexFormFilter;
            }
        }
    }

    protected function initializeKeywordsFilter()
    {
        if ($this->pi_getFFvalue($this->flexForm, 'enable_keywords', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['rule'] = $this->pi_getFFvalue(
                $this->flexForm,
                'keywords_rule',
                $this->flexFormFilterSheet
            );
            $flexFormFilter['rule'] = intval($flexFormFilter['rule']);
            $kw = $this->pi_getFFvalue($this->flexForm, 'keywords', $this->flexFormFilterSheet);
            if (strlen($kw) > 0) {
                $words = \Ipf\Bib\Utility\Utility::multi_explode_trim([',', "\r", "\n"], $kw, true);
                foreach ($words as &$word) {
                    $word = $this->referenceReader->getSearchTerm($word, $this->extConf['charset']['upper']);
                }
                $flexFormFilter['words'] = $words;
                $this->extConf['filters']['flexform']['keywords'] = $flexFormFilter;
            }
        }
    }

    protected function initializeGeneralKeywordSearch()
    {
        if ($this->pi_getFFvalue($this->flexForm, 'enable_search_all', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['rule'] = $this->pi_getFFvalue(
                $this->flexForm,
                'search_all_rule',
                $this->flexFormFilterSheet
            );
            $flexFormFilter['rule'] = intval($flexFormFilter['rule']);
            $kw = $this->pi_getFFvalue($this->flexForm, 'search_all', $this->flexFormFilterSheet);
            if (strlen($kw) > 0) {
                $words = \Ipf\Bib\Utility\Utility::multi_explode_trim([',', "\r", "\n"], $kw, true);
                foreach ($words as &$word) {
                    $word = $this->referenceReader->getSearchTerm($word, $this->extConf['charset']['upper']);
                }
                $flexFormFilter['words'] = $words;
                $this->extConf['filters']['flexform']['all'] = $flexFormFilter;
            }
        }
    }

    /**
     * This initializes the selection filter array from the piVars.
     *
     * @throws \Exception
     */
    protected function initializeSelectionFilter()
    {
        $this->extConf['filters']['selection'] = [];
        $filter = &$this->extConf['filters']['selection'];

        // Publication ids
        if (is_string($this->piVars['search']['ref_ids'])) {
            $ids = $this->piVars['search']['ref_ids'];
            $ids = GeneralUtility::intExplode(',', $ids);

            if (count($ids) > 0) {
                $filter['uid'] = $ids;
            }
        }

        // General search
        if (is_string($this->piVars['search']['all'])) {
            $words = $this->piVars['search']['all'];
            $words = GeneralUtility::trimExplode(',', $words, true);
            if (count($words) > 0) {
                $filter['all']['words'] = $words;

                // AND
                $filter['all']['rule'] = 1;
                $rule = strtoupper(trim($this->piVars['search']['all_rule']));
                if (false === strpos($rule, 'AND')) {
                    // OR
                    $filter['all']['rule'] = 0;
                }
            }
        }
    }

    /**
     * Determines whether hidden entries are displayed or not.
     */
    protected function showHiddenEntries()
    {
        $this->extConf['show_hidden'] = false;
        if ($this->extConf['edit_mode']) {
            $this->extConf['show_hidden'] = true;
        }
        $this->referenceReader->setShowHidden($this->extConf['show_hidden']);
    }

    protected function editAction()
    {
        if ($this->extConf['edit_mode']) {
            // Disable caching in edit mode
            $GLOBALS['TSFE']->set_no_cache();

            // Load edit labels
            $this->extend_ll('EXT:'.$this->extKey.'/Resources/Private/Language/locallang.xml');

            // Do an action type evaluation
            if (is_array($this->piVars['action'])) {
                $actionName = implode('', array_keys($this->piVars['action']));

                switch ($actionName) {
                    case 'new':
                        $this->extConf['view_mode'] = View::VIEW_EDITOR;
                        $this->extConf['editor_mode'] = EditorView::EDIT_NEW;
                        break;
                    case 'edit':
                        $this->extConf['view_mode'] = View::VIEW_EDITOR;
                        $this->extConf['editor_mode'] = EditorView::EDIT_EDIT;
                        break;
                    case 'confirm_save':
                        $this->extConf['view_mode'] = View::VIEW_EDITOR;
                        $this->extConf['editor_mode'] = EditorView::EDIT_CONFIRM_SAVE;
                        break;
                    case 'save':
                        $this->extConf['view_mode'] = View::VIEW_DIALOG;
                        $this->extConf['dialog_mode'] = self::DIALOG_SAVE_CONFIRMED;
                        break;
                    case 'confirm_delete':
                        $this->extConf['view_mode'] = View::VIEW_EDITOR;
                        $this->extConf['editor_mode'] = EditorView::EDIT_CONFIRM_DELETE;
                        break;
                    case 'delete':
                        $this->extConf['view_mode'] = View::VIEW_DIALOG;
                        $this->extConf['dialog_mode'] = self::DIALOG_DELETE_CONFIRMED;
                        break;
                    case 'confirm_erase':
                        $this->extConf['view_mode'] = View::VIEW_EDITOR;
                        $this->extConf['editor_mode'] = EditorView::EDIT_CONFIRM_ERASE;
                        break;
                    case 'erase':
                        $this->extConf['view_mode'] = View::VIEW_DIALOG;
                        $this->extConf['dialog_mode'] = self::DIALOG_ERASE_CONFIRMED;
                        break;
                    case 'hide':
                        $this->hidePublication(true);
                        break;
                    case 'reveal':
                        $this->hidePublication(false);
                        break;
                    default:
                }
            }

            // Set unset extConf and piVars editor mode
            if (View::VIEW_DIALOG == $this->extConf['view_mode']) {
                unset($this->piVars['editor_mode']);
            }

            if (isset($this->extConf['editor_mode'])) {
                $this->piVars['editor_mode'] = $this->extConf['editor_mode'];
            } else {
                if (isset($this->piVars['editor_mode'])) {
                    $this->extConf['view_mode'] = View::VIEW_EDITOR;
                    $this->extConf['editor_mode'] = $this->piVars['editor_mode'];
                }
            }

            // Initialize edit icons
            $this->initializeEditIcons();

            // Switch to an import view on demand
            $allImport = intval(\Ipf\Bib\Utility\Importer\Importer::IMP_BIBTEX | \Ipf\Bib\Utility\Importer\Importer::IMP_XML);
            if (isset($this->piVars['import']) && (intval($this->piVars['import']) & $allImport)) {
                $this->extConf['view_mode'] = View::VIEW_DIALOG;
                $this->extConf['dialog_mode'] = self::DIALOG_IMPORT;
            }
        }
    }

    /**
     * Hides or reveals a publication.
     *
     * @param bool $hide
     */
    protected function hidePublication($hide = true)
    {
        /** @var \Ipf\Bib\Utility\ReferenceWriter $referenceWriter */
        $referenceWriter = GeneralUtility::makeInstance(\Ipf\Bib\Utility\ReferenceWriter::class);
        $referenceWriter->initialize($this->referenceReader);
        $referenceWriter->hidePublication($this->piVars['uid'], $hide);
    }

    /**
     * Initialize the edit icons.
     */
    protected function initializeEditIcons()
    {
        $list = [];
        $more = $this->conf['edit_icons.'];
        if (is_array($more)) {
            $list = array_merge($list, $more);
        }

        // @todo can't figure out $base
        foreach ($list as $key => $val) {
            $this->icon_src[$key] = $GLOBALS['TSFE']->tmpl->getFileName($val);
        }
    }

    /**
     * Switch to export mode on demand.
     */
    protected function exportAction()
    {
        if (is_string($this->extConf['export_navi']['do'])) {
            $this->extConf['view_mode'] = View::VIEW_DIALOG;
            $this->extConf['dialog_mode'] = self::DIALOG_EXPORT;
        }
    }

    /**
     * Switch to single view on demand.
     */
    protected function detailAction()
    {
        if (is_numeric($this->piVars['show_uid'])) {
            $this->extConf['view_mode'] = View::VIEW_SINGLE;
            $this->extConf['single_view']['uid'] = (int) $this->piVars['show_uid'];
            unset($this->piVars['editor_mode']);
            unset($this->piVars['dialog_mode']);
        }
    }

    /**
     * Calls the hook_filter in the search navigation instance.
     */
    protected function callSearchNavigationHook()
    {
        if ($this->extConf['show_nav_search']) {
            $this->extConf['search_navi']['obj']->hook_filter();
        }
    }

    /**
     * Calls the hook_filter in the author navigation instance.
     */
    protected function callAuthorNavigationHook()
    {
        if ($this->extConf['show_nav_author']) {
            $this->extConf['author_navi']['obj']->hook_filter();
        }
    }

    protected function getYearNavigation()
    {
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
                if ('all' == $exportPluginVariables) {
                    $this->extConf['year'] = $exportPluginVariables;
                }
            }

            if ('all' == $this->extConf['year']) {
                if ($this->conf['yearNav.']['selection.']['all_year_split']) {
                    $this->extConf['split_years'] = true;
                }
            }

            // The selected year has no publications so select the closest year
            if (($this->stat['num_all'] > 0) && is_numeric($this->extConf['year'])) {
                $this->extConf['year'] = \Ipf\Bib\Utility\Utility::find_nearest_int(
                    $this->extConf['year'],
                    $this->stat['years']
                );
            }
            // Append default link variable
            $this->extConf['link_vars']['year'] = $this->extConf['year'];

            if (is_numeric($this->extConf['year'])) {
                // Adjust num_page
                $this->stat['num_page'] = $this->stat['year_hist'][$this->extConf['year']];

                // Adjust year filter
                $this->extConf['filters']['br_year'] = [];
                $this->extConf['filters']['br_year']['year'] = [];
                $this->extConf['filters']['br_year']['year']['years'] = [$this->extConf['year']];
            }
        }
    }

    /**
     * Determines the number of publications.
     */
    protected function determineNumberOfPublications()
    {
        if (!is_numeric($this->stat['num_all'])) {
            $this->stat['num_all'] = $this->referenceReader->getNumberOfPublications();
            $this->stat['num_page'] = $this->stat['num_all'];
        }
    }

    protected function getPageNavigation()
    {
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
            $this->extConf['show_nav_page'] = true;

            $this->extConf['filters']['br_page'] = [];

            // Adjust the browse filter limit
            $this->extConf['filters']['br_page']['limit'] = [];
            $this->extConf['filters']['br_page']['limit']['start'] = $this->extConf['sub_page']['current'] * $this->extConf['sub_page']['ipp'];
            $this->extConf['filters']['br_page']['limit']['num'] = $this->extConf['sub_page']['ipp'];
        }
    }

    /**
     * Determines and applies sorting filters to the ReferenceReader.
     */
    protected function getSortFilter()
    {
        $this->extConf['filters']['sort'] = [];
        $this->extConf['filters']['sort']['sorting'] = [];

        // Default sorting
        $defaultSorting = 'DESC';

        if (self::SORT_ASC == $this->extConf['date_sorting']) {
            $defaultSorting = 'ASC';
        }

        // add custom sorting with values from flexform
        if (!empty($this->extConf['sorting'])) {
            $sortFields = GeneralUtility::trimExplode(',', $this->extConf['sorting']);
            foreach ($sortFields as $sortField) {
                if ('surname' == $sortField) {
                    $sort = [
                        'field' => $this->referenceReader->getAuthorTable().'.'.$sortField.' ',
                        'dir' => 'ASC',
                    ];
                } else {
                    $sort = [
                        'field' => $this->referenceReader->getReferenceTable().'.'.$sortField.' ',
                        'dir' => $defaultSorting,
                    ];
                }
                $this->extConf['filters']['sort']['sorting'][] = $sort;
            }
        } else {
            // pre-defined sorting
            $this->extConf['filters']['sort']['sorting'] = [
                ['field' => $this->referenceReader->getReferenceTable().'.year', 'dir' => $defaultSorting],
                ['field' => $this->referenceReader->getReferenceTable().'.month', 'dir' => $defaultSorting],
                ['field' => $this->referenceReader->getReferenceTable().'.day', 'dir' => $defaultSorting],
                ['field' => $this->referenceReader->getReferenceTable().'.bibtype', 'dir' => 'ASC'],
                ['field' => $this->referenceReader->getReferenceTable().'.state', 'dir' => 'ASC'],
                ['field' => $this->referenceReader->getReferenceTable().'.sorting', 'dir' => 'ASC'],
                ['field' => $this->referenceReader->getReferenceTable().'.title', 'dir' => 'ASC'],
            ];
        }
        // Adjust sorting for bibtype split
        if ($this->extConf['split_bibtypes']) {
            if (self::D_SIMPLE == $this->extConf['d_mode']) {
                $this->extConf['filters']['sort']['sorting'] = [
                    ['field' => $this->referenceReader->getReferenceTable().'.bibtype', 'dir' => 'ASC'],
                    ['field' => $this->referenceReader->getReferenceTable().'.year', 'dir' => $defaultSorting],
                    ['field' => $this->referenceReader->getReferenceTable().'.month', 'dir' => $defaultSorting],
                    ['field' => $this->referenceReader->getReferenceTable().'.day', 'dir' => $defaultSorting],
                    ['field' => $this->referenceReader->getReferenceTable().'.state', 'dir' => 'ASC'],
                    ['field' => $this->referenceReader->getReferenceTable().'.sorting', 'dir' => 'ASC'],
                    ['field' => $this->referenceReader->getReferenceTable().'.title', 'dir' => 'ASC'],
                ];
            } else {
                $this->extConf['filters']['sort']['sorting'] = [
                    ['field' => $this->referenceReader->getReferenceTable().'.year', 'dir' => $defaultSorting],
                    ['field' => $this->referenceReader->getReferenceTable().'.bibtype', 'dir' => 'ASC'],
                    ['field' => $this->referenceReader->getReferenceTable().'.month', 'dir' => $defaultSorting],
                    ['field' => $this->referenceReader->getReferenceTable().'.day', 'dir' => $defaultSorting],
                    ['field' => $this->referenceReader->getReferenceTable().'.state', 'dir' => 'ASC'],
                    ['field' => $this->referenceReader->getReferenceTable().'.sorting', 'dir' => 'ASC'],
                    ['field' => $this->referenceReader->getReferenceTable().'.title', 'dir' => 'ASC'],
                ];
            }
        }
        $this->referenceReader->set_filters($this->extConf['filters']);
    }

    /**
     * Disable navigations om demand.
     */
    protected function disableNavigationOnDemand()
    {
        if (0 == $this->stat['num_all']) {
            $this->extConf['show_nav_export'] = false;
        }

        if (0 == $this->stat['num_page']) {
            $this->extConf['show_nav_stat'] = false;
        }
    }

    /**
     * Initializes an array which contains subparts of the
     * html templates.
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function initializeHtmlTemplate()
    {
        $error = [];

        // Already initialized?
        if (isset($this->template['LIST_VIEW'])) {
            return $error;
        }

        $this->template = [];
        $this->itemTemplate = [];

        // List blocks
        $list_blocks = [
            'YEAR_BLOCK',
            'BIBTYPE_BLOCK',
            'SPACER_BLOCK',
        ];

        // Bibtype data blocks
        $bib_types = [];
        foreach ($this->referenceReader->allBibTypes as $val) {
            $bib_types[] = strtoupper($val).'_DATA';
        }
        $bib_types[] = 'DEFAULT_DATA';
        $bib_types[] = 'ITEM_BLOCK';

        // Misc navigation blocks
        $navi_blocks = [
            'EXPORT_NAVI_BLOCK',
            'IMPORT_NAVI_BLOCK',
            'NEW_ENTRY_NAVI_BLOCK',
        ];

        // Fetch the template file list
        $templateList = &$this->conf['templates.'];
        if (!is_array($templateList)) {
            throw new \Exception('HTML templates are not set in TypoScript', 1378817757);
        }

        $info = [
            'main' => [
                'file' => $templateList['main'],
                'parts' => ['LIST_VIEW'],
            ],
            'list_blocks' => [
                'file' => $templateList['list_blocks'],
                'parts' => $list_blocks,
            ],
            'list_items' => [
                'file' => $templateList['list_items'],
                'parts' => $bib_types,
                'no_warn' => true,
            ],
            'navi_misc' => [
                'file' => $templateList['navi_misc'],
                'parts' => $navi_blocks,
            ],
        ];

        foreach ($info as $key => $val) {
            if (0 == strlen($val['file'])) {
                throw new \Exception('HTML template file for \''.$key.'\' is not set', 1378817806);
            }
            $tmpl = $this->cObj->fileResource($val['file']);
            if (0 == strlen($tmpl)) {
                throw new \Exception(
                    'The HTML template file \''.$val['file'].'\' for \''.$key.'\' is not readable or empty',
                    1378817895
                );
            }
            foreach ($val['parts'] as $part) {
                $ptag = '###'.$part.'###';
                $pstr = $this->cObj->getSubpart($tmpl, $ptag);
                // Error message
                if ((0 == strlen($pstr)) && !$val['no_warn']) {
                    throw new \Exception(
                        'The subpart \''.$ptag.'\' in the HTML template file \''.$val['file'].'\' is empty',
                        1378817933
                    );
                }
                $this->template[$part] = $pstr;
            }
        }

        return $error;
    }

    /**
     * This is the last function called before ouptput.
     *
     * @param string $pluginContent
     *
     * @return string The input string with some extra data
     */
    protected function finalize($pluginContent)
    {
        if ($this->extConf['debug']) {
            $pluginContent .= \TYPO3\CMS\Core\Utility\DebugUtility::viewArray(
                [
                    'extConf' => $this->extConf,
                    'conf' => $this->conf,
                    'piVars' => $this->piVars,
                    'stat' => $this->stat,
                    'HTTP_POST_VARS' => $GLOBALS['HTTP_POST_VARS'],
                    'HTTP_GET_VARS' => $GLOBALS['HTTP_GET_VARS'],
                ]
            );
        }

        return $this->pi_wrapInBaseClass($pluginContent);
    }

    /**
     * Determine the requested view mode (List, Single, Editor, Dialog).
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function switchToRequestedViewMode()
    {
        switch ($this->extConf['view_mode']) {
            case View::VIEW_LIST:
                return $this->list_view();
                break;
            case View::VIEW_SINGLE:
                return $this->singleView();
                break;
            case View::VIEW_EDITOR:
                return $this->editorView();
                break;
            case View::VIEW_DIALOG:
                return $this->dialogView();
                break;
            default:
                throw new \Exception('An illegal view mode occurred', 1379064350);
        }
    }

    /**
     * This function composes the html-view of a set of publications.
     *
     * @return string The list view
     */
    protected function list_view()
    {
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
     * Returns the year navigation bar.
     *
     * @return string An HTML string with the year navigation bar
     */
    protected function setupSearchNavigation()
    {
        $trans = [];
        $hasStr = '';

        if ($this->extConf['show_nav_search']) {
            $trans = $this->extConf['search_navi']['obj']->translator();
            $hasStr = ['', ''];

            if (strlen($trans['###SEARCH_NAVI_TOP###']) > 0) {
                $this->extConf['has_top_navi'] = true;
            }
        }

        $this->template['LIST_VIEW'] = $this->cObj->substituteSubpart(
            $this->template['LIST_VIEW'],
            '###HAS_SEARCH_NAVI###',
            $hasStr
        );
        $this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $trans);
    }

    /**
     * Returns the year navigation bar.
     *
     * @return string An HTML string with the year navigation bar
     */
    protected function setupYearNavigation()
    {
        $trans = [];
        $hasStr = '';

        if ($this->extConf['show_nav_year']) {
            $obj = $this->getAndInitializeNavigationInstance('YearNavigation');

            $trans = $obj->translator();
            $hasStr = ['', ''];

            if (strlen($trans['###YEAR_NAVI_TOP###']) > 0) {
                $this->extConf['has_top_navi'] = true;
            }
        }

        $this->template['LIST_VIEW'] = $this->cObj->substituteSubpart(
            $this->template['LIST_VIEW'],
            '###HAS_YEAR_NAVI###',
            $hasStr
        );
        $this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $trans);
    }

    /**
     * Sets up the author navigation bar.
     */
    protected function setupAuthorNavigation()
    {
        $trans = [];
        $hasStr = '';

        if ($this->extConf['show_nav_author']) {
            $trans = $this->extConf['author_navi']['obj']->translator();
            $hasStr = ['', ''];

            if (strlen($trans['###AUTHOR_NAVI_TOP###']) > 0) {
                $this->extConf['has_top_navi'] = true;
            }
        }

        $this->template['LIST_VIEW'] = $this->cObj->substituteSubpart(
            $this->template['LIST_VIEW'],
            '###HAS_AUTHOR_NAVI###',
            $hasStr
        );
        $this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $trans);
    }

    /**
     * Sets up the preferences navigation bar.
     */
    protected function setupPreferenceNavigation()
    {
        $trans = [];
        $hasStr = '';

        if ($this->extConf['show_nav_pref']) {
            $trans = $this->extConf['pref_navi']['obj']->translator();
            $hasStr = ['', ''];

            if (strlen($trans['###PREF_NAVI_TOP###']) > 0) {
                $this->extConf['has_top_navi'] = true;
            }
        }

        $this->template['LIST_VIEW'] = $this->cObj->substituteSubpart(
            $this->template['LIST_VIEW'],
            '###HAS_PREF_NAVI###',
            $hasStr
        );
        $this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $trans);
    }

    /**
     * Sets up the page navigation bar.
     */
    protected function setupPageNavigation()
    {
        $trans = [];
        $hasStr = '';

        if ($this->extConf['show_nav_page']) {
            $obj = $this->getAndInitializeNavigationInstance('PageNavigation');

            $trans = $obj->translator();
            $hasStr = ['', ''];

            if (strlen($trans['###PAGE_NAVI_TOP###']) > 0) {
                $this->extConf['has_top_navi'] = true;
            }
        }

        $this->template['LIST_VIEW'] = $this->cObj->substituteSubpart(
            $this->template['LIST_VIEW'],
            '###HAS_PAGE_NAVI###',
            $hasStr
        );
        $this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $trans);
    }

    /**
     * Setup the add-new-entry element.
     */
    protected function setupNewEntryNavigation()
    {
        $linkStr = '';
        $hasStr = '';

        if ($this->extConf['edit_mode']) {
            $template = $this->setupEnumerationConditionBlock($this->template['NEW_ENTRY_NAVI_BLOCK']);
            $linkStr = $this->getNewManipulator();
            $linkStr = $this->cObj->substituteMarker($template, '###NEW_ENTRY###', $linkStr);
            $hasStr = ['', ''];
            $this->extConf['has_top_navi'] = true;
        }

        $this->template['LIST_VIEW'] = $this->cObj->substituteSubpart(
            $this->template['LIST_VIEW'],
            '###HAS_NEW_ENTRY###',
            $hasStr
        );
        $this->template['LIST_VIEW'] = $this->cObj->substituteMarker(
            $this->template['LIST_VIEW'],
            '###NEW_ENTRY###',
            $linkStr
        );
    }

    /**
     * Removes the enumeration condition block
     * or just the block markers.
     *
     * @param string $template
     *
     * @return string
     */
    public function setupEnumerationConditionBlock($template)
    {
        $sub = $this->extConf['has_enum'] ? [] : '';
        $template = $this->cObj->substituteSubpart(
            $template,
            '###HAS_ENUM###',
            $sub
        );

        return $template;
    }

    /**
     * Returns the new entry button.
     *
     * @return string
     */
    protected function getNewManipulator()
    {
        $label = $this->pi_getLL('manipulators_new', 'New');
        $res = $this->get_link(
            '',
            [
                'action' => [
                    'new' => 1,
                ],
            ],
            true,
            [
                'title' => $label,
                'class' => 'new-record',
            ]
        );

        return $this->cObj->stdWrap($res, $this->conf['editor.']['list.']['manipulators.']['new.']);
    }

    /**
     * Wraps the content into a link to the current page with
     * extra link arguments given in the array $linkVariables.
     *
     * @param string $content
     * @param array  $linkVariables
     * @param bool   $autoCache
     * @param array  $attributes
     *
     * @return string The link to the current page
     */
    public function get_link($content, $linkVariables = [], $autoCache = true, $attributes = null)
    {
        $url = $this->get_link_url($linkVariables, $autoCache);

        return $this->composeLink($url, $content, $attributes);
    }

    /**
     * Same as get_link but returns just the URL.
     *
     * @param array $linkVariables
     * @param bool  $autoCache
     * @param bool  $currentRecord
     *
     * @return string The url
     */
    public function get_link_url($linkVariables = [], $autoCache = true, $currentRecord = true)
    {
        if ($this->extConf['edit_mode']) {
            $autoCache = false;
        }

        $linkVariables = array_merge($this->extConf['link_vars'], $linkVariables);
        $linkVariables = [$this->prefix_pi1 => $linkVariables];

        $record = '';
        if ($this->extConf['ce_links'] && $currentRecord) {
            $record = '#c'.strval($this->cObj->data['uid']);
        }

        $this->pi_linkTP('x', $linkVariables, $autoCache);
        $url = $this->cObj->lastTypoLinkUrl.$record;

        $url = preg_replace('/&([^;]{8})/', '&amp;\\1', $url);

        return $url;
    }

    /**
     * Composes a link of an url an some attributes.
     *
     * @param string $url
     * @param string $content
     * @param array  $attributes
     *
     * @return string The link (HTML <a> element)
     */
    protected function composeLink($url, $content, $attributes = null)
    {
        $linkString = '<a href="'.$url.'"';
        if (is_array($attributes)) {
            foreach ($attributes as $k => $v) {
                $linkString .= ' '.$k.'="'.$v.'"';
            }
        }
        $linkString .= '>'.$content.'</a>';

        return $linkString;
    }

    /**
     * Setup the export-link element.
     */
    protected function setupExportNavigation()
    {
        $hasStr = '';

        if ($this->extConf['show_nav_export']) {
            $cfg = [];
            if (is_array($this->conf['export.'])) {
                $cfg = &$this->conf['export.'];
            }
            $extConf = &$this->extConf['export_navi'];

            $exports = [];

            // Export label
            $label = $this->pi_getLL($cfg['label']);
            $label = $this->cObj->stdWrap($label, $cfg['label.']);

            $exportModes = ['bibtex', 'xml'];

            foreach ($exportModes as $mode) {
                if (in_array($mode, $extConf['modes'])) {
                    $title = $this->pi_getLL('export_'.$mode.'LinkTitle', $mode, true);
                    $txt = $this->pi_getLL('export_'.$mode);
                    $link = $this->get_link(
                        $txt,
                        ['export' => $mode],
                        false,
                        ['title' => $title]
                    );
                    $link = $this->cObj->stdWrap($link, $cfg[$mode.'.']);
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

        $this->template['LIST_VIEW'] = $this->cObj->substituteSubpart(
            $this->template['LIST_VIEW'],
            '###HAS_EXPORT###',
            $hasStr
        );
        $this->template['LIST_VIEW'] = $this->cObj->substituteMarker(
            $this->template['LIST_VIEW'],
            '###EXPORT###',
            $block
        );
    }

    /**
     * Setup the import-link element in the
     * HTML-template.
     */
    protected function setupImportNavigation()
    {
        $str = '';
        $hasStr = '';

        if ($this->extConf['edit_mode']) {
            $cfg = [];
            if (is_array($this->conf['import.'])) {
                $cfg = &$this->conf['import.'];
            }

            $str = $this->setupEnumerationConditionBlock($this->template['IMPORT_NAVI_BLOCK']);
            $translator = [];
            $imports = [];

            // Import bibtex
            $title = $this->pi_getLL('import_bibtexLinkTitle', 'bibtex', true);
            $link = $this->get_link(
                $this->pi_getLL('import_bibtex'),
                ['import' => \Ipf\Bib\Utility\Importer\Importer::IMP_BIBTEX],
                false,
                ['title' => $title]
            );
            $imports[] = $this->cObj->stdWrap($link, $cfg['bibtex.']);

            // Import xml
            $title = $this->pi_getLL('import_xmlLinkTitle', 'xml', true);
            $link = $this->get_link(
                $this->pi_getLL('import_xml'),
                ['import' => \Ipf\Bib\Utility\Importer\Importer::IMP_XML],
                false,
                ['title' => $title]
            );
            $imports[] = $this->cObj->stdWrap($link, $cfg['xml.']);

            $sep = '&nbsp;';
            if (array_key_exists('separator', $cfg)) {
                $sep = $this->cObj->stdWrap($cfg['separator'], $cfg['separator.']);
            }

            // Import label
            $translator['###LABEL###'] = $this->cObj->stdWrap(
                $this->pi_getLL($cfg['label']),
                $cfg['label.']
            );
            $translator['###IMPORTS###'] = implode($sep, $imports);

            $str = $this->cObj->substituteMarkerArrayCached($str, $translator, []);
            $hasStr = ['', ''];
        }

        $this->template['LIST_VIEW'] = $this->cObj->substituteSubpart(
            $this->template['LIST_VIEW'],
            '###HAS_IMPORT###',
            $hasStr
        );
        $this->template['LIST_VIEW'] = $this->cObj->substituteMarker(
            $this->template['LIST_VIEW'],
            '###IMPORT###',
            $str
        );
    }

    /**
     * Setup the statistic element.
     */
    protected function setupStatisticsNavigation()
    {
        $trans = [];
        $hasStr = '';

        if ($this->extConf['show_nav_stat']) {
            $obj = $this->getAndInitializeNavigationInstance('StatisticsNavigation');

            $trans = $obj->translator();
            $hasStr = ['', ''];

            if (strlen($trans['###STAT_NAVI_TOP###']) > 0) {
                $this->extConf['has_top_navi'] = true;
            }
        }

        $this->template['LIST_VIEW'] = $this->cObj->substituteSubpart(
            $this->template['LIST_VIEW'],
            '###HAS_STAT_NAVI###',
            $hasStr
        );
        $this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached($this->template['LIST_VIEW'], $trans);
    }

    /**
     * Setup the a spacer block.
     */
    protected function setupSpacer()
    {
        $spacerBlock = $this->setupEnumerationConditionBlock($this->template['SPACER_BLOCK']);
        $listViewTemplate = &$this->template['LIST_VIEW'];
        $listViewTemplate = $this->cObj->substituteMarker($listViewTemplate, '###SPACER###', $spacerBlock);
    }

    /**
     * Setup the top navigation block.
     */
    protected function setupTopNavigation()
    {
        $hasStr = '';
        if ($this->extConf['has_top_navi']) {
            $hasStr = ['', ''];
        }
        $this->template['LIST_VIEW'] = $this->cObj->substituteSubpart(
            $this->template['LIST_VIEW'],
            '###HAS_TOP_NAVI###',
            $hasStr
        );
    }

    /**
     * Setup items in the html-template.
     */
    protected function setupItems()
    {
        $items = [];

        // Store cObj data
        $contentObjectBackup = $this->cObj->data;

        $this->prepareItemSetup();

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
            $labelValue = $this->pi_getLL('label_'.$label);
            $labelValue = $this->cObj->stdWrap($labelValue, $this->conf['label.'][$label.'.']);
            $this->labelTranslator['###LABEL_'.$upperCaseLabel.'###'] = $labelValue;
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
        $enumerationWrap = $this->conf['enum.'][$enumerationIdentifier.'.'];

        // Warning cfg
        $warningConfiguration = &$this->conf['editor.']['list.']['warn_box.'];
        $editMode = $this->extConf['edit_mode'];

        if (self::D_Y_SPLIT == $this->extConf['d_mode']) {
            $this->extConf['split_years'] = true;
        }

        // Database reading initialization
        $this->referenceReader->initializeReferenceFetching();

        // Determine publication numbers
        $publicationsBefore = 0;
        if ((self::D_Y_NAV == $this->extConf['d_mode']) && is_numeric($this->extConf['year'])) {
            foreach ($this->stat['year_hist'] as $y => $n) {
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
        $i_page = $this->stat['num_page'] - $limit_start;
        $i_page_delta = -1;
        if (self::SORT_ASC == $this->extConf['date_sorting']) {
            $i_page = $limit_start + 1;
            $i_page_delta = 1;
        }

        $i_subpage = 1;
        $i_bibtype = 1;

        // Start the fetch loop
        while ($pub = $this->referenceReader->getReference()) {
            // Get prepared publication data
            $warnings = [];
            $pdata = $this->preparePublicationData($pub, $warnings);

            // Item data
            $this->prepare_pub_cObj_data($pdata);

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
            if (0 == strlen($listViewTemplate)) {
                $key = strtoupper($pdata['bibtype_short']).'_DATA';
                $listViewTemplate = $this->template[$key];

                if (0 == strlen($listViewTemplate)) {
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
            if (!(false === strpos($enum, '###FILE_URL_ICON###'))) {
                $repl = $this->getFileUrlIcon($pub, $pdata);
                $enum = str_replace('###FILE_URL_ICON###', $repl, $enum);
            }
            $translator['###ENUM_NUMBER###'] = $this->cObj->stdWrap($enum, $enumerationWrap);

            // Row classes
            $eo = $evenOdd ? 'even' : 'odd';

            $translator['###ROW_CLASS###'] = $this->conf['classes.'][$eo];

            $translator['###NUMBER_CLASS###'] = $this->prefixShort.'-enum';

            // Manipulators
            $translator['###MANIPULATORS###'] = '';
            $manip_all = [];
            $subst_sub = '';
            if ($editMode) {
                if ($this->checkFEauthorRestriction($pub['uid'])) {
                    $subst_sub = ['', ''];
                    $manip_all[] = $this->getEditManipulator($pub);
                    $manip_all[] = $this->getHideManipulator($pub);
                    $manip_all = \Ipf\Bib\Utility\Utility::html_layout_table([$manip_all]);

                    $translator['###MANIPULATORS###'] = $this->cObj->stdWrap(
                        $manip_all,
                        $this->conf['editor.']['list.']['manipulators.']['all.']
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
                    $this->pi_getLL('bibtype_plural_'.$pub['bibtype'], $pub['bibtype'], true),
                    $this->conf['label.']['bibtype.']
                );
                $items[] = $this->cObj->substituteMarker($bibliographyTypeBlockTemplate, '###BIBTYPE###', $bibStr);
            }

            // Append string for item data
            $append = '';
            if ((count($warnings) > 0) && $editMode) {
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
            ++$i_subpage;
            ++$i_bibtype;

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
            $hasStr = ['', ''];
        } else {
            $no_items = strval($this->extConf['post_items']);
            if (0 == strlen($no_items)) {
                $no_items = $this->get_ll('label_no_items');
            }
            $no_items = $this->cObj->stdWrap($no_items, $this->conf['label.']['no_items.']);
        }

        $this->template['LIST_VIEW'] = $this->cObj->substituteSubpart(
            $this->template['LIST_VIEW'],
            '###HAS_ITEMS###',
            $hasStr
        );
        $this->template['LIST_VIEW'] = $this->cObj->substituteMarkerArrayCached(
            $this->template['LIST_VIEW'],
            $this->labelTranslator
        );
        $this->template['LIST_VIEW'] = $this->cObj->substituteMarker(
            $this->template['LIST_VIEW'],
            '###NO_ITEMS###',
            $no_items
        );
        $this->template['LIST_VIEW'] = $this->cObj->substituteMarker(
            $this->template['LIST_VIEW'],
            '###ITEMS###',
            $items
        );
    }

    /**
     * Setup items in the html-template.
     */
    public function prepareItemSetup()
    {
        $charset = $this->extConf['charset']['upper'];

        // The author name template
        $this->extConf['author_tmpl'] = '###FORENAME### ###SURNAME###';
        if (isset($this->conf['authors.']['template'])) {
            $this->extConf['author_tmpl'] = $this->cObj->stdWrap(
                $this->conf['authors.']['template'],
                $this->conf['authors.']['template.']
            );
        }
        $this->extConf['author_sep'] = ', ';
        if (isset($this->conf['authors.']['separator'])) {
            $this->extConf['author_sep'] = $this->cObj->stdWrap(
                $this->conf['authors.']['separator'],
                $this->conf['authors.']['separator.']
            );
        }
        $this->extConf['author_lfields'] = 'url';
        if (isset($this->conf['authors.']['url_icon_fields'])) {
            $this->extConf['author_lfields'] = GeneralUtility::trimExplode(
                ',',
                $this->conf['authors.']['url_icon_fields'],
                true
            );
        }

        // Acquire author url icon
        $authorsUrlIconFile = trim($this->conf['authors.']['url_icon_file']);
        $imageTag = '';
        if (strlen($authorsUrlIconFile) > 0) {
            $authorsUrlIconFile = $GLOBALS['TSFE']->tmpl->getFileName($authorsUrlIconFile);
            $authorsUrlIconFile = htmlspecialchars($authorsUrlIconFile, ENT_QUOTES, $charset);
            $alt = $this->pi_getLL('img_alt_person', 'Author image', true);
            $imageTag = '<img';
            $imageTag .= ' src="'.$authorsUrlIconFile.'"';
            $imageTag .= ' alt="'.$alt.'"';
            if (is_string($this->conf['authors.']['url_icon_class'])) {
                $imageTag .= ' class="'.$this->conf['authors.']['url_icon_class'].'"';
            }
            $imageTag .= '/>';
        }
        $this->extConf['author_icon_img'] = $imageTag;
    }

    /**
     * Prepares database publication data for displaying.
     *
     * @param array $publication
     * @param array $warnings
     * @param bool  $showHidden
     *
     * @return array The processed publication data array
     */
    public function preparePublicationData($publication, &$warnings = [], $showHidden = false)
    {
        // The error list
        $d_err = [];

        // Prepare processed row data
        $publicationData = $publication;
        foreach ($this->referenceReader->getReferenceFields() as $referenceField) {
            $publicationData[$referenceField] = Utility::filter_pub_html_display(
                $publicationData[$referenceField],
                false,
                $this->extConf['charset']['upper']
            );
        }

        // Preprocess some data

        // File url
        // Check file existance
        $fileUrl = trim(strval($publication['file_url']));
        if (Utility::check_file_nexist($fileUrl)) {
            $publicationData['file_url'] = '';
            $publicationData['_file_nexist'] = true;
        } else {
            $publicationData['_file_nexist'] = false;
        }

        // Bibtype
        $publicationData['bibtype_short'] = $this->referenceReader->allBibTypes[$publicationData['bibtype']];
        $publicationData['bibtype'] = $this->pi_getLL(
            $this->referenceReader->getReferenceTable().'_bibtype_I_'.$publicationData['bibtype'],
            'Unknown bibtype: '.$publicationData['bibtype'],
            true
        );

        // External
        $publicationData['extern'] = (0 == $publication['extern'] ? '' : 'extern');

        // Day
        if (($publication['day'] > 0) && ($publication['day'] <= 31)) {
            $publicationData['day'] = strval($publication['day']);
        } else {
            $publicationData['day'] = '';
        }

        // Month
        if (($publication['month'] > 0) && ($publication['month'] <= 12)) {
            $tme = mktime(0, 0, 0, intval($publication['month']), 15, 2008);
            $publicationData['month'] = $tme;
        } else {
            $publicationData['month'] = '';
        }

        // State
        switch ($publicationData['state']) {
            case 0:
                $publicationData['state'] = '';
                break;
            default:
                $publicationData['state'] = $this->pi_getLL(
                    $this->referenceReader->getReferenceTable().'_state_I_'.$publicationData['state'],
                    'Unknown state: '.$publicationData['state'],
                    true
                );
        }

        // Bool strings
        $b_yes = $this->pi_getLL('label_yes', 'Yes', true);
        $b_no = $this->pi_getLL('label_no', 'No', true);

        // Bool fields
        $publicationData['reviewed'] = ($publication['reviewed'] > 0) ? $b_yes : $b_no;
        $publicationData['in_library'] = ($publication['in_library'] > 0) ? $b_yes : $b_no;

        // Copy field values
        $charset = $this->extConf['charset']['upper'];
        $url_max = 40;
        if (is_numeric($this->conf['max_url_string_length'])) {
            $url_max = intval($this->conf['max_url_string_length']);
        }

        // Iterate through reference fields
        foreach ($this->referenceReader->getReferenceFields() as $referenceField) {
            // Trim string
            $val = trim(strval($publicationData[$referenceField]));

            if (0 == strlen($val)) {
                $publicationData[$referenceField] = $val;
                continue;
            }

            // Check restrictions
            if ($this->checkFieldRestriction('ref', $referenceField, $val)) {
                $publicationData[$referenceField] = '';
                continue;
            }

            // Treat some fields
            switch ($referenceField) {
                case 'file_url':
                case 'web_url':
                case 'web_url2':
                    $publicationData[$referenceField] = Utility::fix_html_ampersand($val);
                    $val = Utility::crop_middle($val, $url_max, $charset);
                    $publicationData[$referenceField.'_short'] = Utility::fix_html_ampersand($val);
                    break;
                case 'DOI':
                    $publicationData[$referenceField] = $val;
                    $publicationData['DOI_url'] = 'http://dx.doi.org/'.$val;
                    break;
                default:
                    $publicationData[$referenceField] = $val;
            }
        }

        // Multi fields
        $multi = [
            'authors' => $this->referenceReader->getAuthorFields(),
        ];
        foreach ($multi as $table => $fields) {
            $elements = &$publicationData[$table];
            if (!is_array($elements)) {
                continue;
            }
            foreach ($elements as &$element) {
                foreach ($fields as $field) {
                    $val = $element[$field];
                    // Check restrictions
                    if (strlen($val) > 0) {
                        if ($this->checkFieldRestriction($table, $field, $val)) {
                            $val = '';
                            $element[$field] = $val;
                        }
                    }
                }
            }
        }

        // Format the author string
        $publicationData['authors'] = $this->getItemAuthorsHtml($publicationData['authors']);

        // store editor's data before processing it
        $cleanEditors = $publicationData['editor'];

        // Editors
        if (strlen($publicationData['editor']) > 0) {
            $editors = Utility::explodeAuthorString($publicationData['editor']);
            $lst = [];
            foreach ($editors as $ed) {
                $app = '';
                if (strlen($ed['forename']) > 0) {
                    $app .= $ed['forename'].' ';
                }
                if (strlen($ed['surname']) > 0) {
                    $app .= $ed['surname'];
                }
                $app = $this->cObj->stdWrap($app, $this->conf['field.']['editor_each.']);
                $lst[] = $app;
            }

            $and = ' '.$this->pi_getLL('label_and', 'and', true).' ';
            $publicationData['editor'] = Utility::implode_and_last(
                $lst,
                ', ',
                $and
            );

            // reset processed data @todo check if the above block may be removed
            $publicationData['editor'] = $cleanEditors;
        }

        // Automatic url
        $order = GeneralUtility::trimExplode(',', $this->conf['auto_url_order'], true);
        $publicationData['auto_url'] = $this->getAutoUrl($publicationData, $order);
        $publicationData['auto_url_short'] = Utility::crop_middle(
            $publicationData['auto_url'],
            $url_max,
            $charset
        );

        // Do data checks
        if ($this->extConf['edit_mode']) {
            $w_cfg = &$this->conf['editor.']['list.']['warnings.'];

            // Local file does not exist
            $type = 'file_nexist';
            if ($w_cfg[$type]) {
                if ($publicationData['_file_nexist']) {
                    $msg = $this->pi_getLL('editor_error_file_nexist');
                    $msg = str_replace('%f', $fileUrl, $msg);
                    $d_err[] = ['type' => $type, 'msg' => $msg];
                }
            }
        }

        $warnings = $d_err;

        return $publicationData;
    }

    /**
     * Returns TRUE if the field/value combination is restricted
     * and should not be displayed.
     *
     * @param string $table
     * @param string $field
     * @param string $value
     * @param bool   $showHidden
     *
     * @return bool TRUE (restricted) or FALSE (not restricted)
     */
    protected function checkFieldRestriction($table, $field, $value, $showHidden = false)
    {
        // No value no restriction
        if (0 == strlen($value)) {
            return false;
        }

        // Field is hidden
        if (!$showHidden && $this->extConf['hide_fields'][$field]) {
            return true;
        }

        // Are there restrictions at all?
        $restrictions = &$this->extConf['restrict'][$table];
        if (!is_array($restrictions) || (0 == count($restrictions))) {
            return false;
        }

        // Check Field restrictions
        if (is_array($restrictions[$field])) {
            $restrictionConfiguration = &$restrictions[$field];

            // Show by default
            $show = true;

            // Hide on 'hide all'
            if ($restrictionConfiguration['hide_all']) {
                $show = false;
            }

            // Hide if any extensions matches
            if ($show && is_array($restrictionConfiguration['hide_ext'])) {
                foreach ($restrictionConfiguration['hide_ext'] as $ext) {
                    // Sanitize input
                    $len = strlen($ext);
                    if (($len > 0) && (strlen($value) >= $len)) {
                        $uext = strtolower(substr($value, -$len));

                        if ($uext == $ext) {
                            $show = false;
                            break;
                        }
                    }
                }
            }

            // Enable if usergroup matches
            if (!$show && isset($restrictionConfiguration['fe_groups'])) {
                $groups = $restrictionConfiguration['fe_groups'];
                if (\Ipf\Bib\Utility\Utility::check_fe_user_groups($groups)) {
                    $show = true;
                }
            }

            // Restricted !
            if (!$show) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the authors string for a publication.
     *
     * @param array $authors
     *
     * @return string
     */
    protected function getItemAuthorsHtml(&$authors)
    {
        $charset = $this->extConf['charset']['upper'];

        $contentObjectBackup = $this->cObj->data;

        // Format the author string$this->
        $separator = $this->extConf['separator'];
        if (isset($separator) && !empty($separator)) {
            $name_separator = $separator;
        } else {
            $name_separator = ' '.$this->pi_getLL('label_and', 'and', true).' ';
        }
        $max_authors = abs(intval($this->extConf['max_authors']));
        $lastAuthor = count($authors) - 1;
        $cutAuthors = false;
        if (($max_authors > 0) && (count($authors) > $max_authors)) {
            $cutAuthors = true;
            if (count($authors) == ($max_authors + 1)) {
                $lastAuthor = $max_authors - 2;
            } else {
                $lastAuthor = $max_authors - 1;
            }
            $name_separator = '';
        }
        $lastAuthor = max($lastAuthor, 0);

        $highlightAuthors = $this->extConf['highlight_authors'] ? true : false;

        $link_fields = $this->extConf['author_sep'];
        $a_sep = $this->extConf['author_sep'];
        $authorTemplate = $this->extConf['author_tmpl'];

        $filter_authors = [];
        if ($highlightAuthors) {
            // Collect filter authors
            foreach ($this->extConf['filters'] as $filter) {
                if (is_array($filter['author']['authors'])) {
                    $filter_authors = array_merge(
                        $filter_authors,
                        $filter['author']['authors']
                    );
                }
            }
        }

        $icon_img = &$this->extConf['author_icon_img'];

        $elements = [];
        // Iterate through authors
        for ($i_a = 0; $i_a <= $lastAuthor; ++$i_a) {
            $author = $authors[$i_a];

            // Init cObj data
            $this->cObj->data = $author;
            $this->cObj->data['url'] = htmlspecialchars_decode($author['url'], ENT_QUOTES);

            // The forename
            $authorForename = trim($author['forename']);
            if (strlen($authorForename) > 0) {
                $authorForename = Utility::filter_pub_html_display(
                    $authorForename,
                    false,
                    $this->extConf['charset']['upper']
                );
                $authorForename = $this->cObj->stdWrap($authorForename, $this->conf['authors.']['forename.']);
            }

            // The surname
            $authorSurname = trim($author['surname']);
            if (strlen($authorSurname) > 0) {
                $authorSurname = Utility::filter_pub_html_display(
                    $authorSurname,
                    false,
                    $this->extConf['charset']['upper']
                );
                $authorSurname = $this->cObj->stdWrap($authorSurname, $this->conf['authors.']['surname.']);
            }

            // The link icon
            $cr_link = false;
            $authorIcon = '';
            foreach ($this->extConf['author_lfields'] as $field) {
                $val = trim(strval($author[$field]));
                if ((strlen($val) > 0) && ('0' != $val)) {
                    $cr_link = true;
                    break;
                }
            }
            if ($cr_link && (strlen($icon_img) > 0)) {
                $wrap = $this->conf['authors.']['url_icon.'];
                if (is_array($wrap)) {
                    if (is_array($wrap['typolink.'])) {
                        $title = $this->pi_getLL('link_author_info', 'Author info', true);
                        $wrap['typolink.']['title'] = $title;
                    }
                    $authorIcon = $this->cObj->stdWrap($icon_img, $wrap);
                }
            }

            // Compose names
            $a_str = str_replace(
                ['###SURNAME###', '###FORENAME###', '###URL_ICON###'],
                [$authorSurname, $authorForename, $authorIcon],
                $authorTemplate
            );

            // apply stdWrap
            $stdWrap = $this->conf['field.']['author.'];
            if (is_array($this->conf['field.'][$bib_str.'.']['author.'])) {
                $stdWrap = $this->conf['field.'][$bib_str.'.']['author.'];
            }
            $a_str = $this->cObj->stdWrap($a_str, $stdWrap);

            // Wrap the filtered authors with a highlighting class on demand
            if ($highlightAuthors) {
                foreach ($filter_authors as $fa) {
                    if ($author['surname'] == $fa['surname']) {
                        if (!$fa['forename'] || ($author['forename'] == $fa['forename'])) {
                            $a_str = $this->cObj->stdWrap($a_str, $this->conf['authors.']['highlight.']);
                            break;
                        }
                    }
                }
            }

            // Append author name
            if (!empty($authorSurname)) {
                $elements[] = $authorSurname.', '.$authorForename;
            }

            // Append 'et al.'
            if ($cutAuthors && ($i_a == $lastAuthor)) {
                // Append et al.
                $etAl = $this->pi_getLL('label_et_al', 'et al.', true);
                $etAl = (strlen($etAl) > 0) ? ' '.$etAl : '';

                if (strlen($etAl) > 0) {
                    // Highlight "et al." on demand
                    if ($highlightAuthors) {
                        $authorsSize = count($authors);
                        for ($j = $lastAuthor + 1; $j < $authorsSize; ++$j) {
                            $a_et = $authors[$j];
                            foreach ($filter_authors as $fa) {
                                if ($a_et['surname'] == $fa['surname']) {
                                    if (!$fa['forename'] || ($a_et['forename'] == $fa['forename'])) {
                                        $wrap = $this->conf['authors.']['highlight.'];
                                        $j = count($authors);
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    $wrap = $this->conf['authors.']['et_al.'];
                    $etAl = $this->cObj->stdWrap($etAl, $wrap);
                    $elements[] = $etAl;
                }
            }
        }

        $res = Utility::implode_and_last($elements, $a_sep, $name_separator);
        // Restore cObj data
        $this->cObj->data = $contentObjectBackup;

        return $res;
    }

    /**
     * Prepares the virtual auto_url from the data and field order.
     *
     * @param array $processedPublicationData The processed publication data
     * @param array $order
     *
     * @return string The generated url
     */
    protected function getAutoUrl($processedPublicationData, $order)
    {
        $url = '';

        foreach ($order as $field) {
            if (0 == strlen($processedPublicationData[$field])) {
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
     * Prepares the cObj->data array for a reference.
     *
     * @param array $pdata
     *
     * @return array The procesed publication data array
     */
    public function prepare_pub_cObj_data($pdata)
    {
        // Item data
        $this->cObj->data = $pdata;
        $data = &$this->cObj->data;
        // Needed since stdWrap/Typolink applies htmlspecialchars to url data
        $data['file_url'] = htmlspecialchars_decode($pdata['file_url'], ENT_QUOTES);
        $data['DOI_url'] = htmlspecialchars_decode($pdata['DOI_url'], ENT_QUOTES);
        $data['auto_url'] = htmlspecialchars_decode($pdata['auto_url'], ENT_QUOTES);
    }

    /**
     * Returns the file url icon.
     *
     * @param array $unprocessedDatabaseData The unprocessed db data
     * @param array $processedDatabaseData   The processed db data
     *
     * @return string The html icon img tag
     */
    protected function getFileUrlIcon($unprocessedDatabaseData, $processedDatabaseData)
    {
        $fileSources = &$this->icon_src['files'];

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
            $imageTag = '<img src="'.$src.'"';
            $imageTag .= ' alt="'.$alt.'"';
            $fileIconClass = $this->conf['enum.']['file_icon_class'];
            if (is_string($fileIconClass)) {
                $imageTag .= ' class="'.$fileIconClass.'"';
            }
            $imageTag .= '/>';
        } else {
            $imageTag = '&nbsp;';
        }

        $wrap = $this->conf['enum.']['file_icon_image.'];
        if (is_array($wrap)) {
            if (is_array($wrap['typolink.'])) {
                $title = $this->get_ll('link_get_file', 'Get file', true);
                $wrap['typolink.']['title'] = $title;
            }
            $imageTag = $this->cObj->stdWrap($imageTag, $wrap);
        }

        return $imageTag;
    }

    /**
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
     * @param int $publicationId
     *
     * @return bool TRUE (allowed) FALSE (restricted)
     */
    protected function checkFEauthorRestriction($publicationId)
    {
        /** @var \TYPO3\CMS\Backend\FrontendBackendUserAuthentication $beUser */
        $beUser = $GLOBALS['BE_USER'];

        // always allow BE users with sufficient rights
        if (is_object($beUser)) {
            if ($beUser->isAdmin()) {
                return true;
            }
            if ($beUser->check('tables_modify', $this->referenceReader->getReferenceTable())) {
                return true;
            }
        }

        // Is FE-user editing only for own records enabled? (set via TS)
        if (isset($this->conf['FE_edit_own_records']) && 0 != $this->conf['FE_edit_own_records']) {
            // query all authors of this publication
            $res = $this->getDatabaseConnection()->exec_SELECTquery(
                'fe_user_id',
                'tx_bib_domain_model_author as a, tx_bib_domain_model_authorships as m',
                'a.uid = m.author_id AND m.pub_id = '.$publicationId
            );

            while ($row = $this->getDatabaseConnection()->sql_fetch_row($res)) {
                // check if author == FE user and allow editing
                if ($row[0] == $GLOBALS['TSFE']->fe_user->user[$GLOBALS['TSFE']->fe_user->userid_column]) {
                    return true;
                }
            }
            $this->getDatabaseConnection()->sql_free_result($res);

            return false;
        }

        // default behavior, FE user can edit all records
        return true;
    }

    /**
     * Returns the edit button.
     *
     * @param array $publication
     *
     * @return string
     */
    protected function getEditManipulator($publication)
    {
        $label = $this->pi_getLL('manipulators_edit', 'Edit', true);
        $res = $this->get_link(
            '',
            [
                'action' => [
                    'edit' => 1,
                ],
                'uid' => $publication['uid'],
            ],
            true,
            [
                'title' => $label,
                'class' => 'edit-record',
            ]
        );

        $res = $this->cObj->stdWrap($res, $this->conf['editor.']['list.']['manipulators.']['edit.']);

        return $res;
    }

    /**
     * Returns the hide button.
     *
     * @param array $publication
     *
     * @return string
     */
    protected function getHideManipulator($publication)
    {
        if (0 == $publication['hidden']) {
            $label = $this->pi_getLL('manipulators_hide', 'Hide', true);
            $class = 'hide';
        } else {
            $label = $this->pi_getLL('manipulators_reveal', 'Reveal', true);
            $class = 'reveal';
        }

        $action = [$class => 1];

        $res = $this->get_link(
            '',
            [
                'action' => $action,
                'uid' => $publication['uid'],
            ],
            true,
            [
                'title' => $label,
                'class' => $class.'-record',
            ]
        );

        return $this->cObj->stdWrap($res, $this->conf['editor.']['list.']['manipulators.']['hide.']);
    }

    /**
     * Returns the html interpretation of the publication
     * item as it is defined in the html template.
     *
     * @param array  $publicationData
     * @param string $template
     *
     * @return string HTML string for a single item in the list view
     */
    protected function getItemHtml($publicationData, $template)
    {
        $translator = [];

        $bib_str = $publicationData['bibtype_short'];
        $all_base = 'rnd'.strval(rand()).'rnd';
        $all_wrap = $all_base;

        // Prepare the translator
        // Remove empty field marker from the template
        $fields = $this->referenceReader->getPublicationFields();
        $fields[] = 'file_url_short';
        $fields[] = 'web_url_short';
        $fields[] = 'web_url2_short';
        $fields[] = 'auto_url';
        $fields[] = 'auto_url_short';

        foreach ($fields as $field) {
            $upStr = strtoupper($field);
            $tkey = '###'.$upStr.'###';
            $hasStr = '';
            $translator[$tkey] = '';

            $val = strval($publicationData[$field]);

            if (strlen($val) > 0) {
                // Wrap default or by bibtype
                $stdWrap = $this->conf['field.'][$field.'.'];

                if (is_array($this->conf['field.'][$bib_str.'.'][$field.'.'])) {
                    $stdWrap = $this->conf['field.'][$bib_str.'.'][$field.'.'];
                }

                if (isset($stdWrap['single_view_link'])) {
                    $val = $this->get_link($val, ['show_uid' => strval($publicationData['uid'])]);
                }
                $val = $this->cObj->stdWrap($val, $stdWrap);

                if (strlen($val) > 0) {
                    $hasStr = ['', ''];
                    $translator[$tkey] = $val;
                }
            }

            $template = $this->cObj->substituteSubpart($template, '###HAS_'.$upStr.'###', $hasStr);
        }

        // Reference wrap
        $all_wrap = $this->cObj->stdWrap($all_wrap, $this->conf['reference.']);

        // Embrace hidden references with wrap
        if ((0 != $publicationData['hidden']) && is_array($this->conf['editor.']['list.']['hidden.'])) {
            $all_wrap = $this->cObj->stdWrap($all_wrap, $this->conf['editor.']['list.']['hidden.']);
        }

        $template = $this->cObj->substituteMarkerArrayCached($template, $translator);
        $template = $this->cObj->substituteMarkerArrayCached($template, $this->labelTranslator);

        // Wrap elements with an anchor
        $url_wrap = ['', ''];
        if (strlen($publicationData['file_url']) > 0) {
            $url_wrap = $this->cObj->typolinkWrap(['parameter' => $publicationData['auto_url']]);
        }
        $template = $this->cObj->substituteSubpart($template, '###URL_WRAP###', $url_wrap);

        $all_wrap = explode($all_base, $all_wrap);
        $template = $this->cObj->substituteSubpart($template, '###REFERENCE_WRAP###', $all_wrap);

        // remove empty divs
        $template = preg_replace("/<div[^>]*>[\s\r\n]*<\/div>/", PHP_EOL, $template);
        // remove multiple line breaks
        $template = preg_replace("/\n+/", PHP_EOL, $template);

        return $template;
    }

    /**
     * This loads the single view.
     *
     * @return string The single view
     */
    protected function singleView()
    {
        /** @var \Ipf\Bib\View\SingleView $singleView */
        $singleView = GeneralUtility::makeInstance(\Ipf\Bib\View\SingleView::class);
        $singleView->initialize($this);

        return $singleView->singleView();
    }

    /**
     * This loads the editor view.
     *
     * @return string The editor view
     */
    protected function editorView()
    {
        /** @var \Ipf\Bib\View\EditorView $editorView */
        $editorView = GeneralUtility::makeInstance(\Ipf\Bib\View\EditorView::class);
        $editorView->initialize($this);

        return $editorView->editor_view();
    }

    /**
     * This switches to the requested dialog.
     *
     * @return string The requested dialog
     */
    protected function dialogView()
    {
        /** @var FlashMessageQueue $flashMessageQueue */
        $flashMessageQueue = GeneralUtility::makeInstance(FlashMessageQueue::class, 'tx_bib');

        $content = '';
        switch ($this->extConf['dialog_mode']) {
            case self::DIALOG_EXPORT:
                $content .= $this->exportDialog();
                break;
            case self::DIALOG_IMPORT:
                $content .= $this->importDialog();
                break;
            default:
                /** @var \Ipf\Bib\View\EditorView $editorView */
                $editorView = GeneralUtility::makeInstance(\Ipf\Bib\View\EditorView::class);
                $editorView->initialize($this);
                $content .= $editorView->dialogView();
        }
        $content .= $flashMessageQueue->renderFlashMessages();
        $content .= '<p>';
        $content .= $this->get_link($this->pi_getLL('link_back_to_list'));
        $content .= '</p>';

        return $content;
    }

    /**
     * The export dialog.
     *
     * @return string The export dialog
     */
    protected function exportDialog()
    {
        /** @var FlashMessageQueue $flashMessageQueue */
        $flashMessageQueue = GeneralUtility::makeInstance(FlashMessageQueue::class, 'tx_bib');

        $mode = $this->extConf['export_navi']['do'];
        $content = '<h2>'.$this->pi_getLL('export_title').'</h2>';

        $label = '';
        switch ($mode) {
            case 'bibtex':
                $exporterClass = \Ipf\Bib\Utility\Exporter\BibTexExporter::class;
                $label = 'export_bibtex';
                break;
            case 'xml':
                $exporterClass = \Ipf\Bib\Utility\Exporter\XmlExporter::class;
                $label = 'export_xml';
                break;
            default:
                /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
                $message = GeneralUtility::makeInstance(
                    \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                    'Unknown export mode',
                    '',
                    FlashMessage::ERROR
                );
                $flashMessageQueue->addMessage($message);
        }

        /** @var \Ipf\Bib\Utility\Exporter\Exporter $exporter */
        $exporter = GeneralUtility::makeInstance($exporterClass);
        $label = $this->pi_getLL($label, $label, true);

        if ($exporter instanceof \Ipf\Bib\Utility\Exporter\Exporter) {
            try {
                $exporter->initialize($this);
            } catch (\Exception $e) {
                $message = GeneralUtility::makeInstance(
                    \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                    $e->getMessage(),
                    $label,
                    FlashMessage::ERROR
                );
                $flashMessageQueue->addMessage($message);
            }

            $dynamic = $this->conf['export.']['dynamic'] ? true : false;

            if ($this->extConf['dynamic']) {
                $dynamic = true;
            }

            $exporter->setDynamic($dynamic);

            try {
                $exporter->export();
                if ($dynamic) {
                    $this->dumpExportDataAndExit($exporter);
                } else {
                    $content .= $this->createLinkToExportFile($exporter);
                }
            } catch (\TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException $e) {
                $message = GeneralUtility::makeInstance(
                    \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                    $e->getMessage(),
                    '',
                    FlashMessage::ERROR
                );
                $flashMessageQueue->addMessage($message);
            }
        }

        return $content;
    }

    /**
     * @param \Ipf\Bib\Utility\Exporter\Exporter $exporter
     */
    protected function dumpExportDataAndExit($exporter)
    {
        // Dump the export data and exit
        $exporterFileName = $exporter->getFileName();
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="'.$exporterFileName.'"');
        header('Cache-Control: no-cache, must-revalidate');
        echo $exporter->getData();
        exit();
    }

    /**
     * @param \Ipf\Bib\Utility\Exporter\Exporter $exporter
     *
     * @return string
     */
    protected function createLinkToExportFile($exporter)
    {
        $link = $this->cObj->getTypoLink(
            $exporter->getFileName(),
            $exporter->getRelativeFilePath()
        );
        $content = '<ul><li><div>';
        $content .= $link;
        if ($exporter->getIsNewFile()) {
            $content .= ' ('.$this->pi_getLL('export_file_new').')';
        }
        $content .= '</div></li>';
        $content .= '</ul>';

        return $content;
    }

    /**
     * The import dialog.
     *
     * @return string The import dialog
     */
    protected function importDialog()
    {
        /** @var FlashMessageQueue $flashMessageQueue */
        $flashMessageQueue = GeneralUtility::makeInstance(FlashMessageQueue::class, 'tx_bib');

        $content = '<h2>'.$this->pi_getLL('import_title').'</h2>';
        $mode = $this->piVars['import'];

        if ((\Ipf\Bib\Utility\Importer\Importer::IMP_BIBTEX == $mode) || (\Ipf\Bib\Utility\Importer\Importer::IMP_XML == $mode)) {
            /** @var \Ipf\Bib\Utility\Importer\Importer $importer */
            $importer = false;

            switch ($mode) {
                case \Ipf\Bib\Utility\Importer\Importer::IMP_BIBTEX:
                    /** @var \Ipf\Bib\Utility\Importer\Importer $importer */
                    $importer = GeneralUtility::makeInstance(\Ipf\Bib\Utility\Importer\BibTexImporter::class);
                    break;
                case \Ipf\Bib\Utility\Importer\Importer::IMP_XML:
                    /** @var \Ipf\Bib\Utility\Importer\Importer $importer */
                    $importer = GeneralUtility::makeInstance(\Ipf\Bib\Utility\Importer\XmlImporter::class);
                    break;
            }

            $importer->initialize($this);
            try {
                $content .= $importer->import();
            } catch (\Exception $e) {
                /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
                $message = GeneralUtility::makeInstance(
                    \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                    $e->getMessage(),
                    '',
                    FlashMessage::ERROR
                );
                $flashMessageQueue->addMessage($message);
            }
        } else {
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
            $message = GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                'Unknown import mode',
                '',
                FlashMessage::ERROR
            );
            $flashMessageQueue->addMessage($message);
        }

        return $content;
    }

    /**
     * Returns the error message wrapped into a message container.
     *
     * @deprecated Since 1.3.0 will be removed in 1.5.0. Use TYPO3 Flash Messaging Service
     *
     * @param string $errorString
     *
     * @return string The wrapper error message
     */
    public function errorMessage($errorString)
    {
        GeneralUtility::logDeprecatedFunction();
        $errorMessage = '<div class="'.$this->prefixShort.'-warning_box">';
        $errorMessage .= '<h3>'.$this->prefix_pi1.' error</h3>';
        $errorMessage .= '<div>'.$errorString.'</div>';
        $errorMessage .= '</div>';

        return $errorMessage;
    }

    /**
     * Same as get_link_url() but for edit mode urls.
     *
     * @param array $linkVariables
     * @param bool  $autoCache
     * @param bool  $currentRecord
     *
     * @return string The url
     */
    public function get_edit_link_url($linkVariables = [], $autoCache = true, $currentRecord = true)
    {
        $parametersToBeKept = ['uid', 'editor_mode', 'editor'];
        foreach ($parametersToBeKept as $parameter) {
            if (is_string($this->piVars[$parameter]) || is_array($this->piVars[$parameter]) || is_numeric($this->piVars[$parameter])) {
                $linkVariables[$parameter] = $this->piVars[$parameter];
            }
        }

        return $this->get_link_url($linkVariables, $autoCache, $currentRecord);
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/pi1/class.tx_bib_pi1.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/pi1/class.tx_bib_pi1.php'];
}
