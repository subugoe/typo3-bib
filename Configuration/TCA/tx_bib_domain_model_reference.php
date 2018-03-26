
<?php
defined('TYPO3_MODE') or die();

return [
    'ctrl' => [
            'title' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference',
            'label' => 'citeid',
            'label_alt' => 'title,bibtype',
            'label_alt_force' => 1,
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'cruser_id' => 'cruser_id',
            'sortby' => 'sorting',
            'default_sortby' => 'ORDER BY year DESC',
            'delete' => 'deleted',
            'enablecolumns' => [
                'disabled' => 'hidden',
            ],
            'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('bib').'Resources/Public/Icons/icon_tx_bib_domain_model_reference.png',
        ],
    'interface' => [
        'showRecordFieldList' => 'hidden,bibtype,citeid,title,journal,year,month,day,volume,number,number2,pages,abstract,affiliation,note,annotation,keywords,tags,file_url,web_url,web_url_date,misc, editor,publisher,address,howpublished,series,edition,chapter,booktitle,school,institute,organization,institution,event_name,event_place,event_date,state,type,ISBN,ISSN,DOI,extern,reviewed,in_library,borrowed_by',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'hidden,bibtype,citeid,title,journal,year,month,day,volume,number,number2,pages,abstract,affiliation,note,annotation,keywords,tags,file_url,web_url,web_url_date,misc, editor,publisher,address,howpublished,series,edition,chapter,booktitle,school,institute,organization,institution,event_name,event_place,event_date,state,type,ISBN,ISSN,DOI,extern,reviewed,in_library,borrowed_by',
    ],    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'bibtype' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_0',
                        '0',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_1',
                        '1',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_2',
                        '2',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_3',
                        '3',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_4',
                        '4',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_5',
                        '5',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_6',
                        '6',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_7',
                        '7',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_8',
                        '8',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_9',
                        '9',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_10',
                        '10',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_11',
                        '11',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_12',
                        '12',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_13',
                        '13',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_14',
                        '14',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_15',
                        '15',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_16',
                        '16',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_17',
                        '17',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_18',
                        '18',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_19',
                        '19',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_20',
                        '20',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_bibtype_I_21',
                        '21',
                    ],
                ],
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'citeid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_citeid',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'nospace,uniqueInPid',
            ],
        ],
        'title' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_title',
            'config' => [
                'type' => 'text',
                'cols' => 48,
                'rows' => 3,
            ],
        ],
        'journal' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_journal',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'year' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_year',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int',
            ],
        ],
        'volume' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_volume',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'number' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_number',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'number2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_number2',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'pages' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_pages',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'day' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_day',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['-', '0'],
                    ['1', '1'],
                    ['2', '2'],
                    ['3', '3'],
                    ['4', '4'],
                    ['5', '5'],
                    ['6', '6'],
                    ['7', '7'],
                    ['8', '8'],
                    ['9', '9'],
                    ['10', '10'],
                    ['11', '11'],
                    ['12', '12'],
                    ['13', '13'],
                    ['14', '14'],
                    ['15', '15'],
                    ['16', '16'],
                    ['17', '17'],
                    ['18', '18'],
                    ['19', '19'],
                    ['20', '20'],
                    ['21', '21'],
                    ['22', '22'],
                    ['23', '23'],
                    ['24', '24'],
                    ['25', '25'],
                    ['26', '26'],
                    ['27', '27'],
                    ['28', '28'],
                    ['29', '29'],
                    ['30', '30'],
                    ['31', '31'],
                ],
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'month' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_month',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['-', '0'],
                    ['1', '1'],
                    ['2', '2'],
                    ['3', '3'],
                    ['4', '4'],
                    ['5', '5'],
                    ['6', '6'],
                    ['7', '7'],
                    ['8', '8'],
                    ['9', '9'],
                    ['10', '10'],
                    ['11', '11'],
                    ['12', '12'],
                ],
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'abstract' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_abstract',
            'config' => [
                'type' => 'text',
                'cols' => 48,
                'rows' => 10,
            ],
        ],
        'affiliation' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_affiliation',
            'config' => [
                'type' => 'text',
                'cols' => 48,
                'rows' => 2,
                'eval' => 'trim',
            ],
        ],
        'note' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_note',
            'config' => [
                'type' => 'text',
                'cols' => 48,
                'rows' => 5,
            ],
        ],
        'annotation' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_annotation',
            'config' => [
                'type' => 'text',
                'cols' => 48,
                'rows' => 5,
            ],
        ],
        'keywords' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_keywords',
            'config' => [
                'type' => 'text',
                'cols' => 48,
                'rows' => 2,
            ],
        ],
        'tags' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_tags',
            'config' => [
                'type' => 'text',
                'cols' => 48,
                'rows' => 2,
            ],
        ],
        'file_url' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_file_url',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'checkbox' => '0',
                'eval' => 'trim',
                'wizards' => [
                    '_PADDING' => 2,
                    'link' => [
                        'type' => 'popup',
                        'title' => 'Link',
                        'icon' => 'link_popup.gif',
                        'module' => [
                            'name' => 'wizard_element_browser',
                            'urlParameters' => [
                                'mode' => 'wizard',
                            ],
                        ],
                        'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
                    ],
                ],
            ],
        ],
        'web_url' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_web_url',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'checkbox' => '0',
                'wizards' => [
                    '_PADDING' => 2,
                    'link' => [
                        'type' => 'popup',
                        'title' => 'Link',
                        'icon' => 'link_popup.gif',
                        'module' => [
                            'name' => 'wizard_element_browser',
                            'urlParameters' => [
                                'mode' => 'wizard',
                            ],
                        ],
                        'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
                    ],
                ],
            ],
        ],
        'web_url_date' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_web_url_date',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'trim',
            ],
        ],
        'web_url2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_web_url2',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'checkbox' => '0',
                'wizards' => [
                    '_PADDING' => 2,
                    'link' => [
                        'type' => 'popup',
                        'title' => 'Link',
                        'icon' => 'link_popup.gif',
                        'module' => [
                            'name' => 'wizard_element_browser',
                            'urlParameters' => [
                                'mode' => 'wizard',
                            ],
                        ],
                        'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
                    ],
                ],
            ],
        ],
        'web_url2_date' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_web_url2_date',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'trim',
            ],
        ],
        'misc' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_misc',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'misc2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_misc2',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'editor' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_editor',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'publisher' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_publisher',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'address' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_address',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'howpublished' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_howpublished',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'series' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_series',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'edition' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_edition',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'chapter' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_chapter',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'booktitle' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_booktitle',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '2',
                'eval' => 'trim',
            ],
        ],
        'school' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_school',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'institute' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_institute',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'organization' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_organization',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'institution' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_institution',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'event_name' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_event_name',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'event_place' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_event_place',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'event_date' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_event_date',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'state' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_state',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_state_I_0',
                        '0',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_state_I_1',
                        '1',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_state_I_2',
                        '2',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_state_I_3',
                        '3',
                    ],
                    [
                        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_state_I_4',
                        '4',
                    ],
                ],
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'type' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_type',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'language' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_language',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'ISBN' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_ISBN',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'ISSN' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_ISSN',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'DOI' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_DOI',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'extern' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_extern',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'reviewed' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_reviewed',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'in_library' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_in_library',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'borrowed_by' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_reference_borrowed_by',
            'config' => [
                'type' => 'input',
                'size' => 48,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'hidden;;1, bibtype, citeid, title;;;;2-2-2, journal;;;;3-3-3, year, month, day, volume, number, number2, pages, abstract, affiliation, note, annotation, keywords, tags, file_url, web_url, web_url_date, web_url2, web_url2_date, misc, misc2, editor, publisher, address, howpublished, series,  edition, chapter, booktitle, school, institute, organization, institution, event_name, event_place, event_date, state, type, language, ISBN, DOI, ISSN, extern, reviewed, in_library, borrowed_by'],
    ],
];
