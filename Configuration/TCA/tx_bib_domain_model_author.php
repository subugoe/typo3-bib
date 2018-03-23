
<?php
defined('TYPO3_MODE') or die();

return [
    'ctrl' => [
            'title' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_author',
            'label' => 'surname',
            'label_alt' => 'forename',
            'label_alt_force' => 1,
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'cruser_id' => 'cruser_id',
            'default_sortby' => 'ORDER BY surname',
            'delete' => 'deleted',
            'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('bib').'Resources/Public/Icons/icon_tx_bib_domain_model_reference.png',
        ],
    'interface' => [
        'showRecordFieldList' => 'surname,forename,url',
    ],
    'fe_admin_fieldList' => 'surname,forename,url',
    'columns' => [
        'surname' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_author_surname',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '255',
                'eval' => 'trim,required',
            ],
        ],
        'forename' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_author_forename',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '255',
                'eval' => 'trim',
            ],
        ],
        'url' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_author_url',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '255',
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
        'fe_user_id' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_author_fe_user_id',
            'config' => [
                'type' => 'group',
                'size' => 1,
                'internal_type' => 'db',
                'allowed' => 'fe_users',
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'surname,forename,url,fe_user_id'],
    ],
];
