<?php

defined('TYPO3_MODE') || die();

call_user_func(function () {
    /**
     * Temporary variables.
     */
    $extensionKey = 'bib';

    /*
     * Default TypoScript for Tmpladw
     */
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        $extensionKey,
        'Configuration/TypoScript/default',
        'Bib'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        $extensionKey,
        'setup',
        'tt_content.CSS_editor.ch.tx_bib_pi1 = < plugin.tx_bib_pi1.CSS_editor',
        43
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        $extensionKey,
        'setup',
        '
        tt_content.shortcut.20.0.conf.tx_bib_domain_model_reference = < plugin.tx_bib_pi1
        tt_content.shortcut.20.0.conf.tx_bib_domain_model_reference.CMD = singleView
    ',
        43
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptConstants(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:bib/Configuration/TypoScript/default/constants.typoscript">'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'bib',
        'Configuration/TypoScript/default',
        'Publication list defaults'
    );
});
