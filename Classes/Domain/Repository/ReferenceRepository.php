<?php
namespace Ipf\Bib\Domain\Repository;

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
 * Reference repository
 */
class ReferenceRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	public function findBibliographyByStoragePid($storagePid) {
		$query = $this->createQuery();

		$query->getQuerySettings()->setRespectStoragePage(FALSE);
				$query->matching(
		  $query->equals('pid', $storagePid)
		);
		return $query->execute();
	}

}