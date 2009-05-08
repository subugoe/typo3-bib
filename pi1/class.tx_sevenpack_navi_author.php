<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');


require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/pi1/class.tx_sevenpack_navi.php') );

class tx_sevenpack_navi_author extends tx_sevenpack_navi  {


	function initialize ( $pi1 ) {
		parent::initialize( $pi1 );
		$this->load_template ( '###AUTHOR_NAVI_BLOCK###' );
	}


	function get ( ) {
		$con = '';
		$cObj =& $this->pi1->cObj;

		$cfg = array();
		$cfgSel = array();
		if ( is_array ( $this->conf['authorNav.'] ) ) {
			$cfg =& $this->conf['authorNav.'];
			if ( is_array ( $cfg['selection.'] ) )
				$cfgSel =& $cfg['selection.'];
		}

		// Treat the template
		$trans = array();

		$tmpl = $this->pi1->enum_condition_block ( $this->template );
		$con = $cObj->substituteMarkerArrayCached ( $tmpl, $trans );


		if ( $cfg['top_disable'] != 1 ) {
			$naviTop = $cObj->stdWrap ( $naviStr, $cfg['top.'] );
			$this->extConf['has_top_navi'] = TRUE;
		}
		if ( $cfg['bottom_disable'] != 1 ) {
			$naviBottom = $cObj->stdWrap ( $naviStr, $cfg['bottom.'] );
		}

		return $con;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi_author.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi_author.php']);
}

?>
