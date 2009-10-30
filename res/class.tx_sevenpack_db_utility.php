<?php
if ( !isset($GLOBALS['TSFE']) )
	die ('This file is not meant to be executed');


require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/res/class.tx_sevenpack_reference_accessor.php' ) );

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/res/class.tx_sevenpack_utility.php' ) );


/**
 * This class provides the reference database interface
 * and some utility methods
 *
 * @author Sebastian Holtermann
 */
class tx_sevenpack_db_utility {

	public $ra;
	public $charset;

	public $pdftotext_bin;
	public $tmp_dir;


	/**
	 * Initializes the import. The argument must be the plugin class
	 *
	 * @return void
	 */
	function initialize ( $ra = FALSE ) {
		if ( is_object ( $ra ) ) {
			$this->ra =& $ra;
		} else {
			$this->ra = t3lib_div::makeInstance ( 'tx_sevenpack_reference_accessor' );
		}

		$this->charset = 'UTF-8';
		$this->pdftotext_bin = 'pdftotext';
		$this->tmp_dir = '/tmp';
	}


	/**
	 * Reads the full text generation configuration
	 *
	 * @return void
	 */
	function read_full_text_conf ( $cfg ) {
		//t3lib_div::debug ( $cfg );
		if ( is_array ( $cfg ) ) {
			if ( isset ( $cfg['pdftotext_bin'] ) ) {
				$this->pdftotext_bin = trim ( $cfg['pdftotext_bin'] );
			}
			if ( isset ( $cfg['tmp_dir'] ) ) {
				$this->tmp_dir = trim ( $cfg['tmp_dir'] );
			}
		}
	}


	/**
	 * Deletes authors that have no publications
	 *
	 * @return The number of deleted authors
	 */
	function delete_no_ref_authors ( ) {
		$aT =& $this->ra->authorTable;
		$sT =& $this->ra->aShipTable;
		$count = 0;

		$sel = 'SELECT t_au.uid' . "\n";
		$sel .= ' FROM ' . $aT . ' AS t_au';
		$sel .= ' LEFT OUTER JOIN ' . $sT . ' AS t_as ' . "\n";
		$sel .= ' ON t_as.author_id = t_au.uid AND t_as.deleted = 0 ' . "\n";
		$sel .= ' WHERE t_au.deleted = 0 ' . "\n";
		$sel .= ' GROUP BY t_au.uid ' . "\n";
		$sel .= ' HAVING count(t_as.uid) = 0;' . "\n";

		$uids = array();
		$res = $GLOBALS['TYPO3_DB']->sql_query ( $sel );
		while ( $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc ( $res ) ) {
			$uids[] = $row['uid'];
		}

		$count = sizeof ( $uids );
		if ( $count > 0 ) {
			$csv = tx_sevenpack_utility::implode_intval ( ',', $uids  );
			//t3lib_div::debug ( $csv );
	
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery ( $aT,
				'uid IN ( ' . $csv . ')', array ( 'deleted' => '1' ) );
		}
		return $count;
	}


	/**
	 * Updates the full_text field for all references if neccessary
	 *
	 * @return An array with some statistical data
	 */
	function update_full_text_all ( $force = FALSE ) {
		$rT =& $this->ra->refTable;
		$stat = array();
		$stat['updated'] = array();
		$stat['errors'] = array();
		$uids = array();

		$WC = array();

		if ( sizeof ( $this->pid_list ) > 0 ) {
			$csv = tx_sevenpack_utility::implode_intval ( ',', $this->pid_list );
			$WC[] = 'pid IN ('.$csv.')';
		}
		$WC[] = '( LENGTH(file_url) > 0 OR LENGTH(full_text_file_url) > 0 )';

		$WC = implode ( ' AND ', $WC );
		$WC .= $this->ra->enable_fields ( $rT );
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( 'uid', $rT, $WC );
		while ( $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc ( $res ) ) {
			$uids[] = intval ( $row['uid'] );
		}

		foreach ( $uids as $uid ) {
			$err = $this->update_full_text ( $uid, $force );
			if ( is_array ( $err ) ) {
				$stat['errors'][] = array ( $uid, $err );
			} else if ( $err ) {
				$stat['updated'][] = $uid;
			}
		}
		return $stat;
	}


