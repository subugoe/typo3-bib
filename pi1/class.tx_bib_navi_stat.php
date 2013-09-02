<?php

class tx_bib_navi_stat extends tx_bib_navi {

	/*
	 * Initialize
	 */
	function initialize($pi1) {
		parent::initialize($pi1);
		if (is_array($pi1->conf['statNav.']))
			$this->conf =& $pi1->conf['statNav.'];

		$this->pref = 'STAT_NAVI';
		$this->load_template('###STAT_NAVI_BLOCK###');
	}


	/*
	 * Returns content
	 */
	function get() {
		$cObj =& $this->pi1->cObj;
		$trans = array();
		$con = '';

		$cfg =& $this->conf;

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
		$stat =& $this->pi1->stat;

		$total_str = strval(intval($stat['num_all']));
		$total_str = $cObj->stdWrap($total_str, $cfg['value_total.']);
		$year_str = strval(intval($stat['year_hist'][$year]));
		$year_str = $cObj->stdWrap($year_str, $cfg['value_year.']);

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
		$label = $cObj->stdWrap($label, $cfg['label.']);
		$stat_str = $cObj->stdWrap($stat_str, $cfg['values.']);

		// Setup translator
		$trans['###NAVI_LABEL###'] = $label;
		$trans['###STATISTIC###'] = $stat_str;

		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug( $trans );

		$tmpl = $this->pi1->setup_enum_cond_block($this->template);
		$con = $cObj->substituteMarkerArrayCached($tmpl, $trans);

		return $con;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/pi1/class.tx_bib_navi_stat.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/pi1/class.tx_bib_navi_stat.php']);
}

?>