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

use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;

abstract class Exporter {

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
	protected $dynamic = FALSE;

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
	protected $extensionManagerConfiguration;

	/**
	 * Initializes the export. The argument must be the plugin class
	 *
	 * @param \tx_bib_pi1 $pi1
	 * @return void
	 */
	public function initialize($pi1) {
		$this->pi1 =& $pi1;
		$this->setReferenceReader($pi1->referenceReader);
		$this->setupFilters();
		$this->setupExportFile();
	}

	/**
	 * This writes the filtered database content
	 * to the export file
	 *
	 * @return void
	 */
	public function export() {
		$this->setIsNewFile(FALSE);

		// Initialize sink
		if ($this->isResourceReady()) {
			// Initialize db access
			$this->getReferenceReader()->set_filters($this->getFilters());
			$this->getReferenceReader()->initializeReferenceFetching();

			// Setup info array
			$infoArr = array();
			$infoArr['pubNum'] = $this->getReferenceReader()->numberOfReferencesToBeFetched();
			$infoArr['index'] = -1;

			// Write pre data
			$data = $this->fileIntro($infoArr);
			$this->writeToResource($data);

			// Write publications
			while ($pub = $this->getReferenceReader()->getReference()) {
				$infoArr['index']++;
				$data = $this->formatPublicationForExport($pub, $infoArr);
				$this->writeToResource($data);
			}

			// Write post data
			$data = $this->fileOutro($infoArr);
			$this->writeToResource($data);

			// Clean up db access
			$this->getReferenceReader()->finalizeReferenceFetching();

			$this->info = $infoArr;
		}

		$this->cleanUpResource();
	}

	/**
	 * @return void
	 */
	protected function setupExportFile() {
		// Setup export file path and name
		$this->filePath = $this->pi1->conf['export.']['path'];
		if (!strlen($this->filePath)) {
			$this->filePath = 'uploads/tx_bib';
		}

		$this->setFileName($this->pi1->extKey . '_' . $this->filterKey . '.dat');
		$this->setIsNewFile(FALSE);
	}

	/**
	 * @return void
	 */
	protected function setupFilters() {
		$this->setFilters($this->pi1->extConf['filters']);
		unset($this->filters['br_page']);

		// The filter key is used for the filename
		$this->filterKey = 'export' . strval($GLOBALS['TSFE']->id);
	}

	/**
	 * Returns the composed path/file name
	 *
	 * @return String The file address
	 */
	public function getRelativeFilePath() {
		return $this->filePath . '/' . $this->fileName;
	}

	/**
	 * Returns absolute system file path
	 *
	 * @return String The absolute file path
	 */
	protected function getAbsoluteFilePath() {
		return PATH_site . $this->getRelativeFilePath();
	}


	/**
	 * Checks if the file exists and is newer than
	 * the latest change (tstamp) in the publication database
	 *
	 * @param String $file
	 * @return bool TRUE if file exists and is newer than the
	 *         database content, FALSE otherwise.
	 */
	protected function isFileMoreUpToDate($file) {
		$databaseTimestamp = $this->getReferenceReader()->getLatestTimestamp();

		if (file_exists($file)) {
			$fileModificationTIme = filemtime($file);
			if (!($fileModificationTIme === FALSE) && ($databaseTimestamp < $fileModificationTIme)) {
				return TRUE;
			}
		}
		return FALSE;
	}


	/**
	 * Formats one publication for the export
	 *
	 * @param array $publication
	 * @param array $infoArr
	 * @return string The export string
	 */
	abstract protected function formatPublicationForExport($publication, $infoArr = array());


	/**
	 * Returns the file intro
	 *
	 * @param $infoArr
	 * @return string The file header string
	 */
	abstract protected function fileIntro($infoArr = array());

	/**
	 * Returns the file outtro
	 *
	 * @param array $infoArr
	 * @return string The file header string
	 */
	abstract protected function fileOutro($infoArr = array());


