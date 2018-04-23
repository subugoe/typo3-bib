<?php

namespace Ipf\Bib\Importer;

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
use Ipf\Bib\Utility\Generator\CiteIdGenerator;
use Ipf\Bib\Utility\ReferenceWriter;
use Ipf\Bib\Utility\Utility;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Page\PageRepository;

abstract class Importer
{
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
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected $view;

    // Import modes
    const IMP_BIBTEX = 1;
    const IMP_XML = 2;

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var array
     */
    protected $conf;

    public function __construct(array $configuration, array $localConfiguration)
    {
        $this->configuration = $configuration;
        $this->conf = $localConfiguration;
    }

    /**
     * Initializes the import. The argument must be the plugin class.
     */
    public function initialize()
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setPartialRootPaths([10 => 'EXT:bib/Resources/Private/Partials/']);

        $this->statistics['warnings'] = [];
        $this->statistics['errors'] = [];
    }

    /**
     * Returns a page title.
     *
     * @param int $uid
     *
     * @return string
     */
    protected function getPageTitle(int $uid): string
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $page = $pageRepository->getPage($uid);

        return sprintf('%s (%d)', $page['title'], $uid);
    }

    /**
     * Returns the default storage uid.
     *
     * @return int The parent id pid
     */
    protected function getDefaultPid(): int
    {
        $pid = 0;
        if (is_numeric($this->conf['editor.']['default_pid'])) {
            $pid = (int) $this->conf['editor.']['default_pid'];
        }

        if (!in_array($pid, $this->configuration['pid_list'])) {
            $pid = (int) $this->configuration['pid_list'][0];
        }

        return $pid;
    }

    /**
     * Acquires $this->storage_pid.
     */
    protected function acquireStoragePid()
    {
        $getPostVariables = GeneralUtility::_GP('tx_bib_pi1');

        $this->storage_pid = (int) $getPostVariables['import_pid'];
        if (!in_array($this->storage_pid, $this->configuration['pid_list'])) {
            $this->storage_pid = $this->getDefaultPid();
        }
    }

    /**
     * Saves a publication.
     *
     * @param array $publication
     *
     * @return bool
     */
    protected function savePublication($publication)
    {
        $res = false;

        // Data checks
        $s_ok = true;
        if (!array_key_exists('bibtype', $publication)) {
            ++$this->statistics['failed'];
            $this->statistics['errors'][] = 'Missing bibtype';
            $s_ok = false;
        }

        // Data adjustments
        $publication['pid'] = $this->storage_pid;

        // Don't accept publication uids since that
        // could override existing publications
        if (array_key_exists('uid', $publication)) {
            unset($publication['uid']);
        }

        if (0 == strlen($publication['citeid'])) {
            $idGenerator = GeneralUtility::makeInstance(CiteIdGenerator::class, $this->configuration);
            $publication['citeid'] = $idGenerator->generateId($publication);
        }

        // Save publications
        if ($s_ok) {
            try {
                $referenceWriter = GeneralUtility::makeInstance(ReferenceWriter::class, $this->configuration);
                $referenceWriter->savePublication($publication);
                ++$this->statistics['succeeded'];
            } catch (DataException $e) {
                ++$this->statistics['failed'];
                $this->statistics['errors'][] = $e->getMessage();
            }
        }

        return $res;
    }

    /**
     * The main importer function.
     *
     * @throws \Exception
     *
     * @return string
     */
    public function import()
    {
        $this->state = 1;
        if ((int) $_FILES['ImportFile']['size'] > 0) {
            $this->state = 2;
        }

        $getPostVariables = GeneralUtility::_GP('tx_bib_pi1');

        switch ($this->state) {
            case 1:
                return $this->importFileSelectionState();
                break;
            case 2:
                $this->acquireStoragePid();
                if (1 === (int) $getPostVariables['import_clear_all']) {
                    $this->clearAllDatasetsBeforeImport();
                }

                $content = $this->importStateTwo();
                $this->postImport();
                $content .= $this->getImportStatistics();

                return $content;
                break;
            default:
                throw new \Exception(sprintf('Bad import state %s', $this->state), 1378910596);
        }
    }

    /**
     * file selection state.
     *
     * @return string
     */
    protected function importFileSelectionState()
    {
        $this->view->setTemplatePathAndFilename('EXT:bib/Resources/Private/Templates/Importer/Import.html');

        $this->view->assign('importType', $this->import_type);
        $this->view->assign('storageSelector', Utility::get_page_titles($this->configuration['pid_list']));

        return $this->view->render();
    }

    /**
     * @return string
     */
    abstract protected function importStateTwo();

    /**
     * Adds an import statistics string to the statistics array.
     */
    protected function postImport()
    {
        if ($this->statistics['succeeded'] > 0) {
            // Update full texts
            if ($this->conf['editor.']['full_text.']['update']) {
                $databaseUtility = GeneralUtility::makeInstance(DbUtility::class, $this->configuration);
                $databaseUtility->readFullTextGenerationConfiguration($this->conf['editor.']['full_text.']);
                $arr = $databaseUtility->update_full_text_all();
                if (count($arr['errors']) > 0) {
                    foreach ($arr['errors'] as $err) {
                        $this->statistics['errors'][] = $err[1]['msg'];
                    }
                }
                $this->statistics['full_text'] = $arr;
            }
        }
    }

    /**
     * Returns an import statistics string.
     *
     * @return string
     */
    protected function getImportStatistics()
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:bib/Resources/Private/Templates/Importer/Statistics.html');

        $view->assign('storageFolder', $this->getPageTitle($this->statistics['storage']));
        $view->assign('fullTextStatistics', is_array($this->statistics['full_text']) ? true : false);

        if (is_array($this->statistics['full_text'])) {
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
                $val .= '<li>'.$str.'</li>';
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
                $val .= '<li>'.$str.'</li>';
            }
            $this->view->assign('errors', $val);
        }

        return $view->render();
    }

    /**
     * @param string $message
     * @param int    $count
     *
     * @return string
     */
    protected function getMessageOccurrenceCounter($message, $count)
    {
        $content = htmlspecialchars($message, ENT_QUOTES);
        if ($count > 1) {
            $content .= ' ('.strval($count);
            $content .= ' times)';
        }

        return $content;
    }

    /**
     * Replaces character code description like &aauml; with
     * the equivalent.
     *
     * @param string $code
     *
     * @return string
     */
    protected function codeToUnicode($code)
    {
        if (!is_array($this->code_trans_tbl)) {
            $this->code_trans_tbl = get_html_translation_table(HTML_ENTITIES, ENT_NOQUOTES);
            $this->code_trans_tbl = array_flip($this->code_trans_tbl);
            // These should stay alive
            unset($this->code_trans_tbl['&amp;']);
            unset($this->code_trans_tbl['&lt;']);
            unset($this->code_trans_tbl['&gt;']);

            foreach ($this->code_trans_tbl as $key => $val) {
                $charsetConverter = GeneralUtility::makeInstance(CharsetConverter::class);
                $this->code_trans_tbl[$key] = $charsetConverter->conv($val, 'iso-8859-1', 'utf-8');
            }
        }

        return strtr($code, $this->code_trans_tbl);
    }

    /**
     * Removes all datasets from the storage on importing data.
     */
    protected function clearAllDatasetsBeforeImport()
    {
        DbUtility::deleteAllFromPid($this->storage_pid);
    }
}
