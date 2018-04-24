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

use Ipf\Bib\Utility\ReferenceReader;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Reference repository.
 */
class ReferenceRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $db;

    /**
     * @var array
     */
    private $storagePid;

    /**
     * @param $storagePid
     *
     * @return array
     */
    public function findBibliographyByStoragePid(int $storagePid)
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setStoragePageIds([$storagePid]);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(ReferenceReader::REFERENCE_TABLE);
        $result = $queryBuilder
            ->select('r.*', 'au.forename', 'au.surname')
            ->from(ReferenceReader::REFERENCE_TABLE, 'r')
            ->leftJoin('r', ReferenceReader::AUTHORSHIP_TABLE, 'aus', 'r.uid = aus.pub_id')
            ->leftJoin('r', ReferenceReader::AUTHOR_TABLE, 'au', 'aus.author_id = au.uid')
            ->where($queryBuilder->expr()->eq('r.pid', (int) $storagePid))
            ->andWhere($queryBuilder->expr()->eq('r.deleted', 0))
            ->andWhere($queryBuilder->expr()->eq('r.hidden', 0))
            ->groupBy('r.uid')
            ->execute()
            ->fetchAll();

        return $result;
    }

    public function setStoragePid(array $pid)
    {
        $this->storagePid = $pid;
    }

    public function findAll()
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setStoragePageIds($this->storagePid);
        $this->setDefaultQuerySettings($querySettings);

        return $this->createQuery()->execute();
    }
}
