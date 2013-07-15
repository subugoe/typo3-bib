<?php

/**
 * This class provides functions to write or manipulate 
 * publication reference entries
 *
 * @author Sebastian Holtermann
 */
class Tx_Bib_Utility_ReferenceWriter {

	public $ref_read;
	public $clear_cache;

	protected $error;


	/**
	 * The constructor
	 *
	 * @return void
	 */
	function initialize ( $ref_read ) {
		$this->ref_read =& $ref_read;
		$this->clear_cache = FALSE;
		$this->error = FALSE;
	}
	
	

	/**
	 * Returns the error message and resets it.
	 * The returned message is either a string or FALSE
	 *
	 * @return The last error message
	 */
	function error_message ( ) {
		$err = $this->error;
		$this->error = FALSE;
		return $err;
	}


	/** 
	 * Same as error_message() but returns a html version
	 *
	 * @return The last error message
	 */
	function html_error_message ( ) {
		$err = $this->error_message();
		$err = str_replace ( "\n", "<br/>\n", $err );
		return $err;
	}


	/** 
	 * Clears the page cache of all selected pages
	 *
	 * @return void
	 */
	function clear_page_cache ( ) {
		if ( $this->clear_cache ) {
			//t3lib_div::debug ( 'Clearing cache' );
			$tce = t3lib_div::makeInstance ( 't3lib_TCEmain' );
			$clear_cache = array();

			$be_user = $GLOBALS['BE_USER'];
			if ( is_object ( $be_user ) || is_array ( $be_user->user ) ) {
				$tce->start ( array(), array(), $be_user );
				// Find storage cache clear requests
				foreach ( $this->ref_read->pid_list as $pid ) {
					$tsc = $tce->getTCEMAIN_TSconfig ( $pid );
					if ( is_array ( $tsc ) && isset ( $tsc['clearCacheCmd'] ) ) {
						$clear_cache[] = $tsc['clearCacheCmd'];
					}
				}
			} else {
				$tce->admin = 1;
			}

			// Clear this page cache
			$clear_cache[] = strval ( $GLOBALS['TSFE']->id );

			foreach ( $clear_cache as $cache ) {
				$tce->clear_cacheCmd ( $cache );
			}
		} else {
			//t3lib_div::debug ( 'Not clearing cache' );
		}
	}


