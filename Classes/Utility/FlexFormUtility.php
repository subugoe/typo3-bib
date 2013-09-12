<?php
namespace Ipf\Bib\Utility;

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
 * Description
 */
class FlexFormUtility {

	public function addFieldsToFlexForm(&$configuration) {

		/** @var \Ipf\Bib\Utility\ReferenceReader $referenceReader */
		$referenceReader = GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\ReferenceReader');
		$optionList = array();

		foreach($referenceReader->getReferenceFields() as $key => $referenceField) {
			$optionList[] = array(
				0 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_bib_domain_model_reference_' . $referenceField, 'bib'),
				1 => $referenceField
			);
		}

		$configuration['items'] = array_merge($configuration['items'], $optionList);
		return $configuration;
	}

} 