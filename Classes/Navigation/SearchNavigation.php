<?php
namespace Ipf\Bib\Navigation;

class SearchNavigation extends Navigation {

	public $hidden_input;

	/*
	 * Intialize
	 */
	function initialize($pi1) {
		parent::initialize($pi1);
		if (is_array($pi1->conf['searchNav.']))
			$this->conf =& $pi1->conf['searchNav.'];

		$this->extConf = array();
		if (is_array($pi1->extConf['search_navi']))
			$this->extConf =& $pi1->extConf['search_navi'];


		$this->hidden_input = array();
		$this->pref = 'SEARCH_NAVI';
		$this->load_template('###SEARCH_NAVI_BLOCK###');
	}


	/*
	 * Hook in to pi1 at init stage
	 */
	function hook_init() {
		$cfg =& $this->pi1->conf['searchNav.'];
		$extConf =& $this->pi1->extConf;
		$sconf =& $extConf['search_navi'];
		$lvars =& $extConf['link_vars'];
		$pivars =& $this->pi1->piVars['search'];

		// Clear string
		if (isset ($this->pi1->piVars['action']['clear_search'])) {
			$clear = TRUE;
		}

		// Search string
		$p_val = $pivars['text'];
		if ((strlen($p_val) > 0) && !$clear) {
			$sconf['string'] = $p_val;
			$lvars['search']['text'] = $p_val;
		}

		// Search rule
		$rule = 'AND';
		$rules = array('OR', 'AND');
		$pvar = strtoupper($cfg['full_text.']['def']);
		if (in_array($pvar, $rules))
			$rule = $pvar;
		$pvar = strtoupper($pivars['rule']);
		if (in_array($pvar, $rules))
			$rule = $pvar;
		$sconf['rule'] = $rule;
		$lvars['search']['rule'] = $rule;

		// extra_b indicates that the page has been visited 'b'efore
		// So that the default values should not be applied
		if ($pivars['extra_b']) {
			$lvars['search']['extra_b'] = 1;
		}
		// Show extra
		$sconf['extra'] = TRUE;
		if (!$pivars['extra']) {
			$sconf['extra'] = FALSE;
			if (!$pivars['extra_b']) {
				$sconf['extra'] = $cfg['extra.']['def'] ? TRUE : FALSE;
			}
		}
		if ($sconf['extra']) $lvars['search']['extra'] = 1;

		// Search in abstracts
		$sconf['abstracts'] = TRUE;
		if (!$pivars['abstracts']) {
			$sconf['abstracts'] = FALSE;
			if (!$pivars['extra_b']) {
				$sconf['abstracts'] = $cfg['abstracts.']['def'] ? TRUE : FALSE;
			}
		}
		if ($sconf['abstracts']) $lvars['search']['abstracts'] = 1;

		// Search in full text
		$sconf['full_text'] = TRUE;
		if (!$pivars['full_text']) {
			$sconf['full_text'] = FALSE;
			if (!$pivars['extra_b']) {
				$sconf['full_text'] = $cfg['full_text.']['def'] ? TRUE : FALSE;
			}
		}
		if ($sconf['full_text']) $lvars['search']['full_text'] = 1;

		// Separator selection
		$sconf['all_sep'] = array(
			'none' => '',
			'space' => ' ',
			'semi' => ';',
			'pipe' => '|'
		);

		$sep_id = 'space';
		if (is_string($cfg['separator.']['def']))
			$sep_id = $cfg['separator.']['def'];
		if (strlen($pivars['sep']) > 0) {
			if (array_key_exists($pivars['sep'], $sconf['all_sep'])) {
				$sep_id = $pivars['sep'];
			}
		}
		$sconf['sep'] = $sep_id;
		$lvars['search']['sep'] = $sep_id;

		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( $sconf );
	}


	function hook_filter() {
		$extConf =& $this->pi1->extConf;
		$charset =& $extConf['charset']['upper'];
		$sconf =& $extConf['search_navi'];

		$strings = array();
		if (strlen($sconf['string']) > 0) {
			$sep = $sconf['sep'];
			if ($sep == 'none') {
				$strings[] = $sconf['string'];
			} else {
				// Explode search string
				$sep = $sconf['all_sep'][$sep];
				$strings = \Ipf\Bib\Utility\Utility::explode_trim(
					$sep, $sconf['string'], TRUE);
			}
		}
		$filter = array();
		if (sizeof($strings) > 0) {
			// Setup search patterns
			$words = array();
			foreach ($strings as $txt) {
				$words[] = $this->pi1->ref_read->search_word($txt, $charset);
			}

			$exclude = array();
			if (!$sconf['abstracts']) $exclude[] = 'abstract';
			if (!$sconf['full_text']) $exclude[] = 'full_text';

			//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( $words );

			$all = array();
			$all['words'] = $words;
			$all['rule'] = $sconf['rule'] == 'AND' ? 1 : 0;
			$all['exclude'] = $exclude;
			$filter['all'] = $all;
		} else {
			$extConf['post_items'] = $this->pi1->get_ll(
				'searchNav_insert_request');
			if ($this->conf['clear_start']) {
				$filter['FALSE'] = TRUE;
			}
		}

		if (sizeof($filter) > 0) {
			$extConf['filters']['search'] = $filter;
		}
	}

