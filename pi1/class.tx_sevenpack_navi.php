<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');

class tx_sevenpack_navi {

	public $pi1;
	public $cObj;
	public $template;


	function initialize ( $pi1 ) {
		$this->pi1 =& $pi1;
		$this->conf =& $pi1->conf['authorNav.'];
	}


	function load_template ( $subpart ) {
		$cObj =& $this->pi1->cObj;
		$tmpl = '<p>ERROR: The html template file ' . $file . 
			' is not readable or empty</p>';

		$file = strval ( $this->conf['template'] );
		if ( strlen ( $file ) > 0 ) {
			$file = $cObj->fileResource ( $file );
			if ( strlen ( $file ) > 0 ) {
				$tmpl = $cObj->getSubpart ( $file, $subpart );
			}
		}
		$this->template = $tmpl;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi.php']);
}

?>
