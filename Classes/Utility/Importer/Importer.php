<?php
namespace Ipf\Bib\Utility\Importer;

use \TYPO3\CMS\Core\Utility\GeneralUtility;

class Importer {

	/**
	 * @var \tx_bib_pi1
	 */
	public $pi1;

	/**
	 * @var \Ipf\Bib\Utility\ReferenceReader
	 */
	public $referenceReader;

	/**
	 * @var \Ipf\Bib\Utility\ReferenceWriter
	 */
	public $referenceWriter;

	/**
	 * @var \Ipf\Bib\Utility\DbUtility
	 */
	public $databaseUtility;

	public $storage_pid;
	public $state;

	public $import_type;

	/**
	 * @var array
	 */
	public $statistics = array();

	// Utility
	public $code_trans_tbl;

	/**
	 * @var bool|\Ipf\Bib\Utility\Generator\CiteIdGenerator
	 */
	public $idGenerator = FALSE;


	/**
	 * Initializes the import. The argument must be the plugin class
	 *
	 * @param \tx_bib_pi1
	 * @return void
	 */
	public function initialize($pi1) {
		$this->pi1 = $pi1;
		$this->referenceReader = $this->pi1->referenceReader;

		$this->referenceWriter = GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\ReferenceWriter');
		$this->referenceWriter->initialize($this->referenceReader);

		$this->storage_pid = 0;

		$this->statistics['warnings'] = array();
		$this->statistics['errors'] = array();

		// setup db_utility
		$this->databaseUtility = GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\DbUtility');
		$this->databaseUtility->initialize($pi1->ref_read);
		$this->databaseUtility->charset = $pi1->extConf['charset']['upper'];
		$this->databaseUtility->read_full_text_conf($pi1->conf['editor.']['full_text.']);


		// Create an instance of the citeid generator
		if (isset ($this->conf['citeid_generator_file'])) {
			$ext_file = $GLOBALS['TSFE']->tmpl->getFileName($this->conf['citeid_generator_file']);
			if (file_exists($ext_file)) {
				require_once($ext_file);
				$this->idGenerator = GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\Generator\\AuthorsCiteIdGenerator');
			}
		} else {
			$this->idGenerator = GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\Generator\\CiteIdGenerator');
		}
		$this->idGenerator->initialize($pi1);
	}


	/**
	 * Returns a page title
	 *
	 * @param int $uid
	 * @return void
	 */
	function get_page_title($uid) {
		$title = FALSE;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title', 'pages', 'uid=' . intval($uid));
		$p_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$charset = $this->pi1->extConf['charset']['upper'];
		if (is_array($p_row)) {
			$title = htmlspecialchars($p_row['title'], ENT_NOQUOTES, $charset);
			$title .= ' (' . strval($uid) . ')';
		}
		return $title;
	}


	/**
	 * Returns the default storage uid
	 *
	 * @return The parent id pid
	 */
	function get_default_pid() {
		$edConf =& $this->pi1->conf['editor.'];
		$pid = 0;
		if (is_numeric($edConf['default_pid'])) {
			$pid = intval($edConf['default_pid']);
		}
		if (!in_array($pid, $this->referenceReader->pid_list)) {
			$pid = intval($this->referenceReader->pid_list[0]);
		}
		return $pid;
	}


	/**
	 * Returns the storage selector if there is more
	 * than one storage folder selected
	 *
	 * @return the storage selector string
	 */
	function storage_selector() {
		// Pid
		$pages = array();
		$content = '';

		$pids = $this->pi1->extConf['pid_list'];
		$default_pid = $this->get_default_pid();

		if (sizeof($pids) > 1) {
			// Fetch page titles
			$pages = \Ipf\Bib\Utility\Utility::get_page_titles($pids);

			$val = $this->pi1->get_ll('import_storage_info', 'import_storage_info', TRUE);
			$content .= '<p>' . $val . '</p>' . "\n";

			$val = \Ipf\Bib\Utility\Utility::html_select_input(
				$pages,
				$default_pid,
				array('name' => $this->pi1->prefixId . '[import_pid]')
			);
			$content .= '<p>' . "\n" . $val . '</p>' . "\n";
		}

		return $content;
	}