	/**
	 * This function updates a publication with all data
	 * found in the HTTP request
	 * 
	 * @return TRUE on error FALSE otherwise
	 */
	function save_publication ( $pub ) {
		if ( !is_array ( $pub ) )
			return TRUE;

		$new = False;
		$uid = -1;
		$db =& $GLOBALS['TYPO3_DB'];
		$rT =& $this->ref_read->refTable;
		//t3lib_div::debug ( $pub );

		// Fetch reference from DB
		$pub_db = NULL;
		if ( is_numeric ( $pub['uid'] ) ) {
			$pub_db = $this->ref_read->fetch_db_pub ( intval ( $pub['uid'] ) );
			if ( is_array ( $pub_db ) ) {
				$uid = intval ( $pub_db['uid'] );
			} else {
				$this->error = 'The publication reference could not be updated' .
					' because it does not exist in the database (anymore?).';
				$this->ref_log ( $this->error, $pub['uid'], 1 );
				return TRUE;
			}
		}

		// Acquire the storage folder pid if it is not given
		if ( !is_numeric ( $pub['pid'] ) ) {
			if ( is_array ( $pub_db ) )
				$pub['pid'] = intval ( $pub_db['pid'] );
			else
				$pub['pid'] = $this->ref_read->pid_list[0];
		}

		// Check if the pid is in the allowed list
		if ( !in_array ( $pub['pid'], $this->ref_read->pid_list ) ) {
			$this->error = 'The given storage folder (pid=' . strval ( $pub['pid'] ) . 
				') is not in the list of allowed publication storage folders';
			$this->log ( $this->error, 1 );
			return TRUE;
		}

		$refRow = array ( );
		// Copy reference fiels
		foreach ( $this->ref_read->refFields as $f ) {
			switch ( $f ) {
				default:
					if ( array_key_exists ( $f, $pub ) )
						$refRow[$f] = $pub[$f];
			}
		}

		// Add TYPO3 fields
		$refRow['pid']    = intval ( $pub['pid'] );
		$refRow['tstamp'] = time();
		$refRow['hidden'] = intval ( $pub['hidden'] );

		$query = '';
		if ( $uid >= 0 ) {
			if ( $pub['mod_key'] == $pub_db['mod_key'] ) {
				// t3lib_div::debug ( array ('updating'=>$refRow ));
				$WC = 'uid=' . intval ( $uid );
				$ret = $db->exec_UPDATEquery ( $rT, $WC, $refRow );
				if ( $ret == FALSE ) {
					$this->error = 'A publication reference could not be updated uid='.strval ( $uid );
					$this->log ( $this->error, 2 );
					return TRUE;
				}
			} else {
				$this->error = 'The publication reference could not be updated' .
					' because the modification key does not match.' . "\n";
				$this->error .= ' Maybe someone edited this reference meanwhile.';
				$this->ref_log ( $this->error, $uid, 1 );
				return TRUE;
			}
		} else {
			$new = TRUE;
			// t3lib_div::debug ( array ( 'saving' => $refRow ) );

			// Creation user id if available
			$cruser_id = 0;
			$be_user = $GLOBALS['BE_USER'];
			if ( is_object ( $be_user ) && is_array ( $be_user->user ) ) {
				$cruser_id = intval ( $be_user->user['uid'] );
			}

			$refRow['crdate']    = $refRow['tstamp'];
			$refRow['cruser_id'] = $cruser_id;
			$db->exec_INSERTquery ( $rT, $refRow );
			$uid = $db->sql_insert_id ( );
			if ( $uid > 0 ) {
				//t3lib_div::debug ( array ('Inserted publication'=>$refRow ) );
			} else {
				$this->error = 'A publication reference could not be inserted into the database';
				$this->log ( $this->error, 2 );
				return TRUE;
			}
		}

		if ( ( $uid > 0 ) && ( sizeof ( $pub['authors'] ) > 0 ) ) {
			$ret = $this->save_publication_authors ( $uid, $pub['pid'], $pub['authors'] );
			if ( $ret )
				return TRUE;
		}

		if ( $new )
			$this->ref_log ( 'A new publication reference was inserted (pid=' . $pub['pid'] . ')', $uid );
		else
			$this->ref_log ( 'A publication reference was modified', $uid );

		$this->clear_page_cache ( );
		return FALSE;
	}


