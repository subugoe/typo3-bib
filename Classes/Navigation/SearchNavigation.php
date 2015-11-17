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

use Ipf\Bib\Utility\Utility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SearchNavigation.
 */
class SearchNavigation extends Navigation
{
    /**
     * @var array
     */
    public $hidden_input = [];

    /**
     * @var array
     */
    protected $extConf;

    /**
     * Intialize.
     *
     * @param \tx_bib_pi1 $pi1
     */
    public function initialize($pi1)
    {
        parent::initialize($pi1);

        if (is_array($pi1->conf['searchNav.'])) {
            $this->conf = &$pi1->conf['searchNav.'];
        }

        $this->extConf = [];
        if (is_array($pi1->extConf['search_navi'])) {
            $this->extConf = &$pi1->extConf['search_navi'];
        }

        $this->prefix = 'SEARCH_NAVI';
    }

    /**
     * Hook in to pi1 at init stage.
     */
    public function hook_init()
    {

        // Clear string
        if (isset($this->pi1->piVars['action']['clear_search'])) {
            $clear = true;
        } else {
            $clear = false;
        }

        // Search string
        $p_val = $this->pi1->piVars['search']['text'];

        if ((strlen($p_val) > 0) && !$clear) {
            $this->pi1->extConf['search_navi']['string'] = $p_val;
            $this->pi1->extConf['link_vars']['search']['text'] = $p_val;
        }

        // Search rule
        $rule = 'AND';
        $rules = ['OR', 'AND'];
        $pvar = strtoupper($this->pi1->conf['searchNav.']['full_text.']['def']);

        if (in_array($pvar, $rules)) {
            $rule = $pvar;
        }

        $pvar = strtoupper($this->pi1->piVars['search']['rule']);

        if (in_array($pvar, $rules)) {
            $rule = $pvar;
        }

        $this->pi1->extConf['search_navi']['rule'] = $rule;
        $this->pi1->extConf['link_vars']['search']['rule'] = $rule;

        // extra_b indicates that the page has been visited 'b'efore
        // So that the default values should not be applied
        if ($this->pi1->piVars['search']['extra_b']) {
            $this->pi1->extConf['link_vars']['search']['extra_b'] = 1;
        }

        // Show extra
        $this->pi1->extConf['search_navi']['extra'] = true;

        if (!$this->pi1->piVars['search']['extra']) {
            $this->pi1->extConf['search_navi']['extra'] = false;
            if (!$this->pi1->piVars['search']['extra_b']) {
                $this->pi1->extConf['search_navi']['extra'] = $this->pi1->conf['searchNav.']['extra.']['def'] ? true : false;
            }
        }

        if ($this->pi1->extConf['search_navi']['extra']) {
            $this->pi1->extConf['link_vars']['search']['extra'] = 1;
        }

        // Search in abstracts
        $this->pi1->extConf['search_navi']['abstracts'] = true;

        if (!$this->pi1->piVars['search']['abstracts']) {
            $this->pi1->extConf['search_navi']['abstracts'] = false;
            if (!$this->pi1->piVars['search']['extra_b']) {
                $this->pi1->extConf['search_navi']['abstracts'] = $this->pi1->conf['searchNav.']['abstracts.']['def'] ? true : false;
            }
        }

        if ($this->pi1->extConf['search_navi']['abstracts']) {
            $this->pi1->extConf['link_vars']['search']['abstracts'] = 1;
        }

        // Search in full text
        $this->pi1->extConf['search_navi']['full_text'] = true;

        if (!$this->pi1->piVars['search']['full_text']) {
            $this->pi1->extConf['search_navi']['full_text'] = false;
            if (!$this->pi1->piVars['search']['extra_b']) {
                $this->pi1->extConf['search_navi']['full_text'] = $this->pi1->conf['searchNav.']['full_text.']['def'] ? true : false;
            }
        }

        if ($this->pi1->extConf['search_navi']['full_text']) {
            $this->pi1->extConf['link_vars']['search']['full_text'] = 1;
        }

        // Separator selection
        $this->pi1->extConf['search_navi']['all_sep'] = [
            'none' => '',
            'space' => ' ',
            'semi' => ';',
            'pipe' => '|',
        ];

        $sep_id = 'space';

        if (is_string($this->pi1->conf['searchNav.']['separator.']['def'])) {
            $sep_id = $this->pi1->conf['searchNav.']['separator.']['def'];
        }

        if (strlen($this->pi1->piVars['search']['sep']) > 0) {
            if (array_key_exists($this->pi1->piVars['search']['sep'], $this->pi1->extConf['search_navi']['all_sep'])) {
                $sep_id = $this->pi1->piVars['search']['sep'];
            }
        }

        $this->pi1->extConf['search_navi']['sep'] = $sep_id;
        $this->pi1->extConf['link_vars']['search']['sep'] = $sep_id;
    }

