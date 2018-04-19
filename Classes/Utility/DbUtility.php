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
use Ipf\Bib\Exception\FileException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class provides the reference database interface
 * and some utility methods.
 */
class DbUtility
{
    /**
     * @var \Ipf\Bib\Utility\ReferenceReader
     */
    public $referenceReader;

    /**
     * @var int
     */
    public $ft_max_num = 100;

    /**
     * @var int
     */
    public $ft_max_sec = 3;

    /**
     * @var string
     */
    public $pdftotext_bin = 'pdftotext';

    /**
     * @var string
     */
    public $tmp_dir = '/tmp';

    /**
     * @var array
     */
    private $configuration;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
        $this->referenceReader = GeneralUtility::makeInstance(\Ipf\Bib\Utility\ReferenceReader::class, $this->configuration);
    }

    /**
     * Deletes authors that have no publications.
     *
     * @return int The number of deleted authors
     */
    public function deleteAuthorsWithoutPublications()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(ReferenceReader::AUTHOR_TABLE);

        $authors = $queryBuilder->select('t_au.uid')
            ->from(ReferenceReader::AUTHOR_TABLE, 't_au')
            ->leftJoin('t_au', ReferenceReader::AUTHORSHIP_TABLE, 't_as', 't_as.author_id = t_au.uid AND t_as.deleted = 0')
            ->where($queryBuilder->expr()->eq('t_au.deleted', 0))
            ->groupBy('t_au.uid')
            ->having($queryBuilder->expr()->count('t_as.uid'), 0)
            ->execute()
            ->fetchAll();

        foreach ($authors as $author) {
            $uids[] = (int) $author['uid'];
        }

        $count = count($uids);
        if ($count > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(ReferenceReader::REFERENCE_TABLE);
            $queryBuilder
                ->update(ReferenceReader::REFERENCE_TABLE)
                ->where($queryBuilder->expr()->in('uid', $uids))
                ->set('deleted', 1)
                ->execute();
        }

        return $count;
    }

    /**
     * Reads the full text generation configuration.
     *
     * @param array $configuration
     */
    public function readFullTextGenerationConfiguration($configuration)
    {
        if (is_array($configuration)) {
            if (isset($configuration['max_num'])) {
                $this->ft_max_num = intval($configuration['max_num']);
            }
            if (isset($configuration['max_sec'])) {
                $this->ft_max_sec = intval($configuration['max_sec']);
            }
            if (isset($configuration['pdftotext_bin'])) {
                $this->pdftotext_bin = trim($configuration['pdftotext_bin']);
            }
            if (isset($configuration['tmp_dir'])) {
                $this->tmp_dir = trim($configuration['tmp_dir']);
            }
        }
    }

    /**
     * Updates the full_text field for all references if neccessary.
     *
     * @return array An array with some statistical data
     */
    public function update_full_text_all(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(ReferenceReader::REFERENCE_TABLE);

        $query = $queryBuilder
            ->select('uid')
            ->from(ReferenceReader::REFERENCE_TABLE);

        $stat = [];
        $stat['updated'] = [];
        $stat['errors'] = [];
        $stat['limit_num'] = 0;
        $stat['limit_time'] = 0;
        $uids = [];

        if (count($this->referenceReader->pid_list) > 0) {
            $query->andWhere($queryBuilder->expr()->in('pid', $this->referenceReader->pid_list));
        }

        $queryBuilder->andWhere(
            $queryBuilder->orWhere(
                $queryBuilder->expr()->gt($queryBuilder->expr()->length('file_url'), 0)
            ),
            $queryBuilder->orWhere(
                $queryBuilder->expr()->gt($queryBuilder->expr()->length('full_text_file_url'), 0)
            )
        );

        $results = $query
            ->execute()
            ->fetchAll();

        foreach ($results as $result) {
            $uids[] = (int) $result['uid'];
        }

        $time_start = time();
        foreach ($uids as $uid) {
            $err = $this->update_full_text($uid);
            if ($err) {
                $stat['errors'][] = [$uid, []];
            } else {
                if ($err) {
                    $stat['updated'][] = $uid;
                    if (count($stat['updated']) >= $this->ft_max_num) {
                        $stat['limit_num'] = 1;
                        break;
                    }
                }
            }

            // Check time limit
            $time_delta = time() - $time_start;
            if ($time_delta >= $this->ft_max_sec) {
                $stat['limit_time'] = 1;
                break;
            }
        }

        return $stat;
    }

    /**
     * Updates the full_text for the reference with the given uid.
     */
    protected function update_full_text(int $uid): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(ReferenceReader::REFERENCE_TABLE);
        $results = $queryBuilder
                    ->select(['file_url', 'full_text_tstamp', 'full_text_file_url'])
                    ->from(ReferenceReader::REFERENCE_TABLE)
                    ->where($queryBuilder->expr()->eq('uid', $uid))
                    ->execute()
                    ->fetchAll();

        if (1 !== count($results)) {
            return false;
        }
        $pub = $results[0];

        // Determine File time
        $file = $pub['file_url'];
        $file_low = strtolower($file);
        $file_start = substr($file, 0, 9);
        $file_end = substr($file_low, -4, 4);
        $file_exists = false;

        if ((strlen($file) > 0)
            && ('fileadmin' === $file_start)
            && ('.pdf' === $file_end)
        ) {
            $root = PATH_site;
            if ('/' != substr($root, -1, 1)) {
                $root .= '/';
            }
            $file = $root.$file;
            if (file_exists($file)) {
                $file_mt = filemtime($file);
                $file_exists = true;
            }
        }

        $db_update = false;

        if (!$file_exists) {
            $clear = false;
            if (strlen($pub['full_text_file_url']) > 0) {
                $clear = true;
                if (strlen($pub['file_url']) > 0) {
                    if ($pub['file_url'] === $pub['full_text_file_url']) {
                        $clear = false;
                    }
                }
            }

            if ($clear) {
                $db_update = true;
            } else {
                return false;
            }
        }

        // Actually update
        if ($file_exists && (
                ($file_mt > $pub['full_text_tstamp']) ||
                ($pub['file_url'] != $pub['full_text_file_url'])
        )
        ) {
            // Check if pdftotext is executable
            if (!is_executable($this->pdftotext_bin)) {
                throw new FileException(sprintf('The pdftotext binary %s is not executable.', $this->pdftotext_bin), 1524121120);
            }

            // Determine temporary text file
            $target = tempnam($this->tmp_dir, 'bib_pdftotext');
            if (false === $target) {
                throw new FileException(sprintf('Could not create temporary file in %s', $this->tmp_dir), 1524120985);
            }

            // Compose and execute command
            $file_shell = escapeshellarg($file);
            $target_shell = escapeshellarg($target);

            $cmd = strval($this->pdftotext_bin);

            $cmd .= ' '.$file_shell;
            $cmd .= ' '.$target_shell;

            $cmd_txt = [];
            $retval = false;
            exec($cmd, $cmd_txt, $retval);
            if (0 !== $retval) {
                throw new FileException(sprintf('pdftotext failed on %s: %s', $pub['file_url'], implode('', $cmd_txt)), 1524121209);
            }

            // Read text file
            $handle = fopen($target, 'rb');
            $full_text = fread($handle, filesize($target));
            fclose($handle);

            // Delete temporary text file
            unlink($target);
        }

        if ($db_update) {
            $ret = $queryBuilder
                ->update(ReferenceReader::REFERENCE_TABLE)
                ->where($queryBuilder->expr()->eq('uid', $uid))
                ->set('full_text', $full_text ?? '')
                ->set('full_text_file_url', $pub['file_url'] ?? '')
                ->set('full_text_tstamp', time())
                ->execute();

            if (false === $ret) {
                throw new DataException('Full text update failed: %s', $ret, 1524121640);
            }

            return true;
        }

        return false;
    }

    /**
     * @param int $pid
     */
    public static function deleteAllFromPid(int $pid)
    {
        $tables = [
            ReferenceReader::AUTHORSHIP_TABLE,
            ReferenceReader::AUTHOR_TABLE,
            ReferenceReader::REFERENCE_TABLE,
        ];

        $delete = function ($table) use ($pid) {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

            $queryBuilder = $connectionPool->getQueryBuilderForTable($table);

            $queryBuilder
               ->delete($table)
               ->where($queryBuilder->expr()->eq('pid', $pid))
               ->execute();
        };

        array_map($delete, $tables);
    }
}
