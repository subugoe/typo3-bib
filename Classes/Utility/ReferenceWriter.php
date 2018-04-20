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
use Ipf\Bib\Exception\DataException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class provides functions to write or manipulate
 * publication reference entries.
 */
class ReferenceWriter
{
    /**
     * @var \Ipf\Bib\Utility\ReferenceReader
     */
    public $referenceReader;

    /**
     * @var bool
     */
    public $clear_cache = false;

    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $db;

    /**
     * @var array
     */
    private $configuration;

    /**
     * constructor.
     */
    public function __construct(array $configuration)
    {
        $this->db = $GLOBALS['TYPO3_DB'];
        $this->referenceReader = GeneralUtility::makeInstance(ReferenceReader::class, $configuration);
        $this->configuration = $configuration;
    }

    /**
     * Clears the page cache of all selected pages.
     */
    private function clear_page_cache()
    {
        if ($this->clear_cache) {
            /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $clear_cache = [];

            $be_user = $GLOBALS['BE_USER'];
            if (is_object($be_user) || is_array($be_user->user)) {
                $dataHandler->start([], [], $be_user);
                // Find storage cache clear requests
                foreach ($this->configuration['pid_list'] as $pid) {
                    $tsc = $dataHandler->getTCEMAIN_TSconfig($pid);
                    if (is_array($tsc) && isset($tsc['clearCacheCmd'])) {
                        $clear_cache[] = $tsc['clearCacheCmd'];
                    }
                }
            } else {
                $dataHandler->admin = 1;
            }

            // Clear this page cache
            $clear_cache[] = strval($GLOBALS['TSFE']->id);

            foreach ($clear_cache as $cache) {
                $dataHandler->clear_cacheCmd($cache);
            }
        }
    }

    /**
     * This function updates a publication with all data
     * found in the HTTP request.
     *
     * @throws DataException
     *
     * @param array $publication
     *
     * @return bool TRUE on error FALSE otherwise
     */
    public function savePublication($publication)
    {
        if (!is_array($publication)) {
            throw new DataException(
                'Publication is not a valid array',
                1378977181
            );
        }

        $new = false;
        $uid = -1;

        // Fetch reference from DB
        $pub_db = null;
        if (is_numeric($publication['uid'])) {
            $pub_db = $this->referenceReader->getPublicationDetails((int) $publication['uid']);
            if (is_array($pub_db)) {
                $uid = $pub_db->getUid();
            } else {
                throw new DataException(sprintf(
                    'The publication reference with uid %s could not be updated'.
                    ' because it does not exist in the database (anymore?).', $publication['uid']),
                    1378973300
                );
            }
        }

        // Acquire the storage folder pid if it is not given
        if (!is_numeric($publication['pid'])) {
            if (is_array($pub_db)) {
                $publication['pid'] = (int) $pub_db['pid'];
            } else {
                $publication['pid'] = $this->configuration['pid_list'][0];
            }
        }

        // Check if the pid is in the allowed list
        if (!in_array($publication['pid'], $this->configuration['pid_list'])) {
            throw new DataException(
                'The given storage folder (pid='.strval($publication['pid']).
                ') is not in the list of allowed publication storage folders',
                1378973653
            );
        }

        $referenceRow = [];
        // Copy reference fields
        foreach (ReferenceReader::$referenceFields as $field) {
            switch ($field) {
                default:
                    if (array_key_exists($field, $publication)) {
                        $referenceRow[$field] = $publication[$field];
                    }
            }
        }

        // Add TYPO3 fields
        $referenceRow['pid'] = (int) $publication['pid'];
        $referenceRow['tstamp'] = time();
        $referenceRow['hidden'] = intval($publication['hidden']);

        if ($uid >= 0) {
            if ($publication['mod_key'] === $pub_db['mod_key']) {
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(ReferenceReader::REFERENCE_TABLE);

                $ret = $connection->update(ReferenceReader::REFERENCE_TABLE, $referenceRow, ['uid' => $uid]);

                if (0 === $ret) {
                    throw new DataException(sprintf('A publication reference could not be updated. uid: %d', $uid), 1378973748);
                }
            } else {
                throw new DataException(
                    'The publication reference could not be updated'.
                    ' because the modification key does not match.'.
                    ' Maybe someone edited this reference meanwhile.',
                    1378973836
                );
            }
        } else {
            $new = true;

            // Creation user id if available
            $cruser_id = 0;
            $be_user = $GLOBALS['BE_USER'];
            if (is_object($be_user) && is_array($be_user->user)) {
                $cruser_id = intval($be_user->user['uid']);
            }

            $referenceRow['crdate'] = $referenceRow['tstamp'];
            $referenceRow['cruser_id'] = $cruser_id;

            $this->db->exec_INSERTquery(
                ReferenceReader::REFERENCE_TABLE,
                $referenceRow
            );

            $uid = $this->db->sql_insert_id();
            if (!($uid > 0)) {
                throw new DataException(
                    'A publication reference could not be inserted into the database',
                    1378973908
                );
            }
        }

        if (($uid > 0) && (count($publication['authors']) > 0)) {
            try {
                $this->savePublicationAuthors($uid, $publication['pid'], $publication['authors']);
            } catch (DataException $e) {
                throw new DataException($e->getMessage(), $e->getCode());
            }
        }

        if ($new) {
            self::log(sprintf('A new publication reference was inserted (pid=%d).', $publication['pid']), $uid);
        } else {
            self::log('A publication reference was modified', $uid);
        }

        $this->clear_page_cache();

        return false;
    }

