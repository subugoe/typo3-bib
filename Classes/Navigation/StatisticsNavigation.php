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

class StatisticsNavigation extends Navigation {

	/*
	 * Initialize
	 *
	 * @param \tx_bib_pi1 $pi1
	 * @return void
	 */
	public function initialize($pi1) {
		parent::initialize($pi1);

		if (is_array($pi1->conf['statNav.'])) {
			$this->conf =& $pi1->conf['statNav.'];
		}

		$this->pref = 'STAT_NAVI';
		$this->load_template('###STAT_NAVI_BLOCK###');
	}

	/**
	 * @param int $index
	 * @return mixed|void
	 */
	protected function sel_get_text($index){}

	/*
	 * Returns content
	 *
	 * @return string
	 */
	protected function get() {
		$translator = array();

		$label = '';

		// Setup mode
		$d_mode = $this->pi1->extConf['d_mode'];
		$mode = intval($this->pi1->extConf['stat_mode']);
		if ($d_mode != $this->pi1->D_Y_NAV) {
			if ($mode == $this->pi1->STAT_YEAR_TOTAL) {
				$mode = $this->pi1->STAT_TOTAL;
			}
		} else {
			if (!is_numeric($this->pi1->extConf['year'])) {
				$mode = $this->pi1->STAT_TOTAL;
			}
		}

		// Setup values
		$year = intval($this->pi1->extConf['year']);

		$total_str = strval(intval($this->pi1->stat['num_all']));
		$total_str = $this->pi1->cObj->stdWrap($total_str, $this->conf['value_total.']);
		$year_str = strval(intval($this->pi1->stat['year_hist'][$year]));
		$year_str = $this->pi1->cObj->stdWrap($year_str, $this->conf['value_year.']);

		// Setup strings
		switch ($mode) {
			case $this->pi1->STAT_TOTAL:
				$label = $this->pi1->get_ll('stat_total_label', 'total', TRUE);
				$stat_str = $total_str;
				break;
			case $this->pi1->STAT_YEAR_TOTAL:
				$label = $this->pi1->get_ll('stat_year_total_label', 'this year', TRUE);
				$stat_str = $year_str . ' / ' . $total_str;
				break;
		}
		$label = $this->pi1->cObj->stdWrap($label, $this->conf['label.']);
		$stat_str = $this->pi1->cObj->stdWrap($stat_str, $this->conf['values.']);

		// Setup translator
		$translator['###NAVI_LABEL###'] = $label;
		$translator['###STATISTIC###'] = $stat_str;

		$template = $this->pi1->setupEnumerationConditionBlock($this->template);
		$content = $this->pi1->cObj->substituteMarkerArrayCached($template, $translator);

		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/StatisticsNavigation.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/StatisticsNavigation.php']);
}

?>