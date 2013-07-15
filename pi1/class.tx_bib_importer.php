<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:bib/pi1/class.tx_bib_citeid_generator.php' ) );

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:bib/res/class.tx_bib_pregexp_translator.php' ) );

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:bib/res/class.tx_bib_utility.php' ) );

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:bib/res/class.tx_bib_reference_writer.php' ) );

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:bib/res/class.tx_bib_db_utility.php' ) );


class tx_bib_importer {

	public $pi1;
	public $ref_read;
	public $ref_write;

	public $db_utility;

	public $storage_pid;
	public $state;

	public $import_type;

	// Statistics
	public $stat;

	// Utility
	public $code_trans_tbl;
	public $idGenerator = FALSE;


	/**
	 * Initializes the import. The argument must be the plugin class
	 *
	 * @return void
	 */
	function initialize ( $pi1 ) {
		$this->pi1 =& $pi1;
		$this->ref_read =& $pi1->ref_read;

		$this->ref_write = t3lib_div::makeInstance ( 'tx_bib_reference_writer' );
		$this->ref_write->initialize( $this->ref_read );

		$this->storage_pid = 0;
		$this->stat = array();
		$this->stat['warnings'] = array();
		$this->stat['errors'] = array();

		// setup db_utility
		$this->db_utility = t3lib_div::makeInstance ( 'tx_bib_db_utility' );
		$this->db_utility->initialize ( $pi1->ref_read );
		$this->db_utility->charset = $pi1->extConf['charset']['upper'];
		$this->db_utility->read_full_text_conf ( $pi1->conf['editor.']['full_text.'] );


		// Create an instance of the citeid generator
		if ( isset ( $this->conf['citeid_generator_file'] ) ) {
			$ext_file = $GLOBALS['TSFE']->tmpl->getFileName ( $this->conf['citeid_generator_file'] );
			if ( file_exists ( $ext_file ) ) {
				require_once ( $ext_file );
				$this->idGenerator = t3lib_div::makeInstance ( 'tx_bib_citeid_generator_ext' );
			}
		} else {
			$this->idGenerator = t3lib_div::makeInstance ( 'tx_bib_citeid_generator' );
		}
		$this->idGenerator->initialize ( $pi1 );
	}


