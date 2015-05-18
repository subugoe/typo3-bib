<?php
namespace Ipf\Bib\Utility;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class provides the reference database interface
 * and some utility methods
 */
class DbUtility {

	/**
	 * @var \Ipf\Bib\Utility\ReferenceReader
	 */
	public $referenceReader;

	/**
	 * @var String
	 */
	public $charset = 'UTF-8';

	/**
	 * @var int
	 */
	public $ft_max_num = 100;

	/**
	 * @var int
	 */
	public $ft_max_sec = 3;

	/**
	 * @var string
	 */
	public $pdftotext_bin = 'pdftotext';

	/**
	 * @var string
	 */
	public $tmp_dir = '/tmp';

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $db;

	/**
	 * constructor
	 */
	public function __construct() {
		$this->db = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Initializes the import. The argument must be the plugin class
	 *
	 * @param bool|\Ipf\Bib\Utility\ReferenceReader $referenceReader
	 * @return void
	 */
	public function initialize($referenceReader = FALSE) {
		if (is_object($referenceReader)) {
			$this->referenceReader =& $referenceReader;
		} else {
			$this->referenceReader = GeneralUtility::makeInstance(\Ipf\Bib\Utility\ReferenceReader::class);
		}
	}


	/**
	 * Deletes authors that have no publications
	 *
	 * @return int The number of deleted authors
	 */
	public function deleteAuthorsWithoutPublications() {

		$selectQuery = 'SELECT t_au.uid';
		$selectQuery .= ' FROM ' . $this->referenceReader->getAuthorTable() . ' AS t_au';
		$selectQuery .= ' LEFT OUTER JOIN ' . $this->referenceReader->getAuthorshipTable() . ' AS t_as ';
		$selectQuery .= ' ON t_as.author_id = t_au.uid AND t_as.deleted = 0 ';
		$selectQuery .= ' WHERE t_au.deleted = 0 ';
		$selectQuery .= ' GROUP BY t_au.uid ';
		$selectQuery .= ' HAVING count(t_as.uid) = 0;';

		$uids = [];
		$res = $this->db->sql_query($selectQuery);

		while ($row = $this->db->sql_fetch_assoc($res)) {
			$uids[] = $row['uid'];
		}

		$count = sizeof($uids);
		if ($count > 0) {
			$csv = Utility::implode_intval(',', $uids);

			$this->db->exec_UPDATEquery(
				$this->referenceReader->getAuthorTable(),
				'uid IN ( ' . $csv . ')',
				[
					'deleted' => '1'
				]
			);
		}
		return $count;
	}


	/**
	 * Reads the full text generation configuration
	 *
	 * @param array $configuration
	 * @return void
	 */
	public function readFullTextGenerationConfiguration($configuration) {

		if (is_array($configuration)) {
			if (isset ($configuration['max_num'])) {
				$this->ft_max_num = intval($configuration['max_num']);
			}
			if (isset ($configuration['max_sec'])) {
				$this->ft_max_sec = intval($configuration['max_sec']);
			}
			if (isset ($configuration['pdftotext_bin'])) {
				$this->pdftotext_bin = trim($configuration['pdftotext_bin']);
			}
			if (isset ($configuration['tmp_dir'])) {
				$this->tmp_dir = trim($configuration['tmp_dir']);
			}
		}
	}


	/**
	 * Updates the full_text field for all references if neccessary
	 *
	 * @param bool $force
	 * @return array An array with some statistical data
	 */
	public function update_full_text_all($force = FALSE) {
		$stat = [];
		$stat['updated'] = [];
		$stat['errors'] = [];
		$stat['limit_num'] = 0;
		$stat['limit_time'] = 0;
		$uids = [];

		$whereClause = [];

		if (sizeof($this->referenceReader->pid_list) > 0) {
			$csv = Utility::implode_intval(',', $this->referenceReader->pid_list);
			$whereClause[] = 'pid IN (' . $csv . ')';
		}
		$whereClause[] = '( LENGTH(file_url) > 0 OR LENGTH(full_text_file_url) > 0 )';

		$whereClause = implode(' AND ', $whereClause);
		$whereClause .= $this->referenceReader->enable_fields($this->referenceReader->getReferenceTable());
		$res = $this->db->exec_SELECTquery(
			'uid',
			$this->referenceReader->getReferenceTable(),
			$whereClause
		);

		while ($row = $this->db->sql_fetch_assoc($res)) {
			$uids[] = intval($row['uid']);
		}

		$time_start = time();
		foreach ($uids as $uid) {
			$err = $this->update_full_text($uid, $force);
			if (is_array($err)) {
				$stat['errors'][] = [$uid, $err];
			} else {
				if ($err) {
					$stat['updated'][] = $uid;
					if (sizeof($stat['updated']) >= $this->ft_max_num) {
						$stat['limit_num'] = 1;
						break;
					}
				}
			}

			// Check time limit
			$time_delta = time() - $time_start;
			if ($time_delta >= $this->ft_max_sec) {
				$stat['limit_time'] = 1;
				break;
			}
		}
		return $stat;
	}


	/**
	 * Updates the full_text for the reference with the given uid
	 *
	 * @param int $uid
	 * @param bool $force
	 * @return string|bool
	 */
	protected function update_full_text($uid, $force = FALSE) {

		$whereClause = 'uid=' . intval($uid);
		$rows = $this->db->exec_SELECTgetRows(
			'file_url,full_text_tstamp,full_text_file_url',
			$this->referenceReader->getReferenceTable(),
			$whereClause
		);
		if (sizeof($rows) != 1) {
			return FALSE;
		}
		$pub = $rows[0];

		// Determine File time
		$file = $pub['file_url'];
		$file_low = strtolower($file);
		$file_start = substr($file, 0, 9);
		$file_end = substr($file_low, -4, 4);
		$file_exists = FALSE;

		if ((strlen($file) > 0)
				&& ($file_start == 'fileadmin')
				&& ($file_end == '.pdf')
		) {
			$root = PATH_site;
			if (substr($root, -1, 1) != '/') {
				$root .= '/';
			}
			$file = $root . $file;
			if (file_exists($file)) {
				$file_mt = filemtime($file);
				$file_exists = TRUE;
			}
		}

		$db_update = FALSE;
		$db_data['full_text'] = '';
		$db_data['full_text_tstamp'] = time();
		$db_data['full_text_file_url'] = '';

		if (!$file_exists) {
			$clear = FALSE;
			if (strlen($pub['full_text_file_url']) > 0) {
				$clear = TRUE;
				if (strlen($pub['file_url']) > 0) {
					if ($pub['file_url'] == $pub['full_text_file_url']) {
						$clear = FALSE;
					}
				}
			}

			if ($clear) {
				$db_update = TRUE;
			} else {
				return FALSE;
			}
		}

		// Actually update
		if ($file_exists && (
						($file_mt > $pub['full_text_tstamp']) ||
						($pub['file_url'] != $pub['full_text_file_url']) ||
						$force)
		) {
			// Check if pdftotext is executable
			if (!is_executable($this->pdftotext_bin)) {
				$err = [];
				$err['msg'] = 'The pdftotext binary \'' . strval($this->pdftotext_bin) .
						'\' is no executable';
				return $err;
			}

			// Determine temporary text file
			$target = tempnam($this->tmp_dir, 'bib_pdftotext');
			if ($target === FALSE) {
				$err = [];
				$err['msg'] = 'Could not create temporary file in ' . strval($this->tmp_dir);
				return $err;
			}

			// Compose and execute command
			$charset = strtoupper($this->charset);
			$file_shell = escapeshellarg($file);
			$target_shell = escapeshellarg($target);

			$cmd = strval($this->pdftotext_bin);
			if (strlen($charset) > 0) {
				$cmd .= ' -enc ' . $charset;
			}
			$cmd .= ' ' . $file_shell;
			$cmd .= ' ' . $target_shell;

			$cmd_txt = [];
			$retval = FALSE;
			exec($cmd, $cmd_txt, $retval);
			if ($retval != 0) {
				$err = [];
				$err['msg'] = 'pdftotext failed on ' . strval($pub['file_url']) . ': ' . implode('', $cmd_txt);
				return $err;
			}

			// Read text file
			$handle = fopen($target, 'rb');
			$full_text = fread($handle, filesize($target));
			fclose($handle);

			// Delete temporary text file
			unlink($target);

			$db_update = TRUE;
			$db_data['full_text'] = $full_text;
			$db_data['full_text_file_url'] = $pub['file_url'];
		}

		if ($db_update) {

			$ret = $this->db->exec_UPDATEquery(
				$this->referenceReader->getReferenceTable(),
				$whereClause,
				$db_data
			);
			if ($ret == FALSE) {
				$err = [];
				$err['msg'] = 'Full text update failed: ' . $this->db->sql_error();
				return $err;
			}
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param int $pid
	 */
	public function deleteAllFromPid($pid) {
		$this->db->exec_DELETEquery(
				$this->referenceReader->getAuthorshipTable(),
				'pid = ' . $pid
		);
		$this->db->exec_DELETEquery(
				$this->referenceReader->getAuthorTable(),
				'pid = ' . $pid
		);
		$this->db->exec_DELETEquery(
				$this->referenceReader->getReferenceTable(),
				'pid = ' . $pid
		);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Utility/DbUtility.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Utility/DbUtility.php']);
}
