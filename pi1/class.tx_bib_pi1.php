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
use Ipf\Bib\View\View;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Publication List' for the 'bib' extension.
 */
class tx_bib_pi1 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    public $prefixId = 'tx_bib_pi1';

    // citeid generation modes
    public $scriptRelPath = 'pi1/class.tx_bib_pi1.php';
    public $extKey = 'bib';
    public $pi_checkCHash = false;

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
     * Statistics.
     *
     * @var array
     */
    private $stat = [];

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
        $this->pi_initPIflexForm();

        $this->flexFormData = $this->cObj->data['pi_flexform'];
        $this
            ->initializeFluidTemplate()
            ->includeCss();

        $storagePid = $this->getStoragePid($conf);

        $configuration = $this->getExtensionConfiguration();
        $configuration = $this->getTypoScriptConfiguration($configuration);
        $configuration = $this->getFrontendEditorConfiguration($configuration);
        $configuration = $this->getPidList($storagePid, $configuration);
        $configuration = $this->makeAdjustments($configuration);
        $configuration = $this->setupNavigations($configuration);

        // allow FE-user editing from special groups (set via TS)
        $validFrontendUser = $this->isValidFrontendUser($this->isValidBackendUser());

        $configuration['edit_mode'] = (($this->isValidBackendUser() || $validFrontendUser) && $configuration['editor']['enabled']);
        $configuration = $this->initializeRestrictions($configuration);
        $configuration = $this->initializeFilters($configuration);
        $configuration = $this->showHiddenEntries($configuration);
        $configuration = $this->editAction($configuration);
        $configuration = $this->exportAction($configuration);
        $configuration = $this->detailAction($configuration);
        $configuration = $this->callAuthorNavigationHook($configuration);
        $configuration = $this->getYearNavigation($configuration);
        $configuration = $this->getPageNavigation($configuration);
        $configuration = $this->getSortFilter($configuration);
        $configuration = $this->disableNavigationOnDemand($configuration);

        $this->referenceReader = GeneralUtility::makeInstance(\Ipf\Bib\Utility\ReferenceReader::class, $configuration);

        $this->determineNumberOfPublications();

        $this->extConf = $configuration;

        // Switch to requested view mode
        try {
            return $this->finalize($this->switchToRequestedViewMode($configuration), $configuration);
        } catch (\Exception $e) {
            return $this->finalize($e->getMessage().'<br>'.$e->getTraceAsString(), $configuration);
        }
    }

    /**
     * @return \tx_bib_pi1
     */
    protected function includeCss()
    {
        /** @var \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        $pageRenderer->addCssFile('EXT:bib/Resources/Public/Css/bib.css');

        return $this;
    }

    /**
     * @return \tx_bib_pi1
     */
    protected function initializeFluidTemplate()
    {
        /* @var \TYPO3\CMS\Fluid\View\StandaloneView $template */
        $view = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:bib/Resources/Private/Templates/');
        $this->view = $view;

        return $this;
    }

    /**
     * Retrieve and optimize Extension configuration.
     */
    protected function getExtensionConfiguration(): array
    {
        $configuration = [];

        // Initialize current configuration
        $configuration['link_vars'] = [];
        $configuration['sub_page'] = [];

        $configuration['view_mode'] = View::VIEW_LIST;
        $configuration['debug'] = $this->conf['debug'] ? true : false;
        $configuration['ce_links'] = $this->conf['ce_links'] ? true : false;

        // Retrieve general FlexForm values
        $fSheet = 'sDEF';
        $configuration['d_mode'] = $this->pi_getFFvalue($this->flexFormData, 'display_mode', $fSheet);
        $configuration['show_nav_search'] = $this->pi_getFFvalue($this->flexFormData, 'show_search', $fSheet);
        $configuration['show_nav_author'] = $this->pi_getFFvalue($this->flexFormData, 'show_authors', $fSheet);
        $configuration['show_nav_pref'] = $this->pi_getFFvalue($this->flexFormData, 'show_pref', $fSheet);
        $configuration['sub_page']['ipp'] = $this->pi_getFFvalue($this->flexFormData, 'items_per_page', $fSheet);
        $configuration['max_authors'] = $this->pi_getFFvalue($this->flexFormData, 'max_authors', $fSheet);
        $configuration['split_bibtypes'] = $this->pi_getFFvalue($this->flexFormData, 'split_bibtypes', $fSheet);
        $configuration['stat_mode'] = $this->pi_getFFvalue($this->flexFormData, 'stat_mode', $fSheet);
        $configuration['show_nav_export'] = $this->pi_getFFvalue($this->flexFormData, 'export_mode', $fSheet);
        $configuration['date_sorting'] = $this->pi_getFFvalue($this->flexFormData, 'date_sorting', $fSheet);
        $configuration['sorting'] = $this->pi_getFFvalue($this->flexFormData, 'sorting', $fSheet);
        $configuration['search_fields'] = $this->pi_getFFvalue($this->flexFormData, 'search_fields', $fSheet);
        $configuration['separator'] = $this->pi_getFFvalue($this->flexFormData, 'separator', $fSheet);
        $configuration['editor_stop_words'] = $this->pi_getFFvalue($this->flexFormData, 'editor_stop_words', $fSheet);
        $configuration['title_stop_words'] = $this->pi_getFFvalue($this->flexFormData, 'title_stop_words', $fSheet);
        $show_fields = $this->pi_getFFvalue($this->flexFormData, 'show_textfields', $fSheet);
        $show_fields = explode(',', $show_fields);

        $configuration['hide_fields'] = [
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
                $configuration['hide_fields'][$field] = 0;
            }
        }

        return $configuration;
    }

    protected function getTypoScriptConfiguration(array $configuration): array
    {
        if ((int) $configuration['d_mode'] < 0) {
            $configuration['d_mode'] = (int) $this->conf['display_mode'];
        }

        if ((int) $configuration['date_sorting'] < 0) {
            $configuration['date_sorting'] = (int) $this->conf['date_sorting'];
        }

        if ((int) $configuration['stat_mode'] < 0) {
            $configuration['stat_mode'] = (int) $this->conf['statNav.']['mode'];
        }

        if ((int) $configuration['sub_page']['ipp'] < 0) {
            $configuration['sub_page']['ipp'] = (int) $this->conf['items_per_page'];
        }

        if ((int) $configuration['max_authors'] < 0) {
            $configuration['max_authors'] = (int) $this->conf['max_authors'];
        }

        return $configuration;
    }

    /**
     * Get configuration from FlexForms.
     */
    protected function getFrontendEditorConfiguration(array $configuration): array
    {
        $flexFormSheet = 's_fe_editor';
        $configuration['editor']['enabled'] = $this->pi_getFFvalue($this->flexFormData, 'enable_editor', $flexFormSheet);
        $configuration['editor']['citeid_gen_new'] = $this->pi_getFFvalue($this->flexFormData, 'citeid_gen_new', $flexFormSheet);
        $configuration['editor']['citeid_gen_old'] = $this->pi_getFFvalue($this->flexFormData, 'citeid_gen_old', $flexFormSheet);
        $configuration['editor']['clear_page_cache'] = $this->pi_getFFvalue($this->flexFormData, 'clear_cache', $flexFormSheet);

        // Overwrite editor configuration from TSsetup
        if (is_array($this->conf['editor.'])) {
            $editorOverride = &$this->conf['editor.'];
            if (array_key_exists('enabled', $editorOverride)) {
                $configuration['editor']['enabled'] = $editorOverride['enabled'] ? true : false;
            }
            if (array_key_exists('citeid_gen_new', $editorOverride)) {
                $configuration['editor']['citeid_gen_new'] = $editorOverride['citeid_gen_new'] ? true : false;
            }
            if (array_key_exists('citeid_gen_old', $editorOverride)) {
                $configuration['editor']['citeid_gen_old'] = $editorOverride['citeid_gen_old'] ? true : false;
            }
        }

        return $configuration;
    }

    /**
     * Get storage pages.
     */
    protected function getStoragePid(array $configuration): array
    {
        $pidList = [];
        if (isset($configuration['pid_list'])) {
            $pidList = GeneralUtility::intExplode(',', $configuration['pid_list']);
        }
        if (isset($this->cObj->data['pages'])) {
            $tmp = GeneralUtility::intExplode(',', $this->cObj->data['pages']);
            $pidList = array_merge($pidList, $tmp);
        }

        return $pidList;
    }

    /**
     * Retrieves and optimizes the pid list and passes it to the referenceReader.
     */
    protected function getPidList(array $pidList, array $configuration): array
    {
        $pidList = array_unique($pidList);
        if (in_array(0, $pidList)) {
            unset($pidList[array_search(0, $pidList)]);
        }

        if (count($pidList) > 0) {
            // Determine the recursive depth
            $configuration['recursive'] = $this->cObj->data['recursive'];
            if (isset($this->conf['recursive'])) {
                $configuration['recursive'] = $this->conf['recursive'];
            }
            $configuration['recursive'] = (int) $configuration['recursive'];

            $pidList = $this->pi_getPidList(implode(',', $pidList), $configuration['recursive']);

            $pidList = GeneralUtility::intExplode(',', $pidList);

            // Due to how recursive prepends the folders
            $configuration['pid_list'] = array_reverse($pidList);
        } else {
            // Use current page as storage
            $configuration['pid_list'] = [(int) $GLOBALS['TSFE']->id];
        }

        return $configuration;
    }

    /**
     * Make adjustments to different modes.
     *
     * @todo find a better method name or split up
     */
    protected function makeAdjustments(array $configuration): array
    {
        switch ($configuration['d_mode']) {
            case \Ipf\Bib\Modes\Display::D_SIMPLE:
            case \Ipf\Bib\Modes\Display::D_Y_SPLIT:
            case \Ipf\Bib\Modes\Display::D_Y_NAV:
                break;
            default:
                $configuration['d_mode'] = \Ipf\Bib\Modes\Display::D_SIMPLE;
        }
        switch ($configuration['date_sorting']) {
            case \Ipf\Bib\Modes\Sort::SORT_DESC:
            case \Ipf\Bib\Modes\Sort::SORT_ASC:
                break;
            default:
                $configuration['date_sorting'] = \Ipf\Bib\Modes\Sort::SORT_DESC;
        }
        switch ($configuration['stat_mode']) {
            case \Ipf\Bib\Modes\Statistics::STAT_NONE:
            case \Ipf\Bib\Modes\Statistics::STAT_TOTAL:
            case \Ipf\Bib\Modes\Statistics::STAT_YEAR_TOTAL:
                break;
            default:
                $configuration['stat_mode'] = \Ipf\Bib\Modes\Statistics::STAT_TOTAL;
        }
        $configuration['sub_page']['ipp'] = max((int) $configuration['sub_page']['ipp'], 0);
        $configuration['max_authors'] = max((int) $configuration['max_authors'], 0);

        return $configuration;
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
    protected function setupNavigations(array $configuration): array
    {
        // Year Navigation
        if (\Ipf\Bib\Modes\Display::D_Y_NAV === (int) $configuration['d_mode']) {
            $configuration = $this->enableYearNavigation($configuration);
        }

        // Author Navigation
        if ($configuration['show_nav_author']) {
            $configuration = $this->initializeAuthorNavigation($configuration);
        }

        // Statistic Navigation
        if (\Ipf\Bib\Modes\Statistics::STAT_NONE !== (int) $configuration['stat_mode']) {
            $configuration = $this->enableStatisticsNavigation($configuration);
        }

        // Export navigation
        if ($configuration['show_nav_export']) {
            $configuration = $this->getExportNavigation($configuration);
        }

        return $configuration;
    }

    /**
     * Returns an instance of a navigation bar class.
     */
    protected function getAndInitializeNavigationInstance(string $type, array $configuration): \Ipf\Bib\Navigation\Navigation
    {
        /** @var \Ipf\Bib\Navigation\Navigation $navigationInstance */
        $navigationInstance = GeneralUtility::makeInstance('Ipf\\Bib\\Navigation\\'.$type, $configuration);

        return $navigationInstance;
    }

    protected function enableYearNavigation(array $configuration): array
    {
        $configuration['show_nav_year'] = true;

        return $configuration;
    }

    protected function initializeAuthorNavigation(array $configuration): array
    {
        $configuration['dynamic'] = true;
        $configuration['author_navi'] = [];
        $configuration['author_navi']['obj'] = $this->getAndInitializeNavigationInstance('AuthorNavigation', $configuration);
        $configuration['author_navi']['obj']->hook_init($configuration);

        return $configuration;
    }

    protected function enableStatisticsNavigation(array $configuration): array
    {
        $configuration['show_nav_stat'] = true;

        return $configuration;
    }

    /**
     * Builds the export navigation.
     */
    protected function getExportNavigation(array $configuration): array
    {
        $configuration['export_navi'] = [];

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
        $configuration['export_navi']['modes'] = [];
        $exportModules = $configuration['export_navi']['modes'];
        if (is_array($modes) && $validFrontendUser) {
            $availableExportModes = ['bibtex', 'xml'];
            $exportModules = array_intersect($availableExportModes, $modes);
        }

        if (0 === count($exportModules)) {
            $configuration['show_nav_export'] = false;
        } else {
            $exportPluginVariables = trim($this->piVars['export']);
            if ((strlen($exportPluginVariables) > 0) && in_array($exportPluginVariables, $exportModules)) {
                $configuration['export_navi']['do'] = $exportPluginVariables;
            }
        }

        return $configuration;
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
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
                    \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE
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
     * This initializes field restrictions.
     */
    protected function initializeRestrictions(array $configuration): array
    {
        $configuration['restrict'] = [];

        if (!is_array($this->conf['restrictions.'])) {
            return $configuration;
        }

        // This is a nested array containing fields
        // that may have restrictions
        $fields = [
            'ref' => [],
            'author' => [],
        ];
        $allFields = [];
        // Acquire field configurations
        foreach ($this->conf['restrictions.'] as $table => $data) {
            if (is_array($data)) {
                $table = substr($table, 0, -1);

                switch ($table) {
                    case 'ref':
                        $allFields = \Ipf\Bib\Utility\ReferenceReader::$referenceFields;
                        break;
                    case 'authors':
                        $allFields = \Ipf\Bib\Utility\ReferenceReader::$authorFields;
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
            $configuration['restrict'][$table] = [];
            $d_table = $table.'.';
            foreach ($tableFields as $field) {
                $d_field = $field.'.';
                $rcfg = $this->conf['restrictions.'][$d_table][$d_field];

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
                    $configuration['restrict'][$table][$field] = [
                        'hide_all' => $all,
                        'hide_ext' => $ext,
                        'fe_groups' => $groups,
                    ];
                }
            }
        }

        return $configuration;
    }

    /**
     * This initializes all filters before the browsing filter.
     */
    protected function initializeFilters(array $configuration): array
    {
        $configuration['filters'] = [];
        $configuration = $this->initializeFlexformFilter($configuration);

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

        return $configuration;
    }

    /**
     * This initializes filter array from the flexform.
     */
    protected function initializeFlexformFilter(array $configuration): array
    {
        // Create and select the flexform filter
        $configuration['filters']['flexform'] = [];

        // Filtersheet and flexform data into variable
        $this->flexForm = $this->cObj->data['pi_flexform'];
        $this->flexFormFilterSheet = 's_filter';

        $configuration = $this->initializePidFilter($configuration);
        $configuration = $this->initializeYearFilter($configuration);
        $configuration = $this->initializeAuthorFilter($configuration);
        $configuration = $this->initializeStateFilter($configuration);
        $configuration = $this->initializeBibliographyTypeFilter($configuration);
        $configuration = $this->initializeOriginFilter($configuration);
        $configuration = $this->initializeReviewFilter($configuration);
        $configuration = $this->initializeInLibraryFilter($configuration);
        $configuration = $this->initializeBorrowedFilter($configuration);
        $configuration = $this->initializeCiteIdFilter($configuration);
        $configuration = $this->initializeTagFilter($configuration);
        $configuration = $this->initializeKeywordsFilter($configuration);
        $configuration = $this->initializeGeneralKeywordSearch($configuration);

        return $configuration;
    }

    protected function initializePidFilter(array $configuration): array
    {
        $configuration['filters']['flexform']['pid'] = $configuration['pid_list'];

        return $configuration;
    }

    protected function initializeYearFilter(array $configuration): array
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
                        $flexFormFilter['years'][] = (int) $year;
                    }
                } else {
                    $range = [];
                    $elms = GeneralUtility::trimExplode('-', $year, false);
                    if (is_numeric($elms[0])) {
                        $range['from'] = (int) $elms[0];
                    }
                    if (is_numeric($elms[1])) {
                        $range['to'] = (int) $elms[1];
                    }
                    if (count($range) > 0) {
                        $flexFormFilter['ranges'][] = $range;
                    }
                }
            }
            if ((count($flexFormFilter['years']) + count($flexFormFilter['ranges'])) > 0) {
                $configuration['filters']['flexform']['year'] = $flexFormFilter;
            }
        }

        return $configuration;
    }

    protected function initializeAuthorFilter(array $configuration): array
    {
        $configuration['highlight_authors'] = $this->pi_getFFvalue(
            $this->flexForm,
            'highlight_authors',
            $this->flexFormFilterSheet
        );

        if (0 !== (int) $this->pi_getFFvalue($this->flexForm, 'enable_author', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['authors'] = [];
            $flexFormFilter['rule'] = $this->pi_getFFvalue($this->flexForm, 'author_rule', $this->flexFormFilterSheet);
            $flexFormFilter['rule'] = (int) $flexFormFilter['rule'];

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
                $configuration['filters']['flexform']['author'] = $flexFormFilter;
            }
        }

        return $configuration;
    }

    protected function initializeStateFilter(array $configuration): array
    {
        if (0 !== (int) $this->pi_getFFvalue($this->flexForm, 'enable_state', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['states'] = [];
            $states = (int) $this->pi_getFFvalue($this->flexForm, 'states', $this->flexFormFilterSheet);

            $j = 1;
            $referenceReaderStateSize = count(\Ipf\Bib\Utility\ReferenceReader::$allStates);
            for ($i = 0; $i < $referenceReaderStateSize; ++$i) {
                if ($states & $j) {
                    $flexFormFilter['states'][] = $i;
                }
                $j = $j * 2;
            }
            if (count($flexFormFilter['states']) > 0) {
                $configuration['filters']['flexform']['state'] = $flexFormFilter;
            }
        }

        return $configuration;
    }

    protected function initializeBibliographyTypeFilter(array $configuration): array
    {
        if (0 !== (int) $this->pi_getFFvalue($this->flexForm, 'enable_bibtype', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['types'] = [];
            $types = $this->pi_getFFvalue($this->flexForm, 'bibtypes', $this->flexFormFilterSheet);
            $types = explode(',', $types);
            foreach ($types as $type) {
                $type = (int) $type;
                if (($type >= 0) && ($type < count(\Ipf\Bib\Utility\ReferenceReader::$allBibTypes))) {
                    $flexFormFilter['types'][] = $type;
                }
            }
            if (count($flexFormFilter['types']) > 0) {
                $configuration['filters']['flexform']['bibtype'] = $flexFormFilter;
            }
        }

        return $configuration;
    }

    protected function initializeOriginFilter(array $configuration): array
    {
        if (0 !== (int) $this->pi_getFFvalue($this->flexForm, 'enable_origin', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['origin'] = $this->pi_getFFvalue($this->flexForm, 'origins', $this->flexFormFilterSheet);

            if (1 === (int) $flexFormFilter['origin']) {
                // Legacy value
                $flexFormFilter['origin'] = 0;
            } else {
                if (2 === (int) $flexFormFilter['origin']) {
                    // Legacy value
                    $flexFormFilter['origin'] = 1;
                }
            }

            $configuration['filters']['flexform']['origin'] = $flexFormFilter;
        }

        return $configuration;
    }

    protected function initializeReviewFilter(array $configuration): array
    {
        if (0 !== (int) $this->pi_getFFvalue($this->flexForm, 'enable_reviewes', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['value'] = $this->pi_getFFvalue($this->flexForm, 'reviewes', $this->flexFormFilterSheet);
            $configuration['filters']['flexform']['reviewed'] = $flexFormFilter;
        }

        return $configuration;
    }

    protected function initializeInLibraryFilter(array $configuration): array
    {
        if (0 !== (int) $this->pi_getFFvalue($this->flexForm, 'enable_in_library', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['value'] = $this->pi_getFFvalue($this->flexForm, 'in_library', $this->flexFormFilterSheet);
            $configuration['filters']['flexform']['in_library'] = $flexFormFilter;
        }

        return $configuration;
    }

    protected function initializeBorrowedFilter(array $configuration): array
    {
        if (0 !== (int) $this->pi_getFFvalue($this->flexForm, 'enable_borrowed', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['value'] = $this->pi_getFFvalue($this->flexForm, 'borrowed', $this->flexFormFilterSheet);
            $configuration['filters']['flexform']['borrowed'] = $flexFormFilter;
        }

        return $configuration;
    }

    protected function initializeCiteIdFilter(array $configuration): array
    {
        if (0 !== (int) $this->pi_getFFvalue($this->flexForm, 'enable_citeid', $this->flexFormFilterSheet)) {
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
                $configuration['filters']['flexform']['citeid'] = $flexFormFilter;
            }
        }

        return $configuration;
    }

    protected function initializeTagFilter(array $configuration): array
    {
        if ($this->pi_getFFvalue($this->flexForm, 'enable_tags', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['rule'] = $this->pi_getFFvalue($this->flexForm, 'tags_rule', $this->flexFormFilterSheet);
            $flexFormFilter['rule'] = (int) $flexFormFilter['rule'];
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
                foreach ($words as $word) {
                    $word = \Ipf\Bib\Utility\ReferenceReader::getSearchTerm($word);
                }
                $flexFormFilter['words'] = $words;
                $this->extConf['filters']['flexform']['tags'] = $flexFormFilter;
            }
        }

        return $configuration;
    }

    protected function initializeKeywordsFilter(array $configuration): array
    {
        if ($this->pi_getFFvalue($this->flexForm, 'enable_keywords', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['rule'] = $this->pi_getFFvalue(
                $this->flexForm,
                'keywords_rule',
                $this->flexFormFilterSheet
            );
            $flexFormFilter['rule'] = (int) $flexFormFilter['rule'];
            $kw = $this->pi_getFFvalue($this->flexForm, 'keywords', $this->flexFormFilterSheet);
            if (strlen($kw) > 0) {
                $words = \Ipf\Bib\Utility\Utility::multi_explode_trim([',', "\r", "\n"], $kw, true);
                foreach ($words as &$word) {
                    $word = \Ipf\Bib\Utility\ReferenceReader::getSearchTerm($word);
                }
                $flexFormFilter['words'] = $words;
                $configuration['filters']['flexform']['keywords'] = $flexFormFilter;
            }
        }

        return $configuration;
    }

    protected function initializeGeneralKeywordSearch(array $configuration): array
    {
        if ($this->pi_getFFvalue($this->flexForm, 'enable_search_all', $this->flexFormFilterSheet)) {
            $flexFormFilter = [];
            $flexFormFilter['rule'] = $this->pi_getFFvalue(
                $this->flexForm,
                'search_all_rule',
                $this->flexFormFilterSheet
            );
            $flexFormFilter['rule'] = (int) $flexFormFilter['rule'];
            $kw = $this->pi_getFFvalue($this->flexForm, 'search_all', $this->flexFormFilterSheet);
            if (strlen($kw) > 0) {
                $words = \Ipf\Bib\Utility\Utility::multi_explode_trim([',', "\r", "\n"], $kw, true);
                foreach ($words as &$word) {
                    $word = \Ipf\Bib\Utility\ReferenceReader::getSearchTerm($word);
                }
                $flexFormFilter['words'] = $words;
                $configuration['filters']['flexform']['all'] = $flexFormFilter;
            }
        }

        return $configuration;
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
    protected function showHiddenEntries(array $configuration): array
    {
        $configuration['show_hidden'] = false;
        if ($configuration['edit_mode']) {
            $configuration['show_hidden'] = true;
        }

        return $configuration;
    }

    protected function editAction(array $configuration): array
    {
        if ($configuration['edit_mode']) {
            // Disable caching in edit mode
            $GLOBALS['TSFE']->set_no_cache();

            // Do an action type evaluation
            if (is_array($this->piVars['action'])) {
                $actionName = implode('', array_keys($this->piVars['action']));

                switch ($actionName) {
                    case 'new':
                        $configuration['view_mode'] = View::VIEW_EDITOR;
                        $configuration['editor_mode'] = \Ipf\Bib\Modes\Editor::EDIT_NEW;
                        break;
                    case 'edit':
                        $configuration['view_mode'] = View::VIEW_EDITOR;
                        $configuration['editor_mode'] = \Ipf\Bib\Modes\Editor::EDIT_EDIT;
                        break;
                    case 'confirm_save':
                        $configuration['view_mode'] = View::VIEW_EDITOR;
                        $configuration['editor_mode'] = \Ipf\Bib\Modes\Editor::EDIT_CONFIRM_SAVE;
                        break;
                    case 'save':
                        $configuration['view_mode'] = View::VIEW_DIALOG;
                        $configuration['dialog_mode'] = \Ipf\Bib\Modes\Dialog::DIALOG_SAVE_CONFIRMED;
                        break;
                    case 'confirm_delete':
                        $configuration['view_mode'] = View::VIEW_EDITOR;
                        $configuration['editor_mode'] = \Ipf\Bib\Modes\Editor::EDIT_CONFIRM_DELETE;
                        break;
                    case 'delete':
                        $configuration['view_mode'] = View::VIEW_DIALOG;
                        $configuration['dialog_mode'] = \Ipf\Bib\Modes\Dialog::DIALOG_DELETE_CONFIRMED;
                        break;
                    case 'confirm_erase':
                        $configuration['view_mode'] = View::VIEW_EDITOR;
                        $configuration['editor_mode'] = \Ipf\Bib\Modes\Editor::EDIT_CONFIRM_ERASE;
                        break;
                    case 'erase':
                        $configuration['view_mode'] = View::VIEW_DIALOG;
                        $configuration['dialog_mode'] = \Ipf\Bib\Modes\Dialog::DIALOG_ERASE_CONFIRMED;
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
            if (View::VIEW_DIALOG === (int) $configuration['view_mode']) {
                unset($this->piVars['editor_mode']);
            }

            if (isset($configuration['editor_mode'])) {
                $this->piVars['editor_mode'] = $configuration['editor_mode'];
            } else {
                if (isset($this->piVars['editor_mode'])) {
                    $configuration['view_mode'] = View::VIEW_EDITOR;
                    $configuration['editor_mode'] = $this->piVars['editor_mode'];
                }
            }

            // Initialize edit icons
            $this->initializeEditIcons();

            // Switch to an import view on demand
            $allImport = (int) \Ipf\Bib\Utility\Importer\Importer::IMP_BIBTEX | \Ipf\Bib\Utility\Importer\Importer::IMP_XML;
            if (isset($this->piVars['import']) && ((int) $this->piVars['import'] & $allImport)) {
                $configuration['view_mode'] = View::VIEW_DIALOG;
                $configuration['dialog_mode'] = \Ipf\Bib\Modes\Dialog::DIALOG_IMPORT;
            }
        }

        return $configuration;
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
        $referenceWriter->hidePublication((int) $this->piVars['uid'], $hide);
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
    protected function exportAction(array $configuration): array
    {
        if (is_string($configuration['export_navi']['do'])) {
            $configuration['view_mode'] = View::VIEW_DIALOG;
            $configuration['dialog_mode'] = \Ipf\Bib\Modes\Dialog::DIALOG_EXPORT;
        }

        return $configuration;
    }

    /**
     * Switch to single view on demand.
     */
    protected function detailAction(array $configuration): array
    {
        if (is_numeric($this->piVars['show_uid'])) {
            $configuration['view_mode'] = View::VIEW_SINGLE;
            $configuration['single_view']['uid'] = (int) $this->piVars['show_uid'];
            unset($this->piVars['editor_mode']);
            unset($this->piVars['dialog_mode']);
        }

        return $configuration;
    }

    /**
     * Calls the hook_filter in the author navigation instance.
     */
    protected function callAuthorNavigationHook(array $configuration): array
    {
        if ($configuration['show_nav_author']) {
            $configuration['author_navi']['obj']->hook_filter($configuration);
        }

        return $configuration;
    }

    protected function getYearNavigation(array $configuration): array
    {
        if ($configuration['show_nav_year']) {
            // Fetch a year histogram
            $histogram = $this->referenceReader->getHistogram('year');
            $this->stat['year_hist'] = $histogram;
            $this->stat['years'] = array_keys($histogram);
            sort($this->stat['years']);

            $this->stat['num_all'] = array_sum($histogram);
            $this->stat['num_page'] = $this->stat['num_all'];

            // Determine the year to display
            $configuration['year'] = (int) date('Y'); // System year

            $exportPluginVariables = strtolower($this->piVars['year']);
            if (is_numeric($exportPluginVariables)) {
                $configuration['year'] = (int) $exportPluginVariables;
            } else {
                if ('all' === $exportPluginVariables) {
                    $configuration['year'] = $exportPluginVariables;
                }
            }

            if ('all' === $configuration['year']) {
                if ($this->conf['yearNav.']['selection.']['all_year_split']) {
                    $configuration['split_years'] = true;
                }
            }

            // The selected year has no publications so select the closest year
            if (($this->stat['num_all'] > 0) && is_numeric($configuration['year'])) {
                $configuration['year'] = \Ipf\Bib\Utility\Utility::find_nearest_int(
                    $configuration['year'],
                    $this->stat['years']
                );
            }
            // Append default link variable
            $configuration['link_vars']['year'] = $configuration['year'];

            if (is_numeric($configuration['year'])) {
                // Adjust num_page
                $this->stat['num_page'] = $this->stat['year_hist'][$configuration['year']];

                // Adjust year filter
                $configuration['filters']['br_year'] = [];
                $configuration['filters']['br_year']['year'] = [];
                $configuration['filters']['br_year']['year']['years'] = [$configuration['year']];
            }
        }

        return $configuration;
    }

    /**
     * Determines the number of publications.
     */
    protected function determineNumberOfPublications()
    {
        if (!is_numeric($this->stat['num_all'])) {
            $this->stat['num_all'] = \Ipf\Bib\Utility\ReferenceReader::getNumberOfPublications();
            $this->stat['num_page'] = $this->stat['num_all'];
        }
    }

    protected function getPageNavigation(array $configuration): array
    {
        $configuration['sub_page']['max'] = 0;
        $configuration['sub_page']['current'] = 0;

        if ($configuration['sub_page']['ipp'] > 0) {
            $configuration['sub_page']['max'] = floor(($this->stat['num_page'] - 1) / $configuration['sub_page']['ipp']);
            $configuration['sub_page']['current'] = \Ipf\Bib\Utility\Utility::crop_to_range(
                $this->piVars['page'],
                0,
                $configuration['sub_page']['max']
            );
        }

        if ($configuration['sub_page']['max'] > 0) {
            $configuration['show_nav_page'] = true;

            $configuration['filters']['br_page'] = [];

            // Adjust the browse filter limit
            $configuration['filters']['br_page']['limit'] = [];
            $configuration['filters']['br_page']['limit']['start'] = $configuration['sub_page']['current'] * $configuration['sub_page']['ipp'];
            $configuration['filters']['br_page']['limit']['num'] = $configuration['sub_page']['ipp'];
        }

        return $configuration;
    }

    /**
     * Determines and applies sorting filters to the ReferenceReader.
     */
    protected function getSortFilter(array $configuration): array
    {
        $configuration['filters']['sort'] = [];
        $configuration['filters']['sort']['sorting'] = [];

        // Default sorting
        $defaultSorting = 'DESC';

        if (\Ipf\Bib\Modes\Sort::SORT_ASC === (int) $configuration['date_sorting']) {
            $defaultSorting = 'ASC';
        }

        // add custom sorting with values from flexform
        if (!empty($configuration['sorting'])) {
            $sortFields = GeneralUtility::trimExplode(',', $configuration['sorting']);
            foreach ($sortFields as $sortField) {
                if ('surname' === $sortField) {
                    $sort = [
                        'field' => \Ipf\Bib\Utility\ReferenceReader::AUTHOR_TABLE.'.'.$sortField.' ',
                        'dir' => 'ASC',
                    ];
                } else {
                    $sort = [
                        'field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.'.$sortField.' ',
                        'dir' => $defaultSorting,
                    ];
                }
                $configuration['filters']['sort']['sorting'][] = $sort;
            }
        } else {
            // pre-defined sorting
            $configuration['filters']['sort']['sorting'] = [
                ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.year', 'dir' => $defaultSorting],
                ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.month', 'dir' => $defaultSorting],
                ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.day', 'dir' => $defaultSorting],
                ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.bibtype', 'dir' => 'ASC'],
                ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.state', 'dir' => 'ASC'],
                ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.sorting', 'dir' => 'ASC'],
                ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.title', 'dir' => 'ASC'],
            ];
        }
        // Adjust sorting for bibtype split
        if ($configuration['split_bibtypes']) {
            if (\Ipf\Bib\Modes\Display::D_SIMPLE === (int) $configuration['d_mode']) {
                $configuration['filters']['sort']['sorting'] = [
                    ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.bibtype', 'dir' => 'ASC'],
                    ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.year', 'dir' => $defaultSorting],
                    ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.month', 'dir' => $defaultSorting],
                    ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.day', 'dir' => $defaultSorting],
                    ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.state', 'dir' => 'ASC'],
                    ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.sorting', 'dir' => 'ASC'],
                    ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.title', 'dir' => 'ASC'],
                ];
            } else {
                $configuration['filters']['sort']['sorting'] = [
                    ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.year', 'dir' => $defaultSorting],
                    ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.bibtype', 'dir' => 'ASC'],
                    ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.month', 'dir' => $defaultSorting],
                    ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.day', 'dir' => $defaultSorting],
                    ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.state', 'dir' => 'ASC'],
                    ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.sorting', 'dir' => 'ASC'],
                    ['field' => \Ipf\Bib\Utility\ReferenceReader::REFERENCE_TABLE.'.title', 'dir' => 'ASC'],
                ];
            }
        }

        return $configuration;
    }

    /**
     * Disable navigations om demand.
     */
    protected function disableNavigationOnDemand(array $configuration): array
    {
        if (array_key_exists('num_all', $this->stat) && 0 === (int) $this->stat['num_all']) {
            $configuration['show_nav_export'] = false;
        }

        if (array_key_exists('num_page', $this->stat) && 0 === (int) $this->stat['num_page']) {
            $configuration['show_nav_stat'] = false;
        }

        return $configuration;
    }

    /**
     * This is the last function called before ouptput.
     */
    protected function finalize(string $pluginContent, array $configuration): string
    {
        if ($configuration['debug']) {
            $pluginContent .= \TYPO3\CMS\Core\Utility\DebugUtility::viewArray(
                [
                    'extConf' => $configuration,
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
    protected function switchToRequestedViewMode(array $configuration): string
    {
        switch ($configuration['view_mode']) {
            case View::VIEW_LIST:
                $listView = GeneralUtility::makeInstance(\Ipf\Bib\View\ListView::class);

                return $listView->initialize($configuration, $this->conf);
                break;
            case View::VIEW_SINGLE:
                $singleView = GeneralUtility::makeInstance(\Ipf\Bib\View\SingleView::class);
                $uid = (int) GeneralUtility::_GET('tx_bib_pi1')['show_uid'];

                return $singleView->get($uid, $configuration);
                break;
            case View::VIEW_EDITOR:
                $editorView = GeneralUtility::makeInstance(\Ipf\Bib\View\EditorView::class);

                return $editorView->initialize($configuration);
               break;
            case View::VIEW_DIALOG:
                $dialogView = GeneralUtility::makeInstance(\Ipf\Bib\View\DialogView::class);

                return $dialogView->initialize($configuration);
                break;
            default:
                throw new \Exception('An illegal view mode occurred', 1379064350);
        }
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/pi1/class.tx_bib_pi1.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/pi1/class.tx_bib_pi1.php'];
}
