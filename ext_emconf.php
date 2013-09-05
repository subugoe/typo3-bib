<?php

########################################################################
# Extension Manager/Repository config file for ext "bib".
#
# Auto generated 06-10-2012 17:19
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Bib - bibliography manager',
	'description' => 'A customizable bibliography and publication reference manager with a convenient frontend editor and import/export functionality.',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '1.0.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'Ingo Pfennigstorf, initially developed by Sebastian Holtermann,',
	'author_email' => 'i.pfennigstorf@gmail.com',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.0.0-6.1.99',
			't3jquery' => ''
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:63:{s:9:"ChangeLog";s:4:"3de2";s:20:"class.ext_update.php";s:4:"a55a";s:12:"ext_icon.gif";s:4:"4c13";s:17:"ext_localconf.php";s:4:"519f";s:14:"ext_tables.php";s:4:"bbcf";s:14:"ext_tables.sql";s:4:"11e6";s:32:"icon_tx_bib_references.png";s:4:"4db6";s:13:"locallang.xml";s:4:"761b";s:16:"locallang_db.xml";s:4:"32a7";s:10:"README.txt";s:4:"9c1b";s:7:"tca.php";s:4:"cf71";s:14:"doc/manual.pdf";s:4:"17bf";s:14:"doc/manual.sxw";s:4:"e9a8";s:16:"doc/syntax.xhtml";s:4:"058a";s:14:"pi1/ce_wiz.gif";s:4:"ba3a";s:14:"pi1/ce_wiz.png";s:4:"64e2";s:14:"pi1/ce_wiz.xcf";s:4:"7c4e";s:43:"pi1/class.Tx_Bib_Utility_Generator_CiteIdGenerator.php";s:4:"fa3c";s:38:"pi1/class.tx_bib_editor_view.php";s:4:"efc6";s:35:"pi1/class.Tx_Bib_Utility_Exporter_Exporter.php";s:4:"c3da";s:42:"pi1/class.Tx_Bib_Utility_Exporter_BibTexExporter.php";s:4:"3eb1";s:39:"pi1/class.Tx_Bib_Utility_Exporter_XmlExporter.php";s:4:"e3a3";s:35:"pi1/class.Tx_Bib_Utility_ImporterBibImporter.php";s:4:"1b5e";s:42:"pi1/class.Tx_Bib_Utility_Importer_BibTexImporter.php";s:4:"f2a2";s:39:"pi1/class.Tx_Bib_Utility_Importer_XmlImporter.php";s:4:"b8c2";s:31:"pi1/class.tx_bib_navi.php";s:4:"983e";s:38:"pi1/class.tx_bib_navi_author.php";s:4:"564d";s:36:"pi1/class.tx_bib_navi_page.php";s:4:"bfbe";s:36:"pi1/class.tx_bib_navi_pref.php";s:4:"727d";s:38:"pi1/class.tx_bib_navi_search.php";s:4:"96b9";s:36:"pi1/class.tx_bib_navi_stat.php";s:4:"05ba";s:36:"pi1/class.tx_bib_navi_year.php";s:4:"c7e4";s:30:"pi1/class.tx_bib_pi1.php";s:4:"db3f";s:38:"pi1/class.tx_bib_pi1_wizicon.php";s:4:"a4da";s:38:"pi1/class.tx_bib_single_view.php";s:4:"41fc";s:13:"pi1/clear.gif";s:4:"cc11";s:19:"pi1/flexform_ds.xml";s:4:"9cd5";s:17:"pi1/locallang.xml";s:4:"feb1";s:24:"pi1/locallang_editor.xml";s:4:"902f";s:22:"pi1/locallang_flex.xml";s:4:"aa98";s:32:"pi1/static/default/constants.txt";s:4:"7d6c";s:32:"pi1/static/default/editorcfg.txt";s:4:"4308";s:28:"pi1/static/default/setup.txt";s:4:"20c4";s:34:"pi1/static/default_style/setup.txt";s:4:"33cd";s:47:"res/class.Tx_Bib_Utility_Generator_CiteIdGenerator_ext.php";s:4:"7bb4";s:37:"res/class.Tx_Bib_Utility_DbUtility.php";s:4:"18b3";s:33:"res/class.Tx_Bib_Utility_Labels.php";s:4:"ee7e";s:45:"res/class.tx_bib_pregexp_translator.php";s:4:"7b09";s:43:"res/class.tx_bib_reference_reader.php";s:4:"e7a0";s:43:"res/class.Tx_Bib_Utility_ReferenceWriter.php";s:4:"cc99";s:34:"res/class.tx_bib_utility.php";s:4:"d792";s:28:"res/sixpack_to_bib.php";s:4:"1f8f";s:30:"res/templates/list_blocks.html";s:4:"154c";s:29:"res/templates/list_items.html";s:4:"f693";s:23:"res/templates/main.html";s:4:"52d9";s:30:"res/templates/navi_author.html";s:4:"9eb0";s:28:"res/templates/navi_misc.html";s:4:"15d4";s:28:"res/templates/navi_page.html";s:4:"815d";s:28:"res/templates/navi_pref.html";s:4:"0053";s:30:"res/templates/navi_search.html";s:4:"f0c7";s:28:"res/templates/navi_stat.html";s:4:"ba8c";s:28:"res/templates/navi_year.html";s:4:"9d05";s:30:"res/templates/single_view.html";s:4:"0e01";}',
);

?>