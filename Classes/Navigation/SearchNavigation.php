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

class SearchNavigation extends Navigation {

	/**
	 * @var array
	 */
	public $hidden_input = array();

	/*
	 * Intialize
	 *
	 * @param \tx_bib_pi1 $pi1
	 * @return void
	 */
	function initialize($pi1) {
		parent::initialize($pi1);
		if (is_array($pi1->conf['searchNav.']))
			$this->conf =& $pi1->conf['searchNav.'];

		$this->extConf = array();
		if (is_array($pi1->extConf['search_navi']))
			$this->extConf =& $pi1->extConf['search_navi'];


		$this->pref = 'SEARCH_NAVI';
		$this->load_template('###SEARCH_NAVI_BLOCK###');
	}

	protected function sel_get_text($index) {}

	/*
	 * Hook in to pi1 at init stage
	 *
	 * @return void
	 */
	public function hook_init() {
		$sconf =& $this->pi1->extConf['search_navi'];
		$lvars =& $this->pi1->extConf['link_vars'];
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
		$pvar = strtoupper($this->pi1->conf['searchNav.']['full_text.']['def']);

		if (in_array($pvar, $rules)) {
			$rule = $pvar;
		}

		$pvar = strtoupper($pivars['rule']);

		if (in_array($pvar, $rules)) {
			$rule = $pvar;
		}

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
				$sconf['extra'] = $this->pi1->conf['searchNav.']['extra.']['def'] ? TRUE : FALSE;
			}
		}

		if ($sconf['extra']) {
			$lvars['search']['extra'] = 1;
		}

		// Search in abstracts
		$sconf['abstracts'] = TRUE;

		if (!$pivars['abstracts']) {
			$sconf['abstracts'] = FALSE;
			if (!$pivars['extra_b']) {
				$sconf['abstracts'] = $this->pi1->conf['searchNav.']['abstracts.']['def'] ? TRUE : FALSE;
			}
		}

		if ($sconf['abstracts']) {
			$lvars['search']['abstracts'] = 1;
		}

		// Search in full text
		$sconf['full_text'] = TRUE;

		if (!$pivars['full_text']) {
			$sconf['full_text'] = FALSE;
			if (!$pivars['extra_b']) {
				$sconf['full_text'] = $this->pi1->conf['searchNav.']['full_text.']['def'] ? TRUE : FALSE;
			}
		}

		if ($sconf['full_text']) {
			$lvars['search']['full_text'] = 1;
		}

		// Separator selection
		$sconf['all_sep'] = array(
			'none' => '',
			'space' => ' ',
			'semi' => ';',
			'pipe' => '|'
		);

		$sep_id = 'space';

		if (is_string($this->pi1->conf['searchNav.']['separator.']['def'])) {
			$sep_id = $this->pi1->conf['searchNav.']['separator.']['def'];
		}

		if (strlen($pivars['sep']) > 0) {
			if (array_key_exists($pivars['sep'], $sconf['all_sep'])) {
				$sep_id = $pivars['sep'];
			}
		}

		$sconf['sep'] = $sep_id;
		$lvars['search']['sep'] = $sep_id;
	}

	/**
	 * @return void
	 */
	public function hook_filter() {
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
	 *
	 * @return string
	 */
	function get() {
		$cObj =& $this->pi1->cObj;
		$content = '';

		$cfg =& $this->conf;
		$extConf =& $this->extConf;

		// The data
		$charset = $this->pi1->extConf['charset']['upper'];

		// The label
		$label = $this->pi1->get_ll('searchNav_label');
		$label = $cObj->stdWrap($label, $cfg['label.']);

		// Form start
		$attributes = array(
			'search' => ''
		);
		$txt = '';
		$txt .= '<form name="' . $this->pi1->prefix_pi1 . '-search_form" ';
		$txt .= 'action="' . $this->pi1->get_link_url($attributes, FALSE) . '"';
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

		if ($size == 0) {
			$size = 24;
		}

		if ($length == 0) {
			$size = 512;
		}

		$attributes = array(
			'size' => $size,
			'maxlength' => $length
		);

		$value = '';

		if (strlen($this->extConf['string']) > 0) {
			$value = htmlspecialchars($extConf['string'], ENT_QUOTES, $charset);
		}

		$button = \Ipf\Bib\Utility\Utility::html_text_input(
			$this->pi1->prefix_pi1 . '[search][text]', $value, $attributes
		);
		$button = $cObj->stdWrap($button, $lcfg['input.']);
		$sea = $button;

		// The search button
		$txt = $this->pi1->get_ll('searchNav_search');

		$attributes = array();

		if (strlen($lcfg['search_btn_class']) > 0) {
			$attributes['class'] = $lcfg['search_btn_class'];
		}

		$button = \Ipf\Bib\Utility\Utility::html_submit_input(
			$this->pi1->prefix_pi1 . '[action][search]',
			$txt,
			$attributes
		);
		$button = $cObj->stdWrap($button, $lcfg['search_btn.']);
		$sea .= $button;

		// The clear button
		$txt = $this->pi1->get_ll('searchNav_clear');

		$attributes = array();

		if (strlen($lcfg['clear_btn_class']) > 0) {
			$attributes['class'] = $lcfg['clear_btn_class'];
		}

		$button = \Ipf\Bib\Utility\Utility::html_submit_input(
			$this->pi1->prefix_pi1 . '[action][clear_search]',
			$txt,
			$attributes
		);
		$button = $cObj->stdWrap($button, $lcfg['clear_btn.']);
		$sea .= $button;

		// Search widget wrap
		$sea = $cObj->stdWrap($sea, $lcfg['widget.']);

		//
		// The extra check
		//
		$lcfg =& $cfg['extra.'];
		$txt = $this->pi1->get_ll('searchNav_extra');
		$txt = $cObj->stdWrap($txt, $lcfg['label.']);

		$attributes = array(
			'onchange' => 'this.form.submit()'
		);

		if (strlen($lcfg['btn_class']) > 0) {
			$attributes['class'] = $lcfg['btn_class'];
		}

		$button = \Ipf\Bib\Utility\Utility::html_check_input(
			$this->pi1->prefix_pi1 . '[search][extra]',
			'1',
			$extConf['extra'],
			$attributes
		);

		$button = $cObj->stdWrap($button, $lcfg['btn.']);

		$extra = $cObj->stdWrap($txt . $button, $lcfg['widget.']);

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


		$translator = array();
		$translator['###NAVI_LABEL###'] = $label;
		$translator['###FORM_START###'] = $form_start;
		$translator['###SEARCH_BAR###'] = $sea;
		$translator['###EXTRA_BTN###'] = $extra;
		$translator['###FORM_END###'] = $form_end;
		if ($extConf['extra']) {
			$this->get_extra($translator);
		}

		$has_extra = $extConf['extra'] ? array('', '') : '';

		$template = $this->pi1->setupEnumerationConditionBlock($this->template);
		$template = $cObj->substituteSubpart($template, '###HAS_EXTRA###', $has_extra);
		$content = $cObj->substituteMarkerArrayCached($template, $translator);

		return $content;
	}


	/**
	 * @param array $translator
	 */
	protected function get_extra(&$translator) {
		$cObj =& $this->pi1->cObj;
		$cfg =& $this->conf;
		$extConf =& $this->extConf;

		// The abstract check
		$abstractConfiguration =& $cfg['abstracts.'];
		$txt = $this->pi1->get_ll('searchNav_abstract');
		$txt = $cObj->stdWrap($txt, $abstractConfiguration['label.']);

		$attributes = array(
			'onchange' => 'this.form.submit()'
		);

		if (strlen($abstractConfiguration['btn_class']) > 0) {
			$attributes['class'] = $abstractConfiguration['btn_class'];
		}

		$button = \Ipf\Bib\Utility\Utility::html_check_input(
			$this->pi1->prefix_pi1 . '[search][abstracts]',
			'1',
			$extConf['abstracts'],
			$attributes
		);
		$button = $cObj->stdWrap($button, $abstractConfiguration['btn.']);

		$abstr = $cObj->stdWrap($txt . $button, $abstractConfiguration['widget.']);

		// The full text check
		$abstractConfiguration =& $cfg['full_text.'];
		$txt = $this->pi1->get_ll('searchNav_full_text');
		$txt = $cObj->stdWrap($txt, $abstractConfiguration['label.']);

		$attributes = array(
			'onchange' => 'this.form.submit()'
		);

		if (strlen($abstractConfiguration['btn_class']) > 0) {
			$attributes['class'] = $abstractConfiguration['btn_class'];
		}

		$button = \Ipf\Bib\Utility\Utility::html_check_input(
			$this->pi1->prefix_pi1 . '[search][full_text]',
			'1',
			$extConf['full_text'],
			$attributes
		);
		$button = $cObj->stdWrap($button, $abstractConfiguration['btn.']);

		$full_txt = $cObj->stdWrap($txt . $button, $abstractConfiguration['widget.']);


		// The separator selection
		$abstractConfiguration =& $cfg['separator.'];
		$txt = $this->pi1->get_ll('searchNav_separator');
		$txt = $cObj->stdWrap($txt, $abstractConfiguration['label.']);

		$types = array('space', 'semi', 'pipe');
		$pairs = array(
			'none' => $this->pi1->get_ll('searchNav_sep_none' . $type),
			'space' => '&nbsp;', 'semi' => ';', 'pipe' => '|'
		);
		foreach ($types as $type) {
			$pairs[$type] .= ' (' .
					$this->pi1->get_ll('searchNav_sep_' . $type) . ')';
		}

		$attributes = array(
			'name' => $this->pi1->prefix_pi1 . '[search][sep]',
			'onchange' => 'this.form.submit()'
		);

		if (strlen($abstractConfiguration['select_class']) > 0) {
			$attributes['class'] = $abstractConfiguration['select_class'];
		}

		$button = \Ipf\Bib\Utility\Utility::html_select_input(
			$pairs,
			$extConf['sep'],
			$attributes
		);
		$button = $cObj->stdWrap($button, $abstractConfiguration['select.']);

		$sep = $cObj->stdWrap($txt . $button, $abstractConfiguration['widget.']);

		// The rule selection
		$abstractConfiguration =& $cfg['rule.'];
		$rule = '';
		$txt = $this->pi1->get_ll('searchNav_rule');
		$txt = $cObj->stdWrap($txt, $abstractConfiguration['label.']);
		$name = $this->pi1->prefix_pi1 . '[search][rule]';

		$attributes = array(
			'onchange' => 'this.form.submit()'
		);
		if (strlen($abstractConfiguration['btn_class']) > 0)
			$attributes['class'] = $abstractConfiguration['btn_class'];

		// OR
		$label = $this->pi1->get_ll('searchNav_OR');
		$label = $cObj->stdWrap($label, $abstractConfiguration['btn_label.']);
		$checked = ($extConf['rule'] == 'OR');
		$button = \Ipf\Bib\Utility\Utility::html_radio_input(
			$name,
			'OR',
			$checked,
			$attributes
		);
		$button = $cObj->stdWrap($button, $abstractConfiguration['btn.']);
		$rule .= $label . $button;

		// AND
		$label = $this->pi1->get_ll('searchNav_AND');
		$label = $cObj->stdWrap($label, $abstractConfiguration['btn_label.']);
		$checked = ($extConf['rule'] == 'AND');
		$button = \Ipf\Bib\Utility\Utility::html_radio_input(
			$name,
			'AND',
			$checked,
			$attributes
		);
		$button = $cObj->stdWrap($button, $abstractConfiguration['btn.']);

		$rule .= $label . $button;

		$rule = $cObj->stdWrap($txt . $rule, $abstractConfiguration['widget.']);

		// Setup the translator
		$translator['###ABSTRACTS_BTN###'] = $abstr;
		$translator['###FULL_TEXT_BTN###'] = $full_txt;
		$translator['###SEPARATOR_SEL###'] = $sep;
		$translator['###RULE_SEL###'] = $rule;
	}


	/**
	 * @param $key
	 * @param $val
	 */
	protected function append_hidden($key, $val) {
		if (is_bool($val)) $val = $val ? '1' : '0';
		$this->hidden_input[] = \Ipf\Bib\Utility\Utility::html_hidden_input(
			$this->pi1->prefix_pi1 . '[search][' . $key . ']',
			$val
		);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/SearchNavigation.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/SearchNavigation.php']);
}

?>