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
use Ipf\Bib\Domain\Model\Reference;
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
            $clear_cache[] = (string) $GLOBALS['TSFE']->id;

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
     * @param Reference $publication
     *
     * @return bool TRUE on error FALSE otherwise
     */
    public function savePublication(Reference $publication)
    {
        $new = false;
        $uid = -1;

        // Fetch reference from DB
        $pub_db = null;
        if (is_numeric($publication->getUid())) {
            $pub_db = $this->referenceReader->getPublicationDetails((int) $publication->getUid());
            if (is_array($pub_db)) {
                $uid = $pub_db->getUid();
            } else {
                throw new DataException(sprintf(
                    'The publication reference with uid %s could not be updated'.
                    ' because it does not exist in the database (anymore?).', $publication->getUid()),
                    1378973300
                );
            }
        }

        // Acquire the storage folder pid if it is not given
        if (!is_numeric($publication->getPid())) {
            if (is_array($pub_db)) {
                $publication->setPid((int) $pub_db['pid']);
            } else {
                $publication->setPid($this->configuration['pid_list'][0]);
            }
        }

        // Check if the pid is in the allowed list
        if (!in_array($publication->getPid(), $this->configuration['pid_list'])) {
            throw new DataException(sprintf(
                'The given storage folder (pid=%d) is not in the list of allowed publication storage folders', $publication->getPid()),
                1378973653
            );
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(ReferenceReader::REFERENCE_TABLE);

        if ($uid >= 0) {
            if ($publication['mod_key'] === $pub_db['mod_key']) {
                $result = $queryBuilder
                    ->update(ReferenceReader::REFERENCE_TABLE)
                    ->where($queryBuilder->expr()->eq('uid', $uid))
                    ->set('tstamp', time())
                    ->set('pid', $publication->getPid())
                    ->set('crdate', $publication->getCrdate())
                    ->set('bibtype', $publication->getBibtype())
                    ->set('citeid', $publication->getCiteid())
                    ->set('title', $publication->getTitle())
                    ->set('journal', $publication->getJournal())
                    ->set('year', $publication->getYear())
                    ->set('month', $publication->getMonth())
                    ->set('day', $publication->getDay())
                    ->set('volume', $publication->getVolume())
                    ->set('number', $publication->getNumber())
                    ->set('number2', $publication->getNumber2())
                    ->set('pages', $publication->getPages())
                    ->set('abstract', $publication->getAbstract())
                    ->set('affiliation', $publication->getAffiliation())
                    ->set('note', $publication->getNote())
                    ->set('annotation', $publication->getAnnotation())
                    ->set('keywords', $publication->getKeywords())
                    ->set('tags', $publication->getTags())
                    ->set('file_url', $publication->getFileUrl())
                    ->set('web_url', $publication->getWebUrl())
                    ->set('web_url_date', $publication->getWebUrlDate())
                    ->set('web_url', $publication->getWebUrl2())
                    ->set('web_url_date', $publication->getWebUrl2Date())
                    ->set('misc', $publication->getMisc())
                    ->set('misc2', $publication->getMisc2())
                    ->set('editor', $publication->getEditor())
                    ->set('publisher', $publication->getPublisher())
                    ->set('address', $publication->getAddress())
                    ->set('howpublished', $publication->getHowpublished())
                    ->set('series', $publication->getSeries())
                    ->set('edition', $publication->getEdition())
                    ->set('chapter', $publication->getChapter())
                    ->set('booktitle', $publication->getBooktitle())
                    ->set('school', $publication->getSchool())
                    ->set('institute', $publication->getInstitute())
                    ->set('organization', $publication->getOrganization())
                    ->set('institution', $publication->getInstitution())
                    ->set('event_place', $publication->getEventPlace())
                    ->set('event_name', $publication->getEventName())
                    ->set('event_date', $publication->getEventDate())
                    ->set('state', $publication->getState())
                    ->set('type', $publication->getType())
                    ->set('language', $publication->getLanguage())
                    ->set('ISBN', $publication->getISBN())
                    ->set('ISSN', $publication->getISSN())
                    ->set('DOI', $publication->getDOI())
                    ->set('extern', $publication->isExtern())
                    ->set('reviewed', $publication->isReviewed())
                    ->set('in_library', $publication->isInLibrary())
                    ->set('hidden', $publication->isHidden())
                    ->set('borrowed_by', $publication->getBorrowedBy())
                    ->execute();

                if (0 === $result) {
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
                $cruser_id = (int) $be_user->user['uid'];
            }

            $insert = $queryBuilder
                ->insert(ReferenceReader::REFERENCE_TABLE)
                ->set('tstamp', time())
                ->set('cruser_id', $cruser_id)
                ->set('pid', $publication->getPid())
                ->set('crdate', $publication->getCrdate())
                ->set('bibtype', $publication->getBibtype())
                ->set('citeid', $publication->getCiteid())
                ->set('title', $publication->getTitle())
                ->set('journal', $publication->getJournal())
                ->set('year', $publication->getYear())
                ->set('month', $publication->getMonth())
                ->set('day', $publication->getDay())
                ->set('volume', $publication->getVolume())
                ->set('number', $publication->getNumber())
                ->set('number2', $publication->getNumber2())
                ->set('pages', $publication->getPages())
                ->set('abstract', $publication->getAbstract())
                ->set('affiliation', $publication->getAffiliation())
                ->set('note', $publication->getNote())
                ->set('annotation', $publication->getAnnotation())
                ->set('keywords', $publication->getKeywords())
                ->set('tags', $publication->getTags())
                ->set('file_url', $publication->getFileUrl())
                ->set('web_url', $publication->getWebUrl())
                ->set('web_url_date', $publication->getWebUrlDate())
                ->set('web_url', $publication->getWebUrl2())
                ->set('web_url_date', $publication->getWebUrl2Date())
                ->set('misc', $publication->getMisc())
                ->set('misc2', $publication->getMisc2())
                ->set('editor', $publication->getEditor())
                ->set('publisher', $publication->getPublisher())
                ->set('address', $publication->getAddress())
                ->set('howpublished', $publication->getHowpublished())
                ->set('series', $publication->getSeries())
                ->set('edition', $publication->getEdition())
                ->set('chapter', $publication->getChapter())
                ->set('booktitle', $publication->getBooktitle())
                ->set('school', $publication->getSchool())
                ->set('institute', $publication->getInstitute())
                ->set('organization', $publication->getOrganization())
                ->set('institution', $publication->getInstitution())
                ->set('event_place', $publication->getEventPlace())
                ->set('event_name', $publication->getEventName())
                ->set('event_date', $publication->getEventDate())
                ->set('state', $publication->getState())
                ->set('type', $publication->getType())
                ->set('language', $publication->getLanguage())
                ->set('ISBN', $publication->getISBN())
                ->set('ISSN', $publication->getISSN())
                ->set('DOI', $publication->getDOI())
                ->set('extern', $publication->isExtern())
                ->set('reviewed', $publication->isReviewed())
                ->set('in_library', $publication->isInLibrary())
                ->set('hidden', $publication->isHidden())
                ->set('borrowed_by', $publication->getBorrowedBy())
                ->execute();

            $uid = $this->db->sql_insert_id();
            if (0 === $insert) {
                throw new DataException(
                    'A publication reference could not be inserted into the database',
                    1378973908
                );
            }
        }

        if (($insert > 0) && (count($publication->getAuthors()) > 0)) {
            try {
                $this->savePublicationAuthors($uid, $publication->getPid(), $publication->getAuthors());
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
                    $authorToBeInserted = $author;
                    $authorToBeInserted['pid'] = (int) $pid;

                    $author['uid'] = $this->insertAuthor($authorToBeInserted);
                    if ($author['uid'] > 0) {
                        throw new DataException(sprintf(
                            'An author %s could not be inserted into the database', $authorToBeInserted['surename']),
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(ReferenceReader::AUTHORSHIP_TABLE);

        for ($ii = 0; $ii < $authorsSize; ++$ii) {
            $author = &$authors[$ii];
            if (is_numeric($author['uid'])) {
                $as = [];
                $as['pid'] = $pid;
                $as['pub_id'] = $pub_uid;
                $as['author_id'] = (int) $author['uid'];
                $as['sorting'] = $author['sorting'];

                if ($ii < $as_present) {
                    // There are present authorships - Update authorship
                    $as_uid = $db_aships[$ii]['uid'];

                    $ret = $queryBuilder
                        ->update(ReferenceReader::AUTHORSHIP_TABLE)
                        ->set('pid', $pid)
                        ->set('pub_id', $pub_uid)
                        ->set('author_id', (int) $author['uid'])
                        ->set('sorting', $author['sorting'])
                        ->where($queryBuilder->expr()->eq('uid', $as_uid))
                        ->execute();

                    if (0 === $ret) {
                        throw new DataException(sprintf(
                            'An authorship could not be updated uid %d', $as_uid),
                            1378977083
                        );
                    }
                } else {
                    // No more present authorships - Insert authorship
                    $ret = $queryBuilder
                        ->Insert(ReferenceReader::AUTHORSHIP_TABLE)
                        ->set('pid', $pid)
                        ->set('pub_id', $pub_uid)
                        ->set('author_id', (int) $author['uid'])
                        ->set('sorting', $author['sorting'])
                        ->execute();

                    if (0 === $ret) {
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
    protected function insertAuthor($author): int
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
