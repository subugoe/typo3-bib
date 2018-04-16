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

use Ipf\Bib\Domain\Model\Author;
use Ipf\Bib\Domain\Model\Reference;
use Ipf\Bib\Service\ItemTransformerService;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class provides the reference database interface
 * and some utility methods.
 */
class ReferenceReader
{
    const REFERENCE_TABLE = 'tx_bib_domain_model_reference';
    const AUTHOR_TABLE = 'tx_bib_domain_model_author';
    const AUTHORSHIP_TABLE = 'tx_bib_domain_model_authorships';
    /**
     * @var array
     */
    public $pid_list = [];
    /**
     * Show hidden references.
     *
     * @var bool
     */
    public $show_hidden;
    /**
     * @var string
     */
    public $searchPrefix = 'search_fields';
    /**
     * @var string
     */
    public $sortPrefix = 'sort_fields';
    /**
     * @var array
     */
    public $t_ref_default = [];
    /**
     * @var array
     */
    public $t_as_default = [];
    /**
     * @var array
     */
    public $t_au_default = [];
    /**
     * These are the publication relevant fields
     * that can be found in the reference table $this->referenceTable.
     * including the important TYPO3 specific fields.
     */
    public $refAllFields;
    /**
     * These are the publication relevant fields
     * that can be found in a php publication array.
     * This includes TYPO3 variables (pid,uid, etc.).
     */
    public $pubAllFields;
    /**
     * @var array
     */
    public static $allBibTypes = [
        0 => 'unknown',
        1 => 'article',
        2 => 'book',
        3 => 'inbook',
        4 => 'booklet',
        5 => 'conference',
        6 => 'incollection',
        7 => 'proceedings',
        8 => 'inproceedings',
        9 => 'manual',
        10 => 'mastersthesis',
        11 => 'phdthesis',
        12 => 'techreport',
        13 => 'unpublished',
        14 => 'miscellaneous',
        15 => 'string',
        16 => 'poster',
        17 => 'thesis',
        18 => 'manuscript',
        19 => 'report',
        20 => 'misc',
        21 => 'url',
    ];
    /**
     * @var array
     */
    public static $allStates = [
        0 => 'published',
        1 => 'accepted',
        2 => 'submitted',
        3 => 'unpublished',
        4 => 'in_preparation',
    ];

    protected $filter;

    /**
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    protected $cObj;

    /**
     * @var bool|\mysqli_result
     */
    protected $databaseResource = null;

    /**
     * @var bool
     */
    protected $clearCache = false;

    /**
     * @var array
     */
    protected $search_fields;

    /**
     * The following tags are allowed in a reference string.
     *
     * @var array
     */
    protected $allowedTags = ['em', 'strong', 'sup', 'sub'];

    /**
     * These are the author relevant fields
     * that can be found in the reference table $this->getAuthorTable().
     * TYPO3 special fields like pid or uid are not listed here.
     *
     * @var array
     */
    public static $authorFields = ['surname', 'forename', 'url', 'fe_user_id'];

    /**
     * @var array
     */
    protected $authorAllFields;

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * These are the publication relevant fields
     * that can be found in the reference table $this->referenceTable.
     * TYPO3 special fields like pid or uid are not listed here.
     *
     * @var array
     */
    public static $referenceFields = [
        'bibtype',
        'citeid',
        'title',
        'journal',
        'year',
        'month',
        'day',
        'volume',
        'number',
        'number2',
        'pages',
        'abstract',
        'affiliation',
        'note',
        'annotation',
        'keywords',
        'tags',
        'file_url',
        'web_url',
        'web_url_date',
        'web_url2',
        'web_url2_date',
        'misc',
        'misc2',
        'editor',
        'publisher',
        'address',
        'howpublished',
        'series',
        'edition',
        'chapter',
        'booktitle',
        'school',
        'institute',
        'organization',
        'institution',
        'event_place',
        'event_name',
        'event_date',
        'state',
        'type',
        'language',
        'ISBN',
        'ISSN',
        'DOI',
        'extern',
        'reviewed',
        'in_library',
        'borrowed_by',
    ];

    /**
     * These are the publication relevant fields
     * that can be found in a php publication array.
     * TYPO3 special fields like pid or uid are not listed here.
     *
     * @var array
     */
    protected $publicationFields;

    /**
     * @var array
     */
    private $configuration;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
        $this->setPidList($configuration['pid_list']);
        $this->setClearCache($configuration['editor']['clear_page_cache']);
        $this->setShowHidden($configuration['show_hidden']);
        $this->set_searchFields($configuration['search_fields']);
        $this->set_editorStopWords($configuration['editor_stop_words']);
        $this->set_titleStopWords($configuration['title_stop_words']);
        $this->set_filters($configuration['filters'] ?? []);

        $this->t_ref_default['table'] = self::REFERENCE_TABLE;
        $this->t_as_default['table'] = self::AUTHORSHIP_TABLE;
        $this->t_au_default['table'] = self::AUTHOR_TABLE;

        // setup authorAllFields
        $this->authorAllFields = ['uid', 'pid', 'tstamp', 'crdate', 'cruser_id'];
        $this->authorAllFields = array_merge($this->authorAllFields, static::$authorFields);

        // setup refAllFields
        $typo3_fields = [
            'uid',
            'pid',
            'hidden',
            'tstamp',
            'sorting',
            'crdate',
            'cruser_id',
        ];
        $this->refAllFields = array_merge($typo3_fields, static::$referenceFields);

        // setup pubFields
        $this->publicationFields = static::$referenceFields;
        $this->publicationFields[] = 'authors';

        // setup pubAllFields
        $this->pubAllFields = array_merge($typo3_fields, $this->publicationFields);

        $this->sortExtraFields = ['surname'];

        $searchFields = static::$referenceFields;
        array_push($searchFields, 'authors');
        natcasesort($searchFields);
        $this->setSearchFields($searchFields);

