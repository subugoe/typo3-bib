<?php

namespace Ipf\Bib\View;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ExportView extends View
{
    public function get(array $configuration): string
    {
        /** @var FlashMessageQueue $flashMessageQueue */
        $flashMessageQueue = GeneralUtility::makeInstance(FlashMessageQueue::class, 'tx_bib');

        $mode = $configuration['export_navi']['do'];
        $content = '<h2>'.$this->pi_getLL('export_title').'</h2>';

        $label = '';
        switch ($mode) {
            case 'bibtex':
                $exporterClass = \Ipf\Bib\Utility\Exporter\BibTexExporter::class;
                $label = 'export_bibtex';
                break;
            case 'xml':
                $exporterClass = \Ipf\Bib\Utility\Exporter\XmlExporter::class;
                $label = 'export_xml';
                break;
            default:
                /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
                $message = GeneralUtility::makeInstance(
                    \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                    'Unknown export mode',
                    '',
                    FlashMessage::ERROR
                );
                $flashMessageQueue->addMessage($message);
        }

        /** @var \Ipf\Bib\Utility\Exporter\Exporter $exporter */
        $exporter = GeneralUtility::makeInstance($exporterClass);
        $label = $this->pi_getLL($label, $label, true);

        if ($exporter instanceof \Ipf\Bib\Utility\Exporter\Exporter) {
            try {
                $exporter->initialize($this);
            } catch (\Exception $e) {
                $message = GeneralUtility::makeInstance(
                    \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                    $e->getMessage(),
                    $label,
                    FlashMessage::ERROR
                );
                $flashMessageQueue->addMessage($message);
            }

            $dynamic = $this->conf['export.']['dynamic'] ? true : false;

            if ($configuration['dynamic']) {
                $dynamic = true;
            }

            $exporter->setDynamic($dynamic);

            try {
                $exporter->export();
                if ($dynamic) {
                    $this->dumpExportDataAndExit($exporter);
                } else {
                    $content .= $this->createLinkToExportFile($exporter);
                }
            } catch (\TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException $e) {
                $message = GeneralUtility::makeInstance(
                    \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                    $e->getMessage(),
                    '',
                    FlashMessage::ERROR
                );
                $flashMessageQueue->addMessage($message);
            }
        }

        return $content;
    }

    /**
     * @param \Ipf\Bib\Utility\Exporter\Exporter $exporter
     */
    private function dumpExportDataAndExit($exporter)
    {
        // Dump the export data and exit
        $exporterFileName = $exporter->getFileName();
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="'.$exporterFileName.'"');
        header('Cache-Control: no-cache, must-revalidate');
        echo $exporter->getData();
        exit();
    }

    /**
     * @param \Ipf\Bib\Utility\Exporter\Exporter $exporter
     *
     * @return string
     */
    private function createLinkToExportFile($exporter)
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $link = $contentObjectRenderer->getTypoLink(
            $exporter->getFileName(),
            $exporter->getRelativeFilePath()
        );
        $content = '<ul><li><div>';
        $content .= $link;
        if ($exporter->getIsNewFile()) {
            $content .= ' ('.$this->pi_getLL('export_file_new').')';
        }
        $content .= '</div></li>';
        $content .= '</ul>';

        return $content;
    }
}
