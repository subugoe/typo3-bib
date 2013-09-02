<?php

class tx_bib_navi_author extends tx_bib_navi {

	public $extConf;


	/*
	 * Initialize
	 */
	function initialize($pi1) {
		parent::initialize($pi1);
		if (is_array($pi1->conf['authorNav.']))
			$this->conf =& $pi1->conf['authorNav.'];

		$this->extConf = array();
		if (is_array($pi1->extConf['author_navi']))
			$this->extConf =& $pi1->extConf['author_navi'];

		$this->pref = 'AUTHOR_NAVI';
		$this->load_template('###AUTHOR_NAVI_BLOCK###');
		$this->sel_link_title = $pi1->get_ll('authorNav_authorLinkTitle', '%a', TRUE);
	}


	/*
	 * Hook in to pi1 at init stage
	 */
	function hook_init() {
		$extConf =& $this->pi1->extConf;
		$aconf =& $extConf['author_navi'];
		$lvars =& $extConf['link_vars'];

		$lvars['author_letter'] = '';
		$pvar = $this->pi1->piVars['author_letter'];
		if (strlen($pvar) > 0) {
			$aconf['sel_letter'] = $pvar;
			$lvars['author_letter'] = $pvar;
		}

		$lvars['author'] = '';
		$pvar = $this->pi1->piVars['author'];
		$aconf['sel_author'] = '0';
		if (strlen($pvar) > 0) {
			$aconf['sel_author'] = $pvar;
			$lvars['author'] = $pvar;
		}

	}


