<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tt_content.list_type_pi1',
        'bib_pi1',
    ],
    'list_type',
    'bib'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'bib_pi1',
    'FILE:EXT:bib/Configuration/FlexForms/flexform_ds.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Ipf.bib',
    'rest',
    'Bib REST Service'
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['bib_pi1'] = 'layout,select_key';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['bib_pi1'] = 'pi_flexform';