    /**
     */
    public function hook_filter()
    {
        $strings = [];
        if (strlen($this->pi1->extConf['search_navi']['string']) > 0) {
            $delimiter = $this->pi1->extConf['search_navi']['sep'];
            if ($delimiter == 'none') {
                $strings[] = $this->pi1->extConf['search_navi']['string'];
            } else {
                // Explode search string
                $delimiter = $this->pi1->extConf['search_navi']['all_sep'][$delimiter];
                $strings = GeneralUtility::trimExplode($delimiter, $this->pi1->extConf['search_navi']['string'], true);
            }
        }
        $filter = [];
        if (sizeof($strings) > 0) {
            // Setup search patterns
            $words = [];
            foreach ($strings as $txt) {
                $words[] = $this->pi1->referenceReader->getSearchTerm($txt, $this->pi1->extConf['charset']['upper']);
            }

            $exclude = [];
            if (!$this->pi1->extConf['search_navi']['abstracts']) {
                $exclude[] = 'abstract';
            }
            if (!$this->pi1->extConf['search_navi']['full_text']) {
                $exclude[] = 'full_text';
            }

            $all = [];
            $all['words'] = $words;
            $all['rule'] = $this->pi1->extConf['search_navi']['rule'] == 'AND' ? 1 : 0;
            $all['exclude'] = $exclude;
            $filter['all'] = $all;
        } else {
            $this->pi1->extConf['post_items'] = $this->pi1->get_ll(
                'searchNav_insert_request');
            if ($this->conf['clear_start']) {
                $filter['FALSE'] = true;
            }
        }

        if (sizeof($filter) > 0) {
            $this->pi1->extConf['filters']['search'] = $filter;
        }
    }

    /**
     * Returns content.
     *
     * @return string
     */
    public function get()
    {

        // Append hidden input
        $this->append_hidden('extra_b', true);
        if (!$this->extConf['extra']) {
            $this->append_hidden('rule', $this->extConf['rule']);
            $this->append_hidden('abstracts', $this->extConf['abstracts']);
            $this->append_hidden('full_text', $this->extConf['full_text']);
        }

        $this->view
            ->assign('label', $this->getLabel())
            ->assign('formStart', $this->getFormStart())
            ->assign('extraButton', $this->getExtraButton())
            ->assign('searchBar', $this->getSearchBar())
            ->assign('formEnd', $this->getFormEnd());

        if ($this->extConf['extra']) {
            $this->view->assign('advancedSearch', true);
            $this->getAdvancedSearch();
        }

        return $this->view->render();
    }

    /**
     * @param $key
     * @param $val
     */
    protected function append_hidden($key, $val)
    {
        if (is_bool($val)) {
            $val = $val ? '1' : '0';
        }
        $this->hidden_input[] = Utility::html_hidden_input(
            $this->pi1->prefix_pi1 . '[search][' . $key . ']',
            $val
        );
    }

    /**
     * @return string
     */
    protected function getLabel()
    {
        $label = $this->pi1->get_ll('searchNav_label');
        $label = $this->pi1->cObj->stdWrap($label, $this->conf['label.']);

        return $label;
    }

    /**
     * @return string
     */
    protected function getFormStart()
    {
        $attributes = [
            'search' => '',
        ];
        $formTag = '<form name="' . $this->pi1->prefix_pi1 . '-search_form" ';
        $formTag .= 'action="' . $this->pi1->get_link_url($attributes, false) . '"';
        $formTag .= ' method="post"';
        $formTag .= strlen($this->conf['form_class']) ? ' class="' . $this->conf['form_class'] . '"' : '';
        $formTag .= '>';

        return $formTag;
    }

