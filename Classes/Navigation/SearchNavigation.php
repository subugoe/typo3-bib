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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class SearchNavigation.
 */
class SearchNavigation extends Navigation
{
    public function initialize()
    {
        $this->view->setPartialRootPaths(['10' => 'EXT:bib/Resources/Private/Partials']);

        $getPostVariables = GeneralUtility::_GP('tx_bib_pi1');

        // Clear string
        if (isset($getPostVariables['action']['clear_search'])) {
            $clear = true;
        } else {
            $clear = false;
        }

        // Search string
        $p_val = $getPostVariables['search']['text'] ?? '';

        if ((strlen($p_val) > 0) && !$clear) {
            $this->configuration['search_navi']['string'] = $p_val;
            $this->configuration['link_vars']['search']['text'] = $p_val;
        }

        // Search rule
        $rule = 'AND';
        $rules = ['OR', 'AND'];

        $pvar = strtoupper($getPostVariables['search']['rule'] ?? '');

        if (in_array($pvar, $rules)) {
            $rule = $pvar;
        }

        $this->configuration['search_navi']['rule'] = $rule;
        $this->configuration['link_vars']['search']['rule'] = $rule;

        // extra_b indicates that the page has been visited 'b'efore
        // So that the default values should not be applied
        if (isset($getPostVariables['search']['extra_b'])) {
            $this->configuration['link_vars']['search']['extra_b'] = 1;
        }

        // Show extra
        $this->configuration['search_navi']['extra'] = true;

        if (!isset($getPostVariables['search']['extra'])) {
            $this->configuration['search_navi']['extra'] = false;
            if (!isset($getPostVariables['search']['extra_b'])) {
                $this->configuration['search_navi']['extra'] = $this->conf['searchNav.']['extra.']['def'] ? true : false;
            }
        }

        if ($this->configuration['search_navi']['extra']) {
            $this->configuration['link_vars']['search']['extra'] = 1;
        }

        // Search in abstracts
        $this->configuration['search_navi']['abstracts'] = true;

        if (!isset($getPostVariables['search']['abstracts'])) {
            $this->configuration['search_navi']['abstracts'] = false;
            if (!isset($getPostVariables['search']['extra_b'])) {
                $this->configuration['search_navi']['abstracts'] = $this->conf['searchNav.']['abstracts.']['def'] ? true : false;
            }
        }

        if ($this->configuration['search_navi']['abstracts']) {
            $this->configuration['link_vars']['search']['abstracts'] = 1;
        }

        // Search in full text
        $this->configuration['search_navi']['full_text'] = true;

        if (!isset($getPostVariables['search']['full_text'])) {
            $this->configuration['search_navi']['full_text'] = false;
            if (!isset($getPostVariables['search']['extra_b'])) {
                $this->configuration['search_navi']['full_text'] = $this->conf['searchNav.']['full_text.']['def'] ? true : false;
            }
        }

        if ($this->configuration['search_navi']['full_text']) {
            $this->configuration['link_vars']['search']['full_text'] = 1;
        }

        // Separator selection
        $this->configuration['search_navi']['all_sep'] = [
            'none' => '',
            'space' => ' ',
            'semi' => ';',
            'pipe' => '|',
        ];

        $sep_id = 'space';

        if (is_string($this->conf['searchNav.']['separator.']['def'])) {
            $sep_id = $this->conf['searchNav.']['separator.']['def'];
        }

        if (isset($getPostVariables['search']['sep'])) {
            if (array_key_exists($getPostVariables['search']['sep'], $this->configuration['search_navi']['all_sep'])) {
                $sep_id = $getPostVariables['search']['sep'];
            }
        }

        $this->configuration['search_navi']['sep'] = $sep_id;
        $this->configuration['link_vars']['search']['sep'] = $sep_id;
    }

    public function hook_filter()
    {
        $strings = [];
        if (strlen($this->configuration['search_navi']['string']) > 0) {
            $delimiter = $this->configuration['search_navi']['sep'];
            if ('none' === $delimiter) {
                $strings[] = $this->configuration['search_navi']['string'];
            } else {
                // Explode search string
                $delimiter = $this->configuration['search_navi']['all_sep'][$delimiter];
                $strings = GeneralUtility::trimExplode($delimiter, $this->configuration['search_navi']['string'], true);
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
            if (!$this->configuration['search_navi']['abstracts']) {
                $exclude[] = 'abstract';
            }
            if (!$this->configuration['search_navi']['full_text']) {
                $exclude[] = 'full_text';
            }

            $all = [];
            $all['words'] = $words;
            $all['rule'] = $this->configuration['search_navi']['rule'] == 'AND' ? 1 : 0;
            $all['exclude'] = $exclude;
            $filter['all'] = $all;
        } else {
            $this->configuration['post_items'] = LocalizationUtility::translate('searchNav_insert_request', 'bib');
            if ($this->conf['clear_start']) {
                $filter['FALSE'] = true;
            }
        }

        if (count($filter) > 0) {
            $this->configuration['filters']['search'] = $filter;
        }
    }

    /**
     * Returns content.
     */
    public function get(): string
    {
        $this->view->assign('configuration', $this->configuration);

        return $this->view->render();
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
