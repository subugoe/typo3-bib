<?php
namespace Ipf\Bib\Utility\Exporter;

class Exporter {

	/**
	 * @var tx_bib_pi1
	 */
	public $pi1;

	/**
	 * @var Tx_Bib_Utility_ReferenceReader
	 */
	public $referenceReader;
	public $filters;
	public $filter_key;
	public $page_mode;

	public $file_path;
	public $file_name;
	public $file_new;

	public $file_res;
	public $dynamic;
	public $data;

	public $info;
	public $error;

	public $EM_CONF;

	/**
	 * Initializes the export. The argument must be the plugin class
	 *
	 * @param tx_bib_pi1 $pi1
	 * @return void
	 */
	function initialize($pi1) {
		$this->pi1 =& $pi1;
		$this->referenceReader =& $pi1->$referenceReader;

		// Setup filters
		$this->filters = $this->pi1->extConf['filters'];
		unset ($this->filters['br_page']);

		// The filter key is used for the filename
		//$this->filter_key = \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5 ( serialize ( $this->filters ) );
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
	function get_file_rel() {
		return $this->file_path . '/' . $this->file_name;
	}


	/**
	 * Returns absolute system file path
	 *
	 * @return String The absolute file path
	 */
	function get_file_abs() {
		return PATH_site . $this->get_file_rel();
	}


	/**
	 * Checks if the file exists and is newer than
	 * the latest change (tstamp) in the publication database
	 *
	 * @param String $file
	 * @return bool TRUE if file exists and is newer than the
	 *         database content, FALSE otherwise.
	 */
	function file_is_newer($file) {
		$db_time = $this->referenceReader->fetch_max_tstamp();

		if (file_exists($file)) {
			$ft = filemtime($file);
			if (!($ft === FALSE) && ($db_time < $ft)) {
				return TRUE;
			}
		}
		return FALSE;
	}


	/**
	 * This writes the filtered database content
	 * to the export file
	 *
	 * @return TRUE ond error, FALSE otherwise
	 */
	function export() {
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
			$data = $this->file_intro($infoArr);
			$this->sink_write($data);

			// Write publications
			while ($pub = $this->referenceReader->mFetch()) {
				$infoArr['index']++;
				$data = $this->export_format_publication($pub, $infoArr);
				$this->sink_write($data);
			}

			// Write post data
			$data = $this->file_outtro($infoArr);
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
	 * @return The export string
	 */
	function export_format_publication($pub, $infoArr = array()) {
		return '';
	}


	/**
	 * Returns the file intro
	 *
	 * @return The file header string
	 */
	function file_intro($infoArr = array()) {
		return '';
	}


	/**
	 * Returns the file outtro
	 *
	 * @return The file header string
	 */
	function file_outtro($infoArr = array()) {
		return '';
	}


	/**
	 * Returns a general information text for the exported dataset
	 *
	 * @return A filter information string
	 */
	function info_text($infoArr = array()) {
		$str = '';

		$num = intval($infoArr['pubNum']);

		$str .= 'This file was created by the TYPO3 extension' . "\n";
		$str .= $this->pi1->extKey;
		if (is_array($this->EM_CONF)) {
			$str .= ' version ' . $this->EM_CONF['version'] . "\n";
		}
		$str .= "\n";
		$str .= '--- Timezone: ' . date('T') . "\n";
		$str .= 'Creation date: ' . date('Y-m-d') . "\n";
		$str .= 'Creation time: ' . date('H-i-s') . "\n";

		if ($num >= 0) {
			$str .= '--- Number of references' . "\n";
			$str .= '' . $num . "\n";
			$str .= '' . "\n";
		}

		return $str;
	}


	/*
	 * Return codes
	 *  0 - Sink ready
	 *  1 - Sink failed
	 * -1 - Sink is up to date
	 */
	function sink_init() {
		if ($this->dynamic) {
			$this->data = '';
		} else {
			// Open file
			$file_abs = $this->get_file_abs();

			if ($this->file_is_newer($file_abs)
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


	function sink_write($data) {
		if ($this->dynamic) {
			$this->data .= $data;
		} else {
			fwrite($this->file_res, $data);
		}
	}


	function sink_finish() {
		if ($this->dynamic) {
			// Nothing
		} else {
			if ($this->file_res) {
				fclose($this->file_res);
				$this->file_res = FALSE;
			}
		}
	}

}

?>