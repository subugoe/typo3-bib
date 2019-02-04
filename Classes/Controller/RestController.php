<?php

namespace Ipf\Bib\Controller;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
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
use Ipf\Bib\Domain\Repository\ReferenceRepository;
use Ipf\Bib\Exception\DataException;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;

/**
 * REST controller for the extension 'bib'.
 */
class RestController extends ActionController
{
    /**
     * @var string
     */
    protected $defaultViewObjectName = JsonView::class;

    /**
     * @var \Ipf\Bib\Domain\Repository\ReferenceRepository
     */
    protected $referenceRepository;

    public function __construct(ReferenceRepository $referenceRepository)
    {
        parent::__construct();
        $this->referenceRepository = $referenceRepository;
    }

    public function listAction()
    {
        if ($this->request->hasArgument('pageUid')) {
            $pageUid = intval($this->request->getArgument('pageUid'));
        } else {
            throw new DataException('No parameter pageUid provided in request', 1405590895);
        }
        $references = $this->referenceRepository->findBibliographyByStoragePid($pageUid);

        $this->view->setVariablesToRender(['references']);

        $this->view->assign('references', $references);
    }
}
