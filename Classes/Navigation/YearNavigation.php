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

		$this->pref = 'YEAR_NAVI';
		$this->load_template('###YEAR_NAVI_BLOCK###');
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
		$cObj =& $this->pi1->cObj;
		$content = '';

		$selectionConfiguration = is_array($this->conf['selection.']) ? $this->conf['selection.'] : array();

		// The data
		$year = $this->pi1->extConf['year'];
		$years = $this->pi1->stat['years'];

		// The label
		$label = $this->pi1->get_ll('yearNav_label');
		$label = $cObj->stdWrap($label, $this->conf['label.']);

		$lbl_all = $this->pi1->get_ll('yearNav_all_years', 'All', TRUE);

		// The year select form
		$sel = '';
		if (sizeof($years) > 0) {
			$name = $this->pi1->prefix_pi1 . '-year_select_form';
			$action = $this->pi1->get_link_url(array('year' => ''), FALSE);
			$sel .= '<form name="' . $name . '" ';
			$sel .= 'action="' . $action . '"';
			$sel .= ' method="post"';
			$sel .= strlen($this->conf['form_class']) ? ' class="' . $this->conf['form_class'] . '"' : '';
			$sel .= '>' . "\n";

			$pairs = array('all' => $lbl_all);
			if (sizeof($years) > 0) {
				foreach (array_reverse($years) as $y)
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
			$button = $cObj->stdWrap($button, $this->conf['select.']);
			$sel .= $button;

			$attributes = array();
			if (strlen($this->conf['go_btn_class']) > 0) {
				$attributes['class'] = $this->conf['go_btn_class'];
			}
			$button = Utility::html_submit_input(
				$this->pi1->prefix_pi1 . '[action][select_year]',
				$this->pi1->get_ll('button_go'), $attributes);
			$button = $cObj->stdWrap($button, $this->conf['go_btn.']);
			$sel .= $button;

			// End of form
			$sel .= '</form>';
			$sel = $cObj->stdWrap($sel, $this->conf['form.']);
		}

		// The year selection
		$selection = '';
		if (sizeof($years) > 0) {

			// The all link
			$sep = ' - ';
			if (isset ($selectionConfiguration['all_sep'])) {
				$sep = $selectionConfiguration['all_sep'];
			}
			$sep = $cObj->stdWrap($sep, $selectionConfiguration['all_sep.']);

			$txt = $lbl_all;
			if (is_numeric($year)) {
				$txt = $this->pi1->get_link($txt, array('year' => 'all'));
			} else {
				$txt = $cObj->stdWrap($txt, $selectionConfiguration['current.']);
			}

			$cur = array_search($year, $years);
			if ($cur === FALSE) {
				$cur = -1;
			}
			$indices = array(0, $cur, sizeof($years) - 1);

			$numSel = 3;
			if (array_key_exists('years', $selectionConfiguration)) {
				$numSel = abs(intval($selectionConfiguration['years']));
			}

			$selection = $this->selection($selectionConfiguration, $indices, $numSel);
			$selection = $cObj->stdWrap($txt . $sep . $selection, $selectionConfiguration['all_wrap.']);
		}

		$translator = array();
		$translator['###NAVI_LABEL###'] = $label;
		$translator['###SELECTION###'] = $selection;
		$translator['###SELECT_FORM###'] = $sel;

		$template = $this->pi1->setupEnumerationConditionBlock($this->template);
		$content = $cObj->substituteMarkerArrayCached($template, $translator);

		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/YearNavigation.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/YearNavigation.php']);
}

?>