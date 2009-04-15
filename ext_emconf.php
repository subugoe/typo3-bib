<?php

########################################################################
# Extension Manager/Repository config file for ext: "sevenpack"
#
# Auto generated 15-04-2009 11:37
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Sevenpack bibliography manager',
	'description' => 'A customizable bibliography and publication reference manager with a convenient frontend editor and import/export functionality.',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '0.5.5',
	'dependencies' => 'ajaxgroupsearch',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Sebastian Holtermann',
	'author_email' => 'sebholt@web.de',
	'author_company' => '',
	'CGLcompliance' => 'XHTML',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'ajaxgroupsearch' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:47:{s:9:"ChangeLog";s:4:"9d65";s:10:"README.txt";s:4:"9c1b";s:20:"class.ext_update.php";s:4:"a55a";s:12:"ext_icon.gif";s:4:"4c13";s:17:"ext_localconf.php";s:4:"18d1";s:14:"ext_tables.php";s:4:"9f16";s:14:"ext_tables.sql";s:4:"6777";s:32:"icon_tx_sevenpack_references.png";s:4:"4db6";s:32:"icon_tx_sevenpack_references.xcf";s:4:"dbdb";s:13:"locallang.xml";s:4:"761b";s:16:"locallang_db.xml";s:4:"827f";s:7:"tca.php";s:4:"80b4";s:14:"pi1/ce_wiz.gif";s:4:"ba3a";s:14:"pi1/ce_wiz.png";s:4:"64e2";s:14:"pi1/ce_wiz.xcf";s:4:"7c4e";s:43:"pi1/class.tx_sevenpack_citeid_generator.php";s:4:"b965";s:35:"pi1/class.tx_sevenpack_exporter.php";s:4:"b529";s:42:"pi1/class.tx_sevenpack_exporter_bibtex.php";s:4:"8b93";s:39:"pi1/class.tx_sevenpack_exporter_xml.php";s:4:"566d";s:35:"pi1/class.tx_sevenpack_importer.php";s:4:"bfea";s:42:"pi1/class.tx_sevenpack_importer_bibtex.php";s:4:"473d";s:39:"pi1/class.tx_sevenpack_importer_xml.php";s:4:"c0bb";s:30:"pi1/class.tx_sevenpack_pi1.php";s:4:"97dc";s:38:"pi1/class.tx_sevenpack_pi1_wizicon.php";s:4:"11e1";s:38:"pi1/class.tx_sevenpack_single_view.php";s:4:"5354";s:13:"pi1/clear.gif";s:4:"cc11";s:19:"pi1/flexform_ds.xml";s:4:"f9be";s:17:"pi1/locallang.xml";s:4:"2ac0";s:24:"pi1/locallang_editor.xml";s:4:"a50f";s:22:"pi1/locallang_flex.xml";s:4:"1dc8";s:32:"pi1/static/default/constants.txt";s:4:"d437";s:32:"pi1/static/default/editorcfg.txt";s:4:"4308";s:28:"pi1/static/default/setup.txt";s:4:"6334";s:34:"pi1/static/default_style/setup.txt";s:4:"52a1";s:47:"res/class.tx_sevenpack_citeid_generator_ext.php";s:4:"7bb4";s:33:"res/class.tx_sevenpack_labels.php";s:4:"9fb4";s:45:"res/class.tx_sevenpack_pregexp_translator.php";s:4:"7b09";s:45:"res/class.tx_sevenpack_reference_accessor.php";s:4:"df25";s:31:"res/class.tx_sevenpack_tmpl.php";s:4:"0c6c";s:34:"res/class.tx_sevenpack_utility.php";s:4:"cfb7";s:28:"res/sixpack_to_sevenpack.php";s:4:"1f8f";s:14:"res/table.tmpl";s:4:"a21a";s:24:"res/template_filter.html";s:4:"657a";s:24:"res/template_search.html";s:4:"6ff3";s:14:"doc/manual.pdf";s:4:"9dcd";s:14:"doc/manual.sxw";s:4:"fe7a";s:16:"doc/syntax.xhtml";s:4:"058a";}',
	'suggests' => array(
	),
);

?>