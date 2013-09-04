<?php
namespace Ipf\Bib\Navigation;

class PageNavigation extends Navigation {

	/*
	 * Initialize
	 */
	function initialize($pi1) {
		parent::initialize($pi1);
		if (is_array($pi1->conf['pageNav.']))
			$this->conf =& $pi1->conf['pageNav.'];

		$this->pref = 'PAGE_NAVI';
		$this->load_template('###PAGE_NAVI_BLOCK###');
		$this->sel_link_title = $pi1->get_ll('pageNav_pageLinkTitle', '%p', TRUE);
	}


	/*
	 * Creates a text for a given index
	 */
	function sel_get_text($ii) {
		return strval($ii + 1);
	}


	/*
	 * Creates a link for the selection
	 */
	function sel_get_link($text, $ii) {
		$title = str_replace('%p', $text, $this->sel_link_title);
		$lnk = $this->pi1->get_link($text, array('page' => strval($ii)), TRUE,
			array('title' => $title));
		return $lnk;
	}


	/*
	 * Returns content
	 */
	function get() {
		$cObj =& $this->pi1->cObj;
		$translation = array();
		$content = '';

		$configuration =& $this->conf;
		$selectionConfiguration = is_array($configuration['selection.']) ? $configuration['selection.'] : array();
		$navigationConfiguration = is_array($configuration['navigation.']) ? $configuration['navigation.'] : array();

		// The data
		$subPage =& $this->pi1->extConf['sub_page'];
		$idxCur = $subPage['current'];
		$idxMax = $subPage['max'];

		// The label
		$label = $cObj->stdWrap($this->pi1->get_ll('pageNav_label'),
			$configuration['label.']);

		// The previous/next buttons
		$nav_prev = $this->pi1->get_ll('pageNav_previous', 'previous', TRUE);
		if ($idxCur > 0) {
			$page = max($idxCur - 1, 0);
			$title = $this->pi1->get_ll('pageNav_previousLinkTitle', 'previous', TRUE);
			$nav_prev = $this->pi1->get_link($nav_prev,
				array('page' => $page), TRUE, array('title' => $title));
		}

		$nav_next = $this->pi1->get_ll('pageNav_next', 'next', TRUE);
		if ($idxCur < $idxMax) {
			$page = min($idxCur + 1, $idxMax);
			$title = $this->pi1->get_ll('pageNav_nextLinkTitle', 'next', TRUE);
			$nav_next = $this->pi1->get_link($nav_next,
				array('page' => $page), TRUE, array('title' => $title));
		}

		// Wrap
		$nav_prev = $cObj->stdWrap($nav_prev, $navigationConfiguration['previous.']);
		$nav_next = $cObj->stdWrap($nav_next, $navigationConfiguration['next.']);

		$navigationSeparator = '&nbsp;';
		if (array_key_exists('separator', $navigationConfiguration))
			$navigationSeparator = $navigationConfiguration['separator'];
		if (is_array($navigationConfiguration['separator.']))
			$navigationSeparator = $cObj->stdWrap($navigationSeparator, $navigationConfiguration['separator.']);

		// Replace separator
		$nav_prev = str_replace('###SEPARATOR###', $navigationSeparator, $nav_prev);
		$nav_next = str_replace('###SEPARATOR###', $navigationSeparator, $nav_next);


		// Create selection
		$indices = array(0, $idxCur, $idxMax);

		// Number of pages to display in the selection
		$numSel = 5;
		if (array_key_exists('pages', $selectionConfiguration))
			$numSel = abs(intval($selectionConfiguration['pages']));

		$translation['###SELECTION###'] = $this->selection($selectionConfiguration, $indices, $numSel);
		$translation['###NAVI_LABEL###'] = $label;
		$translation['###NAVI_BACKWARDS###'] = $nav_prev;
		$translation['###NAVI_FORWARDS###'] = $nav_next;

		$template = $this->pi1->setupEnumerationConditionBlock($this->template);
		$content = $cObj->substituteMarkerArrayCached($template, $translation);

		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/pi1/class.tx_bib_navi_page.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/pi1/class.tx_bib_navi_page.php']);
}

?>