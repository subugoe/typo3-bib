<?php

namespace Ipf\Bib\Utility\Exporter;

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

use Ipf\Bib\Domain\Model\Reference;
use Ipf\Bib\Utility\ReferenceReader;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Exporter.
 */
abstract class Exporter
{
    /**
     * @var \tx_bib_pi1
     */
    protected $pi1;

    /**
     * @var \Ipf\Bib\Utility\ReferenceReader
     */
    protected $referenceReader;

    /**
     * @var array
     */
    protected $filters;

    /**
     * @var string
     */
    protected $filterKey;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var bool
     */
    protected $isNewFile;

    /**
     * @var resource|bool
     */
    protected $fileResource;

    /**
     * @var bool
     */
    protected $dynamic = false;

    /**
     * @var string
     */
    protected $data = '';

    /**
     * @var array
     */
    protected $info;

    /**
     * @var array
     */
    protected $configuration;

    public function __construct(array $configuration)
    {
        $this->referenceReader = GeneralUtility::makeInstance(ReferenceReader::class, $configuration);
        $this->configuration = $configuration;
    }

    /**
     * Initializes the export.
     */
    public function initialize()
    {
        $this->setupFilters();
        $this->setupExportFile();
    }

    protected function setupFilters()
    {
        $this->setFilters($this->configuration['filters']);
        unset($this->filters['br_page']);

        // The filter key is used for the filename
        $this->filterKey = 'export'.(string) $GLOBALS['TSFE']->id;
    }

    protected function setupExportFile()
    {
        // Setup export file path and name
        $this->filePath = $this->pi1->conf['export.']['path'];
        if (!strlen($this->filePath)) {
            $this->filePath = 'uploads/tx_bib';
        }

        $this->setFileName('bib_'.$this->filterKey.'.dat');
        $this->setIsNewFile(false);
    }

    /**
     * This writes the filtered database content
     * to the export file.
     */
    public function export()
    {
        $this->setIsNewFile(false);

        // Initialize sink
        if ($this->isResourceReady()) {
            // Initialize db access
            $this->referenceReader->set_filters($this->getFilters());
            $references = $this->getReferenceReader()->getAllReferences();

            // Setup info array
            $infoArr = [];
            $infoArr['pubNum'] = $this->getReferenceReader()->numberOfReferencesToBeFetched();
            $infoArr['index'] = -1;

            // Write pre data
            $data = $this->fileIntro($infoArr);
            $this->writeToResource($data);

            // Write publications
            foreach ($references as $pub) {
                ++$infoArr['index'];
                $data = $this->formatPublicationForExport($pub, $infoArr);
                $this->writeToResource($data);
            }

            // Write post data
            $data = $this->fileOutro($infoArr);
            $this->writeToResource($data);

            $this->info = $infoArr;
        }

        $this->cleanUpResource();
    }

    /**
     * Return codes
     *  0 - Sink ready
     * -1 - Sink is up to date.
     *
     * @throws FileOperationErrorException
     *
     * @return bool
     */
    protected function isResourceReady(): bool
    {
        if ($this->dynamic) {
            $this->setData('');
        } else {
            // Open file
            $file_abs = $this->getAbsoluteFilePath();

            if ($this->isFileMoreUpToDate($file_abs) && !$this->pi1->extConf['debug']) {
                return false;
            }

            $this->fileResource = fopen($file_abs, 'w');

            if ($this->fileResource) {
                $this->setIsNewFile(true);
            } else {
                throw new FileOperationErrorException(
                    sprintf('Bib error: Could not open file %s for writing.', $file_abs),
                    1379067524
                );
            }
        }

        return true;
    }

    /**
     * Returns absolute system file path.
     *
     * @return string The absolute file path
     */
    protected function getAbsoluteFilePath()
    {
        return PATH_site.$this->getRelativeFilePath();
    }

    /**
     * Returns the composed path/file name.
     *
     * @return string The file address
     */
    public function getRelativeFilePath()
    {
        return $this->filePath.'/'.$this->fileName;
    }

    /**
     * Checks if the file exists and is newer than
     * the latest change (tstamp) in the publication database.
     *
     * @param string $file
     *
     * @return bool TRUE if file exists and is newer than the
     *              database content, FALSE otherwise
     */
    protected function isFileMoreUpToDate($file)
    {
        $databaseTimestamp = ReferenceReader::getLatestTimestamp();

        if (file_exists($file)) {
            $fileModificationTIme = filemtime($file);
            if (!(false === $fileModificationTIme) && ($databaseTimestamp < $fileModificationTIme)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \Ipf\Bib\Utility\ReferenceReader
     */
    public function getReferenceReader()
    {
        return $this->referenceReader;
    }

    /**
     * @param \Ipf\Bib\Utility\ReferenceReader $referenceReader
     */
    public function setReferenceReader($referenceReader)
    {
        $this->referenceReader = $referenceReader;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param array $filters
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
    }

    /**
     * Returns the file intro.
     *
     * @param $infoArr
     *
     * @return string The file header string
     */
    abstract protected function fileIntro($infoArr = []);

    /**
     * @param $data
     */
    protected function writeToResource($data)
    {
        if ($this->dynamic) {
            $this->data .= $data;
        } else {
            fwrite($this->fileResource, $data);
        }
    }

    /**
     * Formats one publication for the export.
     *
     * @param Reference $publication
     * @param array     $infoArr
     *
     * @return string The export string
     */
    abstract protected function formatPublicationForExport(Reference $publication, $infoArr = []);

    /**
     * Returns the file outtro.
     *
     * @param array $infoArr
     *
     * @return string The file header string
     */
    abstract protected function fileOutro($infoArr = []);

    protected function cleanUpResource()
    {
        if (!$this->dynamic) {
            if ($this->fileResource) {
                fclose($this->fileResource);
                $this->fileResource = false;
            }
        }
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @return bool
     */
    public function getIsNewFile()
    {
        return $this->isNewFile;
    }

    /**
     * @param bool $isNewFile
     */
    public function setIsNewFile($isNewFile)
    {
        $this->isNewFile = $isNewFile;
    }

    /**
     * @return bool
     */
    public function getDynamic()
    {
        return $this->dynamic;
    }

    /**
     * @param bool $dynamic
     */
    public function setDynamic($dynamic)
    {
        $this->dynamic = $dynamic;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Returns a general information text for the exported dataset.
     *
     * @return string A filter information string
     */
    protected function getGeneralInformationText()
    {
        $num = ReferenceReader::getNumberOfPublications();

        $content = 'This file was created by the TYPO3 extension bib';
        $content .= PHP_EOL;
        $content .= '--- Timezone: '.date('T').PHP_EOL;
        $content .= 'Creation date: '.date('Y-m-d').PHP_EOL;
        $content .= 'Creation time: '.date('H-i-s').PHP_EOL;

        if ($num >= 0) {
            $content .= '--- Number of references: '.$num.PHP_EOL;
            $content .= ''.PHP_EOL;
        }

        return $content;
    }
}
