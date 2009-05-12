<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');


require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/pi1/class.tx_sevenpack_navi.php') );


class tx_sevenpack_navi_pref extends tx_sevenpack_navi  {

	/*
	 * Intialize
	 */
	function initialize ( $pi1 ) {
		parent::initialize( $pi1 );
		if( is_array ( $pi1->conf['prefNav.'] ) )
			$this->conf =& $pi1->conf['prefNav.'];

		$this->pref = 'PREF_NAVI';
		$this->load_template ( '###PREF_NAVI_BLOCK###' );
	}


	/*
	 * Returns content
	 */
	function get ( ) {
		$cObj =& $this->pi1->cObj;
		$con = '';

		$cfg =& $this->conf;

		// The label
		$nlabel = $cObj->stdWrap ( $this->pi1->get_ll ( 'prefNav_label' ), 
			$cfg['label.'] );

		//
		// Form start and end
		//
		$erase = array ( 'items_per_page' => '', 
			'show_abstracts' => '', 'show_keywords' => '' );
		$fo_sta = '';
		$fo_sta .= '<form name="'.$this->pi1->prefix_pi1.'-preferences_form" ';
		$fo_sta .= 'action="' . $this->pi1->get_link_url ( $erase, FALSE ) . '"';
		$fo_sta .= ' method="post"';
		$fo_sta .= strlen ( $cfg['form_class'] ) ? ' class="'.$cfg['form_class'].'"' : '';
		$fo_sta .= '>' . "\n";

		$fo_end = '</form>';

		//
		// Selection
		//
		$con = '';
		// ipp selection
		$label = $this->pi1->get_ll ( 'prefNav_ipp_sel' );
		$pairs = array();
		foreach ( $this->pi1->extConf['pref_ipps'] as $y )
			$pairs[$y] = '&nbsp;' . $y . '&nbsp;';
		$attribs = array (
			'name'     => $this->pi1->prefix_pi1.'[items_per_page]',
			'onchange' => 'this.form.submit()'
		);
		if ( strlen ( $cfg['select_class'] ) > 0 )
			$attribs['class'] = $cfg['select_class'];
		$btn = tx_sevenpack_utility::html_select_input ( 
			$pairs, $this->pi1->extConf['sub_page']['ipp'], $attribs );
		$widget = $cObj->stdWrap ( $label, $cfg['ipp_label.'] );
		$widget .= $cObj->stdWrap ( $btn, $cfg['ipp_select.'] );
		$widget = $cObj->stdWrap ( $widget, $cfg['ipp_widget.'] );
		$con .= $widget;

		// show abstracts
		$attribs = array ( 'onchange' => 'this.form.submit()' );
		$label = $this->pi1->get_ll ( 'prefNav_show_abstracts' );
		$check = $this->pi1->extConf['hide_fields']['abstract'] ? FALSE : TRUE;
		$btn = tx_sevenpack_utility::html_check_input ( 
			$this->pi1->prefix_pi1.'[show_abstracts]', '1' , $check, $attribs );
		$widget = $cObj->stdWrap ( $label, $cfg['abstract_label.'] );
		$widget .= $cObj->stdWrap ( $btn, $cfg['abstract_btn.'] );
		$widget = $cObj->stdWrap ( $widget, $cfg['abstract_widget.'] );
		$con .= $widget;

		// show keywords
		$label = $this->pi1->get_ll ( 'prefNav_show_keywords' );
		$check = $this->pi1->extConf['hide_fields']['keywords'] ? FALSE : TRUE;
		$btn = tx_sevenpack_utility::html_check_input ( 
			$this->pi1->prefix_pi1.'[show_keywords]', '1', $check, $attribs );
		$widget  = $cObj->stdWrap ( $label, $cfg['keywords_label.'] );
		$widget .= $cObj->stdWrap ( $btn, $cfg['keywords_btn.'] );
		$widget = $cObj->stdWrap ( $widget, $cfg['keywords_widget.'] );
		$con .= $widget;

		// Go button
		$attribs = array ();
		if ( strlen ( $cfg['go_btn_class'] ) > 0 )
			$attribs['class'] =  $cfg['go_btn_class'];
		$widget = tx_sevenpack_utility::html_submit_input ( 
			$this->pi1->prefix_pi1.'[action][eval_pref]',
			$this->pi1->get_ll ( 'button_go' ), $attribs );
		$widget = $cObj->stdWrap ( $widget, $cfg['go_btn.'] );
		$con .= $widget;

		// Translator
		$trans = array();
		$trans['###NAVI_LABEL###'] = $nlabel;
		$trans['###FORM_START###'] = $fo_sta;
		$trans['###SELECTION###'] = $con;
		$trans['###FORM_END###'] = $fo_end;

		$tmpl = $this->pi1->enum_condition_block ( $this->template );
		$con = $cObj->substituteMarkerArrayCached ( $tmpl, $trans );

		return $con;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi_pref.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi_pref.php']);
}

?>
