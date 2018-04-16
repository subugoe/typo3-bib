<?php

namespace Ipf\Bib\View;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SingleView.
 */
class SingleView extends View
{
    /**
     * @var \tx_bib_pi1
     */
    public $pi1;

    /**
     * @var array
     */
    public $conf;

    /**
     * @var \Ipf\Bib\Utility\ReferenceReader
     */
    public $referenceReader;

    /**
     * @var \Ipf\Bib\Utility\DbUtility
     */
    public $databaseUtility;

    /**
     * @var string
     */
    public $LLPrefix = 'editor_';

    /**
     * @var bool
     */
    public $idGenerator = false;

    /**
     * @var bool
     */
    public $isNew = false;

    /**
     * @var bool
     */
    public $isNewFirst = false;

    /**
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected $view;

    /**
     * @var array
     */
    private $extConf;

    /**
     * Initializes this class.
     */
    public function initialize(array $configuration): string
    {
        $this->view->setTemplatePathAndFilename('typo3conf/ext/bib/Resources/Private/Templates/Single/Index.html');

        return $this->singleView($configuration);
    }

    /**
     * Returns the single view.
     *
     * @return string
     */
    public function singleView(array $configuration)
    {
        $content = '';

        $uid = (int) $configuration['single_view']['uid'];
        $ref = $this->referenceReader->getPublicationDetails($uid);

        if (is_array($ref)) {
            try {
                $this->typeReference($ref, $configuration);
            } catch (\Exception $e) {
                $content .= $e->getMessage();
            }
        } else {
            $content .= '<p>';
            $content .= 'No publication with uid '.$uid;
            $content .= '</p>';
        }
        $content = preg_replace("/\n+/", PHP_EOL, $content);

        $this->view->assign('linkBack', $this->pi1->get_link($this->pi1->pi_getLL('link_back_to_list')));
        $this->view->assign('content', $content);

        return $this->view->render();
    }

    private function typeReference($ref, array $configuration)
    {
        // Store the cObj Data for later recovery
        $contentObjectBackup = $this->pi1->cObj->data;

        // Prepare the publication data and environment
        $configuration = $this->pi1->prepareItemSetup($configuration);
        $publicationData = $this->pi1->preparePublicationData($ref, $configuration);
        $this->pi1->prepare_pub_cObj_data($publicationData);

        $bib_str = $publicationData['bibtype_short'];

        // The filed list
        $fields = $this->referenceReader->pubAllFields;
        $dont_show = GeneralUtility::trimExplode(',', $this->conf['dont_show'], true);

        $publication = [];
        foreach ($fields as $field) {
            if ((strlen($publicationData[$field]) > 0)) {
                if (!in_array($field, $dont_show)) {
                    $label = $this->getFieldLabel($field, $bib_str);
                    $label = $this->pi1->cObj->stdWrap($label, $this->conf['all_labels.']);

                    $value = strval($publicationData[$field]);
                    $stdWrap = $this->pi1->conf['field.'][$field.'.'];

                    if (isset($this->pi1->conf['field.'][$bib_str.'.'][$field.'.'])) {
                        $stdWrap = $this->pi1->conf['field.'][$bib_str.'.'][$field.'.'];
                    }

                    if (isset($this->conf['field_wrap.'][$field.'.'])) {
                        $stdWrap = $this->conf['field_wrap.'][$field.'.'];
                    }

                    if (isset($stdWrap['single_view_link'])) {
                        $value = $this->pi1->get_link(
                            $value,
                            ['show_uid' => strval($publicationData['uid'])]
                        );
                    }
                    $publication[$field] = $value;
                    $value = $this->pi1->cObj->stdWrap($value, $stdWrap);

                    $this->view->assign($field, $value);
                    $this->view->assign('label'.ucfirst($field), $label);
                }
            }
        }

        // Single view title
        $title = $this->pi1->pi_getLL('single_view_title');
        $title = $this->pi1->cObj->stdWrap($title, $this->conf['title.']);

        // Pre and post text
        $preText = strval($this->conf['pre_text']);
        $preText = $this->pi1->cObj->stdWrap($preText, $this->conf['pre_text.']);

        $postText = strval($this->conf['post_text']);
        $postText = $this->pi1->cObj->stdWrap($postText, $this->conf['post_text.']);

        $this->view->assignMultiple(
            [
                'pageTitle' => $title,
                'preText' => $preText,
                'postText' => $postText,
            ]
        );

        $this->view->assign('publication', $publication);
        // Restore cObj data
        $this->pi1->cObj->data = $contentObjectBackup;
    }

    /**
     * Depending on the bibliography type this function returns
     * The label for a field.
     *
     * @param string $field      The field
     * @param string $identifier The bibtype identifier string
     *
     * @return string
     */
    protected function getFieldLabel($field, $identifier)
    {
        $label = $this->referenceReader->getReferenceTable().'_'.$field;

        switch ($field) {
            case 'authors':
                $label = $this->referenceReader->getAuthorTable().'_'.$field;
                break;
        }

        $over = [
            $this->pi1->conf['editor.']['olabel.']['all.'][$field],
            $this->pi1->conf['editor.']['olabel.'][$identifier.'.'][$field],
        ];

        foreach ($over as $lvar) {
            if (is_string($lvar)) {
                $label = $lvar;
            }
        }

        $label = trim($label);
        if (strlen($label) > 0) {
            $label = $this->pi1->pi_getLL($label, $label, true);
        }

        return $label;
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/View/SingleView.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/View/SingleView.php'];
}
