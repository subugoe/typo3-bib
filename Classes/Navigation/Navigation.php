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
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class Navigation.
 */
abstract class Navigation
{
    /**
     * @var array
     */
    protected $conf = [];

    /**
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected $view;

    /**
     * @var array
     */
    protected $stat = [];

    /**
     * @var array
     */
    protected $configuration;

    public function __construct(array $configuration, array $localConfiguration)
    {
        $this->configuration = $configuration;
        $this->conf = $localConfiguration;

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename($this->getTemplateFileFromCallingClass());
        $view->setPartialRootPaths(
            [
                10 => 'EXT:bib/Resources/Private/Partials/',
            ]
        );
        $view->getRequest()->setControllerExtensionName('bib');
        $view->getRequest()->setPluginName('pi1');
        $this->view = $view;
    }

    /**
     * @return string
     */
    protected function getTemplateFileFromCallingClass(): string
    {
        $classParts = explode('\\', get_called_class());
        $templateName = str_replace('Navigation', '', $classParts[3]);

        return sprintf('EXT:bib/Resources/Private/Templates/Navigation/%s.html', $templateName);
    }

    abstract public function get(): string;
}