	/**
	 * Returns a page title
	 *
	 * @return void
	 */
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
	 * Returns the default storage uid
	 *
	 * @return The parent id pid
	 */
	function get_default_pid ( ) {
		$edConf =& $this->pi1->conf['editor.'];
		$pid = 0;
		if ( is_numeric ( $edConf['default_pid'] ) ) {
			$pid = intval ( $edConf['default_pid'] );
		}
		if ( !in_array ( $pid, $this->ref_read->pid_list ) ) {
			$pid = intval ( $this->ref_read->pid_list[0] );
		}
		return $pid;
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
		$default_pid = $this->get_default_pid();

		if ( sizeof ( $pids ) > 1 ) {
			// Fetch page titles
			$pages = tx_bib_utility::get_page_titles ( $pids );

			$val = $this->pi1->get_ll ( 'import_storage_info', 'import_storage_info', TRUE );
			$con .= '<p>' . $val . '</p>' . "\n";

			$val = tx_bib_utility::html_select_input (
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
		if ( !in_array ( $pid, $pids ) ) {
			$pid = $this->get_default_pid();
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
		if ( !array_key_exists ( 'bibtype', $pub ) ) {
			$stat['failed']++;
			$stat['errors'][] = 'Missing bibtype';
			$s_ok = FALSE;
		}

		// Data adjustments
		$pub['pid'] = $this->storage_pid;
		
		// Don't accept publication uids since that
		// could override existing publications
		if ( array_key_exists ( 'uid', $pub ) ) {
			unset ( $pub['uid'] );
		}

		if ( strlen ( $pub['citeid'] ) == 0 ) {
			$pub['citeid'] = $this->idGenerator->generateId ( $pub );
		}

		// Save publications
		if ( $s_ok ) {
			//t3lib_div::debug ( $pub );
			$s_fail = $this->ref_write->save_publication ( $pub );

			if ( $s_fail ) {
				$stat['failed']++;
				$stat['errors'][] = $this->ref_write->error_message();
			} else {
				$stat['succeeded']++;
			}
		}

		return $res;
	}


	/**
	 * The main function
	 *
	 */
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
				$con .= $this->post_import();
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
		$btn_attribs = array ( 'class' => 'tx_bib-button' );
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
		$btn = tx_bib_utility::html_submit_input ( 'submit', $val, $btn_attribs );
		$con .= '<p>' . $btn . '</p>' . "\n";

		$con .= '</form>';

		return $con;
	}


	/**
	 * Returns an import statistics string
	 *
	 */
	function post_import ( ) {
		if ( $this->stat['succeeded'] > 0 ) {

			//
			// Update full texts
			//
			if ( $this->pi1->conf['editor.']['full_text.']['update'] ) {
				$arr = $this->db_utility->update_full_text_all();
				if ( sizeof ( $arr['errors'] ) > 0 ) {
					foreach ( $arr['errors'] as $err ) {
						$this->stat['errors'][] = $err[1]['msg'];
					}
				}
				$this->stat['full_text'] = $arr;
			}

		}
	}


	/**
	 * Returns a html table row str
	 *
	 */
	function table_row_str ( $th, $td ) {
		$con  = '<tr>' . "\n";
		$con .= '<th>' . strval ( $th ) . '</th>' . "\n";
		$con .= '<td>' . strval ( $td ) . '</td>' . "\n";
		$con .= '</tr>' . "\n";
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
		$con .= '<table class="tx_bib-editor_fields">';
		$con .= '<tbody>';

		$con .= $this->table_row_str ( 
			'Import file:',
			htmlspecialchars ( $stat['file_name'], ENT_QUOTES, $charset ).
			' (' . strval ( $stat['file_size'] ) . ' Bytes)'
		);

		$con .= $this->table_row_str ( 
			'Storage folder:',
			$this->get_page_title ( $stat['storage'] )
		);


		if ( isset ( $stat['succeeded'] ) ) {
			$con .= $this->table_row_str ( 
				'Successful imports:', 
				intval ( $stat['succeeded'] ) );
		}

		if ( isset ( $stat['failed'] ) && ( $stat['failed'] > 0 ) ) {
			$con .= $this->table_row_str ( 
				'Failed imports:', 
				intval ( $stat['failed'] ) );
		}

		if ( is_array ( $stat['full_text'] ) ) {
			$fts =& $stat['full_text'];
			$con .= $this->table_row_str ( 
				'Updated full texts:', count ( $fts['updated'] ) );
			if ( $fts['limit_num'] ) {
				$con .= $this->table_row_str ( 
					$this->pi1->get_ll ( 'msg_warn_ftc_limit' ), 
					$this->pi1->get_ll ( 'msg_warn_ftc_limit_num' ) );
			}
			if ( $fts['limit_time'] ) {
				$con .= $this->table_row_str ( 
					$this->pi1->get_ll ( 'msg_warn_ftc_limit' ), 
					$this->pi1->get_ll ( 'msg_warn_ftc_limit_time' ) );
			}
		}

		if ( is_array ( $stat['warnings'] ) && ( count ( $stat['warnings'] ) > 0 ) ) {
			$val = '<ul style="padding-top:0px;margin-top:0px;">' . "\n";
			$messages = tx_bib_utility::string_counter ( $stat['warnings'] );
			foreach ( $messages as $msg => $count ) {
				$str = $this->message_times_str ( $msg, $count );
				$val .= '<li>' . $str . '</li>' . "\n";
			}
			$val .= '</ul>' . "\n";

			$con .= $this->table_row_str ( 'Warnings:', $val );
		}

		if ( is_array ( $stat['errors'] ) && ( count ( $stat['errors'] ) > 0 ) ) {
			$val = '<ul style="padding-top:0px;margin-top:0px;">' . "\n";
			$messages = tx_bib_utility::string_counter ( $stat['errors'] );
			foreach ( $messages as $msg => $count ) {
				$str = $this->message_times_str ( $msg, $count );
				$val .= '<li>' . $str . '</li>' . "\n";
			}
			$val .= '</ul>' . "\n";

			$con .= $this->table_row_str ( 'Errors:', $val );
		}

		$con .= '</tbody>';
		$con .= '</table>';


		return $con;
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

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/pi1/class.tx_bib_importer.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/pi1/class.tx_bib_importer.php"]);
}


?>