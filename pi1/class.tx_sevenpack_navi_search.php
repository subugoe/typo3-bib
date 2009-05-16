<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');


require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/pi1/class.tx_sevenpack_navi.php') );


class tx_sevenpack_navi_search extends tx_sevenpack_navi  {

	public $hidden_input;

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


		$this->hidden_input = array();
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
		$extConf =& $this->extConf;

		// The data
		$charset = $this->pi1->extConf['charset']['upper'];

		// The label
		$label = $this->pi1->get_ll ( 'searchNav_label' );
		$label = $cObj->stdWrap ( $label, $cfg['label.'] );

		// Form start
		$attribs = array ( 'search' => '' );
		if ( $this->pi1->extConf['show_nav_author'] ) {
			$attribs['author'] = '';
			$attribs['author_letter'] = '';
		}
		$txt = '';
		$txt .= '<form name="'.$this->pi1->prefix_pi1.'-search_form" ';
		$txt .= 'action="'.$this->pi1->get_link_url ( $attribs, FALSE ).'"';
		$txt .= ' method="post"';
		$txt .= strlen ( $cfg['form_class'] ) ? ' class="'.$cfg['form_class'].'"' : '';
		$txt .= '>' . "\n";
		$form_start = $txt;

		//
		// The search bar
		//
		$attribs = array ( 'size' => 42, 'maxlength' => 1024 );
		$value = '';
		if ( strlen ( $this->extConf['string'] ) > 0 ) {
			$value = htmlspecialchars ( $this->extConf['string'], ENT_QUOTES, $charset );
		}
		$btn = tx_sevenpack_utility::html_text_input (
			$this->pi1->prefix_pi1.'[search][text]', $value, $attribs
		);
		$btn = $cObj->stdWrap ( $btn, $cfg['search_input.'] );
		$sea = $btn;

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
		$sea = $cObj->stdWrap ( $sea, $cfg['search_widget.'] );


		//
		// The extra check
		//
		$txt = $this->pi1->get_ll ( 'button_search_ex' );
		$txt = $cObj->stdWrap ( $txt, $cfg['extra_label.'] );

		$attribs = array (
			'onchange' => 'this.form.submit()'
		);
		if ( strlen ( $cfg['extra_btn_class'] ) > 0 )
			$attribs['class'] =  $cfg['extra_btn_class'];
		$btn = tx_sevenpack_utility::html_check_input ( 
			$this->pi1->prefix_pi1.'[search][extra]', '1', $extConf['extra'], $attribs );
		$btn = $cObj->stdWrap ( $btn, $cfg['extra_btn.'] );

		$extra = $cObj->stdWrap ( $txt . $btn, $cfg['extra_widget.'] );

		$this->hidden_input[] = tx_sevenpack_utility::html_hidden_input (
			$this->pi1->prefix_pi1.'[search][extra_b]', '1' );


		// End of form
		$form_end = implode ( "\n", $this->hidden_input );
		$form_end .= '</form>';


		$trans = array();
		$trans['###NAVI_LABEL###'] = $label;
		$trans['###FORM_START###'] = $form_start;
		$trans['###FORM_END###'] = $form_end;
		$trans['###SEARCH_BAR###'] = $sea;
		$trans['###EXTRA_BTN###'] = $extra;

		$tmpl = $this->pi1->enum_condition_block ( $this->template );
		$con = $cObj->substituteMarkerArrayCached ( $tmpl, $trans );

		return $con;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi_search.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi_search.php']);
}

?>
