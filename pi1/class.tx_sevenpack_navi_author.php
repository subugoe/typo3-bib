<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');


require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/pi1/class.tx_sevenpack_navi.php') );

class tx_sevenpack_navi_author extends tx_sevenpack_navi  {


	function initialize ( $pi1 ) {
		parent::initialize( $pi1 );
		if( is_array ( $pi1->conf['authorNav.'] ) )
			$this->conf =& $pi1->conf['authorNav.'];

		$this->pref = 'AUTHOR_NAVI';
		$this->load_template ( '###AUTHOR_NAVI_BLOCK###' );
	}


	function get ( ) {
		$cObj =& $this->pi1->cObj;
		$con = '';

		$cfg =& $this->conf;
		$cfgSel = is_array ( $cfg['selection.'] ) ? $cfg['selection.'] : array();

		// Translator
		$trans = array();

		$tmpl = $this->pi1->enum_condition_block ( $this->template );
		$con = $cObj->substituteMarkerArrayCached ( $tmpl, $trans );

		return $con;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi_author.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi_author.php']);
}

?>
