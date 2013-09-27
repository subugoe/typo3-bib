<?php
namespace Ipf\Bib\Navigation;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
 *      Goettingen State Library
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

use \Ipf\Bib\Utility\Utility;

class YearNavigation extends Navigation {

	/*
	 * Intialize
	 */
	public function initialize($pi1) {
		parent::initialize($pi1);
		if (is_array($pi1->conf['yearNav.'])) {
			$this->conf =& $pi1->conf['yearNav.'];
		}

		$this->prefix = 'YEAR_NAVI';
		$this->sel_link_title = $pi1->get_ll('yearNav_yearLinkTitle', '%y', TRUE);
	}


	/*
	 * Creates a text for a given index
	 */
	function sel_get_text($index) {
		return strval($this->pi1->stat['years'][$index]);
	}

	/*
	 * Creates a link for the selection
	 */
	function sel_get_link($text, $ii) {
		$title = str_replace('%y', $text, $this->sel_link_title);
		$lnk = $this->pi1->get_link(
			$text,
			array(
				'year' => $text,
				'page' => ''
			),
			TRUE,
			array(
				'title' => $title
			)
		);
		return $lnk;
	}


	/*
	 * Returns content
	 */
	protected function get() {

		// The label
		$label = $this->pi1->get_ll('yearNav_label');
		$label = $this->pi1->cObj->stdWrap($label, $this->conf['label.']);

		$this->view
				->assign('label', $label)
				->assign('selection', $this->getYearSelection())
				->assign('selectForm', $this->getYearSelectionForm());

		return $this->view->render();
	}

	/**
	 * @return string
	 */
	protected function getYearSelection() {

		$selectionConfiguration = is_array($this->conf['selection.']) ? $this->conf['selection.'] : array();

		if (sizeof($this->pi1->stat['years']) > 0) {

			// The all link
			$delimiter = ' - ';
			if (isset ($selectionConfiguration['all_sep'])) {
				$delimiter = $selectionConfiguration['all_sep'];
			}
			$delimiter = $this->pi1->cObj->stdWrap($delimiter, $selectionConfiguration['all_sep.']);

			$txt = $this->pi1->get_ll('yearNav_all_years', 'All', TRUE);
			if (is_numeric($this->pi1->extConf['year'])) {
				$txt = $this->pi1->get_link($txt, array('year' => 'all'));
			} else {
				$txt = $this->pi1->cObj->stdWrap($txt, $selectionConfiguration['current.']);
			}

			$cur = array_search($this->pi1->extConf['year'], $this->pi1->stat['years']);
			if ($cur === FALSE) {
				$cur = -1;
			}
			$indices = array(0, $cur, sizeof($this->pi1->stat['years']) - 1);

			$numSel = 3;
			if (array_key_exists('years', $selectionConfiguration)) {
				$numSel = abs(intval($selectionConfiguration['years']));
			}

			$selection = $this->selection($selectionConfiguration, $indices, $numSel);
			return $this->pi1->cObj->stdWrap($txt . $delimiter . $selection, $selectionConfiguration['all_wrap.']);
		}
	}

	/**
	 * @return string
	 */
	protected function getYearSelectionForm() {
		$selectForm = '';
		if (sizeof($this->pi1->stat['years']) > 0) {
			$name = $this->pi1->prefix_pi1 . '-year_select_form';
			$action = $this->pi1->get_link_url(array('year' => ''), FALSE);
			$selectForm .= '<form name="' . $name . '" ';
			$selectForm .= 'action="' . $action . '"';
			$selectForm .= ' method="post"';
			$selectForm .= strlen($this->conf['form_class']) ? ' class="' . $this->conf['form_class'] . '"' : '';
			$selectForm .= '>' . "\n";

			$pairs = array('all' => $this->pi1->get_ll('yearNav_all_years', 'All', TRUE));
			if (sizeof($this->pi1->stat['years']) > 0) {
				foreach (array_reverse($this->pi1->stat['years']) as $y)
					$pairs[$y] = $y;
			} else {
				$year = strval(intval(date('Y')));
				$pairs = array($year => $year);
			}

			$attributes = array(
				'name' => $this->pi1->prefix_pi1 . '[year]',
				'onchange' => 'this.form.submit()'
			);
			if (strlen($this->conf['select_class']) > 0) {
				$attributes['class'] = $this->conf['select_class'];
			}
			$button = Utility::html_select_input(
				$pairs, $year, $attributes);
			$button = $this->pi1->cObj->stdWrap($button, $this->conf['select.']);
			$selectForm .= $button;

			$attributes = array();
			if (strlen($this->conf['go_btn_class']) > 0) {
				$attributes['class'] = $this->conf['go_btn_class'];
			}
			$button = Utility::html_submit_input(
				$this->pi1->prefix_pi1 . '[action][select_year]',
				$this->pi1->get_ll('button_go'), $attributes);
			$button = $this->pi1->cObj->stdWrap($button, $this->conf['go_btn.']);
			$selectForm .= $button;

			// End of form
			$selectForm .= '</form>';
		}
		return $this->pi1->cObj->stdWrap($selectForm, $this->conf['form.']);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/YearNavigation.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/YearNavigation.php']);
}

?>