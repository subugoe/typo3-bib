<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
	options.saveDocNew.tx_bib_domain_model_reference=1
');

// Extending TypoScript from static template uid=43 to set up userdefined tag:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
    $_EXTKEY,
    'pi1/class.tx_bib_pi1.php',
    '_pi1',
    'list_type',
    1
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Ipf.'.$_EXTKEY,
    'rest',
    [
        'Rest' => 'list',
    ]
);

// provide automagic realUrl configuration
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/realurl/class.tx_realurl_autoconfgen.php']['extensionConfiguration']['bib'] = 'EXT:bib/Classes/Hooks/RealUrl.php:Ipf\\Bib\Hooks\RealUrl->addRealUrlConfiguration';
