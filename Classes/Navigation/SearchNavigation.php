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

use Ipf\Bib\Utility\ReferenceReader;
use Ipf\Bib\Utility\Utility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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

    public function initialize(array $configuration)
    {
        if (is_array($configuration['searchNav.'])) {
            $this->conf = $configuration['searchNav.'];
        }

        $this->extConf = $configuration;
    }

    /**
     * Hook in to pi1 at init stage.
     */
    public function hook_init(): array
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
            $this->extConf['search_navi']['string'] = $p_val;
            $this->extConf['link_vars']['search']['text'] = $p_val;
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

        $this->extConf['search_navi']['rule'] = $rule;
        $this->extConf['link_vars']['search']['rule'] = $rule;

        // extra_b indicates that the page has been visited 'b'efore
        // So that the default values should not be applied
        if ($this->pi1->piVars['search']['extra_b']) {
            $this->extConf['link_vars']['search']['extra_b'] = 1;
        }

        // Show extra
        $this->extConf['search_navi']['extra'] = true;

        if (!$this->pi1->piVars['search']['extra']) {
            $this->extConf['search_navi']['extra'] = false;
            if (!$this->pi1->piVars['search']['extra_b']) {
                $this->extConf['search_navi']['extra'] = $this->pi1->conf['searchNav.']['extra.']['def'] ? true : false;
            }
        }

        if ($this->extConf['search_navi']['extra']) {
            $this->extConf['link_vars']['search']['extra'] = 1;
        }

        // Search in abstracts
        $this->extConf['search_navi']['abstracts'] = true;

        if (!$this->pi1->piVars['search']['abstracts']) {
            $this->extConf['search_navi']['abstracts'] = false;
            if (!$this->pi1->piVars['search']['extra_b']) {
                $this->extConf['search_navi']['abstracts'] = $this->pi1->conf['searchNav.']['abstracts.']['def'] ? true : false;
            }
        }

        if ($this->extConf['search_navi']['abstracts']) {
            $this->extConf['link_vars']['search']['abstracts'] = 1;
        }

        // Search in full text
        $this->extConf['search_navi']['full_text'] = true;

        if (!$this->pi1->piVars['search']['full_text']) {
            $this->extConf['search_navi']['full_text'] = false;
            if (!$this->pi1->piVars['search']['extra_b']) {
                $this->extConf['search_navi']['full_text'] = $this->pi1->conf['searchNav.']['full_text.']['def'] ? true : false;
            }
        }

        if ($this->extConf['search_navi']['full_text']) {
            $this->extConf['link_vars']['search']['full_text'] = 1;
        }

        // Separator selection
        $this->extConf['search_navi']['all_sep'] = [
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
            if (array_key_exists($this->pi1->piVars['search']['sep'], $this->extConf['search_navi']['all_sep'])) {
                $sep_id = $this->pi1->piVars['search']['sep'];
            }
        }

        $this->extConf['search_navi']['sep'] = $sep_id;
        $this->extConf['link_vars']['search']['sep'] = $sep_id;

        return $this->extConf;
    }

    public function hook_filter(array $configuration): array
    {
        $strings = [];
        if (strlen($configuration['search_navi']['string']) > 0) {
            $delimiter = $configuration['search_navi']['sep'];
            if ('none' == $delimiter) {
                $strings[] = $configuration['search_navi']['string'];
            } else {
                // Explode search string
                $delimiter = $configuration['search_navi']['all_sep'][$delimiter];
                $strings = GeneralUtility::trimExplode($delimiter, $configuration['search_navi']['string'], true);
            }
        }
        $filter = [];
        if (count($strings) > 0) {
            // Setup search patterns
            $words = [];
            foreach ($strings as $txt) {
                $words[] = ReferenceReader::getSearchTerm($txt);
            }

            $exclude = [];
            if (!$configuration['search_navi']['abstracts']) {
                $exclude[] = 'abstract';
            }
            if (!$configuration['search_navi']['full_text']) {
                $exclude[] = 'full_text';
            }

            $all = [];
            $all['words'] = $words;
            $all['rule'] = $configuration['search_navi']['rule'] == 'AND' ? 1 : 0;
            $all['exclude'] = $exclude;
            $filter['all'] = $all;
        } else {
            $configuration['post_items'] = LocalizationUtility::translate('searchNav_insert_request', 'bib');
            if ($this->conf['clear_start']) {
                $filter['FALSE'] = true;
            }
        }

        if (count($filter) > 0) {
            $configuration['filters']['search'] = $filter;
        }

        return $configuration;
    }

    /**
     * Returns content.
     */
    public function get(): string
    {
        $this->view
            ->assign('configuration', $this->configuration)
            ->assign('searchBar', $this->getSearchBar());

        if ($this->extConf['search_navi']['extra']) {
            $this->view->assign('advancedSearch', true);
            $this->getAdvancedSearch();
        }

        return $this->view->render();
    }

    /**
     * @return string
     */
    protected function getSearchBar()
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        $size = (int) $this->conf['search.']['input_size'];
        $length = (int) $this->conf['search.']['input_maxlength'];

        if (0 === $size) {
            $size = 24;
        }

        if (0 === $length) {
            $size = 512;
        }

        $attributes = [
            'size' => $size,
            'maxlength' => $length,
        ];

        $value = '';

        if (strlen($this->extConf['search_navi']['string']) > 0) {
            $value = htmlspecialchars($this->extConf['search_navi']['string'], ENT_QUOTES);
        }

        $button = Utility::html_text_input(
            'tx_bib_pi1[search][text]',
            $value,
            $attributes
        );
        $button = $contentObjectRenderer->stdWrap($button, $this->conf['search.']['input.']);
        $sea = $button;

        // The search button
        $txt = LocalizationUtility::translate('searchNav_search', 'bib');

        $attributes = [];

        if (strlen($this->conf['search.']['search_btn_class']) > 0) {
            $attributes['class'] = $this->conf['search.']['search_btn_class'];
        }

        $button = Utility::html_submit_input(
            'tx_bib_pi1[action][search]',
            $txt,
            $attributes
        );
        $button = $contentObjectRenderer->stdWrap($button, $this->conf['search.']['search_btn.']);
        $sea .= $button;

        // The clear button
        $txt = LocalizationUtility::translate('searchNav_clear', 'bib');

        $attributes = [];

        if (strlen($this->conf['search.']['clear_btn_class']) > 0) {
            $attributes['class'] = $this->conf['search.']['clear_btn_class'];
        }

        $button = Utility::html_submit_input(
            'tx_bib_pi1[action][clear_search]',
            $txt,
            $attributes
        );
        $button = $contentObjectRenderer->stdWrap($button, $this->conf['search.']['clear_btn.']);
        $sea .= $button;

        // Search widget wrap
        return $contentObjectRenderer->stdWrap($sea, $this->conf['search.']['widget.']);
    }

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
        $txt = $this->pi1->pi_getLL('searchNav_abstract');
        $txt = $this->pi1->cObj->stdWrap($txt, $this->conf['abstracts.']['label.']);

        $attributes = [
            'onchange' => 'this.form.submit()',
        ];

        if (strlen($this->conf['abstracts.']['btn_class']) > 0) {
            $attributes['class'] = $this->conf['abstracts.']['btn_class'];
        }

        $button = Utility::html_check_input(
            'tx_bib_pi1[search][abstracts]',
            '1',
            $this->extConf['search_navi']['abstracts'],
            $attributes
        );
        $button = $this->pi1->cObj->stdWrap($button, $this->conf['abstracts.']['btn.']);

        return $this->pi1->cObj->stdWrap($txt.$button, $this->conf['abstracts.']['widget.']);
    }

    /**
     * @return string
     */
    protected function getSeparatorSelection()
    {
        $txt = $this->pi1->pi_getLL('searchNav_separator');
        $txt = $this->pi1->cObj->stdWrap($txt, $this->conf['separator.']['label.']);

        $types = ['space', 'semi', 'pipe'];
        $pairs = [
            'none' => $this->pi1->pi_getLL('searchNav_sep_none'.$types),
            'space' => '&nbsp;',
            'semi' => ';',
            'pipe' => '|',
        ];
        foreach ($types as $type) {
            $pairs[$type] .= ' ('.
                $this->pi1->pi_getLL('searchNav_sep_'.$type).')';
        }

        $attributes = [
            'name' => 'tx_bib_pi1[search][sep]',
            'onchange' => 'this.form.submit()',
        ];

        if (strlen($this->conf['separator.']['select_class']) > 0) {
            $attributes['class'] = $this->conf['separator.']['select_class'];
        }

        $button = Utility::html_select_input(
            $pairs,
            $this->extConf['search_navi']['sep'],
            $attributes
        );
        $button = $this->pi1->cObj->stdWrap($button, $this->conf['separator.']['select.']);

        return $this->pi1->cObj->stdWrap($txt.$button, $this->conf['separator.']['widget.']);
    }

    /**
     * @return string
     */
    protected function getRuleSelection()
    {
        $rule = '';
        $txt = $this->pi1->pi_getLL('searchNav_rule');
        $txt = $this->pi1->cObj->stdWrap($txt, $this->conf['rule.']['label.']);
        $name = 'tx_bib_pi1[search][rule]';

        $attributes = [
            'onchange' => 'this.form.submit()',
        ];
        if (strlen($this->conf['rule.']['btn_class']) > 0) {
            $attributes['class'] = $this->conf['rule.']['btn_class'];
        }

        // OR
        $label = $this->pi1->pi_getLL('searchNav_OR');
        $label = $this->pi1->cObj->stdWrap($label, $this->conf['rule.']['btn_label.']);
        $checked = ('OR' == $this->extConf['search_navi']['rule']);
        $button = Utility::html_radio_input(
            $name,
            'OR',
            $checked,
            $attributes
        );
        $button = $this->pi1->cObj->stdWrap($button, $this->conf['rule.']['btn.']);
        $rule .= $label.$button;

        // AND
        $label = $this->pi1->pi_getLL('searchNav_AND');
        $label = $this->pi1->cObj->stdWrap($label, $this->conf['rule.']['btn_label.']);
        $checked = ('AND' == $this->extConf['search_navi']['rule']);
        $button = Utility::html_radio_input(
            $name,
            'AND',
            $checked,
            $attributes
        );
        $button = $this->pi1->cObj->stdWrap($button, $this->conf['rule.']['btn.']);

        $rule .= $label.$button;

        return $this->pi1->cObj->stdWrap($txt.$rule, $this->conf['rule.']['widget.']);
    }

    /**
     * @return string
     */
    protected function getFulltextCheck()
    {
        $txt = $this->pi1->pi_getLL('searchNav_full_text');
        $txt = $this->pi1->cObj->stdWrap($txt, $this->conf['full_text.']['label.']);

        $attributes = [
            'onchange' => 'this.form.submit()',
        ];

        if (strlen($this->conf['full_text.']['btn_class']) > 0) {
            $attributes['class'] = $this->conf['full_text.']['btn_class'];
        }

        $button = Utility::html_check_input(
            'tx_bib_pi1[search][full_text]',
            '1',
            $this->extConf['search_navi']['full_text'],
            $attributes
        );
        $button = $this->pi1->cObj->stdWrap($button, $this->conf['full_text.']['btn.']);

        return $this->pi1->cObj->stdWrap($txt.$button, $this->conf['full_text.']['widget.']);
    }

    protected function sel_get_text(int $index): string
    {
        return '';
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
