<?php

namespace Ipf\Bib\View;

use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DialogView extends View
{
    public function initialize(array $configuration): string
    {
        /** @var FlashMessageQueue $flashMessageQueue */
        $flashMessageQueue = GeneralUtility::makeInstance(FlashMessageQueue::class, 'tx_bib');

        $content = '';
        switch ($configuration['dialog_mode']) {
            case \Ipf\Bib\Modes\Dialog::DIALOG_EXPORT:
                $exportView = GeneralUtility::makeInstance(ExportView::class);
                $content .= $exportView->get($configuration);
                break;
            case \Ipf\Bib\Modes\Dialog::DIALOG_IMPORT:
                $importView = GeneralUtility::makeInstance(ImportView::class);
                $content .= $importView->get((int) $this->piVars['import']);
                break;
            default:
                $editorView = GeneralUtility::makeInstance(EditorView::class);
                $editorView->initialize($configuration);
                $content .= $editorView->dialogView();
        }
        $content .= $flashMessageQueue->renderFlashMessages();
        $content .= '<p>';
        $content .= $this->get_link($this->pi_getLL('link_back_to_list'));
        $content .= '</p>';

        return $content;
    }
}
