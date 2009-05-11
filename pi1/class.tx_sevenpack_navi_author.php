<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');


require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/pi1/class.tx_sevenpack_navi.php') );


class tx_sevenpack_navi_author extends tx_sevenpack_navi  {

	public $extConf;
	public $sel_name_idx;


	/*
	 * Initialize
	 */
	function initialize ( $pi1 ) {
		parent::initialize( $pi1 );
		if ( is_array ( $pi1->conf['authorNav.'] ) )
			$this->conf =& $pi1->conf['authorNav.'];

		$this->extConf =& $pi1->extConf['author_navi'];

		$this->pref = 'AUTHOR_NAVI';
		$this->load_template ( '###AUTHOR_NAVI_BLOCK###' );
		$this->sel_link_title = $pi1->get_ll ( 'yearNav_yearLinkTitle', '%sn', TRUE );
	}


	/*
	 * Creates a text for a given index
	 */
	function sel_get_text ( $ii ) {
		return strval ( $this->pi1->stat['authors']['sel_surnames'][$ii] );
	}

	/*
	 * Creates a link for the selection
	 */
	function sel_get_link ( $text, $ii ) {
		$title = str_replace ( '%sn', $text, $this->sel_link_title );
		$lnk = $this->pi1->get_link ( $text, array ( 'author' => $text ), TRUE, 
			array ( 'title' => $title ) );
		return $lnk;
	}



	/*
	 * Returns content
	 */
	function get ( ) {
		$cObj =& $this->pi1->cObj;
		$cfg =& $this->conf;
		$con = '';

		// find the index of the selected name
		$sns =& $this->pi1->stat['authors']['sel_surnames'];

		$sel1 = $this->extConf['sel_author'];
		$sel2 = html_entity_decode ( $sel1, ENT_QUOTES, 'UTF-8' );
		$sel3 = htmlentities ( $sel1, ENT_QUOTES, 'UTF-8' );

		//t3lib_div::debug ( array ( 
		//	'sel1' => $sel1, 'sel2' => $sel2,  'sel3' => $sel3, 'all' => $sns ) );

		$idx = array_search ( $sel1, $sns );
		if ( $idx === FALSE ) $idx = array_search ( $sel2, $sns );
		if ( $idx === FALSE ) $idx = array_search ( $sel3, $sns );
		if ( $idx === FALSE ) $idx = -1;
		$this->sel_name_idx = $idx;

		// The label
		$nlabel = $cObj->stdWrap ( 
			$this->pi1->get_ll ( 'authorNav_label' ), $cfg['label.'] );


		// Translator
		$trans = array();
		$trans['###NAVI_LABEL###'] = $nlabel;
		$trans['###LETTER_SELECTION###'] = $this->get_letter_selection();

		$trans['###SELECTION###'] = $this->get_selection();
		$trans['###SURNAME_SELECT###'] = $this->get_html_select();

		$tmpl = $this->pi1->enum_condition_block ( $this->template );
		$con = $cObj->substituteMarkerArrayCached ( $tmpl, $trans );

		return $con;
	}


	/*
	 * The author surname select
	 */
	function get_selection ( ) {
		$cfg =& $this->conf;
		$cfgSel = is_array ( $cfg['selection.'] ) ? $cfg['selection.'] : array();

		$sns =& $this->pi1->stat['authors']['sel_surnames'];

		$indices = array ( 0, $this->sel_name_idx, sizeof ( $sns ) - 1 );
		
		$numSel = 3;
		if ( array_key_exists ( 'authors', $cfgSel ) )
			$numSel = abs ( intval ( $cfgSel['authors'] ) );

		return $this->selection ( $cfgSel, $indices, $numSel );
	}


