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
 * Class PreferenceNavigation.
 */
class PreferenceNavigation extends Navigation
{
    public function __construct(array $configuration, array $localConfiguration)
    {
        parent::__construct($configuration, $localConfiguration);
        $this->configuration = $this->getItemsPerPageConfiguration($this->configuration);
        $this->configuration = $this->getAbstractConfiguration($this->configuration);
        $this->configuration = $this->getKeywordConfiguration($this->configuration);
    }

    /**
     * Returns the preference navigation bar.
     */
    public function get(): string
    {
        $this->view
            ->assign('hideFields', $this->configuration['hide_fields']);

        return $this->view->render();
    }

    private function getKeywordConfiguration(array $configuration): array
    {
        $getPostVariables = GeneralUtility::_GP('tx_bib_pi1');
        $show = false;
        if (0 !== (int) $getPostVariables['show_keywords']) {
            $show = true;
        }

        $configuration['hide_fields']['keywords'] = !$show;
        $configuration['hide_fields']['tags'] = $configuration['hide_fields']['keywords'];

        return $configuration;
    }

    private function getAbstractConfiguration(array $configuration): array
    {
        $getPostVariables = GeneralUtility::_GP('tx_bib_pi1');
        $show = false;
        if (0 !== (int) $getPostVariables['show_abstracts']) {
            $show = true;
        }
        $configuration['hide_fields']['abstract'] = !$show;

        return $configuration;
    }

    private function getItemsPerPageConfiguration(array $configuration): array
    {
        $getPostVariables = GeneralUtility::_GP('tx_bib_pi1');

        // Available ipp values
        $configuration['pref_ipps'] = GeneralUtility::intExplode(',', $this->conf['prefNav.']['ipp_values']);

        // Default ipp value
        if (is_numeric($this->conf['prefNav.']['ipp_default'])) {
            $configuration['sub_page']['ipp'] = (int) $this->conf['prefNav.']['ipp_default'];
            $configuration['pref_ipp'] = $configuration['sub_page']['ipp'];
        }

        // Selected ipp value
        $itemsPerPage = $getPostVariables['items_per_page'];
        if (is_numeric($itemsPerPage)) {
            $itemsPerPage = max(intval($itemsPerPage), 0);
            if (in_array($itemsPerPage, $configuration['pref_ipps'])) {
                $configuration['sub_page']['ipp'] = $itemsPerPage;
                if ($configuration['sub_page']['ipp'] !== $configuration['pref_ipp']) {
                    $configuration['link_vars']['items_per_page'] = $configuration['sub_page']['ipp'];
                }
            }
        }

        return $configuration;
    }
}