    /**
     * @return string
     */
    protected function getExtraButton()
    {
        $txt = $this->pi1->get_ll('searchNav_extra');
        $txt = $this->pi1->cObj->stdWrap($txt, $this->conf['extra.']['label.']);

        $attributes = [
            'onchange' => 'this.form.submit()',
        ];

        if (strlen($this->conf['extra.']['btn_class']) > 0) {
            $attributes['class'] = $this->conf['extra.']['btn_class'];
        }

        $button = Utility::html_check_input(
            $this->pi1->prefix_pi1 . '[search][extra]',
            '1',
            $this->extConf['extra'],
            $attributes
        );

        $button = $this->pi1->cObj->stdWrap($button, $this->conf['extra.']['btn.']);

        return $this->pi1->cObj->stdWrap($txt . $button, $this->conf['extra.']['widget.']);
    }

    /**
     * @return string
     */
    protected function getSearchBar()
    {
        $size = intval($this->conf['search.']['input_size']);
        $length = intval($this->conf['search.']['input_maxlength']);

        if ($size == 0) {
            $size = 24;
        }

        if ($length == 0) {
            $size = 512;
        }

        $attributes = [
            'size' => $size,
            'maxlength' => $length,
        ];

        $value = '';

        if (strlen($this->extConf['string']) > 0) {
            $value = htmlspecialchars($this->extConf['string'], ENT_QUOTES, $this->pi1->extConf['charset']['upper']);
        }

        $button = Utility::html_text_input(
            $this->pi1->prefix_pi1 . '[search][text]', $value, $attributes
        );
        $button = $this->pi1->cObj->stdWrap($button, $this->conf['search.']['input.']);
        $sea = $button;

        // The search button
        $txt = $this->pi1->get_ll('searchNav_search');

        $attributes = [];

        if (strlen($this->conf['search.']['search_btn_class']) > 0) {
            $attributes['class'] = $this->conf['search.']['search_btn_class'];
        }

        $button = Utility::html_submit_input(
            $this->pi1->prefix_pi1 . '[action][search]',
            $txt,
            $attributes
        );
        $button = $this->pi1->cObj->stdWrap($button, $this->conf['search.']['search_btn.']);
        $sea .= $button;

        // The clear button
        $txt = $this->pi1->get_ll('searchNav_clear');

        $attributes = [];

        if (strlen($this->conf['search.']['clear_btn_class']) > 0) {
            $attributes['class'] = $this->conf['search.']['clear_btn_class'];
        }

        $button = Utility::html_submit_input(
            $this->pi1->prefix_pi1 . '[action][clear_search]',
            $txt,
            $attributes
        );
        $button = $this->pi1->cObj->stdWrap($button, $this->conf['search.']['clear_btn.']);
        $sea .= $button;

        // Search widget wrap
        return $this->pi1->cObj->stdWrap($sea, $this->conf['search.']['widget.']);
    }

    /**
     * @return string
     */
    protected function getFormEnd()
    {
        $form_end = implode(PHP_EOL, $this->hidden_input);
        $form_end .= '</form>';

        return $form_end;
    }

    /**
     */
    protected function getAdvancedSearch()
    {
        $this->view
            ->assign('abstractsButton', $this->getAbstractCheck())
            ->assign('separatorSelection', $this->getSeparatorSelection())
            ->assign('ruleSelection', $this->getRuleSelection())
            ->assign('fullTextButton', $this->getFulltextCheck());
    }

    /**
     * @return string
     */
    protected function getAbstractCheck()
    {
        $txt = $this->pi1->get_ll('searchNav_abstract');
        $txt = $this->pi1->cObj->stdWrap($txt, $this->conf['abstracts.']['label.']);

        $attributes = [
            'onchange' => 'this.form.submit()',
        ];

        if (strlen($this->conf['abstracts.']['btn_class']) > 0) {
            $attributes['class'] = $this->conf['abstracts.']['btn_class'];
        }

        $button = Utility::html_check_input(
            $this->pi1->prefix_pi1 . '[search][abstracts]',
            '1',
            $this->extConf['abstracts'],
            $attributes
        );
        $button = $this->pi1->cObj->stdWrap($button, $this->conf['abstracts.']['btn.']);

        return $this->pi1->cObj->stdWrap($txt . $button, $this->conf['abstracts.']['widget.']);
    }