	/**
	 * Returns a general information text for the exported dataset
	 *
	 * @param array
	 * @return string A filter information string
	 */
	protected function getGeneralInformationText($infoArr = array()) {
		$num = intval($infoArr['pubNum']);

		$content = 'This file was created by the TYPO3 extension' . "\n";
		$content .= $this->pi1->extKey;
		if (is_array($this->extensionManagerConfiguration)) {
			$content .= ' version ' . $this->extensionManagerConfiguration['version'] . "\n";
		}
		$content .= "\n";
		$content .= '--- Timezone: ' . date('T') . "\n";
		$content .= 'Creation date: ' . date('Y-m-d') . "\n";
		$content .= 'Creation time: ' . date('H-i-s') . "\n";

		if ($num >= 0) {
			$content .= '--- Number of references' . "\n";
			$content .= '' . $num . "\n";
			$content .= '' . "\n";
		}

		return $content;
	}

	/*
	 * Return codes
	 *  0 - Sink ready
	 * -1 - Sink is up to date
	 *
	 * @throws FileOperationErrorException
	 * @return int
	 */
	protected function isResourceReady() {
		if ($this->dynamic) {
			$this->setData('');
		} else {
			// Open file
			$file_abs = $this->getAbsoluteFilePath();

			if ($this->isFileMoreUpToDate($file_abs) && !$this->pi1->extConf['debug']) {
				return FALSE;
			}

			$this->fileResource = fopen($file_abs, 'w');

			if ($this->fileResource) {
				$this->setIsNewFile(TRUE);
			} else {
				throw new FileOperationErrorException(
					$this->pi1->extKey . ' error: Could not open file ' . $file_abs . ' for writing.',
					1379067524
				);
			}
		}

		return TRUE;
	}

	/**
	 * @param $data
	 * @return void
	 */
	protected function writeToResource($data) {
		if ($this->dynamic) {
			$this->data .= $data;
		} else {
			fwrite($this->fileResource, $data);
		}
	}

	/**
	 * @return void
	 */
	protected function cleanUpResource() {
		if (!$this->dynamic) {
			if ($this->fileResource) {
				fclose($this->fileResource);
				$this->fileResource = FALSE;
			}
		}
	}
	/**
	 * @param string $fileName
	 */
	public function setFileName($fileName) {
		$this->fileName = $fileName;
	}

	/**
	 * @return string
	 */
	public function getFileName() {
		return $this->fileName;
	}

	/**
	 * @param string $filePath
	 */
	public function setFilePath($filePath) {
		$this->filePath = $filePath;
	}

	/**
	 * @return string
	 */
	public function getFilePath() {
		return $this->filePath;
	}

	/**
	 * @param \Ipf\Bib\Utility\ReferenceReader $referenceReader
	 */
	public function setReferenceReader($referenceReader) {
		$this->referenceReader = $referenceReader;
	}

	/**
	 * @return \Ipf\Bib\Utility\ReferenceReader
	 */
	public function getReferenceReader() {
		return $this->referenceReader;
	}

	/**
	 * @param array $filters
	 */
	public function setFilters($filters) {
		$this->filters = $filters;
	}

	/**
	 * @return array
	 */
	public function getFilters() {
		return $this->filters;
	}

	/**
	 * @param boolean $isNewFile
	 */
	public function setIsNewFile($isNewFile) {
		$this->isNewFile = $isNewFile;
	}

	/**
	 * @return boolean
	 */
	public function getIsNewFile() {
		return $this->isNewFile;
	}

	/**
	 * @param boolean $dynamic
	 */
	public function setDynamic($dynamic) {
		$this->dynamic = $dynamic;
	}

	/**
	 * @return boolean
	 */
	public function getDynamic() {
		return $this->dynamic;
	}

	/**
	 * @param string $data
	 */
	public function setData($data) {
		$this->data = $data;
	}

	/**
	 * @return string
	 */
	public function getData() {
		return $this->data;
	}

}

?>