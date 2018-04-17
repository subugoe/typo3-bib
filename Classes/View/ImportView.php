<?php

namespace Ipf\Bib\View;

use Ipf\Bib\Utility\Importer\BibTexImporter;
use Ipf\Bib\Utility\Importer\Importer;
use Ipf\Bib\Utility\Importer\XmlImporter;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class ImportView extends View
{
    public function get(int $mode): string
    {
        /** @var FlashMessageQueue $flashMessageQueue */
        $flashMessageQueue = GeneralUtility::makeInstance(FlashMessageQueue::class, 'tx_bib');

        $content = '<h2>'.LocalizationUtility::translate('import_title', 'bib').'</h2>';

        if ((Importer::IMP_BIBTEX === $mode) || (Importer::IMP_XML === $mode)) {
            /** @var \Ipf\Bib\Utility\Importer\Importer $importer */
            $importer = false;

            switch ($mode) {
                    case \Ipf\Bib\Utility\Importer\Importer::IMP_BIBTEX:
                        $importer = GeneralUtility::makeInstance(BibTexImporter::class);
                        break;
                    case \Ipf\Bib\Utility\Importer\Importer::IMP_XML:
                        $importer = GeneralUtility::makeInstance(XmlImporter::class);
                        break;
                }

            $importer->initialize();
            try {
                $content .= $importer->import();
            } catch (\Exception $e) {
                /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
                $message = GeneralUtility::makeInstance(
                        \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                        $e->getMessage(),
                        '',
                        FlashMessage::ERROR
                    );
                $flashMessageQueue->addMessage($message);
            }
        } else {
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
            $message = GeneralUtility::makeInstance(
                    \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                    'Unknown import mode',
                    '',
                    FlashMessage::ERROR
                );
            $flashMessageQueue->addMessage($message);
        }

        return $content;
    }
}
