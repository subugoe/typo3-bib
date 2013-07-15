<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:bib/pi1/class.tx_bib_citeid_generator.php' ) );

class tx_bib_citeid_generator_ext extends tx_bib_citeid_generator {

	function generateBasicId ( $row ) {
		$authors = $row['authors'];
		return $this->simplified_string ( $authors[0]['sn'] );
	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/res/class.tx_bib_citeid_generator_ext.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/res/class.tx_bib_citeid_generator_ext.php"]);
}

?>
