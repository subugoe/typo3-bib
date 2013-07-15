<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_bib_domain_model_reference=1
');

// Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript(
	$_EXTKEY,
	'editorcfg',
	'
	tt_content.CSS_editor.ch.tx_bib_pi1 = < plugin.tx_bib_pi1.CSS_editor
',
	43
);

t3lib_extMgm::addPItoST43(
	$_EXTKEY,
	'pi1/class.tx_bib_pi1.php',
	'_pi1',
	'list_type',
	1
);

t3lib_extMgm::addTypoScript(
	$_EXTKEY,
	'setup',
		'
	tt_content.shortcut.20.0.conf.tx_bib_domain_model_reference = < plugin.' . t3lib_extMgm::getCN($_EXTKEY) . '_pi1
	tt_content.shortcut.20.0.conf.tx_bib_domain_model_reference.CMD = singleView
'
	,43
);

?>
