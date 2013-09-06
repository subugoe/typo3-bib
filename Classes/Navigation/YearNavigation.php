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

class YearNavigation extends \Ipf\Bib\Navigation\Navigation {

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
		$lnk = $this->pi1->get_link($text, array('year' => $text, 'page' => ''), TRUE,
			array('title' => $title));
		return $lnk;
	}


	/*
	 * Returns content
	 */
	protected function get() {
		$cObj =& $this->pi1->cObj;
		$content = '';

		$cfg =& $this->conf;
		$cfgSel = is_array($cfg['selection.']) ? $cfg['selection.'] : array();

		// The data
		$year = $this->pi1->extConf['year'];
		$years = $this->pi1->stat['years'];

		// The label
		$label = $this->pi1->get_ll('yearNav_label');
		$label = $cObj->stdWrap($label, $cfg['label.']);

		$lbl_all = $this->pi1->get_ll('yearNav_all_years', 'All', TRUE);

		//
		// The year select form
		//
		$sel = '';
		if (sizeof($years) > 0) {
			$name = $this->pi1->prefix_pi1 . '-year_select_form';
			$action = $this->pi1->get_link_url(array('year' => ''), FALSE);
			$sel .= '<form name="' . $name . '" ';
			$sel .= 'action="' . $action . '"';
			$sel .= ' method="post"';
			$sel .= strlen($cfg['form_class']) ? ' class="' . $cfg['form_class'] . '"' : '';
			$sel .= '>' . "\n";

			$pairs = array('all' => $lbl_all);
			if (sizeof($years) > 0) {
				foreach (array_reverse($years) as $y)
					$pairs[$y] = $y;
			} else {
				$year = strval(intval(date('Y')));
				$pairs = array($year => $year);
			}

			$attribs = array(
				'name' => $this->pi1->prefix_pi1 . '[year]',
				'onchange' => 'this.form.submit()'
			);
			if (strlen($cfg['select_class']) > 0)
				$attribs['class'] = $cfg['select_class'];
			$btn = \Ipf\Bib\Utility\Utility::html_select_input(
				$pairs, $year, $attribs);
			$btn = $cObj->stdWrap($btn, $cfg['select.']);
			$sel .= $btn;

			$attribs = array();
			if (strlen($cfg['go_btn_class']) > 0)
				$attribs['class'] = $cfg['go_btn_class'];
			$btn = \Ipf\Bib\Utility\Utility::html_submit_input(
				$this->pi1->prefix_pi1 . '[action][select_year]',
				$this->pi1->get_ll('button_go'), $attribs);
			$btn = $cObj->stdWrap($btn, $cfg['go_btn.']);
			$sel .= $btn;

			// End of form
			$sel .= '</form>';
			$sel = $cObj->stdWrap($sel, $cfg['form.']);
		}


		//
		// The year selection
		//
		$selection = '';
		if (sizeof($years) > 0) {

			// The all link
			$sep = ' - ';
			if (isset ($cfgSel['all_sep']))
				$sep = $cfgSel['all_sep'];
			$sep = $cObj->stdWrap($sep, $cfgSel['all_sep.']);

			$txt = $lbl_all;
			if (is_numeric($year)) {
				$txt = $this->pi1->get_link($txt, array('year' => 'all'));
			} else {
				$txt = $cObj->stdWrap($txt, $cfgSel['current.']);
			}

			$cur = array_search($year, $years);
			if ($cur === FALSE) $cur = -1;
			$indices = array(0, $cur, sizeof($years) - 1);

			$numSel = 3;
			if (array_key_exists('years', $cfgSel)) {
				$numSel = abs(intval($cfgSel['years']));
			}

			$selection = $this->selection($cfgSel, $indices, $numSel);
			$selection = $cObj->stdWrap($txt . $sep . $selection, $cfgSel['all_wrap.']);
		}

		$trans = array();
		$trans['###NAVI_LABEL###'] = $label;
		$trans['###SELECTION###'] = $selection;
		$trans['###SELECT_FORM###'] = $sel;

		$tmpl = $this->pi1->setupEnumerationConditionBlock($this->template);
		$content = $cObj->substituteMarkerArrayCached($tmpl, $trans);

		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/YearNavigation.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/YearNavigation.php']);
}

?>