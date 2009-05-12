<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');


require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/pi1/class.tx_sevenpack_navi.php') );


class tx_sevenpack_navi_page extends tx_sevenpack_navi  {

	/*
	 * Initialize
	 */
	function initialize ( $pi1 ) {
		parent::initialize( $pi1 );
		if ( is_array ( $pi1->conf['pageNav.'] ) )
			$this->conf =& $pi1->conf['pageNav.'];

		$this->pref = 'PAGE_NAVI';
		$this->load_template ( '###PAGE_NAVI_BLOCK###' );
		$this->sel_link_title = $pi1->get_ll ( 'pageNav_pageLinkTitle', '%p', TRUE );
	}


	/*
	 * Creates a text for a given index
	 */
	function sel_get_text ( $ii ) {
		return strval ( $ii + 1 );
	}


	/*
	 * Creates a link for the selection
	 */
	function sel_get_link ( $text, $ii ) {
		$title = str_replace ( '%p', $text, $this->sel_link_title );
		$lnk = $this->pi1->get_link ( $text, array ( 'page' => strval ( $ii ) ), TRUE, 
			array ( 'title' => $title ) );
		return $lnk;
	}


	/*
	 * Returns content
	 */
	function get ( ) {
		$cObj =& $this->pi1->cObj;
		$trans = array();
		$con = '';

		$cfg =& $this->conf;
		$cfgSel = is_array ( $cfg['selection.'] ) ? $cfg['selection.'] : array();
		$cfgNav = is_array ( $cfg['navigation.'] ) ? $cfg['navigation.'] : array();

		// The data
		$subPage =& $this->pi1->extConf['sub_page'];
		$idxCur = $subPage['current'];
		$idxMax = $subPage['max'];

		// The label
		$label = $cObj->stdWrap ( $this->pi1->get_ll ( 'pageNav_label' ), 
			$cfg['label.'] );

		// The previous/next buttons
		$nav_prev = $this->pi1->get_ll ( 'pageNav_previous', 'previous', TRUE  );
		if ( $idxCur > 0 ) {
			$page = max ( $idxCur-1, 0 );
			$title = $this->pi1->get_ll ( 'pageNav_previousLinkTitle', 'previous', TRUE  );
			$nav_prev = $this->pi1->get_link ( $nav_prev, 
				array ( 'page' => $page ), TRUE, array( 'title' => $title ) );
		}

		$nav_next = $this->pi1->get_ll ( 'pageNav_next', 'next', TRUE  );
		if ( $idxCur < $idxMax ) {
			$page = min ( $idxCur+1, $idxMax );
			$title = $this->pi1->get_ll ( 'pageNav_nextLinkTitle', 'next', TRUE  );
			$nav_next = $this->pi1->get_link ( $nav_next ,
				array ( 'page' => $page ), TRUE, array ( 'title' => $title ) );
		}

		// Wrap
		$nav_prev = $cObj->stdWrap ( $nav_prev, $cfgNav['previous.'] );
		$nav_next = $cObj->stdWrap ( $nav_next, $cfgNav['next.'] );

		// Navigation separator
		$sepNav = '&nbsp;';
		if ( array_key_exists ( 'separator', $cfgNav  ) )
			$sepNav = $cfgNav['separator'];
		if( is_array( $cfgNav['separator.'] ) )
			$sepNav = $cObj->stdWrap ( $sepNav, $cfgNav['separator.'] );

		// Replace separator
		$nav_prev = str_replace ( '###SEPARATOR###', $sepNav, $nav_prev );
		$nav_next = str_replace ( '###SEPARATOR###', $sepNav, $nav_next );


		// Create selection
		$indices = array ( 0, $idxCur, $idxMax );
		
		// Number of pages to display in the selection
		$numSel = 5;
		if ( array_key_exists ( 'pages', $cfgSel ) )
			$numSel = abs ( intval ( $cfgSel['pages'] ) );

		$trans['###SELECTION###'] = $this->selection ( $cfgSel, $indices, $numSel );
		$trans['###NAVI_LABEL###'] = $label;
		$trans['###NAVI_BACKWARDS###'] = $nav_prev;
		$trans['###NAVI_FORWARDS###'] = $nav_next;

		//t3lib_div::debug( $trans );

		$tmpl = $this->pi1->enum_condition_block ( $this->template );
		$con = $cObj->substituteMarkerArrayCached ( $tmpl, $trans );

		return $con;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi_page.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/pi1/class.tx_sevenpack_navi_page.php']);
}

?>
