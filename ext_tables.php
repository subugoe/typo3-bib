<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

// Allow items on standard pages
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_bib_domain_model_reference');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_bib_domain_model_author');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_bib_domain_model_authorships');
