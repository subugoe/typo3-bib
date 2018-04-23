<?php

namespace Ipf\Bib\View;

use Ipf\Bib\Importer\BibTexImporter;
use Ipf\Bib\Importer\Importer;
use Ipf\Bib\Importer\XmlImporter;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImportView extends View
{
    public function get(int $mode): string
    {
        $flashMessageQueue = GeneralUtility::makeInstance(FlashMessageQueue::class, 'tx_bib');
        $this->view->setTemplatePathAndFilename('EXT:bib/Resources/Private/Templates/Importer/GeneralImport.html');

        if ((Importer::IMP_BIBTEX === $mode) || (Importer::IMP_XML === $mode)) {
            switch ($mode) {
                case Importer::IMP_BIBTEX:
                    $this->view->setTemplatePathAndFilename('EXT:bib/Resources/Private/Templates/Importer/BibTexImport.html');
                    $importer = GeneralUtility::makeInstance(BibTexImporter::class, $this->configuration, $this->conf);
                    break;
                case Importer::IMP_XML:
                    $this->view->setTemplatePathAndFilename('EXT:bib/Resources/Private/Templates/Importer/XmlImport.html');
                    $importer = GeneralUtility::makeInstance(XmlImporter::class, $this->configuration, $this->conf);
                    break;
            }

            try {
                $importer->initialize();
                $content = $importer->import();
            } catch (\Exception $e) {
                /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
                $message = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $e->getMessage(),
                        '',
                        FlashMessage::ERROR
                    );
                $flashMessageQueue->addMessage($message);
            }
        } else {
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
            $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    'Unknown import mode',
                    '',
                    FlashMessage::ERROR
                );
            $flashMessageQueue->addMessage($message);
        }

        $this->view->assign('content', $content ?? '');

        return $this->view->render();
    }
}
