<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');


require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/pi1/class.tx_sevenpack_navi.php') );


class tx_sevenpack_navi_year extends tx_sevenpack_navi  {

	/*
	 * Intialize
	 */
	function initialize ( $pi1 ) {
		parent::initialize( $pi1 );
		if ( is_array ( $pi1->conf['yearNav.'] ) )
			$this->conf =& $pi1->conf['yearNav.'];

		$this->pref = 'YEAR_NAVI';
		$this->load_template ( '###YEAR_NAVI_BLOCK###' );
		$this->sel_link_title = $pi1->get_ll ( 'yearNav_yearLinkTitle', '%y', TRUE );
	}


	/*
	 * Creates a text for a given index
	 */
	function sel_get_text ( $ii ) {
		return strval ( $this->pi1->stat['years'][$ii] );
	}

	/*
	 * Creates a link for the selection
	 */
	function sel_get_link ( $text, $ii ) {
		$title = str_replace ( '%y', $text, $this->sel_link_title );
		$lnk = $this->pi1->get_link ( $text, array ( 'year' => $text, 'page' => '' ), TRUE, 
			array ( 'title' => $title ) );
		return $lnk;
	}


	/*
	 * Returns content
	 */
	function get ( ) {
		$cObj =& $this->pi1->cObj;
		$con = '';

		$cfg =& $this->conf;
		$cfgSel = is_array ( $cfg['selection.'] ) ? $cfg['selection.'] : array();

		// The data
		$year = $this->pi1->extConf['year'];
		$years = $this->pi1->stat['years'];

		// The label
		$label = $this->pi1->get_ll ( 'yearNav_label' );
		$label = $cObj->stdWrap ( $label, $cfg['label.'] );

		//
		// The year select for
		//
		$ys = '';
		$ys .= '<form name="'.$this->pi1->prefix_pi1.'-year_select_form" ';
		$ys .= 'action="'.$this->pi1->get_link_url ( array ( 'year' => '' ), FALSE ).'"';
		$ys .= ' method="post"';
		$ys .= strlen ( $cfg['form_class'] ) ? ' class="'.$cfg['form_class'].'"' : '';
		$ys .= '>' . "\n";
		
		$pairs = array();
		foreach ( array_reverse( $years ) as $y )
			$pairs[$y] = $y;
		$attribs = array (
			'name'     => $this->pi1->prefix_pi1.'[year]',
			'onchange' => 'this.form.submit()'
		);
		if ( strlen ( $cfg['select_class'] ) > 0 )
			$attribs['class'] = $cfg['select_class'];
		$btn = tx_sevenpack_utility::html_select_input ( 
			$pairs, $year, $attribs );
		$btn = $cObj->stdWrap ( $btn, $cfg['select.'] );
		$ys .= $btn;

		$attribs = array ();
		if ( strlen ( $cfg['input_class'] ) > 0 )
			$attribs['class'] =  $cfg['input_class'];
		$btn = tx_sevenpack_utility::html_submit_input ( 
			$this->pi1->prefix_pi1.'[action][select_year]',
			$this->pi1->get_ll ( 'button_go' ), $attribs );
		$btn = $cObj->stdWrap ( $btn, $cfg['input.'] );
		$ys .= $btn;

		// End of form
		$ys .= '</form>';
		$ys = $cObj->stdWrap ( $ys, $cfg['form.'] );


		//
		// The year selection
		//
		$indices = array ( 0,
			intval ( array_search ( $year, $years ) ),
			sizeof ( $years ) - 1
		);
		
		$numSel = 3;
		if ( array_key_exists ( 'years', $cfgSel ) )
			$numSel = abs ( intval ( $cfgSel['years'] ) );

		$selection = $this->selection ( $cfgSel, $indices, $numSel );
		$selection = $cObj->stdWrap ( $selection, $cfgSel['all_wrap.'] );

		$trans = array();
		$trans['###SELECTION###'] = $selection;
		$trans['###YEAR_SELECT###'] = $ys;
		$trans['###NAVI_LABEL###'] = $label;

		$tmpl = $this->pi1->enum_condition_block ( $this->template );
		$con = $cObj->substituteMarkerArrayCached ( $tmpl, $trans );

		return $con;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi_year.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi_year.php']);
}

?>
