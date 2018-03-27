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

/**
 * Class YearNavigation.
 */
class YearNavigation extends Navigation
{
    /**
     * Intialize.
     *
     * @param \tx_bib_pi1 $pi1
     */
    public function initialize($pi1)
    {
        parent::initialize($pi1);
        if (is_array($pi1->conf['yearNav.'])) {
            $this->conf = &$pi1->conf['yearNav.'];
        }

        $this->prefix = 'YEAR_NAVI';
        $this->sel_link_title = $this->languageService->getLL('yearNav_yearLinkTitle', '%y');
    }

    /**
     * Creates a text for a given index.
     *
     * @param int $index
     *
     * @return string
     */
    protected function sel_get_text($index)
    {
        return strval($this->pi1->stat['years'][$index]);
    }

    /**
     * Creates a link for the selection.
     *
     * @param string $text
     * @param int    $ii
     *
     * @return string
     */
    protected function sel_get_link($text, $ii)
    {
        $title = str_replace('%y', $text, $this->sel_link_title);
        $lnk = $this->pi1->get_link(
            $text,
            [
                'year' => $text,
                'page' => '',
            ],
            true,
            [
                'title' => $title,
            ]
        );

        return $lnk;
    }

    /**
     * Returns content.
     *
     * @return string
     */
    protected function get()
    {
        // The label
        $label = $this->languageService->getLL('yearNav_label');
        $label = $this->pi1->cObj->stdWrap($label, $this->conf['label.']);

        $this->view
            ->assign('label', $label)
            ->assign('selection', $this->getYearSelection())
            ->assign('selectForm', $this->getYearSelectionForm());

        return $this->view->render();
    }

    /**
     * @return string
     */
    protected function getYearSelection(): string
    {
        $selectionConfiguration = is_array($this->conf['selection.']) ? $this->conf['selection.'] : [];

        if (count($this->pi1->stat['years']) > 0) {
            // The all link
            $delimiter = ' - ';
            if (isset($selectionConfiguration['all_sep'])) {
                $delimiter = $selectionConfiguration['all_sep'];
            }
            $delimiter = $this->pi1->cObj->stdWrap($delimiter, $selectionConfiguration['all_sep.']);

            $txt = $this->languageService->getLL('yearNav_all_years', 'All');
            if (is_numeric($this->pi1->extConf['year'])) {
                $txt = $this->pi1->get_link($txt, ['year' => 'all']);
            } else {
                $txt = $this->pi1->cObj->stdWrap($txt, $selectionConfiguration['current.']);
            }

            $cur = array_search($this->pi1->extConf['year'], $this->pi1->stat['years']);
            if (false === $cur) {
                $cur = -1;
            }
            $indices = [0, $cur, count($this->pi1->stat['years']) - 1];

            $numSel = 3;
            if (array_key_exists('years', $selectionConfiguration)) {
                $numSel = abs(intval($selectionConfiguration['years']));
            }

            $selection = $this->selection($selectionConfiguration, $indices, $numSel);

            return $this->pi1->cObj->stdWrap($txt.$delimiter.$selection, $selectionConfiguration['all_wrap.']);
        }

        return '';
    }

    /**
     * @return string
     */
    protected function getYearSelectionForm()
    {
        $selectForm = '';
        if (count($this->pi1->stat['years']) > 0) {
            $name = $this->pi1->prefix_pi1.'-year_select_form';
            $action = $this->pi1->get_link_url(['year' => ''], false);
            $selectForm .= '<form name="'.$name.'" ';
            $selectForm .= 'action="'.$action.'"';
            $selectForm .= ' method="post"';
            $selectForm .= strlen($this->conf['form_class']) ? ' class="'.$this->conf['form_class'].'"' : '';
            $selectForm .= '>';

            $pairs = ['all' => $this->languageService->getLL('yearNav_all_years', 'All')];
            if (count($this->pi1->stat['years']) > 0) {
                foreach (array_reverse($this->pi1->stat['years']) as $y) {
                    $pairs[$y] = $y;
                }
            } else {
                $year = strval(intval(date('Y')));
                $pairs = [$year => $year];
            }

            $attributes = [
                'name' => $this->pi1->prefix_pi1.'[year]',
                'onchange' => 'this.form.submit()',
            ];
            if (strlen($this->conf['select_class']) > 0) {
                $attributes['class'] = $this->conf['select_class'];
            }
            $button = Utility::html_select_input(
                $pairs,
                $year,
                $attributes
            );
            $button = $this->pi1->cObj->stdWrap($button, $this->conf['select.']);
            $selectForm .= $button;

            $attributes = [];
            if (strlen($this->conf['go_btn_class']) > 0) {
                $attributes['class'] = $this->conf['go_btn_class'];
            }
            $button = Utility::html_submit_input(
                $this->pi1->prefix_pi1.'[action][select_year]',
                $this->languageService->getLL('button_go'),
                $attributes
            );
            $button = $this->pi1->cObj->stdWrap($button, $this->conf['go_btn.']);
            $selectForm .= $button;

            // End of form
            $selectForm .= '</form>';
        }

        return $this->pi1->cObj->stdWrap($selectForm, $this->conf['form.']);
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/YearNavigation.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/YearNavigation.php'];
}
