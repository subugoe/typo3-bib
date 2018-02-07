<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

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
        $_EXTKEY . '_pi1',
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