	/**
	 * Acquires $this->storage_pid
	 *
	 * @return void
	 */
	function acquire_storage_pid() {
		$pid =& $this->storage_pid;
		$pids =& $this->pi1->extConf['pid_list'];

		$pid = intval($this->pi1->piVars['import_pid']);
		if (!in_array($pid, $pids)) {
			$pid = $this->get_default_pid();
		}
	}


	/**
	 * Saves a publication
	 *
	 * @return void
	 */
	public function save_publication($pub) {
		$stat =& $this->statistics;
		$res = FALSE;

		// Data checks
		$s_ok = TRUE;
		if (!array_key_exists('bibtype', $pub)) {
			$stat['failed']++;
			$stat['errors'][] = 'Missing bibtype';
			$s_ok = FALSE;
		}

		// Data adjustments
		$pub['pid'] = $this->storage_pid;

		// Don't accept publication uids since that
		// could override existing publications
		if (array_key_exists('uid', $pub)) {
			unset ($pub['uid']);
		}

		if (strlen($pub['citeid']) == 0) {
			$pub['citeid'] = $this->idGenerator->generateId($pub);
		}

		// Save publications
		if ($s_ok) {

			$s_fail = $this->referenceWriter->save_publication($pub);

			if ($s_fail) {
				$stat['failed']++;
				$stat['errors'][] = $this->referenceWriter->error_message();
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
	function import() {
		$this->state = 1;
		if (intval($_FILES['ImportFile']['size']) > 0) {
			$this->state = 2;
		}

		$content = '';
		switch ($this->state) {
			case 1:
				$content = $this->import_state_1();
				break;
			case 2:
				$this->acquire_storage_pid();
				$content = $this->import_state_2();
				$content .= $this->post_import();
				$content .= $this->import_stat_str();
				break;
			default:
				$content = $this->pi1->error_msg('Bad import state');
		}

		return $content;
	}


	/**
	 * file selection state
	 *
	 */
	function import_state_1() {
		$btn_attribs = array('class' => 'tx_bib-button');
		$content = '';

		// Pre import information
		$content .= $this->import_pre_info();

		$action = $this->pi1->get_link_url(array('import' => $this->import_type));
		$content .= '<form action="' . $action . '" method="post" enctype="multipart/form-data" >';

		// The storage selector
		$content .= $this->storage_selector();

		// The file selection
		$val = $this->pi1->get_ll('import_select_file', 'import_select_file', TRUE);
		$content .= '<p>' . $val . '</p>' . "\n";

		$val = '<input name="ImportFile" type="file" size="50" accept="text/*" />';
		$content .= '<p>' . $val . '</p>' . "\n";

		// The submit button
		$val = $this->pi1->get_ll('import_file', 'import_file', TRUE);
		$btn = \Ipf\Bib\Utility\Utility::html_submit_input('submit', $val, $btn_attribs);
		$content .= '<p>' . $btn . '</p>' . "\n";

		$content .= '</form>';

		return $content;
	}


	/**
	 * Returns an import statistics string
	 *
	 */
	function post_import() {
		if ($this->statistics['succeeded'] > 0) {

			//
			// Update full texts
			//
			if ($this->pi1->conf['editor.']['full_text.']['update']) {
				$arr = $this->databaseUtility->update_full_text_all();
				if (sizeof($arr['errors']) > 0) {
					foreach ($arr['errors'] as $err) {
						$this->statistics['errors'][] = $err[1]['msg'];
					}
				}
				$this->statistics['full_text'] = $arr;
			}

		}
	}


	/**
	 * Returns a html table row string
	 *
	 */
	function table_row_str($th, $td) {
		$content = '<tr>' . "\n";
		$content .= '<th>' . strval($th) . '</th>' . "\n";
		$content .= '<td>' . strval($td) . '</td>' . "\n";
		$content .= '</tr>' . "\n";
		return $content;
	}


	/**
	 * Returns an import statistics string
	 *
	 */
	function import_stat_str() {
		$stat =& $this->statistics;
		$charset = $this->pi1->extConf['charset']['upper'];

		$content = '';
		$content .= '<strong>Import statistics</strong>: ';
		$content .= '<table class="tx_bib-editor_fields">';
		$content .= '<tbody>';

		$content .= $this->table_row_str(
			'Import file:',
				htmlspecialchars($stat['file_name'], ENT_QUOTES, $charset) .
				' (' . strval($stat['file_size']) . ' Bytes)'
		);

		$content .= $this->table_row_str(
			'Storage folder:',
			$this->get_page_title($stat['storage'])
		);


		if (isset ($stat['succeeded'])) {
			$content .= $this->table_row_str(
				'Successful imports:',
				intval($stat['succeeded']));
		}

		if (isset ($stat['failed']) && ($stat['failed'] > 0)) {
			$content .= $this->table_row_str(
				'Failed imports:',
				intval($stat['failed']));
		}

		if (is_array($stat['full_text'])) {
			$fts =& $stat['full_text'];
			$content .= $this->table_row_str(
				'Updated full texts:', count($fts['updated']));
			if ($fts['limit_num']) {
				$content .= $this->table_row_str(
					$this->pi1->get_ll('msg_warn_ftc_limit'),
					$this->pi1->get_ll('msg_warn_ftc_limit_num'));
			}
			if ($fts['limit_time']) {
				$content .= $this->table_row_str(
					$this->pi1->get_ll('msg_warn_ftc_limit'),
					$this->pi1->get_ll('msg_warn_ftc_limit_time'));
			}
		}

		if (is_array($stat['warnings']) && (count($stat['warnings']) > 0)) {
			$val = '<ul style="padding-top:0px;margin-top:0px;">' . "\n";
			$messages = \Ipf\Bib\Utility\Utility::string_counter($stat['warnings']);
			foreach ($messages as $msg => $count) {
				$str = $this->message_times_str($msg, $count);
				$val .= '<li>' . $str . '</li>' . "\n";
			}
			$val .= '</ul>' . "\n";

			$content .= $this->table_row_str('Warnings:', $val);
		}

		if (is_array($stat['errors']) && (count($stat['errors']) > 0)) {
			$val = '<ul style="padding-top:0px;margin-top:0px;">' . "\n";
			$messages = \Ipf\Bib\Utility\Utility::string_counter($stat['errors']);
			foreach ($messages as $msg => $count) {
				$str = $this->message_times_str($msg, $count);
				$val .= '<li>' . $str . '</li>' . "\n";
			}
			$val .= '</ul>' . "\n";

			$content .= $this->table_row_str('Errors:', $val);
		}

		$content .= '</tbody>';
		$content .= '</table>';


		return $content;
	}


	function message_times_str($msg, $count) {
		$charset = $this->pi1->extConf['charset']['upper'];
		$res = htmlspecialchars($msg, ENT_QUOTES, $charset);
		if ($count > 1) {
			$res .= ' (' . strval($count);
			$res .= ' times)';
		}
		return $res;
	}


	/**
	 * Replaces character code descriotion like &aauml; with
	 * the equivalent
	 */
	function code_to_utf8($str) {
		$translationTable =& $this->code_trans_tbl;
		if (!is_array($translationTable)) {
			$translationTable = get_html_translation_table(HTML_ENTITIES, ENT_NOQUOTES);
			$translationTable = array_flip($translationTable);
			// These should stay alive
			unset ($translationTable['&amp;']);
			unset ($translationTable['&lt;']);
			unset ($translationTable['&gt;']);

			$cs =& $GLOBALS['TSFE']->csConvObj;

			foreach ($translationTable as $key => $val) {
				$translationTable[$key] = $cs->conv($val, 'iso-8859-1', 'utf-8');
			}
		}

		return strtr($str, $translationTable);
	}


	/**
	 * Takes an utf-8 string and changes the character set on demand
	 */
	function import_utf8_string($str, $charset = NULL) {
		if (!is_string($charset)) {
			$charset = $this->pi1->extConf['charset']['lower'];
		}

		if ($charset != 'utf-8') {
			$cs =& $GLOBALS['TSFE']->csConvObj;
			$str = $cs->utf8_decode($str, $charset, TRUE);
		}
		return $str;
	}

}

?>