	/*
	 * The author surname selction
	 */
	function get_html_select ( ) {
		$cObj =& $this->pi1->cObj;
		$cfg =& $this->conf;

		$con = '';
		$con .= '<form name="'.$this->pi1->prefix_pi1.'-author_select_form" ';
		$con .= 'action="'.$this->pi1->get_link_url ( array ( 'author' => '' ), FALSE ).'"';
		$con .= ' method="post"';
		$con .= strlen ( $cfg['form_class'] ) ? ' class="'.$cfg['form_class'].'"' : '';
		$con .= '>' . "\n";

		$pairs = array();
		$names = $this->pi1->stat['authors']['sel_surnames'];
		foreach ( $names as $name )
			$pairs[$name] = $name;
		$attribs = array (
			'name'     => $this->pi1->prefix_pi1.'[author]',
			'onchange' => 'this.form.submit()'
		);
		$sel_name = $names[$this->sel_name_idx];
		if ( strlen ( $cfg['select_class'] ) > 0 )
			$attribs['class'] = $cfg['select_class'];
		$btn = tx_sevenpack_utility::html_select_input ( 
			$pairs, $sel_name, $attribs );

		$btn = $cObj->stdWrap ( $btn, $cfg['select.'] );
		$con .= $btn;

		$attribs = array ();
		if ( strlen ( $cfg['input_class'] ) > 0 )
			$attribs['class'] =  $cfg['input_class'];
		$btn = tx_sevenpack_utility::html_submit_input ( 
			$this->pi1->prefix_pi1.'[action][select_author]',
			$this->pi1->get_ll ( 'button_go' ), $attribs );
		$btn = $cObj->stdWrap ( $btn, $cfg['input.'] );
		$con .= $btn;

		// End of form
		$con .= '</form>';
		return $con;
	}


	/*
	 * Returns the author surname letter selection
	 */
	function get_letter_selection ( ) {
		$cObj =& $this->pi1->cObj;
		$cfg =& $this->conf;
		$extConf =& $this->extConf;
		$cfgLSel = is_array ( $cfg['letters.'] ) ? $cfg['letters.'] : array();

		$letters = array();
		$sns =& $this->pi1->stat['authors']['surnames'];
		foreach ( $sns as $name ) {
			$ll = mb_substr ( $name, 0, 1, 'UTF-8' );
			if ( $ll == '&' ) {
				$match = preg_match ( '/^(&[^;]{1,7};)/', $name, $grp );
				if ( $match ) {
					$ll = html_entity_decode ( $grp[1], ENT_QUOTES, 'UTF-8' );
				} else {
					$ll = FALSE;
				}
			}
			if ( $ll && !in_array ( $ll, $letters ) )
				$letters[] = $ll;
		}
		usort ( $letters, 'strcoll' );
		//t3lib_div::debug ( $letters );

		$con = array();

		// The letter separator
		$let_sep = ', ';
		if ( isset ( $cfgLSel['separator'] ) )
			$let_sep = $cfgLSel['separator'];
		if ( is_array ( $cfgLSel['separator.'] ) )
			$let_sep = $cObj->stdWrap ( $let_sep, $cfgLSel['separator.'] );

		foreach ( $letters as $ll ) {
			$txt = $ll;
			if ( $ll == $extConf['sel_letter'] ) {
				$txt = $cObj->stdWrap ( $txt, $cfgLSel['current.'] );
			} else {
				$txt = $this->pi1->get_link ( $txt, array ( 'author_letter' => $ll, 'author' => ''  ) );
			}
			$let_sel[] = $txt;
		}
		$con = implode ( $let_sep, $let_sel);

		$txt = 'All';
		if ( strlen ( $extConf['sel_letter'] ) == 0 ) {
			$txt = $cObj->stdWrap ( $txt, $cfgLSel['current.'] );
		} else {
			$txt = $this->pi1->get_link ( $txt, array ( 'author_letter' => '', 'author' => '' ) );
		}
		$con .= ' - ' . $txt;
		$let_sel = $con;

		return $con;
	}


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi_author.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi_author.php']);
}

?>
