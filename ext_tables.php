<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$TCA['tx_bib_domain_model_reference'] = [
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
        'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/Tca/References.php',
        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/icon_tx_bib_domain_model_reference.png',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'hidden,bibtype,citeid,title,journal,year,month,day,volume,number,number2,pages,abstract,affiliation,note,annotation,keywords,tags,file_url,web_url,web_url_date,misc, editor,publisher,address,howpublished,series,edition,chapter,booktitle,school,institute,organization,institution,event_name,event_place,event_date,state,type,ISBN,ISSN,DOI,extern,reviewed,in_library,borrowed_by',
    ]
];


$TCA['tx_bib_domain_model_author'] = [
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
        'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/Tca/Authors.php',
        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/icon_tx_bib_domain_model_reference.png',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'surname,forename,url',
    ]
];


$TCA['tx_bib_domain_model_authorships'] = [
    'ctrl' => [
        'title' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_authorships',
        'label' => 'pub_id',
        'label_userFunc' => "Ipf\\Bib\\Utility\\LabelUtility->getAuthorshipLabel",
        'label_alt_force' => 1,
        'default_sortby' => 'ORDER BY pub_id DESC, sorting ASC',
        'delete' => 'deleted',
        'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/Tca/Authorships.php',
        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/icon_tx_bib_domain_model_reference.png',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'pub_id,author_id,sorting',
    ]
];


// Allow items on standard pages
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_bib_domain_model_reference');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_bib_domain_model_author');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_bib_domain_model_authorships');

// Plugin 1: Publication List
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pi1'] = 'layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_pi1'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tt_content.list_type_pi1',
        $_EXTKEY . '_pi1'
    ],
    'list_type'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $_EXTKEY . '_pi1',
    'FILE:EXT:bib/Configuration/FlexForms/flexform_ds.xml'
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    $_EXTKEY,
    'Configuration/TypoScript/default',
    'Publication list defaults'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Ipf.' . $_EXTKEY,
    'rest',
    'Bib REST Service'
);
