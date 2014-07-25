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

/**
 * REST controller for the extension 'bib'
 */
class RestController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var string
	 */
	protected $defaultViewObjectName = 'TYPO3\CMS\Extbase\Mvc\View\JsonView';

	/**
	 * @var \Ipf\Bib\Domain\Repository\ReferenceRepository
	 * @inject
	 */
	protected $referenceRepository;

	/**
	 * @return void
	 */
	public function listAction() {
		if ($this->request->hasArgument('pageUid')) {
			$pageUid = intval($this->request->getArgument('pageUid'));
		} else {
			throw new \Ipf\Bib\Exception\DataException('No parameter pageUid provided in request', 1405590895);
		}
		$references = $this->referenceRepository->findBibliographyByStoragePid($pageUid);
		if (version_compare(TYPO3_version, '6.2.0', '>=')) {
			$this->view->setVariablesToRender(array('references'));
		}
		$this->view->assign('references', $references);
	}
}