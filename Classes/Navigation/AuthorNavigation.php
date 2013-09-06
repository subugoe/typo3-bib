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

class AuthorNavigation extends Navigation {

	/**
	 * @var array
	 */
	public $extConf;

	/*
	 * Initialize
	 *
	 * @param \tx_bib_pi1
	 * @return void
	 */
	public function initialize($pi1) {
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
	 *
	 * @return void
	 */
	public function hook_init() {
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
	 *
	 * @return void
	 */
	public function hook_filter() {
		$extConf =& $this->extConf;
		$charset = $this->pi1->extConf['charset']['upper'];
		$ref_read =& $this->pi1->referenceReader;

		// Init statistics
		$this->pi1->stat['authors'] = array();
		$astat =& $this->pi1->stat['authors'];

		$filter = array();

		// Fetch all surnames and initialize letters
		$astat['surnames'] = $ref_read->fetch_author_surnames();
		$astat['sel_surnames'] = array();
		$this->initializeLetters($astat['surnames']);

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

			// Fetch selected surnames
			$ref_read->set_filters($filters);
			$sel_surnames = $ref_read->fetch_author_surnames();

			// Remove ampersand strings from surname list
			$lst = array();
			$spec = FALSE;
			$sel_up = mb_strtoupper($sel_letter, $charset);
			$sel_low = mb_strtolower($sel_letter, $charset);
			foreach ($sel_surnames as $name) {
				if (!(strpos($name, '&') === FALSE)) {
					$name = html_entity_decode($name, ENT_COMPAT, $charset);
					$spec = TRUE;
				}
				// check if first letter matches
				$ll = mb_substr($name, 0, 1, $charset);
				if (($ll != $sel_up) && ($ll != $sel_low)) {
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

			// Restore filter
			$ref_read->set_filters($this->pi1->extConf['filters']);
		}

		// Setup filter for selected author
		if ($sel_author != '0') {
			$spec = htmlentities($sel_author, ENT_QUOTES, $charset);

			// Check if the selected author is available
			if (in_array($sel_author, $sel_surnames)
					|| in_array($spec, $sel_surnames)
			) {
				$pats = array($sel_author);
				if ($spec != $sel_author)
					$pats[] = $spec;

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

			$ref_read->set_filters($ff);
		}
	}


	/*
	 * Creates a text for a given index
	 *
	 * @param int $index
	 * @return string
	 */
	protected function sel_get_text($index) {
		$txt = strval($this->pi1->stat['authors']['sel_surnames'][$index]);
		$txt = htmlspecialchars($txt, ENT_QUOTES, $this->pi1->extConf['charset']['upper']);
		return $txt;
	}


	/*
	 * Creates a link for the selection
	 */
	protected function sel_get_link($text, $ii) {
		$arg = strval($this->pi1->stat['authors']['sel_surnames'][$ii]);
		$title = str_replace('%a', $text, $this->sel_link_title);
		$lnk = $this->pi1->get_link($text, array('author' => $arg), TRUE,
			array('title' => $title));
		return $lnk;
	}


	/*
	 * Initialize letters
	 *
	 * @param array $names
	 * @return void
	 */
	protected function initializeLetters($names) {
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
	 *
	 * @param array $names
	 * @param string $charset
	 * @return array
	 */
	protected function first_letters($names, $charset) {

		// Acquire letters
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

		return $letters;
	}


	/*
	 * Returns the position of a string in a list
	 *
	 * @param string $string
	 * @param array $list
	 * @param mixed $null
	 * @param string $charset
	 * @return int|mixed
	 */
	protected function string_index($string, $list, $null, $charset) {
		$sel1 = $string;
		$sel2 = htmlentities($sel1, ENT_QUOTES, $charset);
		$sel3 = html_entity_decode($sel1, ENT_QUOTES, $charset);

		$index = -1;
		if ($sel1 != $null) {
			$index = array_search($sel1, $list);
			if ($index === FALSE) $index = array_search($sel2, $list);
			if ($index === FALSE) $index = array_search($sel3, $list);
			if ($index === FALSE) $index = -1;
		}
		return $index;
	}


	/*
	 * Returns content
	 *
	 * @return string
	 */
	protected function get() {
		$charset = $this->pi1->extConf['charset']['upper'];

		// find the index of the selected name
		$this->extConf['sel_name_idx'] = $this->string_index(
			$this->extConf['sel_author'],
			$this->pi1->stat['authors']['sel_surnames'],
			'0',
			$charset
		);

		// The label
		$nlabel = $this->pi1->cObj->stdWrap(
			$this->pi1->get_ll('authorNav_label'), $this->conf['label.']);

		$translator = array();
		$translator['###NAVI_LABEL###'] = $nlabel;
		$translator['###LETTER_SELECTION###'] = $this->get_letter_selection();

		$translator['###SELECTION###'] = $this->getAuthorSelection();
		$translator['###SURNAME_SELECT###'] = $this->getHtmlSelectFormField();

		$template = $this->pi1->setup_enum_cond_block($this->template);
		$content = $this->pi1->cObj->substituteMarkerArrayCached($template, $translator);

		return $content;
	}


	/*
	 * The author surname select
	 *
	 * @return string
	 */
	protected function getAuthorSelection() {
		$configurationSelection = is_array($this->conf['selection.']) ? $this->conf['selection.'] : array();

		// Selection
		$cur = $this->extConf['sel_name_idx'];
		$max = sizeof($this->pi1->stat['authors']['sel_surnames']) - 1;

		$indices = array(0, $cur, $max);

		$numSel = 3;
		if (array_key_exists('authors', $configurationSelection))
			$numSel = abs(intval($configurationSelection['authors']));

		$sel = $this->selection($configurationSelection, $indices, $numSel);

		// All and Separator
		$sep = ' - ';
		if (isset ($configurationSelection['all_sep']))
			$sep = $configurationSelection['all_sep'];
		$sep = $this->pi1->cObj->stdWrap($sep, $configurationSelection['all_sep.']);

		$txt = $this->pi1->get_ll('authorNav_all_authors', 'All', TRUE);
		if ($cur < 0) {
			$txt = $this->pi1->cObj->stdWrap($txt, $configurationSelection['current.']);
		} else {
			$txt = $this->pi1->get_link($txt, array('author' => '0'));
		}

		// All together
		if (sizeof($this->pi1->stat['authors']['sel_surnames']) > 0) {
			$all = $txt . $sep . $sel;
		} else {
			$all = '&nbsp;';
		}

		$all = $this->pi1->cObj->stdWrap($all, $configurationSelection['all_wrap.']);

		return $all;
	}


	/*
	 * The author surname select form field
	 *
	 * @return string
	 */
	protected function getHtmlSelectFormField() {

		$content = '<form name="' . $this->pi1->prefix_pi1 . '-author_select_form" ';
		$content .= 'action="' . $this->pi1->get_link_url(array('author' => ''), FALSE) . '"';
		$content .= ' method="post"';
		$content .= strlen($this->conf['form_class']) ? ' class="' . $this->conf['form_class'] . '"' : '';
		$content .= '>' . "\n";

		// The raw data
		$names = $this->pi1->stat['authors']['sel_surnames'];
		$sel_name = '';
		$sel_idx = $this->extConf['sel_name_idx'];
		if ($sel_idx >= 0) {
			$sel_name = $names[$sel_idx];
			$sel_name = htmlspecialchars($sel_name, ENT_QUOTES, $this->pi1->extConf['charset']['upper']);
		}

		// The 'All with %l' select option
		$all = $this->pi1->get_ll('authorNav_select_all', 'All authors', TRUE);
		$rep = '?';
		if (strlen($this->extConf['sel_letter']) > 0) {
			$rep = htmlspecialchars($this->extConf['sel_letter'], ENT_QUOTES, $this->pi1->extConf['charset']['upper']);
		}
		$all = str_replace('%l', $rep, $all);

		// The processed data pairs
		$pairs = array('' => $all);
		foreach ($names as $name) {
			$name = htmlspecialchars($name, ENT_QUOTES, $this->pi1->extConf['charset']['upper']);
			$pairs[$name] = $name;
		}
		$attributes = array(
			'name' => $this->pi1->prefix_pi1 . '[author]',
			'onchange' => 'this.form.submit()'
		);
		if (strlen($this->conf['select_class']) > 0)
			$attributes['class'] = $this->conf['select_class'];
		$button = \Ipf\Bib\Utility\Utility::html_select_input(
			$pairs, $sel_name, $attributes);

		$button = $this->pi1->cObj->stdWrap($button, $this->conf['select.']);
		$content .= $button;

		// Go button
		$attributes = array();
		if (strlen($this->conf['go_btn_class']) > 0)
			$attributes['class'] = $this->conf['go_btn_class'];
		$button = \Ipf\Bib\Utility\Utility::html_submit_input(
			$this->pi1->prefix_pi1 . '[action][select_author]',
			$this->pi1->get_ll('button_go'), $attributes);
		$button = $this->pi1->cObj->stdWrap($button, $this->conf['go_btn.']);
		$content .= $button;

		// End of form
		$content .= '</form>';

		// Finalize
		if (sizeof($pairs) == 1) {
			$content = '&nbsp;';
		}

		$content = $this->pi1->cObj->stdWrap($content, $this->conf['form.']);

		return $content;
	}


	/*
	 * Returns the author surname letter selection
	 *
	 * @return string
	 */
	protected function get_letter_selection() {
		$cObj =& $this->pi1->cObj;
		$cfg =& $this->conf;
		$extConf =& $this->extConf;
		$charset = $this->pi1->extConf['charset']['upper'];
		$letterConfiguration = is_array($cfg['letters.']) ? $cfg['letters.'] : array();

		if (sizeof($extConf['letters']) == 0) {
			return '';
		}

		// Create list
		// The letter separator
		$letterSeparator = ', ';
		if (isset ($letterConfiguration['separator']))
			$letterSeparator = $letterConfiguration['separator'];
		$letterSeparator = $cObj->stdWrap($letterSeparator, $letterConfiguration['separator.']);

		$titleTemplate = $this->pi1->get_ll('authorNav_LetterLinkTitle', '%l', TRUE);

		// Iterate through letters
		$letterSelection = array();
		foreach ($extConf['letters'] as $letter) {
			$txt = htmlspecialchars($letter, ENT_QUOTES, $charset);
			if ($letter == $extConf['sel_letter']) {
				$txt = $cObj->stdWrap($txt, $letterConfiguration['current.']);
			} else {
				$title = str_replace('%l', $txt, $titleTemplate);
				$txt = $this->pi1->get_link(
					$txt,
					array(
						'author_letter' => $letter,
						'author' => ''
					),
					TRUE,
					array(
						'title' => $title
					)
				);
			}
			$letterSelection[] = $txt;
		}
		$lst = implode($letterSeparator, $letterSelection);

		// All link
		$sep = '-';
		if (isset ($letterConfiguration['all_sep'])) {
			$sep = $letterConfiguration['all_sep'];
		}
		$sep = $cObj->stdWrap($sep, $letterConfiguration['all_sep.']);

		$txt = $this->pi1->get_ll('authorNav_all_letters', 'All', TRUE);
		if (strlen($extConf['sel_letter']) == 0) {
			$txt = $cObj->stdWrap($txt, $letterConfiguration['current.']);
		} else {
			$txt = $this->pi1->get_link($txt, array('author_letter' => '', 'author' => ''));
		}

		// Compose
		$txt = $txt . $sep . $lst;
		$txt = $cObj->stdWrap($txt, $letterConfiguration['all_wrap.']);

		return $txt;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/AuthorNavigation.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/AuthorNavigation.php']);
}

?>