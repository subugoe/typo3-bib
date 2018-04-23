<?php

namespace Ipf\Bib\View;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
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
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class View.
 */
abstract class View
{
    const VIEW_LIST = 0;
    const VIEW_SINGLE = 1;
    const VIEW_EDITOR = 2;
    const VIEW_DIALOG = 3;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var array
     */
    protected $conf;

    public function __construct(array $configuration, array $localConfiguration)
    {
        $this->configuration = $configuration;
        $this->conf = $localConfiguration;

        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setPartialRootPaths(
            [
                10 => 'EXT:bib/Resources/Private/Partials/',
            ]
        );
        $this->view->getRequest()->setControllerExtensionName('bib');
        $this->view->getRequest()->setPluginName('pi1');
    }
}