	/**
	 * Saves the authors of a publication
	 *
	 * @return The uid of the inserted author
	 */
	function save_publication_authors ( $pub_uid, $pid, $authors ) {
		$db =& $GLOBALS['TYPO3_DB'];

		// Fetches missing author uids and
		// inserts new authors on demand
		$sort = 0;
		foreach ( $authors as &$author ) {
			// Set new sorting value
			$sort += 1;
			$author['sorting'] = $sort;

			if ( !is_numeric ( $author['uid'] ) ) {
				$uids = $this->ref_read->fetch_author_uids ( $author, $pid );
				//t3lib_div::debug ( array ('author'=>$author, 'uids'=>$uids ) );

				if ( sizeof ( $uids ) > 0 ) {
					$author['uid'] = $uids[0]['uid'];
				} else {
					// Insert missing author
					$ia = $author;
					$ia['pid'] = intval ( $pid );
					
					$author['uid'] = $this->insert_author ( $ia );
					if ( $author['uid'] > 0 ) {
						//t3lib_div::debug ( array ('Inserted author'=>$author ) );
					} else {
						$this->error = 'An author could not be inserted into the database';
						$this->log ( $this->error, 1 );
						return TRUE;
					}
				}
			}
		}

		$db_aships = $this->ref_read->fetch_authorships ( array ( 'pub_id' => $pub_uid ) );

		$as_delete = array();
		$as_new = sizeof ( $authors ) - sizeof ( $db_aships );
		if ( $as_new < 0 ) {
			// This deletes the first authorships
			$as_new = abs ( $as_new );
			for ( $ii = 0; $ii < $as_new; $ii++ ) {
				$as_delete[] = $db_aships[$ii]['uid'];
			}
			//t3lib_div::debug ( array ('as_delete'=>$as_delete ) );
			$this->delete_authorships ( $as_delete );
			$db_aships = array_slice ( $db_aships, $as_new );
			//t3lib_div::debug ( array ('db_aships'=>$db_aships ) );
			$as_new = 0;
		}
		$as_present = sizeof ( $authors ) - $as_new;

		// Inserts new and updates old authorships
		for ( $ii = 0; $ii < sizeof ( $authors ); $ii++ ) {
			$author =& $authors[$ii];
			if ( is_numeric ( $author['uid'] ) ) {
				$as = array();
				$as['pid'] = intval ( $pid );
				$as['pub_id'] = intval ( $pub_uid );
				$as['author_id'] = intval ( $author['uid'] );
				$as['sorting'] = $author['sorting'];

				if ( $ii < $as_present ) {
					// There are present authorships - Update authorship
					$as_uid = $db_aships[$ii]['uid'];
					$ret = $db->exec_UPDATEquery ( $this->ref_read->aShipTable, 'uid='.intval($as_uid), $as );
					if ( $ret == FALSE ) {
						$this->error = 'An authorship could not be updated uid='.strval( $as_uid );
						$this->log ( $this->error, 1 );
						return TRUE;
					}
				} else {
					// No more present authorships - Insert authorship
					$as_uid = $db->exec_INSERTquery ( $this->ref_read->aShipTable, $as );
					if ( $as_uid > 0 ) {
						//t3lib_div::debug ( array ('Inserted authorship'=>$as ) );
					} else {
						$this->error = 'An authorship could not be inserted into the database';
						$this->log ( $this->error, 1 );
						return TRUE;
					}
				}
			}
		}

	}


	/**
	 * Inserts an author
	 *
	 * @return The uid of the inserted author
	 */
	function insert_author ( $author ) {
		$ia = array();
		$ia['forename'] = $author['forename'];
		$ia['surname']  = $author['surname'];
		$ia['url']      = $author['url'];
		$ia['pid']      = intval ( $author['pid'] );

		// Creation user id if available
		$cruser_id = 0;
		$be_user = $GLOBALS['BE_USER'];
		if ( is_object ( $be_user ) && is_array ( $be_user->user ) ) {
			$cruser_id = intval ( $be_user->user['uid'] );
		}

		$ia['tstamp'] = time();
		$ia['crdate'] = time();
		$ia['cruser_id'] = $cruser_id;

		//t3lib_div::debug( array ( 'insert author ' => $ia ) );

		$GLOBALS['TYPO3_DB']->exec_INSERTquery ( $this->ref_read->authorTable, $ia );
		$a_uid = $GLOBALS['TYPO3_DB']->sql_insert_id ( );
		return $a_uid;
	}


	/**
	 * Deletes an authorship
	 *
	 */
	function delete_authorship ( $uid ) {
		//t3lib_div::debug ( array ('Deleting authorship: '=>$as_id ) );
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery ( $this->ref_read->aShipTable, 
			'uid='.intval($uid).' AND deleted=0', array ( 'deleted'=>1 ) );
	}


