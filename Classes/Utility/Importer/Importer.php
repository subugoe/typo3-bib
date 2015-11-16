<?php
namespace Ipf\Bib\Utility\Importer;

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

use Ipf\Bib\Exception\DataException;
use Ipf\Bib\Utility\DbUtility;
use Ipf\Bib\Utility\Generator\AuthorsCiteIdGenerator;
use Ipf\Bib\Utility\Generator\CiteIdGenerator;
use Ipf\Bib\Utility\ReferenceWriter;
use Ipf\Bib\Utility\Utility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class Importer
 * @package Ipf\Bib\Utility\Importer
 */
abstract class Importer
{

    /**
     * @var \tx_bib_pi1
     */
    public $pi1;

    /**
     * @var \Ipf\Bib\Utility\ReferenceReader
     */
    public $referenceReader;

    /**
     * @var \Ipf\Bib\Utility\ReferenceWriter
     */
    public $referenceWriter;

    /**
     * @var \Ipf\Bib\Utility\DbUtility
     */
    public $databaseUtility;

    /**
     * @var int
     */
    public $storage_pid = 0;

    /**
     * @var int
     */
    public $state;

    /**
     * @var string
     */
    public $import_type;

    /**
     * @var array
     */
    public $statistics = [];

    // Utility
    public $code_trans_tbl;

    /**
     * @var bool|\Ipf\Bib\Utility\Generator\CiteIdGenerator
     */
    public $idGenerator = false;

    /**
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected $view;

    /**
     * @var \TYPO3\CMS\Dbal\Database\DatabaseConnection
     */
    protected $db;

    // Import modes
    const IMP_BIBTEX = 1;
    const IMP_XML = 2;

    /**
     * Initializes the import. The argument must be the plugin class
     *
     * @param \tx_bib_pi1
     * @return void
     */
    public function initialize($pi1)
    {
        $this->pi1 = $pi1;
        $this->referenceReader = $this->pi1->referenceReader;

        $this->db = $GLOBALS['TYPO3_DB'];

        $this->view = GeneralUtility::makeInstance(StandaloneView::class);

        $this->referenceWriter = GeneralUtility::makeInstance(ReferenceWriter::class);
        $this->referenceWriter->initialize($this->referenceReader);

        $this->statistics['warnings'] = [];
        $this->statistics['errors'] = [];

        // setup database utility
        /** @var \Ipf\Bib\Utility\DBUtility $databaseUtility */
        $databaseUtility = GeneralUtility::makeInstance(DbUtility::class);
        $databaseUtility->initialize($this->referenceReader);
        $databaseUtility->charset = $pi1->extConf['charset']['upper'];
        $databaseUtility->readFullTextGenerationConfiguration($pi1->conf['editor.']['full_text.']);

        $this->databaseUtility = $databaseUtility;

        // Create an instance of the citeid generator
        if (isset ($this->pi1->conf['citeid_generator_file'])) {
            $ext_file = $GLOBALS['TSFE']->tmpl->getFileName($this->pi1->conf['citeid_generator_file']);
            if (file_exists($ext_file)) {
                require_once($ext_file);
                $this->idGenerator = GeneralUtility::makeInstance(AuthorsCiteIdGenerator::class);
            }
        } else {
            $this->idGenerator = GeneralUtility::makeInstance(CiteIdGenerator::class);
        }
        $this->idGenerator->initialize($pi1);
    }


    /**
     * Returns a page title
     *
     * @param int $uid
     * @return string
     */
    protected function getPageTitle($uid)
    {
        $title = false;
        $res = $this->db->exec_SELECTquery(
            'title',
            'pages',
            'uid=' . intval($uid)
        );
        $page = $this->db->sql_fetch_assoc($res);
        $charset = $this->pi1->extConf['charset']['upper'];
        if (is_array($page)) {
            $title = htmlspecialchars($page['title'], ENT_NOQUOTES, $charset);
            $title .= ' (' . strval($uid) . ')';
        }
        return $title;
    }


