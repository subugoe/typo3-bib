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
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class FlexFormUtility
 * @package Ipf\Bib\Utility
 */
class FlexFormUtility {

	/**
	 * @var \Ipf\Bib\Utility\ReferenceReader
	 */
	protected $referenceReader;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->referenceReader = GeneralUtility::makeInstance(ReferenceReader::class);
	}

	/**
	 * @param array $configuration
	 * @return mixed
	 */
	public function addFieldsToFlexForm(&$configuration) {

		$optionList = [];

		foreach ($this->referenceReader->getReferenceFields() as $key => $referenceField) {
			$optionList[] = [
					0 => LocalizationUtility::translate($this->referenceReader->getReferenceTable() . '_' . $referenceField, 'bib'),
					1 => $referenceField
			];
		}

		$configuration['items'] = array_merge($configuration['items'], $optionList);
		return $configuration;
	}

	/**
	 * @param array $configuration
	 * @return mixed
	 */
	public function addSearchFieldsToFlexForm(&$configuration) {

		$optionList = [];

		$searchFields = $this->referenceReader->getSearchFields();

		if (count($searchFields) > 0) {

			foreach ($this->referenceReader->getSearchFields() as $searchField) {
				$optionList[] = [
						0 => LocalizationUtility::translate($this->referenceReader->getSearchPrefix() . '_' . $searchField, 'bib'),
						1 => $searchField
				];
			}
		}
		$configuration['items'] = array_merge($configuration['items'], $optionList);
		return $configuration;
	}

	/**
	 * @param array $configuration
	 * @return mixed
	 */
	public function addSortFieldsToFlexForm(&$configuration) {

		/** @var \Ipf\Bib\Utility\ReferenceReader $referenceReader */
		$referenceReader = GeneralUtility::makeInstance(ReferenceReader::class);
		$optionList = [];

		foreach ($referenceReader->getSortFields() as $sortField) {
			$optionList[] = [
					0 => LocalizationUtility::translate($referenceReader->getSortPrefix() . '_' . $sortField, 'bib'),
					1 => $sortField
			];
		}

		usort($optionList, $this->sorter(0));

		$configuration['items'] = array_merge($configuration['items'], $optionList);

		return $configuration;
	}

	/**
	 * @param $key
	 * @return \Closure
	 */
	protected function sorter($key) {
		return function ($a, $b) use ($key) {
			return strnatcmp($a[$key], $b[$key]);
		};
	}

}