    /**
     * Saves the authors of a publication.
     *
     * @throws DataException
     *
     * @param int   $pub_uid
     * @param int   $pid
     * @param array $authors
     */
    protected function savePublicationAuthors(int $pub_uid, int $pid, array $authors)
    {
        // Fetches missing author uids and
        // inserts new authors on demand
        $sort = 0;
        foreach ($authors as &$author) {
            // Set new sorting value
            ++$sort;
            $author['sorting'] = $sort;

            if (!is_numeric($author['uid'])) {
                $uids = $this->referenceReader->fetch_author_uids($author, $pid);

                if (count($uids) > 0) {
                    $author['uid'] = $uids[0]['uid'];
                } else {
                    // Insert missing author
                    $ia = $author;
                    $ia['pid'] = intval($pid);

                    $author['uid'] = $this->insertAuthor($ia);
                    if (!(intval($author['uid']) > 0)) {
                        throw new DataException(
                            'An author '.$ia['surename'].'  could not be inserted into the database',
                            1378976979
                        );
                    }
                }
            }
        }

        $db_aships = $this->referenceReader->getAuthorships(['pub_id' => $pub_uid]);

        $as_delete = [];
        $as_new = count($authors) - count($db_aships);
        if ($as_new < 0) {
            // This deletes the first authorships
            $as_new = abs($as_new);
            for ($ii = 0; $ii < $as_new; ++$ii) {
                $as_delete[] = (int) $db_aships[$ii]['uid'];
            }

            $this->deleteAuthorships($as_delete);
            $db_aships = array_slice($db_aships, $as_new);

            $as_new = 0;
        }
        $as_present = count($authors) - $as_new;

        // Inserts new and updates old authorships
        $authorsSize = count($authors);
        for ($ii = 0; $ii < $authorsSize; ++$ii) {
            $author = &$authors[$ii];
            if (is_numeric($author['uid'])) {
                $as = [];
                $as['pid'] = intval($pid);
                $as['pub_id'] = intval($pub_uid);
                $as['author_id'] = intval($author['uid']);
                $as['sorting'] = $author['sorting'];

                if ($ii < $as_present) {
                    // There are present authorships - Update authorship
                    $as_uid = $db_aships[$ii]['uid'];

                    $ret = $this->db->exec_UPDATEquery(
                        ReferenceReader::AUTHORSHIP_TABLE,
                        'uid='.intval($as_uid),
                        $as
                    );

                    if (false == $ret) {
                        throw new DataException(
                            'An authorship could not be updated uid='.strval($as_uid),
                            1378977083
                        );
                    }
                } else {
                    // No more present authorships - Insert authorship
                    $as_uid = $this->db->exec_INSERTquery(
                        ReferenceReader::AUTHORSHIP_TABLE,
                        $as
                    );
                    if (!(intval($as_uid) > 0)) {
                        throw new DataException(
                            'An authorship could not be inserted into the database',
                            1378977350
                        );
                    }
                }
            }
        }
    }