    /**
     * Returns the default storage uid
     *
     * @return int The parent id pid
     */
    protected function getDefaultPid()
    {
        $pid = 0;
        if (is_numeric($this->pi1->conf['editor.']['default_pid'])) {
            $pid = intval($this->pi1->conf['editor.']['default_pid']);
        }
        if (!in_array($pid, $this->referenceReader->pid_list)) {
            $pid = intval($this->referenceReader->pid_list[0]);
        }
        return $pid;
    }


    /**
     * Returns the storage selector if there is more
     * than one storage folder selected
     *
     * @return string the storage selector string
     */
    protected function getStorageSelector()
    {
        $content = '';

        $pids = $this->pi1->extConf['pid_list'];
        $default_pid = $this->getDefaultPid();

        if (sizeof($pids) > 1) {
            // Fetch page titles
            $pages = Utility::get_page_titles($pids);

            $val = $this->pi1->get_ll('import_storage_info', 'import_storage_info', true);
            $content .= '<p>' . $val . '</p>';

            $val = Utility::html_select_input(
                $pages,
                $default_pid,
                ['name' => $this->pi1->prefixId . '[import_pid]']
            );
            $content .= '<p>' . $val . '</p>';
        }

        return $content;
    }


    /**
     * Acquires $this->storage_pid
     *
     * @return void
     */
    protected function acquireStoragePid()
    {
        $this->storage_pid = intval($this->pi1->piVars['import_pid']);
        if (!in_array($this->storage_pid, $this->pi1->extConf['pid_list'])) {
            $this->storage_pid = $this->getDefaultPid();
        }
    }


    /**
     * Saves a publication
     *
     * @param array $publication
     * @return bool
     */
    protected function savePublication($publication)
    {
        $res = false;

        // Data checks
        $s_ok = true;
        if (!array_key_exists('bibtype', $publication)) {
            $this->statistics['failed']++;
            $this->statistics['errors'][] = 'Missing bibtype';
            $s_ok = false;
        }

        // Data adjustments
        $publication['pid'] = $this->storage_pid;

        // Don't accept publication uids since that
        // could override existing publications
        if (array_key_exists('uid', $publication)) {
            unset ($publication['uid']);
        }

        if (strlen($publication['citeid']) == 0) {
            $publication['citeid'] = $this->idGenerator->generateId($publication);
        }

        // Save publications
        if ($s_ok) {

            try {
                $this->referenceWriter->savePublication($publication);
                $this->statistics['succeeded']++;
            } catch (DataException $e) {
                $this->statistics['failed']++;
                $this->statistics['errors'][] = $e->getMessage();
            }
        }

        return $res;
    }


    /**
     * The main importer function
     * @throws \Exception
     * @return string
     */
    public function import()
    {
        $this->state = 1;
        if (intval($_FILES['ImportFile']['size']) > 0) {
            $this->state = 2;
        }

        switch ($this->state) {
            case 1:
                $content = $this->importFileSelectionState();
                break;
            case 2:
                $this->acquireStoragePid();
                if (GeneralUtility::_GP('import_clear_all')) {
                    $this->clearAllDatasetsBeforeImport();
                }

                $content = $this->importStateTwo();
                $this->postImport();
                $content .= $this->getImportStatistics();
                break;
            default:
                throw new \Exception('Bad import state ' . $this->state, 1378910596);
        }

        return $content;
    }

    /**
     * @return string
     */
    abstract protected function displayInformationBeforeImport();

    /**
     * file selection state
     *
     * @return string
     */
    protected function importFileSelectionState()
    {
        $this->view->setTemplatePathAndFilename(ExtensionManagementUtility::extPath('bib') . 'Resources/Private/Templates/Importer/Import.html');

        // Pre import information
        $this->view->assign('content', $this->displayInformationBeforeImport());
        $formAction = $this->pi1->get_link_url(['import' => $this->import_type]);
        $this->view->assign('formAction', $formAction);
        $this->view->assign('storageSelector', $this->getStorageSelector());

        return $this->view->render();
    }

    /**
     * @return string
     */
    abstract protected function importStateTwo();

    /**
     * Adds an import statistics string to the statistics array
     *
     * @return void
     */
    protected function postImport()
    {
        if ($this->statistics['succeeded'] > 0) {

            // Update full texts
            if ($this->pi1->conf['editor.']['full_text.']['update']) {
                $arr = $this->databaseUtility->update_full_text_all();
                if (sizeof($arr['errors']) > 0) {
                    foreach ($arr['errors'] as $err) {
                        $this->statistics['errors'][] = $err[1]['msg'];
                    }
                }
                $this->statistics['full_text'] = $arr;
            }

        }
    }

