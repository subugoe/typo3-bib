<?php
namespace Ipf\Bib\View;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
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
use Ipf\Bib\Utility\Importer\BibTexImporter;
use Ipf\Bib\Utility\Importer\Importer;
use Ipf\Bib\Utility\Importer\XmlImporter;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Dialog View
 */
class DialogView extends View implements ViewInterface {

	// Various dialog modes
	const DIALOG_SAVE_CONFIRMED = 1;
	const DIALOG_DELETE_CONFIRMED = 2;
	const DIALOG_ERASE_CONFIRMED = 3;
	const DIALOG_EXPORT = 4;
	const DIALOG_IMPORT = 5;

	/**
	 * @var array
	 */
	protected $extConf;

	/**
	 * @var \tx_bib_pi1
	 */
	protected $pi1;

	/**
	 * @param \tx_bib_pi1 $pi1
	 */
	public function initialize($pi1) {
		$this->pi1 = $pi1;
		$this->extConf = $pi1->extConf;
	}

	/**
	 * @return string
	 */
	public function render() {
		$content = '';
		switch ($this->extConf['dialog_mode']) {
			case self::DIALOG_EXPORT :
				$content .= $this->exportDialog();
				break;
			case self::DIALOG_IMPORT :
				$content .= $this->importDialog();
				break;
			default :
				/** @var \Ipf\Bib\View\EditorView $editorView */
				$editorView = GeneralUtility::makeInstance(EditorView::class);
				$editorView->initialize($this->pi1);
				$content .= $editorView->dialogView();
		}

		$content .= sprintf('<p>%s</p>', $this->pi1->get_link($this->pi1->get_ll('link_back_to_list')));

		return $content;

	}

	/**
	 * The export dialog
	 *
	 * @return String The export dialog
	 */
	protected function exportDialog() {
		$mode = $this->extConf['export_navi']['do'];
		$content = '<h2>' . $this->pi1->get_ll('export_title') . '</h2>';

		$label = '';
		switch ($mode) {
			case 'bibtex':
				$exporterClass = 'Ipf\\Bib\\Utility\\Exporter\\BibTexExporter';
				$label = 'export_bibtex';
				break;
			case 'xml':
				$exporterClass = 'Ipf\\Bib\\Utility\\Exporter\\XmlExporter';
				$label = 'export_xml';
				break;
			default:
				$message = GeneralUtility::makeInstance(FlashMessage::class,
					'Unknown export mode',
					'',
					FlashMessage::ERROR
				);
				FlashMessageQueue::addMessage($message);
		}

		/** @var \Ipf\Bib\Utility\Exporter\Exporter $exporter */
		$exporter = GeneralUtility::makeInstance($exporterClass);
		$label = $this->pi1->get_ll($label, $label, TRUE);

		if ($exporter instanceof \Ipf\Bib\Utility\Exporter\Exporter) {
			try {
				$exporter->initialize($this->pi1);
			} catch (\Exception $e) {
				$message = GeneralUtility::makeInstance(FlashMessage::class,
					$e->getMessage(),
					$label,
					FlashMessage::ERROR
				);
				FlashMessageQueue::addMessage($message);
			}

			$dynamic = $this->pi1->conf['export.']['dynamic'] ? TRUE : FALSE;

			if ($this->extConf['dynamic']) {
				$dynamic = TRUE;
			}

			$exporter->setDynamic($dynamic);

			try {
				$exporter->export();
				if ($dynamic) {
					$this->dumpExportDataAndExit($exporter);
				} else {
					$content .= $this->pi1->createLinkToExportFile($exporter);
				}
			} catch (FileOperationErrorException $e) {
				$message = GeneralUtility::makeInstance(FlashMessage::class,
					$e->getMessage(),
					'',
					FlashMessage::ERROR
				);
				FlashMessageQueue::addMessage($message);
			}

		}

		return $content;
	}

	/**
	 * @param \Ipf\Bib\Utility\Exporter\Exporter $exporter
	 */
	protected function dumpExportDataAndExit($exporter) {
		// Dump the export data and exit
		$exporterFileName = $exporter->getFileName();
		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename="' . $exporterFileName . '"');
		header('Cache-Control: no-cache, must-revalidate');
		echo $exporter->getData();
		exit ();
	}

	/**
	 * @param \Ipf\Bib\Utility\Exporter\Exporter $exporter
	 * @return string
	 */
	protected function createLinkToExportFile($exporter) {
		$link = $this->pi1->cObj->getTypoLink(
				$exporter->getFileName(),
				$exporter->getRelativeFilePath()
		);
		$content = '<ul><li><div>';
		$content .= $link;
		if ($exporter->getIsNewFile()) {
			$content .= ' (' . $this->pi1->get_ll('export_file_new') . ')';
		}
		$content .= '</div></li></ul>';

		return $content;
	}

	/**
	 * The import dialog
	 *
	 * @return String The import dialog
	 */
	protected function importDialog() {

		$content = sprintf('<h2>%s</h2>', $this->pi1->get_ll('import_title'));
		$mode = $this->pi1->piVars['import'];

		if (($mode == Importer::IMP_BIBTEX) || ($mode == Importer::IMP_XML)) {

			/** @var Importer $importer */
			$importer = FALSE;

			switch ($mode) {
				case Importer::IMP_BIBTEX:
					/** @var Importer $importer */
					$importer = GeneralUtility::makeInstance(BibTexImporter::class);
					break;
				case Importer::IMP_XML:
					/** @var Importer $importer */
					$importer = GeneralUtility::makeInstance(XmlImporter::class);
					break;
			}

			$importer->initialize($this->pi1);
			try {
				$content .= $importer->import();
			} catch (\Exception $e) {
				$message = GeneralUtility::makeInstance(FlashMessage::class,
						$e->getMessage(),
						'',
						FlashMessage::ERROR
				);
				FlashMessageQueue::addMessage($message);
			}
		} else {
			$message = GeneralUtility::makeInstance(FlashMessage::class,
					'Unknown import mode',
					'',
					FlashMessage::ERROR
			);
			FlashMessageQueue::addMessage($message);
		}

		return $content;
	}

}
