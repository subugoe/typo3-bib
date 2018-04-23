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
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class PageNavigation extends Navigation
{
    /**
     * Returns content.
     */
    public function get(): string
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        $selectionConfiguration = is_array($this->conf['pageNav.']['selection.']) ? $this->conf['pageNav.']['selection.'] : [];
        $navigationConfiguration = is_array($this->conf['pageNav.']['navigation.']) ? $this->conf['pageNav.']['navigation.'] : [];

        // The data
        $subPage = $this->configuration['sub_page'];

        // The label
        $label = $contentObjectRenderer->stdWrap(
            LocalizationUtility::translate('pageNav_label', 'bib'),
            $this->conf['pageNav.']['label.']
        );

        // The previous/next buttons
        $nav_prev = LocalizationUtility::translate('pageNav_previous', 'bib');
        if ($subPage['current'] > 0) {
            $page = max($subPage['current'] - 1, 0);
            $title = LocalizationUtility::translate('pageNav_previousLinkTitle', 'bib');
            $nav_prev = $this->pi1->get_link(
                $nav_prev,
                [
                    'page' => $page,
                ],
                true,
                [
                    'title' => $title,
                ]
            );
        }

        $nav_next = LocalizationUtility::translate('pageNav_next', 'bib');
        if ($subPage['current'] < $subPage['max']) {
            $page = min($subPage['current'] + 1, $subPage['max']);
            $title = LocalizationUtility::translate('pageNav_nextLinkTitle', 'bib');
            $nav_next = $this->pi1->get_link(
                $nav_next,
                [
                    'page' => $page,
                ],
                true,
                [
                    'title' => $title,
                ]
            );
        }

        // Wrap
        $nav_prev = $contentObjectRenderer->stdWrap($nav_prev, $navigationConfiguration['previous.']);
        $nav_next = $contentObjectRenderer->stdWrap($nav_next, $navigationConfiguration['next.']);

        $navigationSeparator = '&nbsp;';
        if (array_key_exists('separator', $navigationConfiguration)) {
            $navigationSeparator = $navigationConfiguration['separator'];
        }
        if (is_array($navigationConfiguration['separator.'])) {
            $navigationSeparator = $contentObjectRenderer->stdWrap(
                $navigationSeparator,
                $navigationConfiguration['separator.']
            );
        }

        // Replace separator
        $nav_prev = str_replace('###SEPARATOR###', $navigationSeparator, $nav_prev);
        $nav_next = str_replace('###SEPARATOR###', $navigationSeparator, $nav_next);

        // Create selection
        $indices = [0, $subPage['current'], $subPage['max']];

        // Number of pages to display in the selection
        $numSel = 5;
        if (array_key_exists('pages', $selectionConfiguration)) {
            $numSel = abs(intval($selectionConfiguration['pages']));
        }

        $this->view
            ->assign('selection', $this->selection($selectionConfiguration, $indices, $numSel))
            ->assign('label', $label)
            ->assign('forward', $nav_next)
            ->assign('backward', $nav_prev);

        return $this->view->render();
    }
}