	/**
	 * Deletes some authorships
	 *
	 */
	function delete_authorships ( $uids ) {
		$uid_list = '';
		for ( $ii = 0; $ii < sizeof ( $uids ); $ii++ ) {
			if ( $ii > 0 )
				$uid_list .= ',';
			$uid_list .= intval ( $uids[$ii] );
		}
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery ( $this->ref_read->aShipTable, 
			'uid IN ('.$uid_list.') AND deleted=0', array ( 'deleted'=>1 ) );
	}


	/**
	 * Sets or unsets the hidden flag in the database entry
	 *
	 * @return void
	 */
	function hide_publication ( $uid, $hidden=TRUE ) {
		$uid = intval ( $uid );
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery (
			$this->ref_read->refTable, 'uid=' . strval ( $uid ) ,
			array ( 'hidden'=>($hidden?'1':'0'),'tstamp'=>time() ) );
		$this->ref_log ( 'A publication reference was ' . ($hidden ? 'hidden' : 'revealed'), $uid );
		$this->clear_page_cache ( );
	}


	/**
	 * Sets the deleted flag in the database entry.
	 * Only the reference and the authorships get deleted.
	 * The author stays untouched even if he/her has no authorship
	 * after this anymore.
	 *
	 * @return Not defined
	 */
	function delete_publication ( $uid, $mod_key ) {
		$deleted = 1;

		$uid = intval ( $uid );
		$db_pub = $this->ref_read->fetch_db_pub ( $uid );
		if ( is_array ( $db_pub ) ) {
			if ( $db_pub['mod_key'] == $mod_key ) {
				// Delete authorships
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery ( $this->ref_read->aShipTable, 
					'pub_id='.intval($uid).' AND deleted=0',
					array ( 'deleted'=>$deleted ) );
		
				// Delete reference
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery ( $this->ref_read->refTable, 
					'uid='.intval($uid).' AND deleted=0',
					array ( 'deleted'=>$deleted,'tstamp'=>time() ) );

				$this->clear_page_cache ( );

				$this->ref_log ( 'A publication reference was deleted', $uid );

			} else {
				$this->error = 'The publication reference could not be deleted' .
					' because the modification key does not match.' . "\n";
				$this->error .= ' Maybe someone edited this reference meanwhile.';
				$this->ref_log ( $this->error, $uid ,1 );
				return TRUE;
			}
		} else {
			$this->error = 'The publication reference could not be deleted' .
				' because it does not exist in the database.';
			$this->ref_log ( $this->error, $uid ,1 );
			return TRUE;
		}

		return FALSE;
	}


	/**
	 * Removes the entry from the database.
	 * The entry must have been marked deleted beforehand.
	 * This erases the referenc and the authorships but not the author
	 *
	 * @return Not defined
	 */
	function erase_publication ( $uid ) {

		// Delete authorships
		$GLOBALS['TYPO3_DB']->exec_DELETEquery (
			$this->ref_read->aShipTable, 'pub_id='.intval($uid).' AND deleted!=0' );

		// Delete reference
		$GLOBALS['TYPO3_DB']->exec_DELETEquery (
			$this->ref_read->refTable, 'uid='.intval($uid).' AND deleted!=0' );

		$this->ref_log ( 'A publication reference was erased', $uid );

		return FALSE;
	}


	/**
	 * Writes a log entry
	 *
	 * @return void
	 */
	function log ( $message, $error = 0 ) {
		$be_user = $GLOBALS['BE_USER'];
		if ( is_object ( $be_user ) ) {
			$be_user->simplelog ( $message, 'bib', $error );
		}
	}


	/**
	 * Writes a log entry
	 *
	 * @return void
	 */
	function ref_log ( $message, $uid, $error = 0 ) {
		$message = $message . ' (' . $this->ref_read->refTable  . ':' . intval ( $uid ) . ')';
		$this->log ( $message, $error );
	}

}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/res/class.Tx_Bib_Utility_ReferenceWriter.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/res/class.Tx_Bib_Utility_ReferenceWriter.php"]);
}

?>