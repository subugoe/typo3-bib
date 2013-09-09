<?php
namespace Ipf\Bib\Utility\Importer;

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

use \TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class Importer {

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
		/** @var \Ipf\Bib\Utility\DBUtility $databaseUtility */
		$databaseUtility = GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\DbUtility');
		$databaseUtility->initialize($this->referenceReader);
		$databaseUtility->charset = $pi1->extConf['charset']['upper'];
		$databaseUtility->readFullTextGenerationConfiguration($pi1->conf['editor.']['full_text.']);

		$this->databaseUtility = $databaseUtility;

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
	 * @return string
	 */
	protected function getPageTitle($uid) {
		$title = FALSE;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'title',
			'pages',
			'uid=' . intval($uid)
		);
		$page = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$charset = $this->pi1->extConf['charset']['upper'];
		if (is_array($page)) {
			$title = htmlspecialchars($page['title'], ENT_NOQUOTES, $charset);
			$title .= ' (' . strval($uid) . ')';
		}
		return $title;
	}


	/**
	 * Returns the default storage uid
	 *
	 * @return int The parent id pid
	 */
	protected function getDefaultPid() {
		$editorConfiguration =& $this->pi1->conf['editor.'];
		$pid = 0;
		if (is_numeric($editorConfiguration['default_pid'])) {
			$pid = intval($editorConfiguration['default_pid']);
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
	 * @return string the storage selector string
	 */
	protected function getStorageSelector() {
		// Pid
		$pages = array();
		$content = '';

		$pids = $this->pi1->extConf['pid_list'];
		$default_pid = $this->getDefaultPid();

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
	protected function acquireStoragePid() {
		$this->storage_pid = intval($this->pi1->piVars['import_pid']);
		if (!in_array($this->storage_pid, $this->pi1->extConf['pid_list'])) {
			$this->storage_pid = $this->getDefaultPid();
		}
	}


	/**
	 * Saves a publication
	 *
	 * @param array $publication
	 * @return bool
	 */
	protected function savePublication($publication) {
		$res = FALSE;

		// Data checks
		$s_ok = TRUE;
		if (!array_key_exists('bibtype', $publication)) {
			$this->statistics['failed']++;
			$this->statistics['errors'][] = 'Missing bibtype';
			$s_ok = FALSE;
		}

		// Data adjustments
		$publication['pid'] = $this->storage_pid;

		// Don't accept publication uids since that
		// could override existing publications
		if (array_key_exists('uid', $publication)) {
			unset ($publication['uid']);
		}

		if (strlen($publication['citeid']) == 0) {
			$publication['citeid'] = $this->idGenerator->generateId($publication);
		}

		// Save publications
		if ($s_ok) {

			$s_fail = $this->referenceWriter->savePublication($publication);

			if ($s_fail) {
				$this->statistics['failed']++;
				$this->statistics['errors'][] = $this->referenceWriter->error_message();
			} else {
				$this->statistics['succeeded']++;
			}
		}

		return $res;
	}


	/**
	 * The main importer function
	 *
	 * @return string
	 */
	public function import() {
		$this->state = 1;
		if (intval($_FILES['ImportFile']['size']) > 0) {
			$this->state = 2;
		}

		switch ($this->state) {
			case 1:
				$content = $this->importFileSelectionState();
				break;
			case 2:
				$this->acquireStoragePid();
				$content = $this->import_state_2();
				$content .= $this->postImport();
				$content .= $this->getImportStatistics();
				break;
			default:
				$content = $this->pi1->error_msg('Bad import state');
		}

		return $content;
	}


	/**
	 * file selection state
	 *
	 * @return string
	 */
	protected function importFileSelectionState() {
		$buttonAttributes = array('class' => 'tx_bib-button');
		$content = '';

		// Pre import information
		$content .= $this->import_pre_info();

		$action = $this->pi1->get_link_url(array('import' => $this->import_type));
		$content .= '<form action="' . $action . '" method="post" enctype="multipart/form-data" >';

		// The storage selector
		$content .= $this->getStorageSelector();

		// The file selection
		$val = $this->pi1->get_ll('import_select_file', 'import_select_file', TRUE);
		$content .= '<p>' . $val . '</p>' . "\n";

		$val = '<input name="ImportFile" type="file" size="50" accept="text/*" />';
		$content .= '<p>' . $val . '</p>' . "\n";

		// The submit button
		$val = $this->pi1->get_ll('import_file', 'import_file', TRUE);
		$button = \Ipf\Bib\Utility\Utility::html_submit_input('submit', $val, $buttonAttributes);
		$content .= '<p>' . $button . '</p>' . "\n";

		$content .= '</form>';

		return $content;
	}

	/**
	 * @return string
	 */
	abstract protected function import_state_2();

	/**
	 * Adds an import statistics string to the statistics array
	 *
	 * @return void
	 */
	protected function postImport() {
		if ($this->statistics['succeeded'] > 0) {

			// Update full texts
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
	 * @param string $th
	 * @param string $td
	 * @return string
	 */
	protected function getTableRowAsString($th, $td) {
		$content = '<tr>' . "\n";
		$content .= '<th>' . strval($th) . '</th>' . "\n";
		$content .= '<td>' . strval($td) . '</td>' . "\n";
		$content .= '</tr>' . "\n";
		return $content;
	}


	/**
	 * Returns an import statistics string
	 *
	 * @return string
	 */
	protected function getImportStatistics() {
		$stat =& $this->statistics;
		$charset = $this->pi1->extConf['charset']['upper'];

		$content = '';
		$content .= '<strong>Import statistics</strong>: ';
		$content .= '<table class="tx_bib-editor_fields">';
		$content .= '<tbody>';

		$content .= $this->getTableRowAsString(
			'Import file:',
				htmlspecialchars($stat['file_name'], ENT_QUOTES, $charset) .
				' (' . strval($stat['file_size']) . ' Bytes)'
		);

		$content .= $this->getTableRowAsString(
			'Storage folder:',
			$this->getPageTitle($stat['storage'])
		);


		if (isset ($stat['succeeded'])) {
			$content .= $this->getTableRowAsString(
				'Successful imports:',
				intval($stat['succeeded']));
		}

		if (isset ($stat['failed']) && ($stat['failed'] > 0)) {
			$content .= $this->getTableRowAsString(
				'Failed imports:',
				intval($stat['failed']));
		}

		if (is_array($stat['full_text'])) {
			$fts =& $stat['full_text'];
			$content .= $this->getTableRowAsString(
				'Updated full texts:', count($fts['updated']));
			if ($fts['limit_num']) {
				$content .= $this->getTableRowAsString(
					$this->pi1->get_ll('msg_warn_ftc_limit'),
					$this->pi1->get_ll('msg_warn_ftc_limit_num'));
			}
			if ($fts['limit_time']) {
				$content .= $this->getTableRowAsString(
					$this->pi1->get_ll('msg_warn_ftc_limit'),
					$this->pi1->get_ll('msg_warn_ftc_limit_time'));
			}
		}

		if (is_array($stat['warnings']) && (count($stat['warnings']) > 0)) {
			$val = '<ul style="padding-top:0px;margin-top:0px;">' . "\n";
			$messages = \Ipf\Bib\Utility\Utility::string_counter($stat['warnings']);
			foreach ($messages as $msg => $count) {
				$str = $this->getMessageOccurrenceCounter($msg, $count);
				$val .= '<li>' . $str . '</li>' . "\n";
			}
			$val .= '</ul>' . "\n";

			$content .= $this->getTableRowAsString('Warnings:', $val);
		}

		if (is_array($stat['errors']) && (count($stat['errors']) > 0)) {
			$val = '<ul style="padding-top:0px;margin-top:0px;">' . "\n";
			$messages = \Ipf\Bib\Utility\Utility::string_counter($stat['errors']);
			foreach ($messages as $msg => $count) {
				$str = $this->getMessageOccurrenceCounter($msg, $count);
				$val .= '<li>' . $str . '</li>' . "\n";
			}
			$val .= '</ul>' . "\n";

			$content .= $this->getTableRowAsString('Errors:', $val);
		}

		$content .= '</tbody>';
		$content .= '</table>';


		return $content;
	}

	/**
	 * @param string $message
	 * @param int $count
	 * @return string
	 */
	protected function getMessageOccurrenceCounter($message, $count) {
		$charset = $this->pi1->extConf['charset']['upper'];
		$content = htmlspecialchars($message, ENT_QUOTES, $charset);
		if ($count > 1) {
			$content .= ' (' . strval($count);
			$content .= ' times)';
		}
		return $content;
	}


	/**
	 * Replaces character code description like &aauml; with
	 * the equivalent
	 *
	 * @param string $code
	 * @return string
	 */
	protected function codeToUnicode($code) {
		$translationTable =& $this->code_trans_tbl;
		if (!is_array($translationTable)) {
			$translationTable = get_html_translation_table(HTML_ENTITIES, ENT_NOQUOTES);
			$translationTable = array_flip($translationTable);
			// These should stay alive
			unset ($translationTable['&amp;']);
			unset ($translationTable['&lt;']);
			unset ($translationTable['&gt;']);

			foreach ($translationTable as $key => $val) {
				$translationTable[$key] = $GLOBALS['TSFE']->csConvObj->conv($val, 'iso-8859-1', 'utf-8');
			}
		}

		return strtr($code, $translationTable);
	}


	/**
	 * Takes an utf-8 string and changes the character set on demand
	 *
	 * @param string $content
	 * @param string $charset
	 * @return string
	 */
	protected function importUnicodeString($content, $charset = NULL) {
		if (!is_string($charset)) {
			$charset = $this->pi1->extConf['charset']['lower'];
		}

		if ($charset != 'utf-8') {
			$content = $GLOBALS['TSFE']->csConvObj->utf8_decode($content, $charset, TRUE);
		}
		return $content;
	}

}

?>