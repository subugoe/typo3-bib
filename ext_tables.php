<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

require_once(t3lib_extMgm::extPath($_EXTKEY).'res/class.tx_bib_labels.php');

$TCA['tx_bib_references'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:bib/locallang_db.xml:tx_bib_references',
		'label'     => 'citeid',
		'label_alt' => 'title,bibtype',
		'label_alt_force'   => 1,
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
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_bib_references.png',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden,bibtype,citeid,title,journal,year,month,day,volume,number,number2,pages,abstract,affiliation,note,annotation,keywords,tags,file_url,web_url,misc, editor,publisher,address,howpublished,series,edition,chapter,booktitle,school,institute,organization,institution,event_name,event_place,event_date,state,type,ISBN,ISSN,DOI,extern,reviewed,in_library,borrowed_by',
	)
);


$TCA['tx_bib_authors'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:bib/locallang_db.xml:tx_bib_authors',
		'label'     => 'surname',
		'label_alt' => 'forename',
    'label_alt_force'   => 1,
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY surname',	
		'delete' => 'deleted',	
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_bib_references.png',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'surname,forename,url',
	)
);


$TCA['tx_bib_authorships'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:bib/locallang_db.xml:tx_bib_authorships',
		'label'     => 'pub_id',
		'label_userFunc'    => "tx_bib_labels->get_authorship_label",
		'label_alt_force'   => 1,
		'default_sortby' => 'ORDER BY pub_id DESC, sorting ASC',	
		'delete' => 'deleted',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_bib_references.png',
		#'hideTable' => true
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'pub_id,author_id,sorting',
	)
);


// Allow items on standard pages
t3lib_extMgm::allowTableOnStandardPages('tx_bib_references');
t3lib_extMgm::allowTableOnStandardPages('tx_bib_authors');
t3lib_extMgm::allowTableOnStandardPages('tx_bib_authorships');


t3lib_div::loadTCA('tt_content');

// Plugin 1: Publication List
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';

t3lib_extMgm::addPlugin(array('LLL:EXT:bib/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:bib/pi1/flexform_ds.xml');

t3lib_extMgm::addStaticFile($_EXTKEY, 'pi1/static/default', 'Publication list defaults');
t3lib_extMgm::addStaticFile($_EXTKEY, 'pi1/static/default_style', 'Publication list CSS');


?>