    /**
     * Returns an import statistics string
     *
     * @return string
     */
    protected function getImportStatistics()
    {
        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(ExtensionManagementUtility::extPath('bib') . 'Resources/Private/Templates/Importer/Statistics.html');

        $view->assign('fileName', $this->statistics['file_name']);
        $view->assign('fileSize', $this->statistics['file_size']);

        $view->assign('storageFolder', $this->getPageTitle($this->statistics['storage']));

        $view->assign('succeeded', $this->statistics['succeeded']);
        $view->assign('failed', $this->statistics['failed']);

        $view->assign('fullTextStatistics', is_array($this->statistics['full_text']) ? true : false);

        if (is_array($this->statistics['full_text'])) {
            $view->assign('updatedFullTexts', count($this->statistics['full_text']['updated']));
            $view->assign('fullTextNumberLimit', $this->statistics['full_text']['limit_num'] ? true : false);
            $view->assign('fullTextTimeLimit', $this->statistics['full_text']['limit_time'] ? true : false);
        }

        $displayWarnings = is_array($this->statistics['warnings']) && (count($this->statistics['warnings']) > 0);
        $view->assign('displayWarnings', $displayWarnings);

        if ($displayWarnings) {
            $messages = Utility::string_counter($this->statistics['warnings']);
            $val = '';
            foreach ($messages as $msg => $count) {
                $str = $this->getMessageOccurrenceCounter($msg, $count);
                $val .= '<li>' . $str . '</li>';
            }
            $view->assign('warnings', $val);
        }

        $displayErrors = is_array($this->statistics['errors']) && (count($this->statistics['errors']) > 0);
        $view->assign('displayErrors', $displayErrors);

        if ($displayErrors) {
            $val = '';
            $messages = Utility::string_counter($this->statistics['errors']);
            foreach ($messages as $msg => $count) {
                $str = $this->getMessageOccurrenceCounter($msg, $count);
                $val .= '<li>' . $str . '</li>';
            }
            $this->view->assign('errors', $val);
        }

        return $view->render();
    }

    /**
     * @param string $message
     * @param int $count
     * @return string
     */
    protected function getMessageOccurrenceCounter($message, $count)
    {
        $charset = $this->pi1->extConf['charset']['upper'];
        $content = htmlspecialchars($message, ENT_QUOTES, $charset);
        if ($count > 1) {
            $content .= ' (' . strval($count);
            $content .= ' times)';
        }
        return $content;
    }


    /**
     * Replaces character code description like &aauml; with
     * the equivalent
     *
     * @param string $code
     * @return string
     */
    protected function codeToUnicode($code)
    {
        $translationTable =& $this->code_trans_tbl;
        if (!is_array($translationTable)) {
            $translationTable = get_html_translation_table(HTML_ENTITIES, ENT_NOQUOTES);
            $translationTable = array_flip($translationTable);
            // These should stay alive
            unset($translationTable['&amp;']);
            unset($translationTable['&lt;']);
            unset($translationTable['&gt;']);

            foreach ($translationTable as $key => $val) {
                $translationTable[$key] = $GLOBALS['TSFE']->csConvObj->conv($val, 'iso-8859-1', 'utf-8');
            }
        }

        return strtr($code, $translationTable);
    }


    /**
     * Takes an utf-8 string and changes the character set on demand
     *
     * @param string $content
     * @param string $charset
     * @return string
     */
    protected function importUnicodeString($content, $charset = null)
    {
        if (!is_string($charset)) {
            $charset = $this->pi1->extConf['charset']['lower'];
        }

        if ($charset != 'utf-8') {
            $content = $GLOBALS['TSFE']->csConvObj->utf8_decode($content, $charset, true);
        }
        return $content;
    }

    /**
     * Removes all datasets from the storage on importing data
     */
    protected function clearAllDatasetsBeforeImport()
    {
        $this->databaseUtility->deleteAllFromPid($this->storage_pid);
    }

}
