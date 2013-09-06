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

class PreferenceNavigation extends Navigation {

	public $extConf;

	/*
	 * Intialize
	 *
	 * @param \tx_bib_pi1 $pi1
	 * @return void
	 */
	public function initialize($pi1) {
		parent::initialize($pi1);
		if (is_array($pi1->conf['prefNav.']))
			$this->conf =& $pi1->conf['prefNav.'];

		$this->extConf = array();
		if (is_array($pi1->extConf['pref_navi']))
			$this->extConf =& $pi1->extConf['pref_navi'];

		$this->pref = 'PREF_NAVI';
		$this->load_template('###PREF_NAVI_BLOCK###');
	}

	protected function sel_get_text($index){}

	/*
	 * Hook in to pi1 at init stage
	 */
	public function hook_init() {
		$extConf =& $this->pi1->extConf;

		// Items per page
		$iPP =& $extConf['sub_page']['ipp'];

		// Available ipp values
		$this->extConf['pref_ipps'] = \Ipf\Bib\Utility\Utility::explode_intval(
			',', $this->conf['ipp_values']);

		// Default ipp value
		if (is_numeric($this->conf['ipp_default'])) {
			$iPP = intval($this->conf['ipp_default']);
			$this->extConf['pref_ipp'] = $iPP;
		}

		// Selected ipp value
		$pvar = $this->pi1->piVars['items_per_page'];
		if (is_numeric($pvar)) {
			$pvar = max(intval($pvar), 0);
			if (in_array($pvar, $this->extConf['pref_ipps'])) {
				$iPP = $pvar;
				if ($iPP != $this->extConf['pref_ipp']) {
					$extConf['link_vars']['items_per_page'] = $iPP;
				}
			}
		}

		// Show abstracts
		$show = FALSE;
		if ($this->pi1->piVars['show_abstracts'] != 0) {
			$show = TRUE;
		}
		$extConf['hide_fields']['abstract'] = $show ? FALSE : TRUE;
		$extConf['link_vars']['show_abstracts'] = $show ? '1' : '0';

		// Show keywords
		$show = FALSE;
		if ($this->pi1->piVars['show_keywords'] != 0) {
			$show = TRUE;
		}
		$extConf['hide_fields']['keywords'] = $show ? FALSE : TRUE;
		$extConf['hide_fields']['tags'] = $extConf['hide_fields']['keywords'];
		$extConf['link_vars']['show_keywords'] = $show ? '1' : '0';
	}


	/*
	 * Returns content
	 *
	 * @return string
	 */
	protected function get() {
		$cObj =& $this->pi1->cObj;

		$cfg =& $this->conf;

		// The label
		$label = $this->pi1->get_ll('prefNav_label');
		$label = $cObj->stdWrap($label, $cfg['label.']);

		//
		// Form start and end
		//
		$erase = array('items_per_page' => '',
			'show_abstracts' => '', 'show_keywords' => '');
		$fo_sta = '';
		$fo_sta .= '<form name="' . $this->pi1->prefix_pi1 . '-preferences_form" ';
		$fo_sta .= 'action="' . $this->pi1->get_link_url($erase, FALSE) . '"';
		$fo_sta .= ' method="post"';
		$fo_sta .= strlen($cfg['form_class']) ? ' class="' . $cfg['form_class'] . '"' : '';
		$fo_sta .= '>' . "\n";

		$fo_end = '</form>';

		//
		// Item per page selection
		//
		$lcfg =& $cfg['ipp.'];
		$lbl = $this->pi1->get_ll('prefNav_ipp_sel');
		$lbl = $cObj->stdWrap($lbl, $lcfg['label.']);
		$pairs = array();
		foreach ($this->extConf['pref_ipps'] as $ii) {
			$pairs[$ii] = '&nbsp;' . strval($ii) . '&nbsp;';
		}
		$attributes = array(
			'name' => $this->pi1->prefix_pi1 . '[items_per_page]',
			'onchange' => 'this.form.submit()'
		);
		if (strlen($lcfg['select_class']) > 0)
			$attributes['class'] = $lcfg['select_class'];
		$button = \Ipf\Bib\Utility\Utility::html_select_input(
			$pairs, $this->pi1->extConf['sub_page']['ipp'], $attributes);
		$button = $cObj->stdWrap($button, $lcfg['select.']);
		$ipp_sel = $cObj->stdWrap($lbl . $button, $lcfg['widget.']);

		//
		// show abstracts
		//
		$lcfg =& $cfg['abstract.'];
		$attributes = array('onchange' => 'this.form.submit()');
		if (strlen($lcfg['btn_class']) > 0)
			$attributes['class'] = $lcfg['btn_class'];

		$lbl = $this->pi1->get_ll('prefNav_show_abstracts');
		$lbl = $cObj->stdWrap($lbl, $lcfg['label.']);
		$check = $this->pi1->extConf['hide_fields']['abstract'] ? FALSE : TRUE;
		$button = \Ipf\Bib\Utility\Utility::html_check_input(
			$this->pi1->prefix_pi1 . '[show_abstracts]',
			'1',
			$check,
			$attributes
		);
		$button = $cObj->stdWrap($button, $lcfg['btn.']);
		$chk_abstr = $cObj->stdWrap($lbl . $button, $lcfg['widget.']);

		//
		// show keywords
		//
		$lcfg =& $cfg['keywords.'];
		$attributes = array('onchange' => 'this.form.submit()');
		if (strlen($lcfg['btn_class']) > 0)
			$attributes['class'] = $lcfg['btn_class'];

		$lbl = $this->pi1->get_ll('prefNav_show_keywords');
		$lbl = $cObj->stdWrap($lbl, $lcfg['label.']);
		$check = $this->pi1->extConf['hide_fields']['keywords'] ? FALSE : TRUE;
		$button = \Ipf\Bib\Utility\Utility::html_check_input(
			$this->pi1->prefix_pi1 . '[show_keywords]',
			'1',
			$check,
			$attributes
		);
		$button = $cObj->stdWrap($button, $lcfg['btn.']);
		$chk_keys = $cObj->stdWrap($lbl . $button, $lcfg['widget.']);

		//
		// Go button
		//
		$attributes = array();
		if (strlen($cfg['go_btn_class']) > 0)
			$attributes['class'] = $cfg['go_btn_class'];
		$widget = \Ipf\Bib\Utility\Utility::html_submit_input(
			$this->pi1->prefix_pi1 . '[action][eval_pref]',
			$this->pi1->get_ll('button_go'),
			$attributes
		);
		$go_btn = $cObj->stdWrap($widget, $cfg['go_btn.']);


		// Translator
		$translator = array();
		$translator['###NAVI_LABEL###'] = $label;
		$translator['###FORM_START###'] = $fo_sta;
		$translator['###IPP_SEL###'] = $ipp_sel;
		$translator['###SHOW_ABSTRACTS###'] = $chk_abstr;
		$translator['###SHOW_KEYS###'] = $chk_keys;
		$translator['###GO###'] = $go_btn;
		$translator['###FORM_END###'] = $fo_end;

		$template = $this->pi1->setupEnumerationConditionBlock($this->template);
		$content = $cObj->substituteMarkerArrayCached($template, $translator);

		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/PreferenceNavigation.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/PreferenceNavigation.php']);
}

?>