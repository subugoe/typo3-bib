<?php

declare(strict_types=1);

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

use Ipf\Bib\Domain\Model\Author;
use Ipf\Bib\Domain\Model\Reference;
use Ipf\Bib\Exception\DataException;
use Ipf\Bib\Modes\AutoId;
use Ipf\Bib\Modes\Dialog;
use Ipf\Bib\Modes\Editor;
use Ipf\Bib\Service\ItemTransformerService;
use Ipf\Bib\Utility\DbUtility;
use Ipf\Bib\Utility\Generator\CiteIdGenerator;
use Ipf\Bib\Utility\ReferenceReader;
use Ipf\Bib\Utility\ReferenceWriter;
use Ipf\Bib\Utility\Utility;
use Subugoe\Substaff\Domain\Model\Publication;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class EditorView.
 */
class EditorView extends View
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
     * @var \Ipf\Bib\Utility\ReferenceWriter
     */
    public $referenceWriter;
    /**
     * Database Utility.
     *
     * @var \Ipf\Bib\Utility\DbUtility
     */
    public $databaseUtility;

    /**
     * @var string
     */
    public $LLPrefix = 'editor_';

    /**
     * @var \Ipf\Bib\Utility\Generator\CiteIdGenerator|bool
     */
    public $idGenerator = false;

    /**
     * @var bool
     */
    public $isNew = false;

    /**
     * @var bool
     */
    public $isFirstEdit = false;

    /**
     * @var int
     */
    protected $widgetMode;

    /**
     * Show and pass value.
     */
    const WIDGET_SHOW = 0;

    /**
     * Edit and pass value.
     */
    const WIDGET_EDIT = 1;

    /**
     * Don't show but pass value.
     */
    const WIDGET_SILENT = 2;

    /**
     * Don't show and don't pass value.
     */
    const WIDGET_HIDDEN = 3;

    /**
     * @var array
     */
    private $configuration;

    /**
     * Initializes this class.
     */
    public function initialize(array $configuration): string
    {
        $this->configuration = $configuration;

//        $this->conf = &$pi1->conf['editor.'];
        $this->referenceReader = GeneralUtility::makeInstance(ReferenceReader::class, $configuration);
        $this->referenceReader->setClearCache($configuration['editor']['clear_page_cache']);

        // setup db_utility
        /** @var \Ipf\Bib\Utility\DbUtility $databaseUtility */
        $databaseUtility = GeneralUtility::makeInstance(DbUtility::class, $configuration);
        $databaseUtility->readFullTextGenerationConfiguration($this->conf['full_text.']);

        $this->databaseUtility = $databaseUtility;

        $this->idGenerator = GeneralUtility::makeInstance(CiteIdGenerator::class, $configuration);

        $this->view->setTemplatePathAndFilename('EXT:bib/Resources/Private/Templates/Editor/Index.html');
        $this->view->setPartialRootPaths([10 => 'EXT:bib/Resources/Private/Partials/']);

        return $this->editor_view($configuration);
    }

    /**
     * The editor shows a single publication entry
     * and allows to edit, delete or save it.
     *
     * @throws \Exception
     *
     * @return string A publication editor
     */
    public function editor_view(array $configuration): string
    {
        $content = '';

        // check whether the BE user is authorized
        if (!$configuration['edit_mode']) {
            throw new \Exception('You are not authorized to edit the publication database.', 1379074809);
        }

        $pub_http = $this->getPublicationDataFromHttpRequest();
        $publicationData = [];
        $preContent = '';

        $this->determineWidgetMode();
        $uid = $this->determinEntryUid();

        $this->isNew = true;
        if ($uid >= 0) {
            $this->isNew = false;
            $pub_http->setUid($uid);
        }

        $getPostVariables = GeneralUtility::_GP('tx_bib_pi1');

        $this->isFirstEdit = true;
        if (is_array($getPostVariables['DATA']['pub'])) {
            $this->isFirstEdit = false;
        }

        $title = $this->getTitle();

        // Load default data
        if ($this->isFirstEdit) {
            if ($this->isNew) {
                // Load defaults for a new publication
                $publicationData = $this->getDefaultPublicationData();
            } else {
                if ($uid < 0) {
                    throw new DataException('No publication id given', 1524043100);
                }

                // Load publication data from database
                $pub_db = $this->referenceReader->getPublicationDetails($uid);
                if (!$pub_db) {
                    throw new DataException(sprintf('No publication with uid %d exists.', $uid), 1524043201);
                }
            }
        }

        // Generate cite id if requested
        $generateCiteIdRequest = false;

        // Evaluate actions
        if (is_array($getPostVariables['action'])) {
            // Generate cite id
            if (array_key_exists('generate_id', $getPostVariables['action'])) {
                $generateCiteIdRequest = true;
            }

            $publicationData = $this->raiseAuthor($publicationData);
            $publicationData = $this->lowerAuthor($publicationData);

            if (isset($getPostVariables['action']['more_authors'])) {
                ++$getPostVariables['editor']['numAuthors'];
            }
            if (isset($getPostVariables['action']['less_authors'])) {
                --$getPostVariables['editor']['numAuthors'];
            }
        }

        $pub_http->setCiteid($this->generateCiteIdOnDemand($generateCiteIdRequest, $pub_http));

        $authorCounter = count($pub_http->getAuthors());

        // Determine the number of authors
        $getPostVariables['editor']['numAuthors'] = max(
            $getPostVariables['editor']['numAuthors'],
            $this->conf['numAuthors'],
            $authorCounter,
            1
        );

        // Data validation
        if (Editor::EDIT_CONFIRM_SAVE === (int) $this->configuration['editor_mode']) {
            $validationErrors = $this->validatePublicationData($publicationData);
            $title = LocalizationUtility::translate('editor_title_confirm_save', 'bib');

            if (count($validationErrors) > 0) {
                $cfg = $this->conf['warn_box.'];
                $txt = LocalizationUtility::translate('editor_error_title', 'bib');
                $box = $this->pi1->cObj->stdWrap($txt, $cfg['title.']);
                $box .= $this->validationErrorMessage($validationErrors);
                $box .= $this->getEditButton();
                $box = $this->pi1->cObj->stdWrap($box, $cfg['all_wrap.']);
                $preContent .= $box;
            }
        }

        $this->view->assign('mode', $this->widgetMode);
        $this->view->assign('preContent', $preContent);
        $this->view->assign('title', $title);
        $this->view->assign('deleteButton', $this->getDeleteButton());
        $this->view->assign('saveButton', $this->getSaveButton());
        $this->view->assign('editButton', $this->getEditButton());

        $content = $this->getFieldGroups($pub_http, $content);

        $content = $this->invisibleUidAndModKeyField($publicationData, $content);

        $this->view->assign('content', $content);

        return htmlspecialchars_decode($this->view->render());
    }

    /**
     * This switches to the requested dialog.
     *
     * @return string The requested dialog
     */
    public function dialogView(): string
    {
        /** @var \Ipf\Bib\Utility\ReferenceWriter $referenceWriter */
        $referenceWriter = GeneralUtility::makeInstance(ReferenceWriter::class);
        $referenceWriter->initialize($this->referenceReader);
        $flashMessageQueue = GeneralUtility::makeInstance(FlashMessageQueue::class, 'tx_bib');
        $getPostVariables = GeneralUtility::_GP('tx_bib_pi1');

        switch ($this->configuration['dialog_mode']) {
            case Dialog::DIALOG_SAVE_CONFIRMED:
                $publication = $this->getPublicationDataFromHttpRequest();

                // Unset fields that should not be edited
                $checkFields = ReferenceReader::$referenceFields;
                $checkFields[] = 'pid';
                $checkFields[] = 'hidden';
                foreach ($checkFields as $ff) {
                    if ($publication[$ff]) {
                        if ($this->conf['no_edit.'][$ff] || $this->conf['no_show.'][$ff]) {
                            unset($publication[$ff]);
                        }
                    }
                }

                try {
                    $referenceWriter->savePublication($publication);
                    /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
                    $message = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $this->createHtmlTextFromPostDatabaseWrite($this->postDatabaseWriteActions()),
                        LocalizationUtility::translate('msg_save_success', 'bib'),

                        FlashMessage::OK
                    );

                    /* @var FlashMessageQueue $flashMessageQueue */
                    $flashMessageQueue->addMessage($message);
                } catch (DataException $e) {
                    $message = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $e->getMessage(),
                        LocalizationUtility::translate('msg_save_fail', 'bib'),
                        FlashMessage::ERROR
                    );
                    $flashMessageQueue->addMessage($message);
                }
                break;

            case Dialog::DIALOG_DELETE_CONFIRMED:
                $publication = $this->getPublicationDataFromHttpRequest();
                try {
                    $referenceWriter->deletePublication($getPostVariables['uid'], $publication['mod_key']);
                    $message = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $this->createHtmlTextFromPostDatabaseWrite($this->postDatabaseWriteActions()),
                        LocalizationUtility::translate('msg_delete_success', 'bib'),
                        FlashMessage::OK
                    );
                    $flashMessageQueue->addMessage($message);
                } catch (DataException $e) {
                    $message = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $e->getMessage(),
                        LocalizationUtility::translate('msg_delete_fail', 'bib'),
                        FlashMessage::ERROR
                    );
                    $flashMessageQueue->addMessage($message);
                }
                break;
            default:
                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    'Unknown dialog mode: '.$this->configuration['dialog_mode'],
                    LocalizationUtility::translate('msg_delete_fail', 'bib'),
                    FlashMessage::ERROR
                );
                $flashMessageQueue->addMessage($message);
        }

        $this->referenceWriter = $referenceWriter;

        return $flashMessageQueue->renderFlashMessages();
    }

    private function getEditButton(): string
    {
        $editButton = '';
        if (Editor::EDIT_CONFIRM_SAVE === (int) $this->configuration['editor_mode']) {
            $editButton = '<input type="submit" ';
            if ($this->isNew) {
                $editButton .= 'name="tx_bib_pi1[action][new]" ';
            } else {
                $editButton .= 'name="tx_bib_pi1[action][edit]" ';
            }
            $editButton .= 'value="'.LocalizationUtility::translate('editor_btn_edit', 'bib').'" class="tx_bib-editor_button"/>';
        }

        return $editButton;
    }

    private function getCiteIdGeneratorButton(): string
    {
        $citeIdeGeneratorButton = '';
        if (self::WIDGET_EDIT === $this->widgetMode) {
            $citeIdeGeneratorButton = '<input type="submit" '.
                'name="tx_bib_pi1[action][generate_id]" '.
                'value="'.LocalizationUtility::translate('editor_btn_generate_id', 'bib').
                '" class="tx_bib-editor_button" />';
        }

        return $citeIdeGeneratorButton;
    }

    private function getUpdateButton(): string
    {
        $updateButton = '';
        if (self::WIDGET_EDIT === $this->widgetMode) {
            $updateButton = '<input type="submit"'.
                ' name="tx_bib_pi1[action][update_form]"'.
                ' value="'.LocalizationUtility::translate('editor_btn_update_form', 'bib').'"'.
                ' class="tx_bib-editor_button"/>';
        }

        return $updateButton;
    }

    private function getSaveButton(): string
    {
        $saveButton = '';
        if (self::WIDGET_EDIT === $this->widgetMode) {
            $saveButton = 'confirm_save';
        }
        if (Editor::EDIT_CONFIRM_SAVE === (int) $this->configuration['editor_mode']) {
            $saveButton = 'save';
        }
        if (strlen($saveButton) > 0) {
            $saveButton = '<input type="submit" name="tx_bib_pi1[action]['.$saveButton.']" '.
                'value="'.LocalizationUtility::translate('editor_btn_save', 'bib').
                '" class="tx_bib-save_button"/>';
        }

        return $saveButton;
    }

    private function getDeleteButton(): string
    {
        $deleteButton = '';

        if (!$this->isNew) {
            if ((Editor::EDIT_SHOW !== (int) $this->configuration['editor_mode'])
                && (Editor::EDIT_CONFIRM_SAVE !== (int) $this->configuration['editor_mode'])) {
                $deleteButton = 'confirm_delete';
            }
            if (Editor::EDIT_CONFIRM_DELETE === (int) $this->configuration['editor_mode']) {
                $deleteButton = 'delete';
            }
            if (strlen($deleteButton)) {
                $deleteButton = '<input type="submit" name="tx_bib_pi1[action]['.$deleteButton.']" '.
                    'value="'.LocalizationUtility::translate('editor_btn_save', 'bib').
                    '" class="tx_bib-delete_button"/>';
            }
        }

        return $deleteButton;
    }

    /**
     * Depending on the bibliography type this function returns
     * The label for a field.
     *
     * @param string $field   The field
     * @param string $bib_str The bibtype identifier string
     *
     * @return string $label
     */
    private function fieldLabel(string $field, string $bib_str): string
    {
        $label = ReferenceReader::REFERENCE_TABLE.'_'.$field;
        switch ($field) {
            case 'authors':
                $label = ReferenceReader::AUTHOR_TABLE.'_'.$field;
                break;
            case 'year':
                $label = 'olabel_year_month_day';
                break;
            case 'month':
            case 'day':
                $label = '';
                break;
        }

        $over = [
            $this->conf['olabel.']['all.'][$field],
            $this->conf['olabel.'][$bib_str.'.'][$field],
        ];

        foreach ($over as $lvar) {
            if (is_string($lvar)) {
                $label = $lvar;
            }
        }

        $label = trim($label);
        if (strlen($label) > 0) {
            return LocalizationUtility::translate($label, 'bib') ?? '';
        }

        return $label;
    }

    /**
     * Depending on the bibliography type this function returns what fields
     * are required and what are optional according to BibTeX.
     *
     * @param $bibType
     *
     * @return array An array with subarrays with field lists for
     */
    private function getEditFields(int $bibType): array
    {
        $fields = [];
        $bib_str = ReferenceReader::$allBibTypes[$bibType];

        $all_groups = ['all', $bib_str];
        $all_types = ['required', 'optional', 'library'];

        // Read field list from TS configuration
        $cfg_fields = [];
        foreach ($all_groups as $group) {
            $cfg_fields[$group] = [];
            $cfg_arr = &$this->conf['groups.'][$group.'.'];
            if (is_array($cfg_arr)) {
                foreach ($all_types as $type) {
                    $cfg_fields[$group][$type] = [];
                    $ff = Utility::multi_explode_trim(
                        [',', '|'],
                        $cfg_arr[$type],
                        true
                    );
                    $cfg_fields[$group][$type] = $ff;
                }
            }
        }

        // Merge field lists
        $pubFields = $this->referenceReader->getPublicationFields();
        unset($pubFields[array_search('bibtype', $pubFields)]);
        foreach ($all_types as $type) {
            $fields[$type] = [];
            $cur = &$fields[$type];
            if (is_array($cfg_fields[$bib_str][$type])) {
                $cur = $cfg_fields[$bib_str][$type];
            }
            if (is_array($cfg_fields['all'][$type])) {
                foreach ($cfg_fields['all'][$type] as $field) {
                    $cur[] = $field;
                }
            }
            $cur = array_unique($cur);

            $cur = array_intersect($cur, $pubFields);
            $pubFields = array_diff($pubFields, $cur);
        }

        // Calculate the remaining 'other' fields
        $fields['other'] = $pubFields;
        $fields['typo3'] = ['uid', 'hidden', 'pid'];

        return $fields;
    }

    /**
     * Get the widget mode for an edit widget.
     *
     * @param string $field
     * @param int    $widgetMode
     *
     * @return int The widget mode
     */
    private function getWidgetMode(string $field, int $widgetMode): int
    {
        if ((self::WIDGET_EDIT === $widgetMode) && $this->conf['no_edit.'][$field]) {
            $widgetMode = self::WIDGET_SHOW;
        }
        if ($this->conf['no_show.'][$field]) {
            $widgetMode = self::WIDGET_HIDDEN;
        }

        if ('uid' === $field) {
            if (self::WIDGET_EDIT === $widgetMode) {
                $widgetMode = self::WIDGET_SHOW;
            } else {
                if (self::WIDGET_HIDDEN === $widgetMode) {
                    // uid must be passed always
                    $widgetMode = self::WIDGET_SILENT;
                }
            }
        } else {
            if ('pid' === $field) {
                // pid must be passed always
                if (self::WIDGET_HIDDEN === $widgetMode) {
                    $widgetMode = self::WIDGET_SILENT;
                }
            }
        }

        return $widgetMode;
    }

    /**
     * Get the edit widget for a row field.
     *
     * @param string           $field
     * @param string|array|int $value
     * @param int              $mode
     *
     * @return string The field widget
     */
    private function getWidget(string $field, $value, int $mode): string
    {
        $content = '';

        switch ($field) {
            case 'authors':
                $content .= $this->getAuthorsWidget([$value], $mode);
                break;
            case 'pid':
                $content .= $this->getPidWidget((int) $value, $mode);
                break;
            default:
                if (self::WIDGET_EDIT === $mode) {
                    $content .= $this->getDefaultEditWidget($field, (string) $value, $mode);
                } else {
                    $content .= $this->getDefaultStaticWidget($field, (string) $value, $mode);
                }
        }

        return $content;
    }

    /**
     * @param string $field
     * @param string $value
     * @param int    $mode
     *
     * @return string
     */
    private function getDefaultEditWidget(string $field, string $value, int $mode): string
    {
        $cfg = $GLOBALS['TCA'][ReferenceReader::REFERENCE_TABLE]['columns'][$field]['config'];

        $content = '';

        $isize = 60;
        $all_size = [
            $this->conf['input_size.']['default'],
            $this->conf['input_size.'][$field],
        ];
        foreach ($all_size as $ivar) {
            if (is_numeric($ivar)) {
                $isize = (int) $ivar;
            }
        }

        // Default widget
        $widgetType = $cfg['type'];
        $fieldAttr = 'tx_bib_pi1[DATA][pub]['.$field.']';
        $htmlValue = Utility::filter_pub_html((string) $value, true);

        $attributes = [];
        $attributes['class'] = 'tx_bib-editor_input';

        switch ($widgetType) {
            case 'input':
                if ($cfg['max']) {
                    $attributes['maxlength'] = $cfg['max'];
                }
                $size = intval($cfg['size']);
                if ($size > 40) {
                    $size = $isize;
                }
                $attributes['size'] = strval($size);

                $content .= Utility::html_text_input(
                    $fieldAttr,
                    $htmlValue,
                    $attributes
                );

                break;

            case 'text':
                $content .= '<textarea name="'.$fieldAttr.'"';
                $content .= ' rows="'.$cfg['rows'].'"';
                $content .= ' cols="'.strval($isize).'"';
                $content .= ' class="tx_bib-editor_input">';
                $content .= $htmlValue;
                $content .= '</textarea>';

                break;

            case 'select':
                $attributes['name'] = $fieldAttr;
                if ('bibtype' === $field) {
                    $attributes['onchange'] = 'click_update_button()';
                }

                $pairs = [];
                $itemConfigurationSize = count($cfg['items']);
                for ($ii = 0; $ii < $itemConfigurationSize; ++$ii) {
                    $p_desc = LocalizationUtility::translate($cfg['items'][$ii][0], 'bib');
                    $p_val = $cfg['items'][$ii][1];
                    $pairs[$p_val] = $p_desc;
                }

                $content .= Utility::html_select_input(
                    $pairs,
                    $value,
                    $attributes
                );

                break;

            case 'check':
                $content .= Utility::html_check_input(
                    $fieldAttr,
                    '1',
                    ('1' === $value),
                    $attributes
                );

                break;

            default:
                $content .= 'Unknown edit widget: '.$widgetType;
        }

        return $content;
    }

    /**
     * @param string $field
     * @param string $value
     * @param int    $mode
     *
     * @return string
     */
    private function getDefaultStaticWidget(string $field, string $value, int $mode): string
    {
        $configuration = $GLOBALS['TCA'][$this->referenceReader->getReferenceTable()]['columns'][$field]['config'];

        // Default widget
        $widgetType = $configuration['type'];
        $fieldAttributes = 'tx_bib_pi1[DATA][pub]['.$field.']';
        $htmlValue = Utility::filter_pub_html((string) $value, true);

        $content = '';
        if (self::WIDGET_SHOW === $mode) {
            $content .= Utility::html_hidden_input($fieldAttributes, $htmlValue);

            switch ($widgetType) {
                case 'select':
                    $name = '';
                    foreach ($configuration['items'] as $it) {
                        if (strtolower($it[1]) === strtolower($value)) {
                            $name = LocalizationUtility::translate($it[0], 'bib');
                            break;
                        }
                    }
                    $content .= $name;
                    break;

                case 'check':
                    $content .= LocalizationUtility::translate(('0' === $value) ? 'editor_no' : 'editor_yes', 'bib');
                    break;

                default:
                    $content .= $htmlValue;
            }
        } else {
            if (self::WIDGET_SILENT === $mode) {
                $content .= Utility::html_hidden_input(
                    $fieldAttributes,
                    $htmlValue
                );
            }
        }

        return $content;
    }

    private function localFileDoesNotExist(array $pub, array $d_err): array
    {
        $type = 'file_nexist';
        if ($this->conf['warnings.'][$type]) {
            $file = $pub['file_url'];
            if (Utility::check_file_nexist($file)) {
                $message = LocalizationUtility::translate('editor_error_file_nexist', 'bib');
                $message = str_replace('%f', $file, $message);
                $d_err[] = ['type' => $type, 'msg' => $message];
            }
        }

        return $d_err;
    }

    /**
     * Get the authors widget.
     *
     * @param array $value
     * @param int   $mode
     *
     * @return string The authors widget
     */
    private function getAuthorsWidget(array $value, int $mode): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:bib/Resources/Private/Templates/Editor/AuthorsWidget.html');
        $view->assign('mode', $mode);
        $view->assign('authors', $value);
        $getPostVariables = GeneralUtility::_GP('tx_bib_pi1');
        $content = '';

        $key_action = 'tx_bib_pi1[action]';
        $key_data = 'tx_bib_pi1[DATA][pub][authors]';

        // Author widget
        $authors = is_array($value) ? $value : [];
        $aNum = count($authors);
        $edOpts = &$getPostVariables['editor'];
        $edOpts['numAuthors'] = max(
            (int) $edOpts['numAuthors'],
            $aNum,
            (int) $this->configuration['editor']['numAuthors'],
            1
        );
        if ((self::WIDGET_SHOW === $mode) || (self::WIDGET_EDIT === $mode)) {
            $au_con = [];
            for ($i = 0; $i < $edOpts['numAuthors']; ++$i) {
                if ($i > ($aNum - 1) && (self::WIDGET_EDIT !== $mode)) {
                    break;
                }
                $row_con = [];

                $foreName = Utility::filter_pub_html($authors[$i]['forename'] ?? '', true);
                $surName = Utility::filter_pub_Html($authors[$i]['surname'] ?? '', true);

                $row_con[0] = strval($i + 1);
                if (self::WIDGET_SHOW === $mode) {
                    $row_con[1] = Utility::html_hidden_input(
                        $key_data.'['.$i.'][forename]',
                        $foreName
                    );
                    $row_con[1] .= $foreName;

                    $row_con[2] = Utility::html_hidden_input(
                        $key_data.'['.$i.'][surname]',
                        $surName
                    );
                    $row_con[2] .= $surName;
                } else {
                    if (self::WIDGET_EDIT === $mode) {
                        $lowerBtn = Utility::html_image_input(
                            $key_action.'[lower_author]',
                            (string) $i,
                            $this->pi1->icon_src['down']
                        );

                        $raiseBtn = Utility::html_image_input(
                            $key_action.'[raise_author]',
                            (string) $i,
                            $this->pi1->icon_src['up']
                        );

                        $row_con[3] = ($i < ($aNum - 1)) ? $lowerBtn : '';
                        $row_con[4] = (($i > 0) && ($i < ($aNum))) ? $raiseBtn : '';
                    }
                }

                $au_con[] = $row_con;
            }
        } else {
            if (self::WIDGET_SILENT === $mode) {
                $authorsSize = count($authors);
                for ($i = 0; $i < $authorsSize; ++$i) {
                    $foreName = Utility::filter_pub_html(
                        $authors[$i]['forename'],
                        true
                    );
                    $surName = Utility::filter_pub_Html(
                        $authors[$i]['surname'],
                        true
                    );

                    $content .= Utility::html_hidden_input(
                        $key_data.'['.$i.'][forename]',
                        $foreName
                    );

                    $content .= Utility::html_hidden_input(
                        $key_data.'['.$i.'][surname]',
                        $surName
                    );
                }
            }
        }

        $view->assign('content', $content);

        return $view->render();
    }

    /**
     * Get the pid (storage folder) widget.
     *
     * @param int $value
     * @param int $mode
     *
     * @return string The pid widget
     */
    private function getPidWidget(int $value, int $mode): string
    {
        $content = '';

        // Pid
        $pids = $this->configuration['pid_list'];
        $value = (int) $value;
        $fieldAttr = 'tx_bib_pi1[DATA][pub][pid]';

        $attributes = [];
        $attributes['class'] = 'tx_bib-editor_input';

        // Fetch page titles
        $pages = Utility::get_page_titles($pids);

        if (self::WIDGET_SHOW === $mode) {
            $content .= Utility::html_hidden_input(
                $fieldAttr,
                (string) $value
            );
            $content .= strval($pages[$value]);
        } else {
            if (self::WIDGET_EDIT === $mode) {
                $attributes['name'] = $fieldAttr;
                $content .= Utility::html_select_input(
                    $pages,
                    (string) $value,
                    $attributes
                );
            } else {
                if (self::WIDGET_SILENT === $mode) {
                    $content .= Utility::html_hidden_input(
                        $fieldAttr,
                        (string) $value
                    );
                }
            }
        }

        return $content;
    }

    /**
     * Returns the default storage uid.
     *
     * @return int The parent id pid
     */
    private function getDefaultPid(): int
    {
        $pid = 0;
        if (is_numeric($this->conf['default_pid'])) {
            $pid = intval($this->conf['default_pid']);
        }
        if (!in_array($pid, $this->referenceReader->pid_list)) {
            $pid = intval($this->referenceReader->pid_list[0]);
        }

        return $pid;
    }

    /**
     * Returns the default publication data.
     *
     * @return array An array containing the default publication data
     */
    private function getDefaultPublicationData(): array
    {
        $publication = [];

        if (is_array($this->conf['field_default.'])) {
            foreach (ReferenceReader::$referenceFields as $field) {
                if (array_key_exists($field, $this->conf['field_default.'])) {
                    $publication[$field] = strval($this->conf['field_default.'][$field]);
                }
            }
        }

        if (0 === (int) $publication['bibtype']) {
            $publication['bibtype'] = array_search('article', ReferenceReader::$allBibTypes);
        }

        if (0 === (int) $publication['year']) {
            if (is_numeric($this->configuration['year'])) {
                $publication['year'] = intval($this->configuration['year']);
            } else {
                $publication['year'] = intval(date('Y'));
            }
        }

        if (!in_array($publication['pid'], $this->referenceReader->pid_list)) {
            $publication['pid'] = $this->getDefaultPid();
        }

        return $publication;
    }

    /**
     * Returns the publication data that was encoded in the
     * HTTP request.
     *
     * @param bool $htmlSpecialChars
     *
     * @return array An array containing the formatted publication
     *               data that was found in the HTTP request
     */
    private function getPublicationDataFromHttpRequest(): Reference
    {
        $getPostVariables = GeneralUtility::_GP('tx_bib_pi1');

        $Publication = [];
        $fields = $this->referenceReader->getPublicationFields();
        $fields[] = 'uid';
        $fields[] = 'pid';
        $fields[] = 'hidden';
        $fields[] = 'mod_key'; // Gets generated on loading from the database
        $data = $getPostVariables['DATA']['pub'];
        if (is_array($data)) {
            foreach ($fields as $ff) {
                switch ($ff) {
                    case 'authors':
                        if (is_array($data[$ff])) {
                            $authors = [];
                            $Publication['authors'] = [];
                            foreach ($data[$ff] as $v) {
                                $author = new Author();
                                $author->setForeName(trim($v['forename']));
                                $author->setSurName(trim($v['surname']));

                                if (strlen($author->getForeName()) || strlen($author->getSurName())) {
                                    $authors[] = $author;
                                }
                            }
                        }

                        break;

                    default:
                        if (array_key_exists($ff, $data)) {
                            $Publication[$ff] = $data[$ff];
                        }
                }
            }
        }
        $reference = GeneralUtility::makeInstance(ItemTransformerService::class, $this->configuration)->transformPublication($Publication);
        $reference->setAuthors((array) $authors);

        return $reference;
    }

    /**
     * Performs actions after Database write access (save/delete).
     *
     * @return array The requested dialog
     */
    private function postDatabaseWriteActions(): array
    {
        $events = [];
        $errors = [];
        if ($this->conf['delete_no_ref_authors']) {
            $count = $this->databaseUtility->deleteAuthorsWithoutPublications();
            if ($count > 0) {
                $message = LocalizationUtility::translate('msg_deleted_authors', 'bib');
                $message = str_replace('%d', strval($count), $message);
                $events[] = $message;
            }
        }
        if ($this->conf['full_text.']['update']) {
            $stat = $this->databaseUtility->update_full_text_all();

            $count = count($stat['updated']);
            if ($count > 0) {
                $message = LocalizationUtility::translate('msg_updated_full_text', 'bib');
                $message = str_replace('%d', strval($count), $message);
                $events[] = $message;
            }

            if (count($stat['errors']) > 0) {
                foreach ($stat['errors'] as $err) {
                    $message = $err[1]['msg'];
                    $errors[] = $message;
                }
            }

            if ($stat['limit_num']) {
                $message = LocalizationUtility::translate('msg_warn_ftc_limit', 'bib').' - ';
                $message .= LocalizationUtility::translate('msg_warn_ftc_limit_num', 'bib');
                $errors[] = $message;
            }

            if ($stat['limit_time']) {
                $message = LocalizationUtility::translate('msg_warn_ftc_limit', 'bib').' - ';
                $message .= LocalizationUtility::translate('msg_warn_ftc_limit_time', 'bib');
                $errors[] = $message;
            }
        }

        return [$events, $errors];
    }

    /**
     * Creates a html text from a post db write event.
     *
     * @param array $messages
     *
     * @return string The html message string
     */
    private function createHtmlTextFromPostDatabaseWrite(array $messages): string
    {
        $content = '';
        if (count($messages[0]) > 0) {
            $content .= '<h4>'.LocalizationUtility::translate('msg_title_events', 'bib').'</h4>';
            $content .= $this->createHtmlTextFromPostDatabaseWriteEvent($messages[0]);
        }
        if (count($messages[1]) > 0) {
            $content .= '<h4>'.LocalizationUtility::translate('msg_title_errors', 'bib').'</h4>';
            $content .= $this->createHtmlTextFromPostDatabaseWriteEvent($messages[1]);
        }

        return $content;
    }

    /**
     * Creates a html text from a post db write event.
     *
     * @param array $messages
     *
     * @return string The html message string
     */
    private function createHtmlTextFromPostDatabaseWriteEvent(array $messages): string
    {
        $messages = Utility::string_counter($messages);
        $content = '<ul>';
        foreach ($messages as $msg => $count) {
            $msg = htmlspecialchars($msg, ENT_QUOTES, $this->configuration['charset']['upper']);
            $content .= '<li>';
            $content .= $msg;
            if ($count > 1) {
                $app = str_replace('%d', strval($count), LocalizationUtility::translate('msg_times', 'bib'));
                $content .= '('.$app.')';
            }
            $content .= '</li>';
        }
        $content .= '</ul>';

        return $content;
    }

    /**
     * Validates the data in a publication.
     *
     * @param array $pub
     *
     * @return array An array with error messages
     */
    private function validatePublicationData(array $pub): array
    {
        $d_err = [];
        $bib_str = ReferenceReader::$allBibTypes[$pub['bibtype']];

        $d_err = $this->findEmptyRequiredFields($pub, $this->getEditFields($bib_str), $this->getConditions($bib_str), $d_err);
        $d_err = $this->localFileDoesNotExist($pub, $d_err);
        $d_err = $this->citeIdDoubles($pub, $d_err);

        return $d_err;
    }

    /**
     * Makes some html out of the return array of
     * validatePublicationData().
     *
     * @param array $errors
     * @param int   $level
     *
     * @return string
     */
    private function validationErrorMessage(array $errors, int $level = 0): string
    {
        if (!is_array($errors) || (0 === count($errors))) {
            return '';
        }

        $charset = $this->configuration['charset']['upper'];

        $content = '<ul>';
        foreach ($errors as $error) {
            $errorIterator = '<li>';
            $msg = htmlspecialchars($error['msg'], ENT_QUOTES, $charset);
            $errorIterator .= $this->pi1->cObj->stdWrap(
                $msg,
                $this->conf['warn_box.']['msg.']
            );

            $list = &$error['list'];
            if (is_array($list) && (count($list) > 0)) {
                $errorIterator .= '<ul>';
                $errorIterator .= $this->validationErrorMessage($list, $level + 1);
                $errorIterator .= '</ul>';
            }

            $errorIterator .= '</li>';
            $content .= $errorIterator;
        }
        $content .= '</ul>';

        return $content;
    }

    private function generateCiteIdOnDemand(bool $generateCiteIdRequest, Reference $publicationData): string
    {
        if ($this->isNew) {
            // Generate cite id for new entries
            switch ($this->configuration['editor']['citeid_gen_new']) {
                case AutoId::AUTOID_FULL:
                    $generateCiteId = true;
                    break;
                case AutoId::AUTOID_HALF:
                    if ($generateCiteIdRequest) {
                        $generateCiteId = true;
                    }
                    break;
                default:
                    break;
            }
        } else {
            // Generate cite id for already existing (old) entries
            $auto_id = $this->configuration['editor']['citeid_gen_old'];
            if (($generateCiteIdRequest && (AutoId::AUTOID_HALF === $auto_id))
                || (0 === strlen($publicationData->getCiteid()))
            ) {
                $generateCiteId = true;
            }
        }
        if ($generateCiteId) {
            return $this->idGenerator->generateId($publicationData);
        }

        return '';
    }

    private function determineWidgetMode(): void
    {
        switch ($this->configuration['editor_mode']) {
            case Editor::EDIT_SHOW:
                $this->widgetMode = self::WIDGET_SHOW;

                return;
            case Editor::EDIT_EDIT:
            case Editor::EDIT_NEW:
                $this->widgetMode = self::WIDGET_EDIT;

                return;
            case Editor::EDIT_CONFIRM_SAVE:
            case Editor::EDIT_CONFIRM_DELETE:
            case Editor::EDIT_CONFIRM_ERASE:
                $this->widgetMode = self::WIDGET_SHOW;

                return;
            default:
                $this->widgetMode = self::WIDGET_SHOW;
        }
    }

    /**
     * @return int
     */
    private function determinEntryUid(): int
    {
        $uid = -1;
        $getPostVariables = GeneralUtility::_GP('tx_bib_pi1');
        if (array_key_exists('uid', $getPostVariables)) {
            if (is_numeric($getPostVariables['uid'])) {
                $uid = (int) $getPostVariables['uid'];
            }
        }

        return $uid;
    }

    private function lowerAuthor(array $publicationData): array
    {
        $getPostVariables = GeneralUtility::_GP('tx_bib_pi1');

        if (is_numeric($getPostVariables['action']['lower_author'])) {
            $num = (int) $getPostVariables['action']['lower_author'];
            if (($num >= 0) && ($num < (count($publicationData['authors']) - 1))) {
                $tmp = $publicationData['authors'][$num + 1];
                $publicationData['authors'][$num + 1] = $publicationData['authors'][$num];
                $publicationData['authors'][$num] = $tmp;
            }
        }

        return $publicationData;
    }

    private function raiseAuthor(array $publicationData): array
    {
        $getPostVariables = GeneralUtility::_GP('tx_bib_pi1');

        if (is_numeric($getPostVariables['action']['raise_author'])) {
            $num = (int) $getPostVariables['action']['raise_author'];
            if (($num > 0) && ($num < count($publicationData['authors']))) {
                $tmp = $publicationData['authors'][$num - 1];
                $publicationData['authors'][$num - 1] = $publicationData['authors'][$num];
                $publicationData['authors'][$num] = $tmp;
            }
        }

        return $publicationData;
    }

    private function invisibleUidAndModKeyField(array $publicationData, string $content): string
    {
        if (!$this->isNew) {
            if (isset($publicationData['mod_key'])) {
                $content .= Utility::html_hidden_input(
                    'tx_bib_pi1[DATA][pub][mod_key]',
                    htmlspecialchars($publicationData['mod_key'], ENT_QUOTES)
                );
            }
        }

        return $content;
    }

    private function appendHeaderAndTableIfThereAreRows(string $rows_vis, string $content, string $fg): string
    {
        if (strlen($rows_vis) > 0) {
            $content .= '<h3>';
            $content .= LocalizationUtility::translate(sprintf('editor_fields_%s', $fg), 'bib');
            $content .= '</h3>';

            $content .= '<table class="tx_bib-editor_fields">';
            $content .= '<tbody>';

            $content .= $rows_vis;

            $content .= '</tbody>';
            $content .= '</table>';
        }

        return $content;
    }

    /**
     * @param Reference $publicationData
     * @param $content
     *
     * @return string
     */
    private function getFieldGroups(Reference $publicationData, $content): string
    {
        $fields = $this->getEditFields($publicationData->getBibtype());
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $fieldGroups = [
            'required',
            'optional',
            'other',
            'library',
            'typo3',
        ];
        array_unshift($fields['required'], 'bibtype');

        foreach ($fieldGroups as $fieldGroup) {
            $bib_str = ReferenceReader::$allBibTypes[$publicationData->getBibtype()];

            $rows_vis = '';
            $rows_silent = '';
            $rows_hidden = '';

            $reflectionObject = new \ReflectionObject($publicationData);

            foreach ($reflectionObject->getProperties() as $ff) {
                $ff->setAccessible(true);
                // Field label
                $label = $this->fieldLabel($ff->getName(), $bib_str);

                // Adjust the widget mode on demand
                $wm = $this->getWidgetMode($ff->getName(), $this->widgetMode);

                // Field value widget
                $widget = '';
                switch ($ff->getName()) {
                    case 'citeid':
                        if (AutoId::AUTOID_FULL === (int) $this->configuration['editor']['citeid_gen_new']) {
                            $widget .= $this->getWidget($ff->getName(), $publicationData->getCiteid(), $wm);
                        } else {
                            $widget .= $this->getWidget($ff->getName(), $publicationData->getCiteid(), $wm);
                        }
                        // Add the id generation button
                        if ($this->isNew) {
                            if (AutoId::AUTOID_HALF === (int) $this->configuration['editor']['citeid_gen_new']) {
                                $widget .= $this->getCiteIdGeneratorButton();
                            }
                        } else {
                            if (AutoId::AUTOID_HALF === (int) $this->configuration['editor']['citeid_gen_old']) {
                                $widget .= $this->getCiteIdGeneratorButton();
                            }
                        }
                        break;
                    case 'year':
                        $widget .= $this->getWidget('year', $publicationData->getYear(), $wm);
                        $widget .= ' - ';
                        $widget .= $this->getWidget('month', $publicationData->getMonth(), $wm);
                        $widget .= ' - ';
                        $widget .= $this->getWidget('day', $publicationData->getDay(), $wm);
                        break;
                    case 'month':
                    case 'day':
                        break;
                    default:
                        $widget .= $this->getWidget($ff->getName(), $ff->getValue($publicationData), $wm);
                }
                if ('bibtype' === $ff->getName()) {
                    $widget .= $this->getUpdateButton();
                }

                if (strlen($widget) > 0) {
                    if (self::WIDGET_SILENT === $wm) {
                        $rows_silent .= $widget;
                    } else {
                        if (self::WIDGET_HIDDEN === $wm) {
                            $rows_hidden .= $widget;
                        } else {
                            $label = $contentObjectRenderer->stdWrap($label, $this->conf['field_labels.']);
                            $widget = $contentObjectRenderer->stdWrap($widget, $this->conf['field_widgets.']);
                            $rows_vis .= '<tr>';
                            $rows_vis .= '<th class="tx_bib-editor_'.$fieldGroup.'">'.$label.'</th>';
                            $rows_vis .= '<td class="tx_bib-editor_'.$fieldGroup.'">'.$widget.'</td>';
                            $rows_vis .= '</tr>';
                        }
                    }
                }
            }

            $content = $this->appendHeaderAndTableIfThereAreRows($rows_vis, $content, $fieldGroup);

            if (strlen($rows_silent) > 0) {
                $content .= $rows_silent;
            }

            if (strlen($rows_hidden) > 0) {
                $content .= $rows_hidden;
            }
        }

        return $content;
    }

    /**
     * @return string
     */
    private function getTitle(): string
    {
        $title = $this->LLPrefix;
        switch ($this->configuration['editor_mode']) {
            case Editor::EDIT_SHOW:
                $title .= 'title_view';
                break;
            case Editor::EDIT_EDIT:
                $title .= 'title_edit';
                break;
            case Editor::EDIT_NEW:
                $title .= 'title_new';
                break;
            case Editor::EDIT_CONFIRM_DELETE:
                $title .= 'title_confirm_delete';
                break;
            case Editor::EDIT_CONFIRM_ERASE:
                $title .= 'title_confirm_erase';
                break;
            default:
                $title .= 'title_edit';
                break;
        }
        $title = LocalizationUtility::translate($title, 'bib');

        return $title;
    }

    /**
     * @param array $pub
     * @param $d_err
     *
     * @return array
     */
    private function citeIdDoubles(array $pub, $d_err): array
    {
        $type = 'double_citeid';
        if ($this->conf['warnings.'][$type] && !$this->conf['no_edit.']['citeid'] && !$this->conf['no_show.']['citeid']) {
            if ($this->referenceReader->citeIdExists($pub['citeid'], $pub['uid'])) {
                $err = ['type' => $type];
                $err['msg'] = LocalizationUtility::translate('editor_error_id_exists', 'bib');
                $d_err[] = $err;
            }
        }

        return $d_err;
    }

    /**
     * @param $empty
     * @param $cond
     *
     * @return array
     */
    private function checkConditions($empty, $cond): array
    {
        $clear = [];
        foreach ($empty as $em) {
            $ok = false;
            foreach ($cond as $con_ored) {
                if (in_array($em, $con_ored)) {
                    // Check if at least one field is not empty
                    foreach ($con_ored as $ff) {
                        if (!in_array($ff, $empty)) {
                            $ok = true;
                            break;
                        }
                    }
                    if ($ok) {
                        break;
                    }
                }
            }
            if ($ok) {
                $clear[] = $em;
            }
        }

        return $clear;
    }

    /**
     * @param array $pub
     * @param $fields
     * @param $cond
     * @param $d_err
     *
     * @return array
     */
    private function findEmptyRequiredFields(array $pub, $fields, $cond, $d_err): array
    {
        $type = 'empty_fields';
        if ($this->conf['warnings.'][$type]) {
            $empty = [];
            // Find empty fields
            foreach ($fields['required'] as $ff) {
                if (!$this->conf['no_edit.'][$ff] && !$this->conf['no_show.'][$ff]) {
                    switch ($ff) {
                        case 'authors':
                            if (!is_array($pub[$ff]) || (0 === count($pub[$ff]))) {
                                $empty[] = $ff;
                            }
                            break;
                        default:
                            if (0 === strlen(trim($pub[$ff]))) {
                                $empty[] = $ff;
                            }
                    }
                }
            }

            $clear = $this->checkConditions($empty, $cond);
            $empty = array_diff($empty, $clear);

            if (count($empty)) {
                $err = ['type' => $type];
                $err['msg'] = LocalizationUtility::translate('editor_error_empty_fields', 'bib');
                $err['list'] = [];
                $bib_str = ReferenceReader::$allBibTypes[$pub['bibtype']];
                foreach ($empty as $field) {
                    switch ($field) {
                        case 'authors':
                            $str = $this->fieldLabel($field, $bib_str);
                            break;
                        default:
                            $str = $this->fieldLabel($field, $bib_str);
                    }
                    $err['list'][] = ['msg' => $str];
                }
                $d_err[] = $err;
            }
        }

        return $d_err;
    }

    /**
     * @param $bib_str
     *
     * @return array
     */
    private function getConditions(string $bib_str): array
    {
        $cond = [];
        $parts = GeneralUtility::trimExplode(',', $this->conf['groups.'][$bib_str.'.']['required']);
        foreach ($parts as $part) {
            if (!(false === strpos($part, '|'))) {
                $cond[] = GeneralUtility::trimExplode('|', $part);
            }
        }

        return $cond;
    }
}