	/*
	 * Returns content
	 */
	function get() {
		$cObj =& $this->pi1->cObj;
		$con = '';

		$cfg =& $this->conf;
		$extConf =& $this->extConf;

		// The data
		$charset = $this->pi1->extConf['charset']['upper'];

		// The label
		$label = $this->pi1->get_ll('searchNav_label');
		$label = $cObj->stdWrap($label, $cfg['label.']);

		// Form start
		$attribs = array(
			'search' => ''
		);
		$txt = '';
		$txt .= '<form name="' . $this->pi1->prefix_pi1 . '-search_form" ';
		$txt .= 'action="' . $this->pi1->get_link_url($attribs, FALSE) . '"';
		$txt .= ' method="post"';
		$txt .= strlen($cfg['form_class']) ? ' class="' . $cfg['form_class'] . '"' : '';
		$txt .= '>' . "\n";
		$form_start = $txt;

		//
		// The search bar
		//
		$lcfg =& $cfg['search.'];
		$size = intval($lcfg['input_size']);
		$length = intval($lcfg['input_maxlength']);
		if ($size == 0) $size = 24;
		if ($length == 0) $size = 512;
		$attribs = array('size' => $size, 'maxlength' => $length);

		$value = '';
		if (strlen($this->extConf['string']) > 0) {
			$value = htmlspecialchars($extConf['string'], ENT_QUOTES, $charset);
		}
		$btn = \Ipf\Bib\Utility\Utility::html_text_input(
			$this->pi1->prefix_pi1 . '[search][text]', $value, $attribs
		);
		$btn = $cObj->stdWrap($btn, $lcfg['input.']);
		$sea = $btn;

		// The search button
		$txt = $this->pi1->get_ll('searchNav_search');

		$attribs = array();
		if (strlen($lcfg['search_btn_class']) > 0)
			$attribs['class'] = $lcfg['search_btn_class'];
		$btn = \Ipf\Bib\Utility\Utility::html_submit_input(
			$this->pi1->prefix_pi1 . '[action][search]', $txt, $attribs);
		$btn = $cObj->stdWrap($btn, $lcfg['search_btn.']);
		$sea .= $btn;


		// The clear button
		$txt = $this->pi1->get_ll('searchNav_clear');

		$attribs = array();
		if (strlen($lcfg['clear_btn_class']) > 0)
			$attribs['class'] = $lcfg['clear_btn_class'];
		$btn = \Ipf\Bib\Utility\Utility::html_submit_input(
			$this->pi1->prefix_pi1 . '[action][clear_search]', $txt, $attribs);
		$btn = $cObj->stdWrap($btn, $lcfg['clear_btn.']);
		$sea .= $btn;

		// Search widget wrap
		$sea = $cObj->stdWrap($sea, $lcfg['widget.']);


		//
		// The extra check
		//
		$lcfg =& $cfg['extra.'];
		$txt = $this->pi1->get_ll('searchNav_extra');
		$txt = $cObj->stdWrap($txt, $lcfg['label.']);

		$attribs = array(
			'onchange' => 'this.form.submit()'
		);
		if (strlen($lcfg['btn_class']) > 0)
			$attribs['class'] = $lcfg['btn_class'];
		$btn = \Ipf\Bib\Utility\Utility::html_check_input(
			$this->pi1->prefix_pi1 . '[search][extra]', '1', $extConf['extra'], $attribs);
		$btn = $cObj->stdWrap($btn, $lcfg['btn.']);

		$extra = $cObj->stdWrap($txt . $btn, $lcfg['widget.']);

		// Append hidden input
		$this->append_hidden('extra_b', TRUE);
		if (!$extConf['extra']) {
			$this->append_hidden('rule', $extConf['rule']);
			$this->append_hidden('abstracts', $extConf['abstracts']);
			$this->append_hidden('full_text', $extConf['full_text']);
		}

		// End of form
		$form_end = implode("\n", $this->hidden_input);
		$form_end .= '</form>';


		$trans = array();
		$trans['###NAVI_LABEL###'] = $label;
		$trans['###FORM_START###'] = $form_start;
		$trans['###SEARCH_BAR###'] = $sea;
		$trans['###EXTRA_BTN###'] = $extra;
		$trans['###FORM_END###'] = $form_end;
		if ($extConf['extra']) {
			$this->get_extra($trans);
		}

		$has_extra = $extConf['extra'] ? array('', '') : '';

		$tmpl = $this->pi1->setup_enum_cond_block($this->template);
		$tmpl = $cObj->substituteSubpart($tmpl, '###HAS_EXTRA###', $has_extra);
		$con = $cObj->substituteMarkerArrayCached($tmpl, $trans);

		return $con;
	}


