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

/**
 * This class provides functions to write or manipulate
 * publication reference entries
 */
class ReferenceWriter {

	/**
	 * @var \Ipf\Bib\Utility\ReferenceReader
	 */
	public $referenceReader;

	/**
	 * @var bool
	 */
	public $clear_cache = FALSE;

	/**
	 * @var bool
	 */
	protected $error = FALSE;

	/**
	 * Initialize ReferenceWriter
	 *
	 * @param \Ipf\Bib\Utility\ReferenceReader $referenceReader
	 * @return void
	 */
	public function initialize($referenceReader) {
		$this->referenceReader =& $referenceReader;
	}


	/**
	 * Returns the error message and resets it.
	 * The returned message is either a string or FALSE
	 *
	 * @return string The last error message
	 */
	public function error_message() {
		$err = $this->error;
		$this->error = FALSE;
		return $err;
	}


	/**
	 * Same as error_message() but returns a html version
	 *
	 * @return string The last error message
	 */
	public function html_error_message() {
		$err = $this->error_message();
		$err = str_replace("\n", "<br/>\n", $err);
		return $err;
	}


	/**
	 * Clears the page cache of all selected pages
	 *
	 * @return void
	 */
	protected function clear_page_cache() {
		if ($this->clear_cache) {
			/** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
			$dataHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$clear_cache = array();

			$be_user = $GLOBALS['BE_USER'];
			if (is_object($be_user) || is_array($be_user->user)) {
				$dataHandler->start(array(), array(), $be_user);
				// Find storage cache clear requests
				foreach ($this->referenceReader->pid_list as $pid) {
					$tsc = $dataHandler->getTCEMAIN_TSconfig($pid);
					if (is_array($tsc) && isset ($tsc['clearCacheCmd'])) {
						$clear_cache[] = $tsc['clearCacheCmd'];
					}
				}
			} else {
				$dataHandler->admin = 1;
			}

			// Clear this page cache
			$clear_cache[] = strval($GLOBALS['TSFE']->id);

			foreach ($clear_cache as $cache) {
				$dataHandler->clear_cacheCmd($cache);
			}
		}
	}


	/**
	 * This function updates a publication with all data
	 * found in the HTTP request
	 *
	 * @param array $publication
	 * @return bool TRUE on error FALSE otherwise
	 */
	public function savePublication($publication) {
		if (!is_array($publication)) {
			return TRUE;
		}

		$new = False;
		$uid = -1;

		$referenceTable =& $this->referenceReader->getReferenceTable();

		// Fetch reference from DB
		$pub_db = NULL;
		if (is_numeric($publication['uid'])) {
			$pub_db = $this->referenceReader->getPublicationDetails(intval($publication['uid']));
			if (is_array($pub_db)) {
				$uid = intval($pub_db['uid']);
			} else {
				$this->error = 'The publication reference could not be updated' .
						' because it does not exist in the database (anymore?).';
				$this->referenceLog($this->error, $publication['uid'], 1);
				return TRUE;
			}
		}

		// Acquire the storage folder pid if it is not given
		if (!is_numeric($publication['pid'])) {
			if (is_array($pub_db)) {
				$publication['pid'] = intval($pub_db['pid']);
			} else {
				$publication['pid'] = $this->referenceReader->pid_list[0];
			}
		}

		// Check if the pid is in the allowed list
		if (!in_array($publication['pid'], $this->referenceReader->pid_list)) {
			$this->error = 'The given storage folder (pid=' . strval($publication['pid']) .
					') is not in the list of allowed publication storage folders';
			$this->log($this->error, 1);
			return TRUE;
		}

		$referenceRow = array();
		// Copy reference fields
		foreach ($this->referenceReader->getReferenceFields() as $field) {
			switch ($field) {
				default:
					if (array_key_exists($field, $publication)) {
						$referenceRow[$field] = $publication[$field];
					}
			}
		}

		// Add TYPO3 fields
		$referenceRow['pid'] = intval($publication['pid']);
		$referenceRow['tstamp'] = time();
		$referenceRow['hidden'] = intval($publication['hidden']);

		$query = '';
		if ($uid >= 0) {
			if ($publication['mod_key'] == $pub_db['mod_key']) {

				$whereClause = 'uid=' . intval($uid);

				$ret = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					$referenceTable,
					$whereClause,
					$referenceRow
				);

				if ($ret == FALSE) {
					$this->error = 'A publication reference could not be updated uid=' . strval($uid);
					$this->log($this->error, 2);
					return TRUE;
				}
			} else {
				$this->error = 'The publication reference could not be updated' .
						' because the modification key does not match.' . "\n";
				$this->error .= ' Maybe someone edited this reference meanwhile.';
				$this->referenceLog($this->error, $uid, 1);
				return TRUE;
			}
		} else {
			$new = TRUE;

			// Creation user id if available
			$cruser_id = 0;
			$be_user = $GLOBALS['BE_USER'];
			if (is_object($be_user) && is_array($be_user->user)) {
				$cruser_id = intval($be_user->user['uid']);
			}

			$referenceRow['crdate'] = $referenceRow['tstamp'];
			$referenceRow['cruser_id'] = $cruser_id;
			$GLOBALS['TYPO3_DB']->exec_INSERTquery(
				$referenceTable,
				$referenceRow
			);

			$uid = $GLOBALS['TYPO3_DB']->sql_insert_id();
			if ($uid > 0) {

			} else {
				$this->error = 'A publication reference could not be inserted into the database';
				$this->log($this->error, 2);
				return TRUE;
			}
		}

		if (($uid > 0) && (sizeof($publication['authors']) > 0)) {
			$ret = $this->save_publication_authors($uid, $publication['pid'], $publication['authors']);
			if ($ret) {
				return TRUE;
			}
		}

		if ($new) {
			$this->referenceLog('A new publication reference was inserted (pid=' . $publication['pid'] . ')', $uid);
		} else {
			$this->referenceLog('A publication reference was modified', $uid);
		}

		$this->clear_page_cache();
		return FALSE;
	}


	/**
	 * Saves the authors of a publication
	 *
	 * @param int $pub_uid
	 * @param int $pid
	 * @param array $authors
	 * @return int The uid of the inserted author
	 */
	public function save_publication_authors($pub_uid, $pid, $authors) {
		// Fetches missing author uids and
		// inserts new authors on demand
		$sort = 0;
		foreach ($authors as &$author) {
			// Set new sorting value
			$sort += 1;
			$author['sorting'] = $sort;

			if (!is_numeric($author['uid'])) {
				$uids = $this->referenceReader->fetch_author_uids($author, $pid);

				if (sizeof($uids) > 0) {
					$author['uid'] = $uids[0]['uid'];
				} else {
					// Insert missing author
					$ia = $author;
					$ia['pid'] = intval($pid);

					$author['uid'] = $this->insert_author($ia);
					if ($author['uid'] > 0) {

					} else {
						$this->error = 'An author ' . $ia['surename'] . '  could not be inserted into the database';
						$this->log($this->error, 1);
						return TRUE;
					}
				}
			}
		}

		$db_aships = $this->referenceReader->getAuthorships(array('pub_id' => $pub_uid));

		$as_delete = array();
		$as_new = sizeof($authors) - sizeof($db_aships);
		if ($as_new < 0) {
			// This deletes the first authorships
			$as_new = abs($as_new);
			for ($ii = 0; $ii < $as_new; $ii++) {
				$as_delete[] = $db_aships[$ii]['uid'];
			}

			$this->delete_authorships($as_delete);
			$db_aships = array_slice($db_aships, $as_new);

			$as_new = 0;
		}
		$as_present = sizeof($authors) - $as_new;

		// Inserts new and updates old authorships
		for ($ii = 0; $ii < sizeof($authors); $ii++) {
			$author =& $authors[$ii];
			if (is_numeric($author['uid'])) {
				$as = array();
				$as['pid'] = intval($pid);
				$as['pub_id'] = intval($pub_uid);
				$as['author_id'] = intval($author['uid']);
				$as['sorting'] = $author['sorting'];

				if ($ii < $as_present) {
					// There are present authorships - Update authorship
					$as_uid = $db_aships[$ii]['uid'];

					$ret = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						$this->referenceReader->getAuthorshipTable(),
						'uid=' . intval($as_uid),
						$as
					);

					if ($ret == FALSE) {
						$this->error = 'An authorship could not be updated uid=' . strval($as_uid);
						$this->log($this->error, 1);
						return TRUE;
					}
				} else {
					// No more present authorships - Insert authorship
					$as_uid = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
						$this->referenceReader->getAuthorshipTable(),
						$as
					);

					if ($as_uid > 0) {

					} else {
						$this->error = 'An authorship could not be inserted into the database';
						$this->log($this->error, 1);
						return TRUE;
					}
				}
			}
		}
	}


	/**
	 * Inserts an author
	 *
	 * @param array $author
	 * @return int The uid of the inserted author
	 */
	public function insert_author($author) {
		$author['pid'] = intval($author['pid']);

		// Creation user id if available
		$cruser_id = 0;
		$backendUser = $GLOBALS['BE_USER'];
		if (is_object($backendUser) && is_array($backendUser->user)) {
			$cruser_id = intval($backendUser->user['uid']);
		}

		$author['tstamp'] = time();
		$author['crdate'] = time();
		$author['cruser_id'] = $cruser_id;

		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			$this->referenceReader->getAuthorTable(),
			$author
		);

		$authorUid = $GLOBALS['TYPO3_DB']->sql_insert_id();
		return $authorUid;
	}


	/**
	 * Deletes an authorship
	 *
	 * @param int $uid;
	 * @return void
	 */
	public function delete_authorship($uid) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			$this->referenceReader->getAuthorshipTable(),
			'uid=' . intval($uid) . ' AND deleted=0',
			array(
				'deleted' => 1
			)
		);
	}


	/**
	 * Deletes some authorships
	 *
	 * @param array $uids
	 * @return void
	 */
	public function delete_authorships($uids) {
		$uid_list = '';

		for ($ii = 0; $ii < sizeof($uids); $ii++) {
			if ($ii > 0)
				$uid_list .= ',';
			$uid_list .= intval($uids[$ii]);
		}

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			$this->referenceReader->getAuthorshipTable(),
			'uid IN (' . $uid_list . ') AND deleted=0',
			array(
				'deleted' => 1
			)
		);
	}


	/**
	 * Sets or unsets the hidden flag in the database entry
	 *
	 * @param int $uid
	 * @param bool $hidden
	 * @return void
	 */
	public function hide_publication($uid, $hidden = TRUE) {
		$uid = intval($uid);

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			$this->referenceReader->getReferenceTable(),
			'uid=' . strval($uid),
			array(
				'hidden' => ($hidden ? '1' : '0'),
				'tstamp' => time()
			)
		);

		$this->referenceLog('A publication reference was ' . ($hidden ? 'hidden' : 'revealed'), $uid);
		$this->clear_page_cache();
	}


	/**
	 * Sets the deleted flag in the database entry.
	 * Only the reference and the authorships get deleted.
	 * The author stays untouched even if he/her has no authorship
	 * after this anymore.
	 *
	 * @param int $uid
	 * @param string $mod_key
	 * @return bool
	 */
	public function delete_publication($uid, $mod_key) {
		$deleted = 1;

		$uid = intval($uid);
		$db_pub = $this->referenceReader->getPublicationDetails($uid);
		if (is_array($db_pub)) {
			if ($db_pub['mod_key'] == $mod_key) {

				// Delete authorships
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					$this->referenceReader->getAuthorshipTable(),
					'pub_id=' . intval($uid) . ' AND deleted=0',
					array(
						'deleted' => $deleted
					)
				);

				// Delete reference
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					$this->referenceReader->getReferenceTable(),
					'uid=' . intval($uid) . ' AND deleted=0',
					array(
						'deleted' => $deleted,
						'tstamp' => time()
					)
				);

				$this->clear_page_cache();

				$this->referenceLog('A publication reference was deleted', $uid);

			} else {
				$this->error = 'The publication reference could not be deleted' .
						' because the modification key does not match.' . "\n";
				$this->error .= ' Maybe someone edited this reference meanwhile.';
				$this->referenceLog($this->error, $uid, 1);
				return TRUE;
			}
		} else {
			$this->error = 'The publication reference could not be deleted' .
					' because it does not exist in the database.';
			$this->referenceLog($this->error, $uid, 1);
			return TRUE;
		}

		return FALSE;
	}


	/**
	 * Removes the entry from the database.
	 * The entry must have been marked deleted beforehand.
	 * This erases the reference and the authorships but not the author
	 *
	 * @param int $uid
	 * @return bool
	 */
	public function erase_publication($uid) {

		// Delete authorships
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			$this->referenceReader->getAuthorshipTable(),
			'pub_id=' . intval($uid) . ' AND deleted!=0'
		);

		// Delete reference
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			$this->referenceReader->getReferenceTable(),
			'uid=' . intval($uid) . ' AND deleted!=0'
		);

		$this->referenceLog('A publication reference was erased', $uid);

		return FALSE;
	}


	/**
	 * Writes a log entry
	 *
	 * @param string $message
	 * @param int $error
	 * @return void
	 */
	protected function log($message, $error = 0) {
		$be_user = $GLOBALS['BE_USER'];
		if (is_object($be_user)) {
			$be_user->simplelog($message, 'bib', $error);
		}
	}


	/**
	 * Writes a log entry for the reference log
	 *
	 * @param string $message
	 * @param int $uid
	 * @param mixed $error
	 * @return void
	 */
	protected function referenceLog($message, $uid, $error = 0) {
		$message = $message . ' (' . $this->referenceReader->getReferenceTable() . ':' . intval($uid) . ')';
		$this->log($message, $error);
	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/Classes/Utility/ReferenceWriter.php"]) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/Classes/Utility/ReferenceWriter.php"]);
}

?>