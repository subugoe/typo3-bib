<?php

namespace Ipf\Bib\View;

use Ipf\Bib\Exporter\BibTexExporter;
use Ipf\Bib\Exporter\Exporter;
use Ipf\Bib\Exporter\XmlExporter;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ExportView extends View
{
    public function get(): string
    {
        /** @var FlashMessageQueue $flashMessageQueue */
        $flashMessageQueue = GeneralUtility::makeInstance(FlashMessageQueue::class, 'tx_bib');

        $this->view->setTemplatePathAndFilename('EXT:bib/Resources/Private/Templates/Exporter/Exporter.html');

        $mode = $this->configuration['export_navi']['do'];

        $label = '';
        switch ($mode) {
            case 'bibtex':
                $exporter = GeneralUtility::makeInstance(BibTexExporter::class, $this->configuration, $this->conf);
                $label = 'export_bibtex';
                break;
            case 'xml':
                $exporter = GeneralUtility::makeInstance(XmlExporter::class, $this->configuration, $this->conf);
                $label = 'export_xml';
                break;
            default:
                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    'Unknown export mode',
                    '',
                    FlashMessage::ERROR
                );
                $flashMessageQueue->addMessage($message);
        }

        $this->view->assign('label', $label);

        if ($exporter instanceof Exporter) {
            try {
                $exporter->initialize($this->configuration);
            } catch (\Exception $e) {
                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $e->getMessage(),
                    $label,
                    FlashMessage::ERROR
                );
                $flashMessageQueue->addMessage($message);
            }

            $dynamic = $this->conf['export.']['dynamic'] ? true : false;

            if ($this->configuration['dynamic']) {
                $dynamic = true;
            }

            $exporter->setDynamic($dynamic);

            try {
                $exporter->export();
                if ($exporter->getDynamic()) {
                    $this->dumpExportDataAndExit($exporter);
                } else {
                    $content = $this->createLinkToExportFile($exporter);
                }
            } catch (\Exception $e) {
                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $e->getMessage(),
                    '',
                    FlashMessage::ERROR
                );
                $flashMessageQueue->addMessage($message);
            }
        }

        $this->view->assign('content', $content);

        return $this->view->render();
    }

    private function dumpExportDataAndExit(Exporter $exporter)
    {
        // Dump the export data and exit
        $exporterFileName = $exporter->getFileName();
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="'.$exporterFileName.'"');
        header('Cache-Control: no-cache, must-revalidate');
        echo $exporter->getData();
        exit();
    }

    private function createLinkToExportFile(Exporter $exporter): string
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $link = $contentObjectRenderer->getTypoLink(
            $exporter->getFileName(),
            $exporter->getRelativeFilePath()
        );
        $content = '<ul><li><div>';
        $content .= $link;
        if ($exporter->getIsNewFile()) {
            $content .= ' ('.LocalizationUtility::translate('export_file_new', 'bib').')';
        }
        $content .= '</div></li>';
        $content .= '</ul>';

        return $content;
    }
}