	function get_extra(&$trans) {
		$cObj =& $this->pi1->cObj;
		$cfg =& $this->conf;
		$extConf =& $this->extConf;

		//
		// The abstract check
		//
		$lcfg =& $cfg['abstracts.'];
		$txt = $this->pi1->get_ll('searchNav_abstract');
		$txt = $cObj->stdWrap($txt, $lcfg['label.']);

		$attribs = array(
			'onchange' => 'this.form.submit()'
		);
		if (strlen($lcfg['btn_class']) > 0)
			$attribs['class'] = $lcfg['btn_class'];
		$btn = \Ipf\Bib\Utility\Utility::html_check_input(
			$this->pi1->prefix_pi1 . '[search][abstracts]', '1',
			$extConf['abstracts'], $attribs);
		$btn = $cObj->stdWrap($btn, $lcfg['btn.']);

		$abstr = $cObj->stdWrap($txt . $btn, $lcfg['widget.']);


		//
		// The full text check
		//
		$lcfg =& $cfg['full_text.'];
		$txt = $this->pi1->get_ll('searchNav_full_text');
		$txt = $cObj->stdWrap($txt, $lcfg['label.']);

		$attribs = array(
			'onchange' => 'this.form.submit()'
		);
		if (strlen($lcfg['btn_class']) > 0)
			$attribs['class'] = $lcfg['btn_class'];
		$btn = \Ipf\Bib\Utility\Utility::html_check_input(
			$this->pi1->prefix_pi1 . '[search][full_text]', '1',
			$extConf['full_text'], $attribs);
		$btn = $cObj->stdWrap($btn, $lcfg['btn.']);

		$full_txt = $cObj->stdWrap($txt . $btn, $lcfg['widget.']);


		//
		// The separator selection
		//
		$lcfg =& $cfg['separator.'];
		$txt = $this->pi1->get_ll('searchNav_separator');
		$txt = $cObj->stdWrap($txt, $lcfg['label.']);

		$types = array('space', 'semi', 'pipe');
		$pairs = array(
			'none' => $this->pi1->get_ll('searchNav_sep_none' . $type),
			'space' => '&nbsp;', 'semi' => ';', 'pipe' => '|'
		);
		foreach ($types as $type) {
			$pairs[$type] .= ' (' .
					$this->pi1->get_ll('searchNav_sep_' . $type) . ')';
		}

		$attribs = array(
			'name' => $this->pi1->prefix_pi1 . '[search][sep]',
			'onchange' => 'this.form.submit()'
		);
		if (strlen($lcfg['select_class']) > 0)
			$attribs['class'] = $lcfg['select_class'];
		$btn = \Ipf\Bib\Utility\Utility::html_select_input(
			$pairs, $extConf['sep'], $attribs);
		$btn = $cObj->stdWrap($btn, $lcfg['select.']);

		$sep = $cObj->stdWrap($txt . $btn, $lcfg['widget.']);


		//
		// The rule selection
		//
		$lcfg =& $cfg['rule.'];
		$rule = '';
		$txt = $this->pi1->get_ll('searchNav_rule');
		$txt = $cObj->stdWrap($txt, $lcfg['label.']);
		$name = $this->pi1->prefix_pi1 . '[search][rule]';

		$attribs = array(
			'onchange' => 'this.form.submit()'
		);
		if (strlen($lcfg['btn_class']) > 0)
			$attribs['class'] = $lcfg['btn_class'];

		// OR
		$lbl = $this->pi1->get_ll('searchNav_OR');
		$lbl = $cObj->stdWrap($lbl, $lcfg['btn_label.']);
		$checked = ($extConf['rule'] == 'OR');
		$btn = \Ipf\Bib\Utility\Utility::html_radio_input(
			$name, 'OR', $checked, $attribs);
		$btn = $cObj->stdWrap($btn, $lcfg['btn.']);
		$rule .= $lbl . $btn;

		// AND
		$lbl = $this->pi1->get_ll('searchNav_AND');
		$lbl = $cObj->stdWrap($lbl, $lcfg['btn_label.']);
		$checked = ($extConf['rule'] == 'AND');
		$btn = \Ipf\Bib\Utility\Utility::html_radio_input(
			$name, 'AND', $checked, $attribs);
		$btn = $cObj->stdWrap($btn, $lcfg['btn.']);

		$rule .= $lbl . $btn;

		$rule = $cObj->stdWrap($txt . $rule, $lcfg['widget.']);

		//
		// Setup the translator
		//
		$trans['###ABSTRACTS_BTN###'] = $abstr;
		$trans['###FULL_TEXT_BTN###'] = $full_txt;
		$trans['###SEPARATOR_SEL###'] = $sep;
		$trans['###RULE_SEL###'] = $rule;
	}


	function append_hidden($key, $val) {
		if (is_bool($val)) $val = $val ? '1' : '0';
		$this->hidden_input[] = \Ipf\Bib\Utility\Utility::html_hidden_input(
			$this->pi1->prefix_pi1 . '[search][' . $key . ']', $val);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/pi1/class.tx_bib_navi_search.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/pi1/class.tx_bib_navi_search.php']);
}

?>