    /**
     * @return string
     */
    protected function getSeparatorSelection()
    {
        $txt = $this->pi1->get_ll('searchNav_separator');
        $txt = $this->pi1->cObj->stdWrap($txt, $this->conf['separator.']['label.']);

        $types = ['space', 'semi', 'pipe'];
        $pairs = [
            'none' => $this->pi1->get_ll('searchNav_sep_none' . $types),
            'space' => '&nbsp;',
            'semi' => ';',
            'pipe' => '|',
        ];
        foreach ($types as $type) {
            $pairs[$type] .= ' (' .
                $this->pi1->get_ll('searchNav_sep_' . $type) . ')';
        }

        $attributes = [
            'name' => $this->pi1->prefix_pi1 . '[search][sep]',
            'onchange' => 'this.form.submit()',
        ];

        if (strlen($this->conf['separator.']['select_class']) > 0) {
            $attributes['class'] = $this->conf['separator.']['select_class'];
        }

        $button = Utility::html_select_input(
            $pairs,
            $this->extConf['sep'],
            $attributes
        );
        $button = $this->pi1->cObj->stdWrap($button, $this->conf['separator.']['select.']);

        return $this->pi1->cObj->stdWrap($txt . $button, $this->conf['separator.']['widget.']);
    }

    /**
     * @return string
     */
    protected function getRuleSelection()
    {
        $rule = '';
        $txt = $this->pi1->get_ll('searchNav_rule');
        $txt = $this->pi1->cObj->stdWrap($txt, $this->conf['rule.']['label.']);
        $name = $this->pi1->prefix_pi1 . '[search][rule]';

        $attributes = [
            'onchange' => 'this.form.submit()',
        ];
        if (strlen($this->conf['rule.']['btn_class']) > 0) {
            $attributes['class'] = $this->conf['rule.']['btn_class'];
        }

        // OR
        $label = $this->pi1->get_ll('searchNav_OR');
        $label = $this->pi1->cObj->stdWrap($label, $this->conf['rule.']['btn_label.']);
        $checked = ($this->extConf['rule'] == 'OR');
        $button = Utility::html_radio_input(
            $name,
            'OR',
            $checked,
            $attributes
        );
        $button = $this->pi1->cObj->stdWrap($button, $this->conf['rule.']['btn.']);
        $rule .= $label . $button;

        // AND
        $label = $this->pi1->get_ll('searchNav_AND');
        $label = $this->pi1->cObj->stdWrap($label, $this->conf['rule.']['btn_label.']);
        $checked = ($this->extConf['rule'] == 'AND');
        $button = Utility::html_radio_input(
            $name,
            'AND',
            $checked,
            $attributes
        );
        $button = $this->pi1->cObj->stdWrap($button, $this->conf['rule.']['btn.']);

        $rule .= $label . $button;

        return $this->pi1->cObj->stdWrap($txt . $rule, $this->conf['rule.']['widget.']);
    }

    /**
     * @return string
     */
    protected function getFulltextCheck()
    {
        $txt = $this->pi1->get_ll('searchNav_full_text');
        $txt = $this->pi1->cObj->stdWrap($txt, $this->conf['full_text.']['label.']);

        $attributes = [
            'onchange' => 'this.form.submit()',
        ];

        if (strlen($this->conf['full_text.']['btn_class']) > 0) {
            $attributes['class'] = $this->conf['full_text.']['btn_class'];
        }

        $button = Utility::html_check_input(
            $this->pi1->prefix_pi1 . '[search][full_text]',
            '1',
            $this->extConf['full_text'],
            $attributes
        );
        $button = $this->pi1->cObj->stdWrap($button, $this->conf['full_text.']['btn.']);

        return $this->pi1->cObj->stdWrap($txt . $button, $this->conf['full_text.']['widget.']);
    }

    /**
     * @param $index
     *
     * @return mixed
     */
    protected function sel_get_text($index)
    {
    }

    /**
     * @param $text
     * @param $index
     *
     * @return mixed
     */
    protected function sel_get_link($text, $index)
    {
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/SearchNavigation.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/SearchNavigation.php'];
}