        $sortFields = static::$referenceFields;
        $sortFields = array_merge($sortFields, $this->sortExtraFields);
        $this->setSortFields($sortFields);
    }

    /**
     * @return mixed|\TYPO3\CMS\Core\Database\DatabaseConnection
     */
    private function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return string
     */
    public function getReferenceTable()
    {
        return static::REFERENCE_TABLE;
    }

    /**
     * @return string
     */
    public function getAuthorshipTable()
    {
        return static::AUTHORSHIP_TABLE;
    }

    /**
     * @return string
     */
    public function getAuthorTable()
    {
        return static::AUTHOR_TABLE;
    }

    /**
     * @return array
     */
    public function getAuthorAllFields()
    {
        return $this->authorAllFields;
    }

    /**
     * @param array $authorAllFields
     */
    public function setAuthorAllFields($authorAllFields)
    {
        $this->authorAllFields = $authorAllFields;
    }

    /**
     * @return array
     */
    public function getAuthorFields()
    {
        return $this->authorFields;
    }

    /**
     * @param array $authorFields
     */
    public function setAuthorFields($authorFields)
    {
        $this->authorFields = $authorFields;
    }

    /**
     * @return array
     */
    public function getReferenceFields()
    {
        return $this->referenceFields;
    }

    /**
     * @param array $referenceFields
     */
    public function setReferenceFields($referenceFields)
    {
        $this->referenceFields = $referenceFields;
    }

    /**
     * @return array
     */
    public function getPublicationFields()
    {
        return $this->publicationFields;
    }

    /**
     * @param array $publicationFields
     */
    public function setPublicationFields($publicationFields)
    {
        $this->publicationFields = $publicationFields;
    }

    /**
     * @param array $sortFields
     */
    public function setSortFields($sortFields)
    {
        $this->sortFields = $sortFields;
    }

    /**
     * This changes the character set of a publication (array).
     *
     * @param array  $publication
     * @param string $originalCharset
     * @param string $targetCharset
     *
     * @return array The character set adjusted publication
     */
    public function change_pub_charset(array $publication, string $originalCharset, string $targetCharset)
    {
        if (is_array($publication) && strlen($originalCharset) && strlen($targetCharset)
            && ($originalCharset != $targetCharset)
        ) {
            $charsetConverter = GeneralUtility::makeInstance(CharsetConverter::class);

            $keys = array_keys($publication);
            foreach ($keys as $key) {
                switch ($key) {
                    case 'authors':
                        if (is_array($publication[$key])) {
                            foreach ($publication[$key] as &$author) {
                                $author['forename'] = $charsetConverter->conv(
                                    $author['forename'],
                                    $originalCharset,
                                    $targetCharset
                                );

                                $author['surname'] = $charsetConverter->conv(
                                    $author['surname'],
                                    $originalCharset,
                                    $targetCharset
                                );
                            }
                        }
                        break;
                    default:
                        if (is_string($publication[$key])) {
                            $publication[$key] = $charsetConverter->conv(
                                $publication[$key],
                                $originalCharset,
                                $targetCharset
                            );
                        }
                }
            }
        }

        return $publication;
    }

    /**
     * This sets the filter which will be asked for most
     * query compositions.
     *
     * @param array $filter
     */
    public function set_filter($filter)
    {
        $this->filters = [];
        $this->append_filter($filter);
    }

    /**
     * This appends a filter to the filter list.
     *
     * @param array $filter
     */
    public function append_filter($filter)
    {
        if (is_array($filter)) {
            if (!is_array($filter['pid'])) {
                if (is_string($filter['pid'])) {
                    $filter['pid'] = explode(',', strval($filter['pid']));
                }
            }
            $this->getFilteredAuthorsUids($filter);
            $this->filters[] = $filter;
        }
    }

    /**
     * Fetches the uids of the auhors in the author filter.
     *
     * @param array $filter
     */
    protected function getFilteredAuthorsUids(&$filter)
    {
        if (is_array($filter['author']['authors'])) {
            $filter['author']['sets'] = [];
            foreach ($filter['author']['authors'] as &$a) {
                if (!is_numeric($a['uid'])) {
                    $pid = $this->pid_list;
                    if (isset($filter['pid'])) {
                        $pid = $filter['pid'];
                    }
                    $uids = $this->fetch_author_uids($a, $pid);
                    $uidSize = count($uids);
                    for ($i = 0; $i < $uidSize; ++$i) {
                        $uid = $uids[$i];
                        if (0 == $i) {
                            $a['uid'] = $uid['uid'];
                            $a['pid'] = $uid['pid'];
                        } else {
                            // Push further authors that match to the filter
                            $aa = $a;
                            $aa['uid'] = $uid['uid'];
                            $aa['pid'] = $uid['pid'];
                            $filter['author']['authors'][] = $aa;
                        }
                    }
                    if (count($uids) > 0) {
                        $filter['author']['sets'][] = $uids;
                    }
                }
            }
        }
    }

    /**
     * Fetches the uid(s) of the given auhor.
     * Checked is against the forename and the surname.
     *
     * @param array     $author
     * @param array|int $pids
     *
     * @return array Not defined
     */
    public function fetch_author_uids($author, $pids)
    {
        $uids = [];
        $all_fields = ['forename', 'surname', 'url'];

        $whereClause = [];

        foreach ($all_fields as $field) {
            if (array_key_exists($field, $author)) {
                $chk = ' = ';
                $word = $author[$field];
                if (preg_match('/(^%|^_|[^\\\\]%|[^\\\\]_)/', $word)) {
                    $chk = ' LIKE ';
                }
                $whereClause[] = $field.$chk.$this->getDatabaseConnection()->fullQuoteStr($word, $this->getAuthorTable());
            }
        }

        if (count($whereClause) > 0) {
            if (is_array($pids)) {
                $whereClause[] = 'pid IN ('.implode(',', $pids).')';
            } else {
                $whereClause[] = 'pid='.(int) $pids;
            }
            $whereClause = implode(' AND ', $whereClause);
            $whereClause .= $this->enable_fields($this->getAuthorTable());

            $res = $this->getDatabaseConnection()->exec_SELECTquery(
                'uid,pid',
                $this->getAuthorTable(),
                $whereClause
            );

            while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                $uids[] = ['uid' => $row['uid'], 'pid' => $row['pid']];
            }
        }

        return $uids;
    }

    /**
     * Returns the where clause part for a table.
     *
     * @param string $table
     * @param string $alias
     * @param bool   $show_hidden
     *
     * @return string The where clause part
     */
    public function enable_fields(string $table, string $alias = '', bool $show_hidden = false)
    {
        if (0 === strlen($alias)) {
            $alias = $table;
        }
        if (isset($this->cObj)) {
            $whereClause = $this->cObj->enableFields($table, $show_hidden ? 1 : 0);
        } else {
            $whereClause = ' AND '.$table.'.deleted=0';
        }
        if ($alias != $table) {
            $whereClause = str_replace($table, $alias, $whereClause);
        }

        return $whereClause;
    }

    /**
     * This sets the filters which will be asked for most
     * query compositions.
     *
     * @param array $filters
     */
    public function set_filters(array $filters)
    {
        $this->filters = [];
        foreach ($filters as $filter) {
            $this->append_filter($filter);
        }
    }

    /**
     * This sets the editor stop words to be used in order clause
     * query compositions.
     *
     * @param array $editor_stop_words
     */
    public function set_editorStopWords($editor_stop_words)
    {
        $this->editor_stop_words = $editor_stop_words;
    }

    /**
     * This sets the title stop words to be used in order clause
     * query compositions.
     *
     * @param array $title_stop_words
     */
    public function set_titleStopWords($title_stop_words)
    {
        $this->title_stop_words = $title_stop_words;
    }

    /**
     * Returns a search word object as it is required by the 'all' search
     * filter argument.
     *
     * @param string $word
     * @param array  $wrap
     *
     * @return string|array The search object (string or array)
     */
    public static function getSearchTerm($word, $wrap = ['%', '%'])
    {
        $spec = htmlentities($word, ENT_QUOTES, 'UTF-8');
        $words = [$word];
        if ($spec != $word) {
            $words[] = $spec;
        }
        if (is_array($wrap) && (count($wrap) > 0)) {
            foreach ($words as $key => $txt) {
                $words[$key] = strval($wrap[0]).strval($txt).strval($wrap[1]);
            }
        }
        if (1 === count($words)) {
            return $words[0];
        }

        return $words;
    }

    /**
     * Checks if a publication that has not the given uid
     * but the citeId exists in the database. The lookup is restricted
     * to the current storage folders ($filter['pid']).
     *
     * @param string $citeId
     * @param int    $uid    ;
     *
     * @return bool TRUE on existence FALSE otherwise
     */
    public function citeIdExists($citeId, $uid = -1)
    {
        if (0 === strlen($citeId)) {
            return false;
        }

        $num = 0;
        $whereClause = [];
        $whereClause[] = 'citeid='.$this->getDatabaseConnection()->fullQuoteStr($citeId, $this->getReferenceTable());

        if (is_numeric($uid) && ($uid >= 0)) {
            $whereClause[] = 'uid!='."'".intval($uid)."'";
        }

        if (count($this->pid_list) > 0) {
            $csv = Utility::implode_intval(',', $this->pid_list);
            $whereClause[] = 'pid IN ('.$csv.')';
        }

        $whereClause = implode(' AND ', $whereClause);
        $whereClause .= $this->enable_fields($this->getReferenceTable(), '', $this->show_hidden);

        $res = $this->getDatabaseConnection()->exec_SELECTquery('count(uid)', $this->getReferenceTable(), $whereClause);
        $row = $this->getDatabaseConnection()->sql_fetch_assoc($res);

        if (is_array($row)) {
            $num = intval($row['count(uid)']);
        }

        return $num > 0;
    }

    /**
     * Returns the number of publications  which match
     * the filtering criteria.
     *
     * @return int The number of publications
     */
    public function getNumberOfPublications()
    {
        $select = $this->getReferenceSelectClause([$this->getReferenceTable().'.uid'], null);
        $select = preg_replace('/;\s*$/', '', $select);
        $query = 'SELECT count(pubs.uid) FROM ('.$select.') pubs;';
        $res = $this->getDatabaseConnection()->sql_query($query);
        $row = $this->getDatabaseConnection()->sql_fetch_assoc($res);

        if (is_array($row)) {
            return intval($row['count(pubs.uid)']);
        }

        return 0;
    }

    /**
     * This function returns the SQL LIMIT clause configured
     * by the filter.
     *
     * @param string $fields
     * @param string $order
     * @param string $group
     *
     * @return string The LIMIT clause string
     */
    protected function getReferenceSelectClause(array $fields, $order = '', $group = '')
    {
        $columns = [];
        $whereClause = $this->getReferenceWhereClause($columns);

        $groupClause = '';
        if (is_string($group)) {
            $groupClause = strlen($group) ? $group : $this->getReferenceTable().'.uid';
        }

        $orderClause = '';
        if (is_string($order)) {
            $orderClause = strlen($order) ? $order : $this->getOrderClause();
        }

        $limitClause = $this->getLimitClause();

        // Find the tables that should be included
        $tables = [$this->t_ref_default];
        foreach ($fields as $field) {
            if (!(false === strpos($field, $this->getAuthorshipTable()))) {
                $tables[] = $this->t_as_default;
            }
            if (!(false === strpos($field, $this->getAuthorTable()))) {
                $tables[] = $this->t_au_default;
            }
        }

        foreach ($columns as $column) {
            if (!(false === strpos($column, $this->getAuthorshipTable()))) {
                $table = $this->t_as_default;
                $table['table'] = $column;

                $tables[] = $this->t_ref_default;
                $tables[] = $table;
            }
        }

        $q = $this->select_clause_start($fields, $tables);
        if (strlen($whereClause)) {
            $q .= ' WHERE '.$whereClause;
        }
        if (strlen($groupClause)) {
            $q .= ' GROUP BY '.$groupClause;
        }
        if (strlen($orderClause)) {
            $q .= ' ORDER BY '.$orderClause;
        }
        if (strlen($limitClause)) {
            $q .= ' LIMIT '.$limitClause;
        }
        $q .= ';';

        return $q;
    }

    /**
     * This function returns the SQL WHERE clause configured
     * by the filters.
     *
     * @param array $columns
     *
     * @return string The WHERE clause string
     */
    protected function getReferenceWhereClause(&$columns)
    {
        $WCA = [];
        $columns = [];
        $runvar = [
            'columns' => [],
            'aShip_count' => 0,
        ];

        // Get where parts for each filter
        foreach ($this->filters as $filter) {
            $parts = $this->getFilterWhereClauseParts($filter, $runvar);
            $WCA = array_merge($WCA, $parts);
        }

        $whereClause = implode(' AND ', $WCA);

        if (strlen($whereClause) > 0) {
            $columns = array_merge([$this->getReferenceTable()], $runvar['columns']);
            $columns = array_unique($columns);

            foreach ($columns as &$column) {
                $column = preg_replace('/\.[^\.]*$/', '', $column);
                if (!(false === strpos($column, $this->getReferenceTable()))) {
                    $whereClause .= $this->enable_fields($this->getReferenceTable(), $column, $this->show_hidden);
                }
                if (!(false === strpos($column, $this->getAuthorshipTable()))) {
                    $whereClause .= $this->enable_fields($this->getAuthorshipTable(), $column);
                }
            }
        }

        return $whereClause;
    }

    /**
     * This function returns the SQL WHERE clause parts for one filter.
     *
     * @param array $filter
     * @param array $runvar
     *
     * @return array The WHERE clause parts in an array
     */
    protected function getFilterWhereClauseParts($filter, &$runvar)
    {
        $whereClause = [];

        // Filter by UID
        if (isset($filter['FALSE'])) {
            $whereClause[] = 'FALSE';

            return $whereClause;
        }

        // Filter by UID
        if (is_array($filter['uid']) && (count($filter['uid']) > 0)) {
            $csv = Utility::implode_intval(',', $filter['uid']);
            $whereClause[] = $this->getReferenceTable().'.uid IN ('.$csv.')';
        }

        // Filter by storage PID
        if (is_array($filter['pid']) && (count($filter['pid']) > 0)) {
            $csv = Utility::implode_intval(',', $filter['pid']);
            $whereClause[] = $this->getReferenceTable().'.pid IN ('.$csv.')';
        }

        // Filter by year
        if (is_array($filter['year']) && (count($filter['year']) > 0)) {
            $wca = '';
            // years
            if (is_array($filter['year']['years']) && (count($filter['year']['years']) > 0)) {
                $csv = Utility::implode_intval(',', $filter['year']['years']);
                $wca .= ' '.$this->getReferenceTable().'.year IN ('.$csv.')';
            }
            // ranges
            if (is_array($filter['year']['ranges']) && count($filter['year']['ranges'])) {
                if (count($filter['year']['ranges'])) {
                    $yearRangeFilterSize = count($filter['year']['ranges']);
                    for ($i = 0; $i < $yearRangeFilterSize; ++$i) {
                        $both = (isset($filter['year']['ranges'][$i]['from']) && isset($filter['year']['ranges'][$i]['to'])) ? true : false;
                        if (strlen($wca)) {
                            $wca .= ' OR ';
                        }
                        if ($both) {
                            $wca .= '(';
                        }
                        if (isset($filter['year']['ranges'][$i]['from'])) {
                            $wca .= $this->getReferenceTable().'.year >= '.intval($filter['year']['ranges'][$i]['from']);
                        }
                        if ($both) {
                            $wca .= ' AND ';
                        }
                        if (isset($filter['year']['ranges'][$i]['to'])) {
                            $wca .= $this->getReferenceTable().'.year <= '.intval($filter['year']['ranges'][$i]['to']);
                        }
                        if ($both) {
                            $wca .= ')';
                        }
                    }
                }
            }
            $whereClause[] = '('.$wca.')';
        }

        // Filter by authors
        if (is_array($filter['author']) && (count($filter['author']) > 0)) {
            $f = &$filter['author'];
            if (1 == $f['rule']) {
                // AND
                if (is_array($f['sets']) && (count($f['sets']) > 0)) {
                    $wc_set = [];
                    $authorFilterSize = count($f['sets']);
                    for ($i = 0; $authorFilterSize; ++$i) {
                        $set = $f['sets'][$i];
                        $uid_lst = [];
                        foreach ($set as $au) {
                            if (is_numeric($au['uid'])) {
                                $uid_lst[] = intval($au['uid']);
                            }
                        }
                        if (count($uid_lst) > 0) {
                            $uid_lst = implode(',', $uid_lst);
                            $col_num = $runvar['aShip_count'];
                            ++$runvar['aShip_count'];
                            $column = $this->getAuthorshipTable().(($col_num > 0) ? strval($col_num) : '');
                            $wc_set[] = $column.'.author_id IN ('.$uid_lst.')';
                            $runvar['columns'][] = $column;
                        }
                    }

                    // Append set clause
                    if (count($wc_set) > 0) {
                        $whereClause = array_merge($whereClause, $wc_set);
                    } else {
                        $whereClause[] = 'FALSE';
                    }
                } else {
                    $whereClause[] = 'FALSE';
                }
            } else {
                // OR
                if (count($f['authors']) > 0) {
                    $authors = &$f['authors'];
                    $uid_lst = [];
                    foreach ($authors as $au) {
                        if (is_numeric($au['uid'])) {
                            $uid_lst[] = intval($au['uid']);
                        }
                    }
                    if (count($uid_lst) > 0) {
                        $uid_lst = implode(',', $uid_lst);
                        $col_num = $runvar['aShip_count'];
                        ++$runvar['aShip_count'];
                        $column = $this->getAuthorshipTable().(($col_num > 0) ? strval($col_num) : '');
                        $whereClause[] = $column.'.author_id IN ('.$uid_lst.')';
                        $runvar['columns'][] = $column;
                    } else {
                        $whereClause[] = 'FALSE';
                    }
                }
            }
        }

        // Filter by bibtype
        if (is_array($filter['bibtype']) && (count($filter['bibtype']) > 0)) {
            if (is_array($filter['bibtype']['types']) && (count($filter['bibtype']['types']) > 0)) {
                $csv = Utility::implode_intval(',', $filter['bibtype']['types']);
                $whereClause[] = $this->getReferenceTable().'.bibtype IN ('.$csv.')';
            }
        }

        // Filter by publication state
        if (is_array($filter['state']) && (count($filter['state']) > 0)) {
            if (is_array($filter['state']['states']) && (count($filter['state']['states']) > 0)) {
                $csv = Utility::implode_intval(',', $filter['state']['states']);
                $whereClause[] = $this->getReferenceTable().'.state IN ('.$csv.')';
            }
        }

        // Filter by origin
        if (is_array($filter['origin']) && (count($filter['origin']) > 0)) {
            if (is_numeric($filter['origin']['origin'])) {
                $wca = $this->getReferenceTable().'.extern = 0';
                if (0 != intval($filter['origin']['origin'])) {
                    $wca = $this->getReferenceTable().'.extern != 0';
                }
                $whereClause[] = $wca;
            }
        }

        // Filter by reviewed
        if (is_array($filter['reviewed']) && (count($filter['reviewed']) > 0)) {
            if (is_numeric($filter['reviewed']['value'])) {
                $wca = $this->getReferenceTable().'.reviewed = 0';
                if (0 != intval($filter['reviewed']['value'])) {
                    $wca = $this->getReferenceTable().'.reviewed != 0';
                }
                $whereClause[] = $wca;
            }
        }

        // Filter by borrowed
        if (is_array($filter['borrowed']) && (count($filter['borrowed']) > 0)) {
            if (is_numeric($filter['borrowed']['value'])) {
                $wca = 'LENGTH('.$this->getReferenceTable().'.borrowed_by) = \'0\'';
                if (0 != intval($filter['borrowed']['value'])) {
                    $wca = 'LENGTH('.$this->getReferenceTable().'.borrowed_by) != \'0\'';
                }
                $whereClause[] = $wca;
            }
        }

        // Filter by in_library
        if (is_array($filter['in_library']) && (count($filter['in_library']) > 0)) {
            if (is_numeric($filter['in_library']['value'])) {
                $wca = $this->getReferenceTable().'.in_library = \'0\'';
                if (0 != intval($filter['in_library']['value'])) {
                    $wca = $this->getReferenceTable().'.in_library != \'0\'';
                }
                $whereClause[] = $wca;
            }
        }

        // Filter by citeid
        if (is_array($filter['citeid']) && (count($filter['citeid']) > 0)) {
            if (is_array($filter['citeid']['ids']) && (count($filter['citeid']['ids']) > 0)) {
                $wca = $this->getReferenceTable().'.citeid IN (';
                $citeIdSize = count($filter['citeid']['ids']);
                for ($i = 0; $i < $citeIdSize; ++$i) {
                    if ($i > 0) {
                        $wca .= ',';
                    }

                    $wca .= $this->getDatabaseConnection()->fullQuoteStr($filter['citeid']['ids'][$i], $this->getReferenceTable());
                }
                $wca .= ')';
                $whereClause[] = $wca;
            }
        }

        // Filter by tags
        if (is_array($filter['tags']) && (count($filter['tags']) > 0)) {
            if (is_array($filter['tags']['words']) && (count($filter['tags']['words']) > 0)) {
                $wca = [];

                if ($filter['tags']['rule'] == 0) { // OR
                    $wca[] = $this->getFilterSearchFieldsClause($filter['tags']['words'], ['tags']);
                } else { // AND
                    foreach ($filter['tags']['words'] as $word) {
                        $wca[] = $this->getFilterSearchFieldsClause([$word], ['tags']);
                    }
                }

                foreach ($wca as $app) {
                    if (strlen($app) > 0) {
                        $whereClause[] = $app;
                    }
                }
            }
        }

        // Filter by keywords
        if (is_array($filter['keywords']) && (count($filter['keywords']) > 0)) {
            if (is_array($filter['keywords']['words']) && (count($filter['keywords']['words']) > 0)) {
                $wca = [];

                if ($filter['keywords']['rule'] == 0) { // OR
                    $wca[] = $this->getFilterSearchFieldsClause($filter['keywords']['words'], ['keywords']);
                } else { // AND
                    foreach ($filter['keywords']['words'] as $word) {
                        $wca[] = $this->getFilterSearchFieldsClause([$word], ['keywords']);
                    }
                }

                foreach ($wca as $app) {
                    if (strlen($app) > 0) {
                        $whereClause[] = $app;
                    }
                }
            }
        }

        // General keyword search
        if (is_array($filter['all']) && (count($filter['all']) > 0)) {
            if (is_array($filter['all']['words']) && (count($filter['all']['words']) > 0)) {
                $wca = [];
                $fields = explode(',', $this->search_fields);
                $fields[] = 'full_text';

                if (is_array($filter['all']['exclude'])) {
                    $fields = array_diff($fields, $filter['all']['exclude']);
                }

                if ($filter['all']['rule'] == 0) { // OR
                    $wca[] = $this->getFilterSearchFieldsClause($filter['all']['words'], $fields);
                } else { // AND
                    foreach ($filter['all']['words'] as $word) {
                        $wca[] = $this->getFilterSearchFieldsClause([$word], $fields);
                    }
                }

                foreach ($wca as $app) {
                    if (strlen($app) > 0) {
                        $whereClause[] = $app;
                    }
                }
            }
        }

        return $whereClause;
    }

    /**
     * This function returns the SQL WHERE clause part
     * for the search for keywords (OR).
     *
     * @param array $words  An array or words
     * @param array $fields An array of fields to search in
     *
     * @return string The WHERE clause string
     */
    protected function getFilterSearchFieldsClause($words, $fields)
    {
        $res = '';
        $wca = [];

        // Wildcard words
        $proc_words = [];
        foreach ($words as $word) {
            if (is_array($word)) {
                foreach ($word as $oword) {
                    $oword = trim(strval($oword));
                    if (strlen($oword) > 0) {
                        $proc_words[] = $oword;
                    }
                }
            } else {
                $oword = trim(strval($word));
                if (strlen($oword) > 0) {
                    $proc_words[] = $oword;
                }
            }
        }

        // Fields
        $refFields = $this->getReferenceFields();
        $refFields[] = 'full_text';

        foreach ($fields as $field) {
            if (in_array($field, $refFields)) {
                foreach ($proc_words as $word) {
                    $word = $this->getDatabaseConnection()->fullQuoteStr($word, $this->getReferenceTable());
                    $wca[] = $this->getReferenceTable().'.'.$field.' LIKE '.$word;
                }
            }
        }

        // Authors
        if (in_array('authors', $fields)) {
            $a_ships = $this->searchAuthorAuthorships($proc_words, $this->pid_list);
            if (count($a_ships) > 0) {
                $uids = [];
                foreach ($a_ships as $as) {
                    $uids[] = intval($as['pub_id']);
                }
                $wca[] = $this->getReferenceTable().'.uid IN ('.implode(',', $uids).')';
            }
        }

        if (count($wca) > 0) {
            $res = ' ( '.implode(PHP_EOL.' OR ', $wca).' )';
        }

        return $res;
    }

    /**
     * Searches and returns the authorships of authors whose name
     * looks like any of the words (array).
     *
     * @param array $words
     * @param array $pids
     * @param array $fields
     *
     * @return array An array containing the authors
     */
    protected function searchAuthorAuthorships(array $words, array $pids, array $fields = ['forename', 'surname']): array
    {
        $authorships = [];
        $authors = $this->searchByAuthor($words, $pids, $fields);
        if (count($authors) > 0) {
            $uids = [];
            foreach ($authors as $author) {
                $uids[] = intval($author['uid']);
            }
            $whereClause = 'author_id IN ('.implode(',', $uids).')';
            $whereClause .= $this->enable_fields($this->getAuthorshipTable());

            $res = $this->getDatabaseConnection()->exec_SELECTquery(
                '*',
                $this->getAuthorshipTable(),
                $whereClause
            );

            while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                $authorships[] = $row;
            }
        }

        return $authorships;
    }

    /**
     * Searches and returns authors whose name looks like any of the
     * words (array).
     *
     * @param array $words
     * @param array $pids
     * @param array $fields
     *
     * @return array An array containing the authors
     */
    protected function searchByAuthor(array $words, array $pids, array $fields = ['forename', 'surname'])
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::AUTHOR_TABLE);
        $query = $queryBuilder->select('*')
            ->from(self::AUTHOR_TABLE);

        $all_fields = ['forename', 'surname', 'url'];
        $authors = [];
        foreach ($words as $word) {
            $word = trim(strval($word));
            if (strlen($word) > 0) {
                $word = $this->getDatabaseConnection()->fullQuoteStr($word, $this->getAuthorTable());
                foreach ($all_fields as $field) {
                    if (in_array($field, $fields)) {
                        if (preg_match('/(^%|^_|[^\\\\]%|[^\\\\]_)/', $word)) {
                            $query->orWhere($queryBuilder->expr()->like($field, $queryBuilder->expr()->literal(sprintf('%%s%', $word))));
                        } else {
                            $query->orWhere($queryBuilder->expr()->eq($field, $word));
                        }
                    }
                }
            }
        }
        if (is_array($pids)) {
            $query->andWhere($queryBuilder->expr()->in('pid', $pids));
        } else {
            $query->andWhere($queryBuilder->expr()->eq('pid', (int) $pids));
        }

        $results = $query->execute()->fetchAll();

        foreach ($results as $result) {
            $authors[] = $result;
        }

        return $authors;
    }

    /**
     * This function returns the SQL ORDER clause configured
     * by the filter.
     *
     * @return string The ORDER clause string
     */
    protected function getOrderClause()
    {
        $orderClause = '';
        foreach ($this->filters as $filter) {
            if (is_array($filter['sorting'])) {
                $orderClause = [];
                $sortingFilterSize = count($filter['sorting']);
                for ($i = 0; $i < $sortingFilterSize; ++$i) {
                    if (isset($filter['sorting'][$i]['field']) && isset($filter['sorting'][$i]['dir'])) {
                        if (trim($filter['sorting'][$i]['field']) == self::REFERENCE_TABLE.'.editor') {
                            if (isset($this->editor_stop_words) && !empty($this->editor_stop_words)) {
                                $editorStopWords = explode('#', $this->editor_stop_words);
                                $oc = '';
                                for ($k = 0; $k < count($editorStopWords); ++$k) {
                                    $oc .= 'REPLACE(';
                                }
                                $oc .= trim($filter['sorting'][$i]['field']);
                                $oc .= ',';
                                foreach ($editorStopWords as $key => $editorStopWord) {
                                    $oc .= "'".$editorStopWord."', '')";
                                    if ($key < count($editorStopWords) - 1) {
                                        $oc .= ',';
                                    }
                                }
                                $orderClause[] = $oc;
                            }
                        } elseif (trim($filter['sorting'][$i]['field']) == self::REFERENCE_TABLE.'.title') {
                            if (isset($this->title_stop_words) && !empty($this->title_stop_words)) {
                                $titleStopWords = explode('#', $this->title_stop_words);
                                $oc = '';
                                for ($k = 0; $k < count($titleStopWords); ++$k) {
                                    $oc .= 'REPLACE(';
                                }
                                $oc .= trim($filter['sorting'][$i]['field']);
                                $oc .= ',';
                                foreach ($titleStopWords as $key => $titleStopWord) {
                                    $oc .= "'".$titleStopWord."',";
                                    switch ($titleStopWord) {
                                        case 'Ä':
                                            $oc .= "'Ae')";
                                            break;
                                        case 'ä':
                                            $oc .= "'ae')";
                                            break;
                                        case 'Ö':
                                            $oc .= "'Oe')";
                                            break;
                                        case 'ö':
                                            $oc .= "'oe')";
                                            break;
                                        case 'Ü':
                                            $oc .= "'Ue')";
                                            break;
                                        case 'ü':
                                            $oc .= "'ue')";
                                            break;
                                        default:
                                            $oc .= "'')";
                                    }
                                    if ($key < count($titleStopWords) - 1) {
                                        $oc .= ',';
                                    }
                                }
                                $orderClause[] = $oc;
                            }
                        } else {
                            if (1 == $sortingFilterSize && trim($filter['sorting'][$i]['field']) == self::AUTHOR_TABLE.'.surname') {
                                if (isset($this->title_stop_words) && !empty($this->title_stop_words)) {
                                    $titleStopWords = explode('#', $this->title_stop_words);
                                    $oc = '';
                                    for ($k = 0; $k < count($titleStopWords); ++$k) {
                                        $oc .= 'REPLACE(';
                                    }
                                    $oc .= self::REFERENCE_TABLE.'.title';
                                    $oc .= ',';
                                    foreach ($titleStopWords as $key => $titleStopWord) {
                                        $oc .= "'".$titleStopWord."',";
                                        switch ($titleStopWord) {
                                            case 'Ä':
                                                $oc .= "'Ae')";
                                                break;
                                            case 'ä':
                                                $oc .= "'ae')";
                                                break;
                                            case 'Ö':
                                                $oc .= "'Oe')";
                                                break;
                                            case 'ö':
                                                $oc .= "'oe')";
                                                break;
                                            case 'Ü':
                                                $oc .= "'Ue')";
                                                break;
                                            case 'ü':
                                                $oc .= "'ue')";
                                                break;
                                            default:
                                                $oc .= "'')";
                                        }
                                        if ($key < count($titleStopWords) - 1) {
                                            $oc .= ',';
                                        }
                                    }
                                }
                                $orderClause[] = 'CASE WHEN '.$filter['sorting'][$i]['field'].'!=\'\' THEN '.$filter['sorting'][$i]['field'].'ELSE '.$oc.' END '.$filter['sorting'][$i]['dir'];
                            } else {
                                $orderClause[] = $filter['sorting'][$i]['field'].' '.$filter['sorting'][$i]['dir'];
                            }
                        }
                    }
                }
                $orderClause = implode(',', $orderClause);
            }
        }

        return $orderClause;
    }

    /**
     * This function returns the SQL LIMIT clause configured
     * by the filter.
     *
     * @return string The LIMIT clause string
     */
    protected function getLimitClause()
    {
        $limitClause = '';
        foreach ($this->filters as $filter) {
            if (is_array($filter['limit'])) {
                if (isset($filter['limit']['start']) && isset($filter['limit']['num'])) {
                    $limitClause = intval($filter['limit']['start']).','.intval($filter['limit']['num']);
                }
            }
        }

        return $limitClause;
    }

    /**
     * This function returns the SQL SELECT clause
     * beginning with all the joins configured in $tables
     * included.
     *
     * @param array $fields
     * @param array $tables
     *
     * @return string The SELECT clause beginning
     */
    protected function select_clause_start($fields, $tables)
    {
        $selectClause = '';
        if (is_array($fields) && is_array($tables)) {
            $base = &$tables[0];
            $joins = '';
            $aliases = [$base['table']];
            $tableSize = count($tables);
            for ($i = 1; $i < $tableSize; ++$i) {
                $previous = $tables[$i - 1];
                $current = $tables[$i];

                if ((($previous['table'] == $this->getReferenceTable()) && ($current['table'] == $this->getAuthorTable())) ||
                    (($previous['table'] == $this->getAuthorTable()) && ($current['table'] == $this->getReferenceTable()))
                ) {
                    $joins .= $this->getSqlJoinPart($previous, $this->t_as_default, $aliases);
                    $joins .= $this->getSqlJoinPart($this->t_as_default, $current, $aliases);
                } else {
                    $joins .= $this->getSqlJoinPart($previous, $current, $aliases);
                }
            }

            $selectClause = 'SELECT '.implode(',', $fields);
            $selectClause .= ' FROM '.$base['table'].' '.$base['table'];
            $selectClause .= $joins;
        }

        return $selectClause;
    }

    /**
     * This function returns a SQL JOIN string for the requested
     * table if it has not yet been joined with the requested alias.
     *
     * @param array $table
     * @param array $join
     * @param array $aliases
     *
     * @return string The WHERE clause string
     */
    protected function getSqlJoinPart(array $table, $join, &$aliases)
    {
        $joinStatement = '';

        if (in_array($join['table'], $aliases)) {
            return '';
        }

        // The match fields
        $tableMatchField = '';
        $joinMatchField = '';

        switch ($table['table']) {
            case $this->getReferenceTable():
                switch ($join['table']) {
                    case $this->getAuthorshipTable():
                        $tableMatchField = 'uid';
                        $joinMatchField = 'pub_id';
                        break;
                }
                break;
            case $this->getAuthorshipTable():
                switch ($join['table']) {
                    case $this->getReferenceTable():
                        $tableMatchField = 'pub_id';
                        $joinMatchField = 'uid';
                        break;
                    case $this->getAuthorTable():
                        $tableMatchField = 'author_id';
                        $joinMatchField = 'uid';
                        break;
                }
                break;
            case $this->getAuthorTable():
                switch ($join['table']) {
                    case $this->getAuthorshipTable():
                        $tableMatchField = 'uid';
                        $joinMatchField = 'author_id';
                        break;
                }
                break;
        }

        $aliases[] = $join['table'];
        $joinStatement .= ' LEFT JOIN '.$join['table'].' AS '.$join['table'];
        $joinStatement .= ' ON '.$table['table'].'.'.$tableMatchField;
        $joinStatement .= '='.$join['table'].'.'.$joinMatchField;

        return $joinStatement;
    }

    /**
     * Returns the latest timestamp found in the database.
     *
     * @return int The publication data from the database
     */
    public function getLatestTimestamp()
    {
        $maximalValueFromReferenceTable = 'max('.$this->getReferenceTable().'.tstamp)';
        $maximumValueFromAuthorTable = 'max('.$this->getReferenceTable().'.tstamp)';

        $query = $this->getReferenceSelectClause(
            [$maximalValueFromReferenceTable, $maximumValueFromAuthorTable],
            null,
            null
        );
        $res = $this->getDatabaseConnection()->sql_query($query);
        $row = $this->getDatabaseConnection()->sql_fetch_assoc($res);

        if (is_array($row)) {
            return max($row);
        }

        return 0;
    }

    /**
     * Returns a publication histogram to a given key.
     * I.e. the number of publications per year if year
     * is the requested key.
     *
     * @param string $field
     *
     * @return array A histogram
     */
    public function getHistogram($field = 'year')
    {
        $histogram = [];

        $query = $this->getReferenceSelectClause(
            [$this->getReferenceTable().'.'.$field],
            $this->getReferenceTable().'.'.$field.' ASC'
        );
        $res = $this->getDatabaseConnection()->sql_query($query);

        $cVal = null;
        $cNum = null;
        while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
            $val = $row[$field];
            if ($cVal == $val) {
                ++$cNum;
            } else {
                $cVal = $val;
                $histogram[$val] = 1;
                $cNum = &$histogram[$val];
            }
        }
        $this->getDatabaseConnection()->sql_free_result($res);

        return $histogram;
    }

    /**
     * Fetches all author surnames.
     *
     * @return array An array containing the authors
     */
    public function getSurnamesOfAllAuthors()
    {
        $authors = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::AUTHOR_TABLE);

        $results = $queryBuilder->select('surname')
            ->from(self::AUTHOR_TABLE)
            ->groupBy('surname')
            ->orderBy('surname', 'ASC')
            ->execute()
            ->fetchAll();

        foreach ($results as $result) {
            $author = new Author();
            $author->setSurName($result['surname']);
            $authors[] = $author;
        }

        return $authors;
    }

    /**
     * This retrieves the publication data from the database.
     *
     * @param int $uid
     *
     * @return Reference The publication data from the database
     */
    public function getPublicationDetails(int $uid): Reference
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(static::REFERENCE_TABLE);

        $queryBuilder->select('*')
            ->from(static::REFERENCE_TABLE)
            ->where($queryBuilder->expr()->eq('uid', $uid));

        if (count($this->pid_list) > 0) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('pid', $this->pid_list));
        }

        if (!$this->show_hidden) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq('hidden', 0));
        }

        $results = $queryBuilder->execute()->fetchAll();
        $publication = $results[0];
        if (is_array($results)) {
            $publication = GeneralUtility::makeInstance(ItemTransformerService::class)->transformPublication($publication);
            $publication->setAuthors($this->getAuthorByPublication($publication['uid']));
            $publication->setModificationKey($this->getModificationKey($publication));
        }

        return $publication;
    }

    /**
     * Fetches the authors of a publication.
     *
     * @param Reference $publication
     *
     * @return array An array containing author array
     */
    protected function getAuthorByPublication(Reference $publication): array
    {
        $authors = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::AUTHOR_TABLE);
        $query = $queryBuilder
            ->select('*')
            ->from(self::AUTHOR_TABLE, 'a')
            ->leftJoin('a', self::AUTHORSHIP_TABLE, 'aus', 'aus.author_id = a.uid')
            ->where($queryBuilder->expr()->eq('aus.pub_id', $publication->getUid()))
            ->orderBy('aus.sorting', 'ASC')
            ->execute()
            ->fetchAll();

        foreach ($query as $authorData) {
            $author = new Author();
            $author
                ->setSurName($authorData['surname'])
                ->setForeName($authorData['forename'])
                ->setUrl($authorData['url'])
                ->setFrontEndUserId($authorData['fe_user_id'])
                ->setUid($authorData['uid']);

            $authors[] = $author;
        }

        return $authors;
    }

    /**
     * This returns the modification key for a publication.
     *
     * @param Reference $publication
     *
     * @return string The mod_key string
     */
    private function getModificationKey(Reference $publication)
    {
        $modificationKey = '';
        /** @var Author $author */
        foreach ($publication->getAuthors() as $author) {
            $modificationKey .= $author->getSurName();
            $modificationKey .= $author->getForeName();
        }
        $modificationKey .= $publication->getTitle();
        $modificationKey .= (string) $publication->getCrdate();
        $modificationKey .= (string) $publication->getTstamp();
        $hashedModificationKey = sha1($modificationKey);

        return $hashedModificationKey;
    }

    /**
     * This initializes the reference fetching.
     * Executes a select query.
     */
    public function getAllReferences(): array
    {
        $references = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::REFERENCE_TABLE);
        $query = $queryBuilder->select('*')
            ->from(self::REFERENCE_TABLE)
            ->execute()
            ->fetchAll();

        foreach ($query as $referenceData) {
            $itemTransformer = GeneralUtility::makeInstance(ItemTransformerService::class, $this->configuration);
            $reference = $itemTransformer->transformPublication($referenceData);
            $reference->setAuthors($this->getAuthorByPublication($reference));
            $reference->setModificationKey($this->getModificationKey($reference));

            $references[] = GeneralUtility::makeInstance(ItemTransformerService::class, $this->configuration)->transformPublication($referenceData);
        }

        return $references;
    }

    /**
     * Returns the number of references that will be fetched.
     *
     * @return int The number of references
     */
    public function numberOfReferencesToBeFetched()
    {
        return $this->getDatabaseConnection()->sql_num_rows($this->databaseResource);
    }

    /**
     * Fetches a reference.
     *
     * @return Reference
     */
    public function getReference(): Reference
    {
        $itemTransformer = GeneralUtility::makeInstance(ItemTransformerService::class, $this->configuration);
        $row = $this->getDatabaseConnection()->sql_fetch_assoc($this->databaseResource);

        if ($row) {
            $reference = $itemTransformer->transformPublication($row);
            $reference->setAuthors($this->getAuthorByPublication($reference));
            $reference->setModificationKey($this->getModificationKey($reference));

            return $reference;
        }

        return new Reference();
    }

    /**
     * Fetches an authorship.
     *
     * @param array $authorship
     *
     * @return null|array The matching authorship row or NULL
     */
    public function getAuthorships($authorship)
    {
        $ret = [];
        if (is_array($authorship)) {
            if (isset($authorship['pub_id']) || isset($authorship['author_id']) || isset($authorship['pid'])) {
                $whereClause = [];
                if (isset($authorship['pub_id'])) {
                    $whereClause[] = 'pub_id='.intval($authorship['pub_id']);
                }
                if (isset($authorship['author_id'])) {
                    $whereClause[] = 'author_id='.intval($authorship['author_id']);
                }
                if (isset($authorship['pid'])) {
                    $whereClause[] = 'pid='.intval($authorship['pid']);
                }
                $whereClause = implode(' AND ', $whereClause);
                $whereClause .= $this->enable_fields($this->getAuthorshipTable());

                $res = $this->getDatabaseConnection()->exec_SELECTquery(
                    '*',
                    $this->getAuthorshipTable(),
                    $whereClause
                );

                while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                    $ret[] = $row;
                }
            }
        }

        return $ret;
    }

    /**
     * @param string $citationId
     *
     * @return int
     */
    public function getUidFromCitationId($citationId)
    {
        $citationId = filter_var($citationId, FILTER_SANITIZE_STRING);

        $whereClause = [];

        if (count($this->pid_list) > 0) {
            $csv = Utility::implode_intval(',', $this->pid_list);
            $whereClause[] = 'pid IN ('.$csv.')';
        }

        $whereClause[] = 'citeid = "'.$citationId.'"';

        $whereClause = implode(' AND ', $whereClause);
        $whereClause .= $this->enable_fields($this->getReferenceTable(), '', $this->show_hidden);

        $query = $this->getDatabaseConnection()->exec_SELECTQuery(
            'uid',
            $this->getReferenceTable(),
            $whereClause,
            '',
            '',
            1
        );

        while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($query)) {
            $result = $row['uid'];
        }

        return $result;
    }

    /**
     * @param array $pidList
     */
    public function setPidList($pidList)
    {
        $this->pid_list = $pidList;
    }

    /**
     * @param $showHidden
     */
    public function setShowHidden($showHidden)
    {
        $this->show_hidden = $showHidden;
    }

    /**
     * @return string
     */
    public function getSearchPrefix()
    {
        return $this->searchPrefix;
    }

    /**
     * @param string $searchPrefix
     */
    public function setSearchPrefix($searchPrefix)
    {
        $this->searchPrefix = $searchPrefix;
    }

    /**
     * @return array
     */
    public function getSearchFields()
    {
        return $this->search_fields;
    }

    /**
     * This sets the selected search fields to be used in search query
     * query compositions.
     *
     * @param array $search_fields
     */
    public function set_searchFields($search_fields)
    {
        $this->search_fields = $search_fields;
    }

    /**
     * @param array $searchFields
     */
    public function setSearchFields($searchFields)
    {
        $this->search_fields = $searchFields;
    }

    /**
     * @return string
     */
    public function getSortPrefix()
    {
        return $this->sortPrefix;
    }

    /**
     * @param string $sortPrefix
     */
    public function setSortPrefix($sortPrefix)
    {
        $this->sortPrefix = $sortPrefix;
    }

    /**
     * @return array
     */
    public function getSortFields()
    {
        return $this->sortFields;
    }

    /**
     * @return array
     */
    public function getAllowedTags()
    {
        return $this->allowedTags;
    }

    /**
     * @param array $allowedTags
     */
    public function setAllowedTags($allowedTags)
    {
        $this->allowedTags = $allowedTags;
    }

    /**
     * Clears the page cache of all selected pages.
     */
    protected function clearPageCache()
    {
        if ($this->getClearCache()) {
            /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $clear_cache = [];

            if (is_object($GLOBALS['BE_USER']) || is_array($GLOBALS['BE_USER']->user)) {
                $dataHandler->start([], [], $GLOBALS['BE_USER']);
                // Find storage cache clear requests
                foreach ($this->pid_list as $pid) {
                    $tSConfig = $dataHandler->getTCEMAIN_TSconfig($pid);
                    if (is_array($tSConfig) && isset($tSConfig['clearCacheCmd'])) {
                        $clear_cache[] = $tSConfig['clearCacheCmd'];
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
     * @return bool
     */
    public function getClearCache()
    {
        return $this->clearCache;
    }

    /**
     * @param bool $clearCache
     */
    public function setClearCache($clearCache)
    {
        $this->clearCache = $clearCache;
    }
}
