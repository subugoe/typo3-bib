<?php

namespace Ipf\Bib\View;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class DialogView extends View
{
    public function initialize(): string
    {
        $this->view->setTemplatePathAndFilename('EXT:bib/Resources/Private/Templates/Dialog/Index.html');
        $getPostVariables = GeneralUtility::_GP('tx_bib_pi1');

        switch ($this->configuration['dialog_mode']) {
            case \Ipf\Bib\Modes\Dialog::DIALOG_EXPORT:
                $exportView = GeneralUtility::makeInstance(ExportView::class, $this->configuration, $this->conf);
                $content = $exportView->get();
                break;
            case \Ipf\Bib\Modes\Dialog::DIALOG_IMPORT:
                $importView = GeneralUtility::makeInstance(ImportView::class, $this->configuration, $this->conf);
                $content = $importView->get((int) $getPostVariables['import']);
                break;
            default:
                $editorView = GeneralUtility::makeInstance(EditorView::class, $this->configuration, $this->conf);
                $editorView->initialize($configuration);
                $content = $editorView->dialogView();
        }

        $this->view->assign('content', $content);

        return $this->view->render();
    }
}
