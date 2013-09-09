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

abstract class Exporter {

	/**
	 * @var \tx_bib_pi1
	 */
	public $pi1;

	/**
	 * @var \Ipf\Bib\Utility\ReferenceReader
	 */
	public $referenceReader;

	/**
	 * @var array
	 */
	public $filters;
	public $filter_key;
	public $page_mode;

	/**
	 * @var string
	 */
	public $file_path;

	/**
	 * @var string
	 */
	public $file_name;
	public $file_new;

	public $file_res;
	public $dynamic;
	public $data;

	public $info;
	public $error;

	/**
	 * @var array
	 */
	public $extensionManagerConfiguration;

	/**
	 * Initializes the export. The argument must be the plugin class
	 *
	 * @param \tx_bib_pi1 $pi1
	 * @return void
	 */
	public function initialize($pi1) {
		$this->pi1 =& $pi1;
		$this->referenceReader =& $pi1->referenceReader;

		// Setup filters
		$this->filters = $this->pi1->extConf['filters'];
		unset ($this->filters['br_page']);

		// The filter key is used for the filename
		$this->filter_key = 'export' . strval($GLOBALS['TSFE']->id);

		// Setup export file path and name
		$this->file_path = $this->pi1->conf['export.']['path'];
		if (!strlen($this->file_path))
			$this->file_path = 'uploads/tx_bib';

		$this->file_name = $this->pi1->extKey . '_' . $this->filter_key . '.dat';
		$this->file_new = FALSE;

		// Disable dynamic
		$this->dynamic = FALSE;
		$this->data = '';
	}

	/**
	 * Returns the composed path/file name
	 *
	 * @return String The file address
	 */
	public function getRelativeFilePath() {
		return $this->file_path . '/' . $this->file_name;
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
		$databaseTimestamp = $this->referenceReader->fetch_max_tstamp();

		if (file_exists($file)) {
			$fileModificationTIme = filemtime($file);
			if (!($fileModificationTIme === FALSE) && ($databaseTimestamp < $fileModificationTIme)) {
				return TRUE;
			}
		}
		return FALSE;
	}


	/**
	 * This writes the filtered database content
	 * to the export file
	 *
	 * @return bool TRUE ond error, FALSE otherwise
	 */
	public function export() {
		$this->file_new = FALSE;

		// Initialize sink
		$ret = $this->sink_init();

		if ($ret == 1) {
			return TRUE; // Error
		} else if ($ret == -1) {
			return FALSE; // Up to date
		} else if ($ret == 0) {

			// Initialize db access
			$this->referenceReader->set_filters($this->filters);
			$this->referenceReader->mFetch_initialize();

			// Setup info array
			$infoArr = array();
			$infoArr['pubNum'] = $this->referenceReader->mFetch_num();
			$infoArr['index'] = -1;

			// Write pre data
			$data = $this->fileIntro($infoArr);
			$this->sink_write($data);

			// Write publications
			while ($pub = $this->referenceReader->mFetch()) {
				$infoArr['index']++;
				$data = $this->formatPublicationForExport($pub, $infoArr);
				$this->sink_write($data);
			}

			// Write post data
			$data = $this->fileOutro($infoArr);
			$this->sink_write($data);

			// Clean up db access
			$this->referenceReader->mFetch_finish();

			$this->info = $infoArr;
		}

		// Clean up sink
		$this->sink_finish();

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
		$content = '';

		$num = intval($infoArr['pubNum']);

		$content .= 'This file was created by the TYPO3 extension' . "\n";
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
	 *  1 - Sink failed
	 * -1 - Sink is up to date
	 *
	 * @return int
	 */
	protected function sink_init() {
		if ($this->dynamic) {
			$this->data = '';
		} else {
			// Open file
			$file_abs = $this->getAbsoluteFilePath();

			if ($this->isFileMoreUpToDate($file_abs)
					&& !$this->pi1->extConf['debug']
			) {
				return -1;
			} else {
			}

			$this->file_res = fopen($file_abs, 'w');

			if ($this->file_res) {
				$this->file_new = TRUE;
			} else {
				$this->error = $this->pi1->extKey . ' error: Could not open file ' . $file_abs . ' for writing.';
				return 1;
			}
		}

		return 0;
	}

	/**
	 * @param $data
	 * @return void
	 */
	protected function sink_write($data) {
		if ($this->dynamic) {
			$this->data .= $data;
		} else {
			fwrite($this->file_res, $data);
		}
	}

	/**
	 * @return void
	 */
	protected function sink_finish() {
		if (!$this->dynamic) {
			if ($this->file_res) {
				fclose($this->file_res);
				$this->file_res = FALSE;
			}
		}
	}

}

?>