    /**
     * Inserts an author.
     *
     * @param array $author
     *
     * @return int The uid of the inserted author
     */
    protected function insertAuthor($author)
    {
        $author['pid'] = (int) $author['pid'];

        // Creation user id if available
        $cruser_id = 0;
        $backendUser = $GLOBALS['BE_USER'];
        if (is_object($backendUser) && is_array($backendUser->user)) {
            $cruser_id = (int) $backendUser->user['uid'];
        }

        // field not present in the database causes write fails
        unset($author['sorting']);

        $author['tstamp'] = time();
        $author['crdate'] = time();
        $author['cruser_id'] = $cruser_id;

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(ReferenceReader::AUTHOR_TABLE);
        $queryBuilder->insert(
            ReferenceReader::AUTHOR_TABLE,
            $author
        );

        $authorUid = $pageUid = (int) $queryBuilder->lastInsertId(ReferenceReader::AUTHOR_TABLE);

        return $authorUid;
    }

    /**
     * Deletes some authorships.
     *
     * @param array $uids
     */
    protected function deleteAuthorships(array $uids)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(ReferenceReader::AUTHORSHIP_TABLE);
        $queryBuilder
            ->update(ReferenceReader::AUTHORSHIP_TABLE)
            ->where($queryBuilder->expr()->in('uid', $uids))
            ->andWhere($queryBuilder->expr()->eq('deleted', 0))
            ->set('deleted', 1)
            ->execute();
    }

    /**
     * Sets or unsets the hidden flag in the database entry.
     *
     * @param int  $uid
     * @param bool $hidden
     */
    public function hidePublication(int $uid, bool $hidden = true)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(ReferenceReader::REFERENCE_TABLE);
        $queryBuilder
            ->update(ReferenceReader::REFERENCE_TABLE)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->quote($uid)))
            ->set('hidden', ($hidden ? 1 : 0))
            ->set('tstamp', time())
            ->execute();

        self::log('A publication reference was '.($hidden ? 'hidden' : 'revealed'), $uid);
        $this->clear_page_cache();
    }

    /**
     * Sets the deleted flag in the database entry.
     * Only the reference and the authorships get deleted.
     * The author stays untouched even if he/her has no authorship
     * after this anymore.
     *
     * @throws DataException
     *
     * @param int    $uid
     * @param string $mod_key
     */
    public function deletePublication(int $uid, string $mod_key)
    {
        $deleted = 1;

        $db_pub = $this->referenceReader->getPublicationDetails($uid);
        if (!empty($db_pub->getModificationKey())) {
            if ($db_pub->getModificationKey() === $mod_key) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(ReferenceReader::AUTHORSHIP_TABLE);
                $queryBuilder
                    ->update(ReferenceReader::AUTHORSHIP_TABLE)
                    ->where($queryBuilder->expr()->eq('pub_id', $uid))
                    ->andWhere($queryBuilder->expr()->eq('deleted', 0))
                    ->set('deleted', $deleted)
                    ->execute();

                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(ReferenceReader::REFERENCE_TABLE);
                $queryBuilder
                    ->update(ReferenceReader::REFERENCE_TABLE)
                    ->where($queryBuilder->expr()->eq('uid', $uid))
                    ->andWhere($queryBuilder->expr()->eq('deleted', 0))
                    ->set('deleted', 0)
                    ->set('tstamp', time())
                    ->execute();

                $this->clear_page_cache();

                self::log('A publication reference was deleted', $uid);
            } else {
                throw new DataException(
                    'The publication reference could not be deleted'.
                    ' because the modification key does not match.'.
                    ' Maybe someone edited this reference meanwhile.',
                    1378975765
                );
            }
        } else {
            throw new DataException(
                'The publication reference could not be deleted'.
                ' because it does not exist in the database.',
                1378975870
            );
        }
    }

    /**
     * Writes a log entry.
     */
    private static function log(string $message, int $uid)
    {
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger('bib');
        $logger->info($message,
            [
                'uid' => $uid,
                'table' => ReferenceReader::REFERENCE_TABLE,
            ]
        );
    }
}