	/*
	 * Hook in to pi1 at filter stage
	 */
	function hook_filter() {
		$extConf =& $this->extConf;
		$charset = $this->pi1->extConf['charset']['upper'];
		$ref_read =& $this->pi1->ref_read;

		// Init statistics
		$this->pi1->stat['authors'] = array();
		$astat =& $this->pi1->stat['authors'];

		$filter = array();

		//
		// Fetch all surnames and initialize letters
		//
		$astat['surnames'] = $ref_read->fetch_author_surnames();
		$astat['sel_surnames'] = array();
		$this->init_letters($astat['surnames']);

		// aliases
		$surnames =& $astat['surnames'];
		$sel_author =& $extConf['sel_author'];
		$sel_letter =& $extConf['sel_letter'];
		$sel_surnames =& $astat['sel_surnames'];

		// Filter for selected author letter
		// with a temporary filter
		if (strlen($sel_letter) > 0) {
			$filters = $this->pi1->extConf['filters'];

			$txt = $sel_letter;
			$spec = htmlentities($txt, ENT_QUOTES, $charset);
			$pats = array($txt . '%');
			if ($spec != $txt)
				$pats[] = $spec . '%';

			// Append surname letter to filter
			foreach ($pats as $pat) {
				$filter[] = array('surname' => $pat);
			}

			$filters['temp'] = array();
			$filters['temp']['author'] = array();
			$filters['temp']['author']['authors'] = $filter;

			//
			// Fetch selected surnames
			//
			$ref_read->set_filters($filters);
			$sel_surnames = $ref_read->fetch_author_surnames();
			//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( $sel_surnames );

			//
			// Remove ampersand strings from surname list
			//
			$lst = array();
			$spec = FALSE;
			$sel_up = mb_strtoupper($sel_letter, $charset);
			$sel_low = mb_strtolower($sel_letter, $charset);
			foreach ($sel_surnames as $name) {
				if (!(strpos($name, '&') === FALSE)) {
					$name = html_entity_decode($name, ENT_COMPAT, $charset);
					$spec = TRUE;
					//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'sur' => $name ) );
				}
				// check if first letter matches
				$ll = mb_substr($name, 0, 1, $charset);
				if (($ll != $sel_up) && ($ll != $sel_low)) {
					//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'sel' => $sel_letter, 'll' => $ll ) );
					continue;
				}
				if (!in_array($name, $lst)) {
					$lst[] = $name;
				}
			}
			if ($spec) {
				usort($lst, 'strcoll');
			}
			$sel_surnames = $lst;
			//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( $sel_surnames );

			//
			// Restore filter
			//
			$ref_read->set_filters($this->pi1->extConf['filters']);
		}

		//
		// Setup filter for selected author
		//
		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'sel_author' => $sel_author ) );
		if ($sel_author != '0') {
			$spec = htmlentities($sel_author, ENT_QUOTES, $charset);

			// Check if the selected author is available
			if (in_array($sel_author, $sel_surnames)
					|| in_array($spec, $sel_surnames)
			) {
				$pats = array($sel_author);
				if ($spec != $sel_author)
					$pats[] = $spec;

				//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'pats' => $pats ) );
				// Reset filter with the surname only
				$filter = array();
				foreach ($pats as $pat) {
					$filter[] = array('surname' => $pat);
				}
			} else {
				$sel_author = '0';
			}
		}

		// Append filter
		if (sizeof($filter) > 0) {
			$ff =& $this->pi1->extConf['filters'];
			$ff['author'] = array();
			$ff['author']['author'] = array();
			$ff['author']['author']['authors'] = $filter;

			//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( $extConf['filters'] );
			$ref_read->set_filters($ff);
		}
	}


	/*
	 * Creates a text for a given index
	 */
	function sel_get_text($ii) {
		$txt = strval($this->pi1->stat['authors']['sel_surnames'][$ii]);
		$txt = htmlspecialchars($txt, ENT_QUOTES, $this->pi1->extConf['charset']['upper']);
		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( $txt );
		return $txt;
	}


	/*
	 * Creates a link for the selection
	 */
	function sel_get_link($text, $ii) {
		$arg = strval($this->pi1->stat['authors']['sel_surnames'][$ii]);
		$title = str_replace('%a', $text, $this->sel_link_title);
		$lnk = $this->pi1->get_link($text, array('author' => $arg), TRUE,
			array('title' => $title));
		return $lnk;
	}


	/*
	 * Initialize letters
	 */
	function init_letters($names) {
		$cObj =& $this->pi1->cObj;
		$cfg =& $this->conf;
		$extConf =& $this->extConf;
		$charset = $this->pi1->extConf['charset']['upper'];

		// Acquire letter
		$letters = $this->first_letters($names, $charset);

		// Acquire selected letter
		$sel = strval($extConf['sel_letter']);
		$idx = $this->string_index($sel, $letters, '', $charset);
		if ($idx < 0) $sel = '';
		else $sel = $letters[$idx];

		$extConf['letters'] = $letters;
		$extConf['sel_letter'] = $sel;
	}


	/*
	 * Returns the first letters of all strings in a list
	 */
	function first_letters($names, $charset) {
		//
		// Acquire letters
		//
		$letters = array();
		foreach ($names as $name) {
			$ll = mb_substr($name, 0, 1, $charset);
			if ($ll == '&') {
				$match = preg_match('/^(&[^;]{1,7};)/', $name, $grp);
				if ($match) {
					$ll = html_entity_decode($grp[1], ENT_QUOTES, $charset);
				} else {
					$ll = FALSE;
				}
			}
			$up = mb_strtoupper($ll, $charset);
			if ($up != $ll) $ll = $up;
			if ($ll && !in_array($ll, $letters))
				$letters[] = $ll;
		}
		usort($letters, 'strcoll');
		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( $letters );

		return $letters;
	}


	/*
	 * Returns the position of a string in a list
	 */
	function string_index($string, $list, $null, $charset) {
		$sel1 = $string;
		$sel2 = htmlentities($sel1, ENT_QUOTES, $charset);
		$sel3 = html_entity_decode($sel1, ENT_QUOTES, $charset);

		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 
		//	'sel1' => $sel1, 'sel2' => $sel2, 
		// 'sel3' => $sel3, 'all' => $list ) );

		$idx = -1;
		if ($sel1 != $null) {
			//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'sns' => $sns ) );
			$idx = array_search($sel1, $list);
			if ($idx === FALSE) $idx = array_search($sel2, $list);
			if ($idx === FALSE) $idx = array_search($sel3, $list);
			if ($idx === FALSE) $idx = -1;
			//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'idx' => $idx ) );
		}
		return $idx;
	}


	/*
	 * Returns content
	 */
	function get() {
		$cObj =& $this->pi1->cObj;
		$cfg =& $this->conf;
		$extConf =& $this->extConf;
		$charset = $this->pi1->extConf['charset']['upper'];
		$con = '';

		// find the index of the selected name
		$sns =& $this->pi1->stat['authors']['sel_surnames'];
		$sel = $this->extConf['sel_author'];

		$extConf['sel_name_idx'] =
				$this->string_index($sel, $sns, '0', $charset);
		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug( $extConf );

		// The label
		$nlabel = $cObj->stdWrap(
			$this->pi1->get_ll('authorNav_label'), $cfg['label.']);

		// Translator
		$trans = array();
		$trans['###NAVI_LABEL###'] = $nlabel;
		$trans['###LETTER_SELECTION###'] = $this->get_letter_selection();

		$trans['###SELECTION###'] = $this->get_author_selection();
		$trans['###SURNAME_SELECT###'] = $this->get_html_select();

		$tmpl = $this->pi1->setup_enum_cond_block($this->template);
		$con = $cObj->substituteMarkerArrayCached($tmpl, $trans);

		return $con;
	}


	/*
	 * The author surname select
	 */
	function get_author_selection() {
		$cObj =& $this->pi1->cObj;
		$cfg =& $this->conf;
		$cfgSel = is_array($cfg['selection.']) ? $cfg['selection.'] : array();

		// Selection
		$sns =& $this->pi1->stat['authors']['sel_surnames'];
		$cur = $this->extConf['sel_name_idx'];
		$max = sizeof($sns) - 1;

		$indices = array(0, $cur, $max);
		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug( $indices );

		$numSel = 3;
		if (array_key_exists('authors', $cfgSel))
			$numSel = abs(intval($cfgSel['authors']));

		$sel = $this->selection($cfgSel, $indices, $numSel);

		// All and Separator
		$sep = ' - ';
		if (isset ($cfgSel['all_sep']))
			$sep = $cfgSel['all_sep'];
		$sep = $cObj->stdWrap($sep, $cfgSel['all_sep.']);

		$txt = $this->pi1->get_ll('authorNav_all_authors', 'All', TRUE);
		if ($cur < 0) {
			$txt = $cObj->stdWrap($txt, $cfgSel['current.']);
		} else {
			$txt = $this->pi1->get_link($txt, array('author' => '0'));
		}


		// All together
		if (sizeof($sns) > 0) {
			$all = $txt . $sep . $sel;
		} else {
			$all = '&nbsp;';
		}

		$all = $cObj->stdWrap($all, $cfgSel['all_wrap.']);

		return $all;
	}


	/*
	 * The author surname selction
	 */
	function get_html_select() {
		$cObj =& $this->pi1->cObj;
		$cfg =& $this->conf;
		$charset = $this->pi1->extConf['charset']['upper'];

		$con = '';
		$con .= '<form name="' . $this->pi1->prefix_pi1 . '-author_select_form" ';
		$con .= 'action="' . $this->pi1->get_link_url(array('author' => ''), FALSE) . '"';
		$con .= ' method="post"';
		$con .= strlen($cfg['form_class']) ? ' class="' . $cfg['form_class'] . '"' : '';
		$con .= '>' . "\n";

		// The raw data
		$names = $this->pi1->stat['authors']['sel_surnames'];
		$sel_name = '';
		$sel_idx = $this->extConf['sel_name_idx'];
		if ($sel_idx >= 0) {
			$sel_name = $names[$sel_idx];
			$sel_name = htmlspecialchars($sel_name, ENT_QUOTES, $charset);
		}

		// The 'All with %l' select option
		$all = $this->pi1->get_ll('authorNav_select_all', 'All authors', TRUE);
		$rep = '?';
		if (strlen($this->extConf['sel_letter']) > 0) {
			$rep = htmlspecialchars($this->extConf['sel_letter'], ENT_QUOTES, $charset);
		}
		$all = str_replace('%l', $rep, $all);

		// The processed data pairs
		$pairs = array('' => $all);
		foreach ($names as $name) {
			$name = htmlspecialchars($name, ENT_QUOTES, $charset);
			//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'select' => $name ) );
			$pairs[$name] = $name;
		}
		$attribs = array(
			'name' => $this->pi1->prefix_pi1 . '[author]',
			'onchange' => 'this.form.submit()'
		);
		if (strlen($cfg['select_class']) > 0)
			$attribs['class'] = $cfg['select_class'];
		$btn = \Ipf\Bib\Utility\Utility::html_select_input(
			$pairs, $sel_name, $attribs);

		$btn = $cObj->stdWrap($btn, $cfg['select.']);
		$con .= $btn;

		// Go button
		$attribs = array();
		if (strlen($cfg['go_btn_class']) > 0)
			$attribs['class'] = $cfg['go_btn_class'];
		$btn = \Ipf\Bib\Utility\Utility::html_submit_input(
			$this->pi1->prefix_pi1 . '[action][select_author]',
			$this->pi1->get_ll('button_go'), $attribs);
		$btn = $cObj->stdWrap($btn, $cfg['go_btn.']);
		$con .= $btn;

		// End of form
		$con .= '</form>';

		// Finalize
		if (sizeof($pairs) == 1) {
			$con = '&nbsp;';
		}

		$con = $cObj->stdWrap($con, $cfg['form.']);

		return $con;
	}


	/*
	 * Returns the author surname letter selection
	 */
	function get_letter_selection() {
		$cObj =& $this->pi1->cObj;
		$cfg =& $this->conf;
		$extConf =& $this->extConf;
		$charset = $this->pi1->extConf['charset']['upper'];
		$lcfg = is_array($cfg['letters.']) ? $cfg['letters.'] : array();

		if (sizeof($extConf['letters']) == 0) {
			return '';
		}

		//
		// Create list
		//
		// The letter separator
		$let_sep = ', ';
		if (isset ($lcfg['separator']))
			$let_sep = $lcfg['separator'];
		$let_sep = $cObj->stdWrap($let_sep, $lcfg['separator.']);

		$title_tmpl = $this->pi1->get_ll('authorNav_LetterLinkTitle', '%l', TRUE);

		// Iterate through letters
		$let_sel = array();
		foreach ($extConf['letters'] as $ll) {
			$txt = htmlspecialchars($ll, ENT_QUOTES, $charset);
			if ($ll == $extConf['sel_letter']) {
				$txt = $cObj->stdWrap($txt, $lcfg['current.']);
			} else {
				$title = str_replace('%l', $txt, $title_tmpl);
				$txt = $this->pi1->get_link($txt,
					array('author_letter' => $ll, 'author' => ''),
					TRUE, array('title' => $title));
			}
			$let_sel[] = $txt;
		}
		$lst = implode($let_sep, $let_sel);


		//
		// All link
		//
		$sep = '-';
		if (isset ($lcfg['all_sep']))
			$sep = $lcfg['all_sep'];
		$sep = $cObj->stdWrap($sep, $lcfg['all_sep.']);


		$txt = $this->pi1->get_ll('authorNav_all_letters', 'All', TRUE);
		if (strlen($extConf['sel_letter']) == 0) {
			$txt = $cObj->stdWrap($txt, $lcfg['current.']);
		} else {
			$txt = $this->pi1->get_link($txt, array('author_letter' => '', 'author' => ''));
		}

		//
		// Compose
		//
		$txt = $txt . $sep . $lst;
		$txt = $cObj->stdWrap($txt, $lcfg['all_wrap.']);

		return $txt;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/pi1/class.tx_bib_navi_author.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/pi1/class.tx_bib_navi_author.php']);
}

?>