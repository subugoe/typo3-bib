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

use Ipf\Bib\Modes\Display;
use Ipf\Bib\Modes\Statistics;
use Ipf\Bib\Utility\ReferenceReader;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class StatisticsNavigation.
 */
class StatisticsNavigation extends Navigation
{
    public function get(): string
    {
        // Setup mode
        $d_mode = (int) $this->configuration['d_mode'];
        $mode = (int) $this->configuration['stat_mode'];
        if (Display::D_Y_NAV !== $d_mode) {
            if (Statistics::STAT_YEAR_TOTAL == $mode) {
                $mode = Statistics::STAT_TOTAL;
            }
        } else {
            if (!is_numeric($this->configuration['year'])) {
                $mode = Statistics::STAT_TOTAL;
            }
        }

        // Setup values
        $year = (int) $this->configuration['year'];
        $histogram = GeneralUtility::makeInstance(ReferenceReader::class, $this->configuration)->getHistogram('year');

        $this->view
            ->assign('mode', $mode)
            ->assign('total', ReferenceReader::getNumberOfPublications())
            ->assign('yearly', $histogram[$year]);

        return $this->view->render();
    }
}
