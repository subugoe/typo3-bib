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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class YearNavigation.
 */
class YearNavigation extends Navigation
{
    public function initialize()
    {
        if ($this->configuration['show_nav_year']) {
            $referenceReader = GeneralUtility::makeInstance(\Ipf\Bib\Utility\ReferenceReader::class,
                $this->configuration);

            // Fetch a year histogram
            $histogram = $referenceReader->getHistogram('year');
            $this->stat['year_hist'] = $histogram;
            $this->stat['years'] = array_keys($histogram);
            sort($this->stat['years']);

            $this->stat['num_all'] = array_sum($histogram);
            $this->stat['num_page'] = $this->stat['num_all'];

            // Determine the year to display
            $this->configuration['year'] = (int) date('Y'); // System year

            $exportPluginVariables = strtolower($this->piVars['year']);
            if (is_numeric($exportPluginVariables)) {
                $this->configuration['year'] = (int) $exportPluginVariables;
            } else {
                if ('all' === $exportPluginVariables) {
                    $this->configuration['year'] = $exportPluginVariables;
                }
            }

            if ('all' === $this->configuration['year']) {
                if ($this->conf['yearNav.']['selection.']['all_year_split']) {
                    $this->configuration['split_years'] = true;
                }
            }

            // The selected year has no publications so select the closest year
            if (($this->stat['num_all'] > 0) && is_numeric($this->configuration['year'])) {
                $this->configuration['year'] = \Ipf\Bib\Utility\Utility::find_nearest_int(
                    $this->configuration['year'],
                    $this->stat['years']
                );
            }
            // Append default link variable
            $this->configuration['link_vars']['year'] = $this->configuration['year'];

            if (is_numeric($this->configuration['year'])) {
                // Adjust num_page
                $this->stat['num_page'] = $this->stat['year_hist'][$this->configuration['year']];

                // Adjust year filter
                $this->configuration['filters']['br_year'] = [];
                $this->configuration['filters']['br_year']['year'] = [];
                $this->configuration['filters']['br_year']['year']['years'] = [$this->configuration['year']];
            }
        }
    }

    /**
     * Creates a text for a given index.
     */
    protected function sel_get_text(int $index): string
    {
        return (string) $this->stat['years'][$index];
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

        return '<a href="#" title="'.$title.'">'.$text.'</a>';

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
     */
    public function get(): string
    {
        $this->view
            ->assign('stat', $this->stat['years'] ?? []);

        return $this->view->render();
    }
}
