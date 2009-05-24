<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/res/class.tx_sevenpack_pregexp_translator.php' ) );

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/res/class.tx_sevenpack_utility.php' ) );

class tx_sevenpack_importer {

	public $pi1;
	public $ra;

	public $storage_pid;
	public $state;

	public $import_type;

	// Statistics
	public $stat;

	// Utiklity
	public $code_trans_tbl;

	/**
	 * Initializes the import. The argument must be the plugin class
	 *
	 * @return void
	 */
	function initialize ( $pi1 ) {
		$this->pi1 =& $pi1;
		$this->ra  =& $pi1->ra;
		$this->storage_pid = 0;
		$this->stat = array();
	}


	function get_page_title ( $uid ) {
		$title = FALSE;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( 'title', 'pages', 'uid='.intval( $uid ) );
		$p_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc ( $res );
		$charset = $pi1->extConf['charset']['upper'];
		if ( is_array ( $p_row ) ) {
			$title = htmlspecialchars ( $p_row['title'], ENT_NOQUOTES, $charset );
			$title .= ' (' . strval ( $uid ) . ')';
		}
		return $title;
	}


	/**
	 * Returns the storage selector if there is more
	 * than one storage folder selected
	 *
	 * @return the storage selector string
	 */
	function storage_selector ( ) {
		// Pid
		$pages = array();
		$con = '';

		$pids = $this->pi1->extConf['pid_list'];
		$default_pid = intval ( $this->pi1->conf['editor.']['default_pid'] );

		if ( sizeof ( $pids ) > 1 ) {
			// Fetch page titles
			$pages = tx_sevenpack_utility::get_page_titles ( $pids ); 
			$pages = array_reverse ( $pages, TRUE ); // Due to how recursive prepends the folders


			$val = $this->pi1->get_ll ( 'import_storage_info', 'import_storage_info', TRUE );
			$con .= '<p>' . $val . '</p>' . "\n";

			$val = tx_sevenpack_utility::html_select_input (
				$pages, $default_pid,
				array ( 'name' => $this->pi1->prefixId . '[import_pid]' )
			);
			$con .= '<p>' . "\n" . $val . '</p>' . "\n";
		}

		return $con;
	}


	/**
	 * Acquires $this->storage_pid
	 *
	 * @return void
	 */
	function acquire_storage_pid ( ) {
		$pid  =& $this->storage_pid;
		$pids =& $this->pi1->extConf['pid_list'];

		$pid = intval ( $this->pi1->piVars['import_pid'] );
		if ( ! in_array ( $pid, $pids ) ) {
			$pid = intval ( $pids[0] );
		}
		//t3lib_div::debug ( 'Acquire pid: ' + $pid );
	}


	/**
	 * Saves a publication
	 *
	 * @return void
	 */
	function save_publication ( $pub ) {
		$stat =& $this->stat;
		$res = FALSE;

		// Data checks
		$s_ok = TRUE;
		$pub['pid'] = $this->storage_pid;
		if ( !array_key_exists ( 'bibtype', $pub ) ) {
			$stat['failed']++;
			$stat['errors'][] = 'Missing bibtype';
			$s_ok = FALSE;
		}

		// Save publications
		if ( $s_ok ) {
			//t3lib_div::debug ( $pub );
			$s_ret = $this->ra->save_publication ( $pub );
	
			if ( $s_ret ) {
				$stat['failed']++;
				$stat['errors'][] = $this->ra->error_message ( );
			} else {
				$stat['succeeded']++;
			}
		}

		return $res;
	}


	function import ( ) {
		$this->state = 1;
		if ( intval ( $_FILES['ImportFile']['size'] ) > 0 ) {
			$this->state = 2;
		}

		$con = '';
		switch ( $this->state ) {
			case 1:
				$con = $this->import_state_1();
				break;
			case 2:
				$this->acquire_storage_pid();
				$con = $this->import_state_2();
				$con .= $this->import_stat_str();
				break;
			default:
				$con = $this->pi1->error_msg ( 'Bad import state' );
		}

		return $con;
	}


	/**
	 * file selection state
	 *
	 */
	function import_state_1 ( ) {
		$btn_attribs = array ( 'class' => 'tx_sevenpack-button' );
		$con = '';

		// Pre import information
		$con .= $this->import_pre_info();

		$action = $this->pi1->get_link_url ( array ( 'import' => $this->import_type ) );
		$con .= '<form action="' . $action . '" method="post" enctype="multipart/form-data" >';

		// The storage selector
		$con .= $this->storage_selector();

		// The file selection
		$val = $this->pi1->get_ll ( 'import_select_file', 'import_select_file', TRUE );
		$con .= '<p>' . $val . '</p>' . "\n";

		$val = '<input name="ImportFile" type="file" size="50" accept="text/*" />';
		$con .= '<p>' . $val . '</p>' . "\n";

		// The submit button
		$val = $this->pi1->get_ll ( 'import_file', 'import_file', TRUE );
		$btn = tx_sevenpack_utility::html_submit_input ( 'submit', $val, $btn_attribs );
		$con .= '<p>' . $btn . '</p>' . "\n";

		$con .= '</form>';

		return $con;
	}


