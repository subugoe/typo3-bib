<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');

class Tx_Bib_Utility_PRegExpTanslator {

	var $pat;
	var $rep;

	function Tx_Bib_Utility_PRegExpTanslator () {
		$this->clear ( );
	}


	function clear () {
		$this->pat = array();
		$this->rep = array();
	}


	function push($pat, $rep) {
		$this->pat[] = $pat;
		$this->rep[] = $rep;
	}


	function translate($str) {
		return preg_replace( $this->pat, $this->rep, $str);
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/Classes/Utility/Tx_Bib_Utility_PRegExpTanslator.php"]) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/Classes/Utility/Tx_Bib_Utility_PRegExpTanslator.php"]);
}

?>