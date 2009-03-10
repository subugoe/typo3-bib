<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
$TCA['tx_sevenpack_references'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references',
		'label'     => 'citeid',
		'label_alt' => 'title,bibtype',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby'    => 'sorting',
		'default_sortby' => 'ORDER BY year DESC',
		'delete' => 'deleted',	
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_sevenpack_references.png',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden, bibtype, citeid, title, journal, year, volume, number, pages, day, month, abstract, affiliation, note, annotation, keywords, file_url, misc, editor, publisher, series, address, edition, chapter, howpublished, booktitle, organization, school, institution, state, type, ISBN, extern, reviewed, in_library, borrowed_by',
	)
);


$TCA['tx_sevenpack_authors'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_authors',		
		'label'     => 'surname',
		'label_alt' => 'forename',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY surname',	
		'delete' => 'deleted',	
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_sevenpack_references.png',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'surname, forename',
	)
);


$TCA['tx_sevenpack_authorships'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_authorships',
		'label'     => 'pub_id',
		'default_sortby' => 'ORDER BY pub_id DESC, sorting ASC',	
		'delete' => 'deleted',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_sevenpack_references.png',
		#'hideTable' => true
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'pub_id, author_id, sorting',
	)
);



t3lib_div::loadTCA('tt_content');

// Plugin 1: Publication List
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';

t3lib_extMgm::addPlugin(array('LLL:EXT:sevenpack/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:sevenpack/pi1/flexform_ds.xml');

t3lib_extMgm::addStaticFile($_EXTKEY, 'pi1/static/default', 'Publication list defaults');
t3lib_extMgm::addStaticFile($_EXTKEY, 'pi1/static/default_style', 'Publication list CSS');

if (TYPO3_MODE=='BE')	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_sevenpack_pi1_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_sevenpack_pi1_wizicon.php';


?>
