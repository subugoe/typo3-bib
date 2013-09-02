<?php

class tx_bib_navi {

	public $pi1;
	public $template; // The template string
	public $pref; // A prefix string


	function initialize($pi1) {
		$this->pi1 =& $pi1;
		$this->conf = array();
	}


	function load_template($subpart) {
		$cObj =& $this->pi1->cObj;
		$tmpl = '<p>ERROR: The html template file ' . $file .
				' is not readable or empty</p>';

		$file = strval($this->conf['template']);
		if (strlen($file) > 0) {
			$file = $cObj->fileResource($file);
			if (strlen($file) > 0) {
				$tmpl = $cObj->getSubpart($file, $subpart);
			}
		}
		$this->template = $tmpl;
	}


	/*
	 * Returns the translator for the main template
	 */
	function translator() {
		$cfg = $this->conf;
		$cObj =& $this->pi1->cObj;

		$pref = '###' . $this->pref;
		$con = $this->get();

		$res = array();
		$res[$pref . '###'] = $con;

		$val = '';
		if ($this->conf['top_disable'] == 0)
			$val = $cObj->stdWrap($con, $cfg['top.']);
		$res[$pref . '_TOP###'] = $val;

		$val = '';
		if ($this->conf['bottom_disable'] == 0)
			$val = $cObj->stdWrap($con, $cfg['bottom.']);
		$res[$pref . '_BOTTOM###'] = $val;

		return $res;
	}


	/*
	 * Returns a selection translator
	 *
	 */
	function selection($cfgSel, $indices, $numSel) {
		$cObj =& $this->pi1->cObj;

		$sel = array('prev' => array(), 'cur' => array(), 'next' => array());

		// Determine ranges of year navigation bar
		$idxMin = $indices[0];
		$idxCur = $indices[1];
		$idxMax = $indices[2];

		$no_cur = FALSE;
		if ($idxCur < 0) {
			$idxCur = floor(($idxMax - $idxMin) / 2);
			$no_cur = TRUE;
		}

		// Number of items to display in the selection - must be odd
		$numSel = intval($numSel);
		$numSel = ($numSel % 2) ? $numSel : ($numSel + 1);
		$numLR = intval(($numSel - 1) / 2);

		$idxMin = $idxMin + 1;

		$idx1 = $idxCur - $numLR;
		if ($idx1 < $idxMin) {
			$idx1 = $idxMin;
			$numLR = $numLR + ($numLR - $idxCur) + 1;
		}
		$idx2 = ($idxCur + $numLR);
		if ($idx2 > ($idxMax - 1)) {
			$idx2 = $idxMax - 1;
			$numLR += $numLR - ($idxMax - $idxCur) + 1;
			$idx1 = max($idxMin, $idxCur - $numLR);
		}

		// Generate year navigation bar
		$ii = 0;
		while ($ii <= $idxMax) {
			$text = $this->sel_get_text($ii);
			$cr_link = TRUE;

			//\TYPO3\CMS\Core\Utility\GeneralUtility::debug( array('$ii' =>$ii, '$text' => $text) );

			if ($ii == $idxCur) { // Current
				$key = 'cur';
				if (!$no_cur) {
					$wrap = $cfgSel['current.'];
					$cr_link = FALSE;
				} else {
					$wrap = $cfgSel['below.'];
				}
			} else if ($ii == 0) { // First
				$key = 'prev';
				$wrap = $cfgSel['first.'];
			} else if ($ii < $idx1) { // More before
				$key = 'prev';
				$text = '...';
				if (array_key_exists('more_below', $cfgSel))
					$text = strval($cfgSel['more_below']);
				$wrap = $cfgSel['more_below.'];
				$cr_link = FALSE;
				$ii = $idx1 - 1;
			} else if ($ii < $idxCur) { // Previous
				$key = 'prev';
				$wrap = $cfgSel['below.'];
			} else if ($ii <= $idx2) { // Following
				$key = 'next';
				$wrap = $cfgSel['above.'];
			} else if ($ii < $idxMax) { // More after
				$key = 'next';
				$text = '...';
				if (array_key_exists('more_above', $cfgSel))
					$text = strval($cfgSel['more_above']);
				$wrap = $cfgSel['more_above.'];
				$cr_link = FALSE;
				$ii = $idxMax - 1;
			} else { // Last
				$key = 'next';
				$wrap = $cfgSel['last.'];
			}

			// Create link
			if ($cr_link)
				$text = $this->sel_get_link($text, $ii);
			if (is_array($wrap))
				$text = $cObj->stdWrap($text, $wrap);

			$sel[$key][] = $text;
			$ii += 1;
		}

		// Item separator
		$sep = '&nbsp;';
		if (array_key_exists('separator', $cfgSel))
			$sep = strval($cfgSel['separator']);
		if (is_array($cfgSel['separator.']))
			$sep = $cObj->stdWrap($sep, $cfgSel['separator.']);

		// Setup the translator
		$res = implode($sep, $sel['prev']);
		$res .= (sizeof($sel['prev']) ? $sep : '');
		$res .= implode($sep, $sel['cur']);
		$res .= (sizeof($sel['next']) ? $sep : '');
		$res .= implode($sep, $sel['next']);

		return $res;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/pi1/class.tx_bib_navi.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/pi1/class.tx_bib_navi.php']);
}

?>