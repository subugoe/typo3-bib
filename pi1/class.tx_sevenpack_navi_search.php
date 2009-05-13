<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');


require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/pi1/class.tx_sevenpack_navi.php') );


class tx_sevenpack_navi_search extends tx_sevenpack_navi  {

	/*
	 * Intialize
	 */
	function initialize ( $pi1 ) {
		parent::initialize( $pi1 );
		if ( is_array ( $pi1->conf['searchNav.'] ) )
			$this->conf =& $pi1->conf['searchNav.'];

		$this->extConf = array();
		if ( is_array ( $pi1->extConf['search_navi'] ) )
			$this->extConf =& $pi1->extConf['search_navi'];

		$this->pref = 'SEARCH_NAVI';
		$this->load_template ( '###SEARCH_NAVI_BLOCK###' );
	}


	/*
	 * Returns content
	 */
	function get ( ) {
		$cObj =& $this->pi1->cObj;
		$con = '';

		$cfg =& $this->conf;

		// The data

		// The label
		$label = $this->pi1->get_ll ( 'searchNav_label' );
		$label = $cObj->stdWrap ( $label, $cfg['label.'] );

		$attribs = array ( 'search' => '' );
		if ( $this->pi1->extConf['show_nav_author'] ) {
			$attribs['author'] = '';
			$attribs['author_letter'] = '';
		}
		$sea = '';
		$sea .= '<form name="'.$this->pi1->prefix_pi1.'-search_form" ';
		$sea .= 'action="'.$this->pi1->get_link_url ( $attribs, FALSE ).'"';
		$sea .= ' method="post"';
		$sea .= strlen ( $cfg['form_class'] ) ? ' class="'.$cfg['form_class'].'"' : '';
		$sea .= '>' . "\n";

		// The text input
		$attribs = array ( 'size' => 42, 'maxlength' => 1024 );
		$value = '';
		if ( strlen ( $this->extConf['string'] ) > 0 )
			$value = htmlspecialchars ( $this->extConf['string'], ENT_QUOTES, 'UTF-8' );
		$sea .= tx_sevenpack_utility::html_text_input (
			$this->pi1->prefix_pi1.'[search][text]', $value, $attribs
		);

		// The search button
		$attribs = array ();
		if ( strlen ( $cfg['search_btn_class'] ) > 0 )
			$attribs['class'] =  $cfg['search_btn_class'];
		$btn = tx_sevenpack_utility::html_submit_input ( 
			$this->pi1->prefix_pi1.'[action][search]',
			$this->pi1->get_ll ( 'button_search' ), $attribs );
		$btn = $cObj->stdWrap ( $btn, $cfg['search_btn.'] );
		$sea .= $btn;

		// The clear button
		$attribs = array ();
		if ( strlen ( $cfg['clear_btn_class'] ) > 0 )
			$attribs['class'] =  $cfg['clear_btn_class'];
		$btn = tx_sevenpack_utility::html_submit_input ( 
			$this->pi1->prefix_pi1.'[action][clear_search]',
			$this->pi1->get_ll ( 'button_clear' ), $attribs );
		$btn = $cObj->stdWrap ( $btn, $cfg['clear_btn.'] );
		$sea .= $btn;

		// End of form
		$sea .= '</form>';


		$trans = array();
		$trans['###NAVI_LABEL###'] = $label;
		$trans['###SEARCH###'] = $sea;

		$tmpl = $this->pi1->enum_condition_block ( $this->template );
		$con = $cObj->substituteMarkerArrayCached ( $tmpl, $trans );

		return $con;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi_search.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi_search.php']);
}

?>
