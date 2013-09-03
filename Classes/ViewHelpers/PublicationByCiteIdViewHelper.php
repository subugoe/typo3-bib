<?php
namespace Ipf\Bib\ViewHelpers;

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
use TYPO3\CMS\Core\FormProtection\Exception;

/**
 * Retrieve an array of the publication by providing a CiteId
 */
class PublicationByCiteIdViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Register arguments.
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('citeId', 'string', 'Citation id');
	}

	/**
	 * @return String
	 */
	public function render() {

		if ($this->hasArgument('citeId')) {
			$citationId = $this->arguments['citeId'];
		} else {
			$citationId = $this->renderChildren();
		}

		if (empty($citationId)) {
			throw new \Exception('A citation Id has to be Provided for ' . __CLASS__, 1378194424);
		} else {
			return $this->getBibliographicDataFromCitationId($citationId);
		}
	}

	/**
	 * @param string $citationId
	 * @return array
	 */
	protected function getBibliographicDataFromCitationId($citationId) {

		/** @var \Ipf\Bib\Utility\ReferenceReader $referenceReader */
		$referenceReader = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\ReferenceReader');

		if ($referenceReader->citeid_exists($citationId)) {
			$referenceReader->append_filter(array('citeid' => array('ids' => $citationId)));
		} else {
			throw new \Exception('Citation Id ' . $citationId . ' does not exist', 1378195258);
		}

		$citationUid = $referenceReader->getUidFromCitationId($citationId);
		return $referenceReader->fetch_db_pub($citationUid);
	}

}