	/**
	 * Updates the full_text for the reference with the geiven uid
	 *
	 * @return Not defined
	 */
	function update_full_text ( $uid, $force = FALSE ) {
		$rT =& $this->ra->refTable;
		$db =& $GLOBALS['TYPO3_DB'];

		//t3lib_div::debug ( array ( 'Updating full text' => $uid ) );

		$WC = 'uid=' . intval ( $uid );
		$rows = $db->exec_SELECTgetRows ( 'file_url,full_text_tstamp,full_text_file_url', $rT, $WC );
		if ( sizeof ( $rows ) != 1 ) {
			return FALSE;
		}
		$pub = $rows[0];

		// Determine File time
		$file = $pub['file_url'];
		$file_low = strtolower ( $file );
		$file_start = substr ( $file, 0, 9 );
		$file_end = substr ( $file_low, -4, 4 );
		$file_exists = FALSE;
		//t3lib_div::debug ( array ( 'file' => $file ) );
		//t3lib_div::debug ( array ( 'file_start' => $file_start ) );
		//t3lib_div::debug ( array ( 'file_end' => $file_end ) );
		if ( ( strlen ( $file ) > 0 )
			&& ( $file_start == 'fileadmin' ) 
			&& ( $file_end == '.pdf' ) 
		)
		{
			$root = PATH_site;
			if ( substr ( $root, -1, 1 ) != '/' ) {
				$root .= '/';
			}
			$file = $root . $file;
			if ( file_exists ( $file ) ) {
				$file_mt = filemtime ( $file );
				$file_exists = TRUE;
			}
		}

		$db_update = FALSE;
		$db_data['full_text'] = '';
		$db_data['full_text_tstamp'] = time();
		$db_data['full_text_file_url'] = '';

		if ( !$file_exists ) {
			$clear = FALSE;
			if ( strlen ( $pub['full_text_file_url'] ) > 0 ) {
				$clear = TRUE;
				if ( strlen ( $pub['file_url'] ) > 0 ) {
					if ( $pub['file_url'] == $pub['full_text_file_url'] ) {
						$clear = FALSE;
					}
				}
			}

			if ( $clear ) {
				//t3lib_div::debug ( 'Clearing full_text_cache for ' . $WC );
				$db_update = TRUE;
			} else {
				//t3lib_div::debug ( 'Keeping full_text_cache for ' . $WC );
				return FALSE;
			}
		}

		// Actually update
		if ( $file_exists && (
				( $file_mt > $pub['full_text_tstamp'] ) ||
				( $pub['file_url'] != $pub['full_text_file_url'] ) ||
				$force )
		) 
		{
			// Check if pdftotext is executable
			if ( !is_executable ( $this->pdftotext_bin ) ) {
				$err = array();
				$err['msg'] = 'The pdftotext binary \'' . strval ( $this->pdftotext_bin ) .
					'\' is no executable';
				return $err;
			}

			// Determine temporary text file
			$target = tempnam ( $this->tmp_dir, 'sevenpack_pdftotext' );

			// Compose and execute command
			$charset = strtoupper ( $this->charset );
			$cmd = strval ( $this->pdftotext_bin );
			if ( strlen ( $charset ) > 0 ) {
				$cmd .= ' -enc ' . $charset;
			}
			$cmd .= ' ' . $file;
			$cmd .= ' ' . $target;
			//t3lib_div::debug ( array ( 'cmd' => $cmd ) );

			$cmd_txt = array();
			$retval = FALSE;
			exec ( $cmd, $cmd_txt, $retval );
			if ( $retval != 0 ) {
				$err = array();
				$err['msg'] = 'pdftotext failed: ' . implode ( '', $cmd_txt );
				return $err;
			}

			// Read text file
			$handle = fopen ( $target, 'rb' );
			$full_text = fread ( $handle, filesize ( $target ) );
			fclose ( $handle );

			// Delete temporary text file
			unlink ( $target );

			//t3lib_div::debug ( $full_text );

			$db_update = TRUE;
			$db_data['full_text'] = $full_text;
			$db_data['full_text_file_url'] = $pub['file_url'];
		}

		if ( $db_update ) {
			//t3lib_div::debug ( 'Updating full_text_cache ' );
			$ret = $db->exec_UPDATEquery ( $rT, $WC, $db_data );
			if ( $ret == FALSE ) {
				$err = array();
				$err['msg'] = 'Full text update failed: ' . $db->sql_error();
				return $err;
			}
			return TRUE;
		}

		return FALSE;
	}

}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/res/class.tx_sevenpack_db_utility.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/res/class.tx_sevenpack_db_utility.php']);
}


?>
