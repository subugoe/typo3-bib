<?php

class Tx_Bib_Utility_PRegExpTranslator {

	var $pat;
	var $rep;

	function Tx_Bib_Utility_PRegExpTranslator () {
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

?>