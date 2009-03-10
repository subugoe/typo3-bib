<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');

class tx_sevenpack_PRegExp_Translator {

	var $pat;
	var $rep;


	function tx_sevenpack_PRegExp_Translator ( ) {
		$this->clear ( );
	}


	function clear ( ) {
		$this->pat = array();
		$this->rep = array();
	}


	function push ( $pat, $rep ) {
		$this->pat[] = $pat;
		$this->rep[] = $rep;
	}


	function translate ( $str ) {
		return preg_replace ( $this->pat, $this->rep, $str );
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/res/class.tx_sevenpack_pregexp_translator.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/res/class.tx_sevenpack_pregexp_translator.php"]);
}

?>