	/**
	 * Returns an import statistics string
	 *
	 */
	function import_stat_str ( ) {
		$stat =& $this->stat;
		$charset = $this->pi1->extConf['charset']['upper'];

		$con = '';
		$con .= '<strong>Import statistics</strong>: ';
		$con .= '<table>';
		$con .= '<tbody>';

		$con .= '<tr>' . "\n";
		$con .= '<th>Import file:</th>' . "\n";
		$con .= '<td>' . htmlspecialchars ( $stat['file_name'], ENT_QUOTES, $charset );
		$con .= ' (' . strval ( $stat['file_size'] ) . ' Bytes)';
		$con .= '</td>' . "\n";
		$con .= '</tr>' . "\n";

		$con .= '<tr>' . "\n";
		$con .= '<th>Storage folder:</th>' . "\n";
		$con .= '<td>' . strval ( $this->get_page_title ( $stat['storage'] ) );
		$con .= '</td>' . "\n";
		$con .= '</tr>' . "\n";

		if ( isset ( $stat['succeeded'] ) ) {
			$con .= '<tr>' . "\n";
			$con .= '<th>Successful imports:</th>' . "\n";
			$con .= '<td>' . strval ( $stat['succeeded'] ) . '</td>' . "\n";
			$con .= '</tr>' . "\n";
		}

		if ( isset ( $stat['failed'] ) && ( $stat['failed'] > 0 ) ) {
			$con .= '<tr>' . "\n";
			$con .= '<th>Failed imports:</th>' . "\n";
			$con .= '<td>' . strval ( $stat['failed'] ) . '</td>' . "\n";
			$con .= '</tr>' . "\n";
		}

		if ( is_array ( $stat['warnings'] ) && ( count ( $stat['warnings'] ) > 0 ) ) {
			$con .= '<tr>' . "\n";
			$con .= '<th>Warnings:</th>' . "\n";
			$con .= '<td>' . "\n";
			$con .= '<ul style="padding-top:0px;margin-top:0px;">' . "\n";
			$messages = $this->message_counter ( $stat['warnings'] );
			foreach ( $messages as $msg => $count ) {
				$con .= '<li>';
				$con .= $this->message_times_str ( $msg, $count );
				$con .= '</li>' . "\n";
			}
			$con .= '</ul>' . "\n";
			$con .= '</td>' . "\n";
			$con .= '</tr>' . "\n";
		}

		if ( is_array ( $stat['errors'] ) && ( count ( $stat['errors'] ) > 0 ) ) {
			$con .= '<tr>' . "\n";
			$con .= '<th>Errors:</th>' . "\n";
			$con .= '<td>' . "\n";
			$con .= '<ul style="padding-top:0px;margin-top:0px;">' . "\n";
			$messages = $this->message_counter ( $stat['errors'] );
			foreach ( $messages as $msg => $count ) {
				$con .= '<li>';
				$con .= $this->message_times_str ( $msg, $count );
				$con .= '</li>' . "\n";
			}
			$con .= '</ul>' . "\n";
			$con .= '</td>' . "\n";
			$con .= '</tr>' . "\n";
		}

		$con .= '</tbody>';
		$con .= '</table>';


		return $con;
	}


	function message_counter ( $messages ) {
		$res = array();
		foreach ( $messages as $msg ) {
			if ( array_key_exists ( $msg, $res ) ) {
				$res[$msg] += 1;
			} else {
				$res[$msg] = 1;
			}
		}
		arsort( $res );
		return $res;
	}


	function message_times_str ( $msg, $count ) {
		$charset = $pi1->extConf['charset']['upper'];
		$res = htmlspecialchars ( $msg, ENT_QUOTES, $charset );
		if ( $count > 1 ) {
			$res .= ' (' . strval ( $count );
			$res .= ' times)';
		}
		return $res;
	}


	/**
	 * Replaces character code descriotion like &aauml; with
	 * the equivalent
	 */
	function code_to_utf8 ( $str ) {
		$trans_tbl =& $this->code_trans_tbl;
		if ( !is_array ( $trans_tbl ) ) {
			$trans_tbl = get_html_translation_table ( HTML_ENTITIES, ENT_NOQUOTES );
			$trans_tbl = array_flip ( $trans_tbl );
			// These should stay alive
			unset ( $trans_tbl['&amp;'] );
			unset ( $trans_tbl['&lt;'] );
			unset ( $trans_tbl['&gt;'] );
			//t3lib_div::debug( $trans_tbl );

			$cs =& $GLOBALS['TSFE']->csConvObj;

			foreach ( $trans_tbl as $key => $val ) {
				$trans_tbl[$key] = $cs->conv ( $val, 'iso-8859-1', 'utf-8' );
			}
		}

		return strtr ( $str, $trans_tbl);
	}


	/**
	 * Takes an utf-8 string and changes the character set on demand
	 */
	function import_utf8_string ( $str, $charset = NULL ) {
		if ( ! is_string ( $charset ) ) {
			$charset = $this->pi1->extConf['charset']['lower'];
		}

		if ( $charset != 'utf-8' ) {
			$cs =& $GLOBALS['TSFE']->csConvObj;
			$str = $cs->utf8_decode ( $str, $charset, TRUE );
		}
		return $str;
	}


}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_importer.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_importer.php"]);
}


?>
