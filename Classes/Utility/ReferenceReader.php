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
 * This class provides the reference database interface
 * and some utility methods
 */
class ReferenceReader {

	protected $filter;

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $cObj;

	/**
	 * @var boolean|\mysqli_result|object
	 */
	protected $databaseResource = NULL;

	/**
	 * @var bool
	 */
	protected $clearCache = FALSE;

	/**
	 * @var array
	 */
	public $pid_list = array();

	/**
	 * Show hidden references
	 * @var bool
	 */
	public $show_hidden;

	/**
	 * @var string
	 */
	protected $referenceTable = 'tx_bib_domain_model_reference';

	/**
	 * @var string
	 */
	protected $authorTable = 'tx_bib_domain_model_author';

	/**
	 * @var string
	 */
	protected $authorshipTable = 'tx_bib_domain_model_authorships';

	/**
	 * @var string
	 */
	protected $referenceTableAlias = 't_ref';

	/**
	 * @var string
	 */
	protected $authorTableAlias = 't_authors';

	/**
	 * @var string
	 */
	public $authorshipTableAlias = 't_aships';

	/**
	 * @var array
	 */
	public $t_ref_default = array();

	/**
	 * @var array
	 */
	public $t_as_default = array();

	/**
	 * @var array
	 */
	public $t_au_default = array();

	/**
	 * The following tags are allowed in a reference string
	 * @var array
	 */
	protected $allowedTags = array('em', 'strong', 'sup', 'sub');

	/**
	 * These are the author relevant fields
	 * that can be found in the reference table $this->getAuthorTable().
	 * TYPO3 special fields like pid or uid are not listed here
	 * @var array
	 */
	protected $authorFields = array(
		'surname', 'forename', 'url', 'fe_user_id'
	);

	/**
	 * @var array
	 */
	protected $authorAllFields;

	/**
	 * @var array
	 */
	protected $filters = array();

	/**
	 * These are the publication relevant fields
	 * that can be found in the reference table $this->referenceTable.
	 * TYPO3 special fields like pid or uid are not listed here
	 * @var array
	 */
	protected $referenceFields = array(
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
		'borrowed_by'
	);

	/**
	 * These are the publication relevant fields
	 * that can be found in the reference table $this->referenceTable.
	 * including the important TYPO3 specific fields
	 */
	public $refAllFields;


	/**
	 * These are the publication relevant fields
	 * that can be found in a php publication array.
	 * TYPO3 special fields like pid or uid are not listed here
	 *
	 * @var array
	 */
	protected $publicationFields;

	/**
	 * These are the publication relevant fields
	 * that can be found in a php publication array.
	 * This includes TYPO3 variables (pid,uid, etc.)
	 */
	public $pubAllFields;

	/**
	 * @var array
	 */
	public $allBibTypes = array(
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
		21 => 'url'
	);

	/**
	 * @var array
	 */
	public $allStates = array(
		0 => 'published',
		1 => 'accepted',
		2 => 'submitted',
		3 => 'unpublished',
		4 => 'in_preparation'
	);

	/**
	 * The constructor
	 *
	 * @return \Ipf\Bib\Utility\ReferenceReader
	 */
	public function __construct() {

		$this->t_ref_default['table'] = $this->getReferenceTable();
		$this->t_ref_default['alias'] = $this->getReferenceTableAlias();

		$this->t_as_default['table'] = $this->getAuthorshipTable();
		$this->t_as_default['alias'] = $this->getAuthorshipTableAlias();

		$this->t_au_default['table'] = $this->getAuthorTable();
		$this->t_au_default['alias'] = $this->getAuthorTableAlias();

		// setup authorAllFields
		$this->setAuthorAllFields(
			array(
				'uid', 'pid', 'tstamp', 'crdate', 'cruser_id'
			)
		);
		$this->setAuthorAllFields(
			array_merge($this->getAuthorAllFields(), $this->getAuthorFields())
		);

		// setup refAllFields
		$typo3_fields = array(
			'uid', 'pid', 'hidden', 'tstamp', 'sorting', 'crdate', 'cruser_id'
		);
		$this->refAllFields = array_merge($typo3_fields, $this->getReferenceFields());

		// setup pubFields
		$this->setPublicationFields($this->getReferenceFields());
		$this->publicationFields[] = 'authors';

		// setup pubAllFields
		$this->pubAllFields = array_merge($typo3_fields, $this->getPublicationFields());
	}

	/**
	 * set the cObject
	 *
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 * @return void
	 */
	public function set_cObj(&$cObj) {
		$this->cObj =& $cObj;
	}

	/**
	 * Clears the page cache of all selected pages
	 *
	 * @return void
	 */
	protected function clearPageCache() {
		if ($this->getClearCache()) {
			/** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
			$dataHandler = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$clear_cache = array();

			if (is_object($GLOBALS['BE_USER']) || is_array($GLOBALS['BE_USER']->user)) {
				$dataHandler->start(array(), array(), $GLOBALS['BE_USER']);
				// Find storage cache clear requests
				foreach ($this->pid_list as $pid) {
					$tSConfig = $dataHandler->getTCEMAIN_TSconfig($pid);
					if (is_array($tSConfig) && isset ($tSConfig['clearCacheCmd'])) {
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
	 * This changes the character set of a publication (array)
	 *
	 * @param array $publication
	 * @param string $originalCharset
	 * @param string $targetCharset
	 * @return array The character set adjusted publication
	 */
	public function change_pub_charset($publication, $originalCharset, $targetCharset) {
		if (is_array($publication) && strlen($originalCharset) && strlen($targetCharset)
				&& ($originalCharset != $targetCharset)
		) {
			$keys = array_keys($publication);
			foreach ($keys as $key) {
				switch ($key) {
					case 'authors':
						if (is_array($publication[$key])) {
							foreach ($publication[$key] as &$author) {
								$author['forename'] = $GLOBALS['TSFE']->csConvObj->conv(
									$author['forename'],
									$originalCharset,
									$targetCharset
								);

								$author['surname'] = $GLOBALS['TSFE']->csConvObj->conv(
									$author['surname'],
									$originalCharset,
									$targetCharset
								);
							}
						}
						break;
					default:
						if (is_string($publication[$key]))
							$publication[$key] = $GLOBALS['TSFE']->csConvObj->conv(
								$publication[$key],
								$originalCharset,
								$targetCharset
							);
				}
			}
		}
		return $publication;
	}

	/**
	 * This appends a filter to the filter list
	 *
	 * @param array $filter
	 * @return void
	 */
	public function append_filter($filter) {
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
	 * This sets the filter which will be asked for most
	 * query compositions
	 *
	 * @param array $filter
	 * @return void
	 */
	public function set_filter($filter) {
		$this->filters = array();
		$this->append_filter($filter);
	}


	/**
	 * This sets the filters which will be asked for most
	 * query compositions
	 *
	 * @param array $filters
	 * @return void
	 */
	public function set_filters($filters) {
		$this->filters = array();
		foreach ($filters as $filter) {
			$this->append_filter($filter);
		}
	}


	/**
	 * Returns the where clause part for a table
	 *
	 * @param string $table
	 * @param string $alias
	 * @param bool $show_hidden
	 * @return string The where clause part
	 */
	public function enable_fields($table, $alias = '', $show_hidden = FALSE) {
		$whereClause = '';
		if (strlen($alias) == 0)
			$alias = $table;
		if (isset ($this->cObj)) {
			$whereClause = $this->cObj->enableFields($table, $show_hidden ? 1 : 0);
		} else {
			$whereClause = ' AND ' . $table . '.deleted=0';
		}
		if ($alias != $table)
			$whereClause = str_replace($table, $alias, $whereClause);

		return $whereClause;
	}


	/**
	 * This function returns the SQL SELECT clause
	 * beginning with all the joins configured in $tables
	 * included
	 *
	 * @param array $fields
	 * @param array $tables
	 * @return string The SELECT clause beginning
	 */
	protected function select_clause_start($fields, $tables) {
		$selectClause = '';
		if (is_array($fields) && is_array($tables)) {
			$base =& $tables[0];
			$joins = '';
			$aliases = array($base['alias']);
			for ($i = 1; $i < sizeof($tables); $i++) {
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

			$selectClause = 'SELECT ' . implode(',', $fields) . "\n";
			$selectClause .= ' FROM ' . $base['table'] . ' ' . $base['alias'] . "\n";
			$selectClause .= $joins;
		}
		return $selectClause;
	}


	/**
	 * This function returns a SQL JOIN string for the requested
	 * table if it has not yet been joined with the requested alias
	 *
	 * @param string $table
	 * @param array $join
	 * @param array $aliases
	 * @return string The WHERE clause string
	 */
	protected function getSqlJoinPart($table, $join, &$aliases) {
		$joinStatement = '';

		if (in_array($join['alias'], $aliases))
			return '';

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

		$aliases[] = $join['alias'];
		$joinStatement .= ' INNER JOIN ' . $join['table'] . ' AS ' . $join['alias'];
		$joinStatement .= ' ON ' . $table['alias'] . '.' . $tableMatchField;
		$joinStatement .= '=' . $join['alias'] . '.' . $joinMatchField;
		$joinStatement .= "\n";

		return $joinStatement;
	}


	/**
	 * This function returns the SQL WHERE clause configured
	 * by the filters
	 *
	 * @param array $columns
	 * @return string The WHERE clause string
	 */
	protected function getReferenceWhereClause(&$columns) {

		$WCA = array();
		$columns = array();
		$runvar = array(
			'columns' => array(),
			'aShip_count' => 0,
		);

		// Get where parts for each filter
		foreach ($this->filters as $filter) {
			$parts = $this->getFilterWhereClauseParts($filter, $runvar);
			$WCA = array_merge($WCA, $parts);
		}

		$whereClause = implode(' AND ', $WCA);

		if (strlen($whereClause) > 0) {
			$columns = array_merge(array($this->getReferenceTableAlias()), $runvar['columns']);
			$columns = array_unique($columns);

			foreach ($columns as &$column) {
				$column = preg_replace('/\.[^\.]*$/', '', $column);
				if (!(strpos($column, $this->getReferenceTableAlias()) === FALSE)) {
					$whereClause .= $this->enable_fields($this->getReferenceTable(), $column, $this->show_hidden);
				}
				if (!(strpos($column, $this->authorshipTableAlias) === FALSE)) {
					$whereClause .= $this->enable_fields($this->getAuthorshipTable(), $column);
				}
			}
		}

		return $whereClause;
	}


	/**
	 * This function returns the SQL WHERE clause parts for one filter
	 *
	 * @param array $filter
	 * @param array $runvar
	 * @return array The WHERE clause parts in an array
	 */
	protected function getFilterWhereClauseParts($filter, &$runvar) {

		$whereClause = array();

		// Filter by UID
		if (isset ($filter['FALSE'])) {
			$whereClause[] = 'FALSE';
			return $whereClause;
		}

		// Filter by UID
		if (is_array($filter['uid']) && (sizeof($filter['uid']) > 0)) {
			$csv = Utility::implode_intval(',', $filter['uid']);
			$whereClause[] = $this->getReferenceTableAlias() . '.uid IN (' . $csv . ')';
		}

		// Filter by storage PID
		if (is_array($filter['pid']) && (sizeof($filter['pid']) > 0)) {
			$csv = Utility::implode_intval(',', $filter['pid']);
			$whereClause[] = $this->getReferenceTableAlias() . '.pid IN (' . $csv . ')';
		}

		// Filter by year
		if (is_array($filter['year']) && (sizeof($filter['year']) > 0)) {
			$wca = '';
			// years
			if (is_array($filter['year']['years']) && (sizeof($filter['year']['years']) > 0)) {
				$csv = Utility::implode_intval(',', $filter['year']['years']);
				$wca .= ' ' . $this->getReferenceTableAlias() . '.year IN (' . $csv . ')' . "\n";
			}
			// ranges
			if (is_array($filter['year']['ranges']) && sizeof($filter['year']['ranges'])) {
				if (sizeof($filter['year']['ranges'])) {
					for ($i = 0; $i < sizeof($filter['year']['ranges']); $i++) {
						$both = (isset ($filter['year']['ranges'][$i]['from']) && isset ($filter['year']['ranges'][$i]['to'])) ? TRUE : FALSE;
						if (strlen($wca)) {
							$wca .= ' OR ';
						}
						if ($both) {
							$wca .= '(';
						}
						if (isset ($filter['year']['ranges'][$i]['from'])) {
							$wca .= $this->getReferenceTableAlias() . '.year >= ' . intval($filter['year']['ranges'][$i]['from']);
						}
						if ($both) {
							$wca .= ' AND ';
						}
						if (isset ($filter['year']['ranges'][$i]['to'])) {
							$wca .= $this->getReferenceTableAlias() . '.year <= ' . intval($filter['year']['ranges'][$i]['to']);
						}
						if ($both) {
							$wca .= ')';
						}
					}
				}
			}
			$whereClause[] = '(' . $wca . ')';
		}

		// Filter by authors
		if (is_array($filter['author']) && (sizeof($filter['author']) > 0)) {
			$f =& $filter['author'];
			if ($f['rule'] == 1) {
				// AND
				if (is_array($f['sets']) && (sizeof($f['sets']) > 0)) {
					$wc_set = array();
					for ($i = 0; $i < sizeof($f['sets']); $i++) {
						$set = $f['sets'][$i];
						$uid_lst = array();
						foreach ($set as $au) {
							if (is_numeric($au['uid']))
								$uid_lst[] = intval($au['uid']);
						}
						if (sizeof($uid_lst) > 0) {
							$uid_lst = implode(',', $uid_lst);
							$col_num = $runvar['aShip_count'];
							$runvar['aShip_count'] += 1;
							$column = $this->authorshipTableAlias . (($col_num > 0) ? strval($col_num) : '');
							$wc_set[] = $column . '.author_id IN (' . $uid_lst . ')';
							$runvar['columns'][] = $column;
						}
					}

					// Append set clause
					if (sizeof($wc_set) > 0) {
						$whereClause = array_merge($whereClause, $wc_set);
					} else {
						$whereClause[] = 'FALSE';
					}

				} else {
					$whereClause[] = 'FALSE';
				}

			} else {
				// OR
				if (sizeof($f['authors']) > 0) {
					$authors =& $f['authors'];
					$uid_lst = array();
					foreach ($authors as $au) {
						if (is_numeric($au['uid']))
							$uid_lst[] = intval($au['uid']);
					}
					if (sizeof($uid_lst) > 0) {
						$uid_lst = implode(',', $uid_lst);
						$col_num = $runvar['aShip_count'];
						$runvar['aShip_count'] += 1;
						$column = $this->authorshipTableAlias . (($col_num > 0) ? strval($col_num) : '');
						$whereClause[] = $column . '.author_id IN (' . $uid_lst . ')';
						$runvar['columns'][] = $column;
					} else {
						$whereClause[] = 'FALSE';
					}
				}
			}
		}

		// Filter by bibtype
		if (is_array($filter['bibtype']) && (sizeof($filter['bibtype']) > 0)) {
			$f =& $filter['bibtype'];
			if (is_array($f['types']) && (sizeof($f['types']) > 0)) {
				$csv = Utility::implode_intval(',', $f['types']);
				$whereClause[] = $this->getReferenceTableAlias() . '.bibtype IN (' . $csv . ')';
			}
		}

		// Filter by publication state
		if (is_array($filter['state']) && (sizeof($filter['state']) > 0)) {
			$f =& $filter['state'];
			if (is_array($f['states']) && (sizeof($f['states']) > 0)) {
				$csv = Utility::implode_intval(',', $f['states']);
				$whereClause[] = $this->getReferenceTableAlias() . '.state IN (' . $csv . ')';
			}
		}

		// Filter by origin
		if (is_array($filter['origin']) && (sizeof($filter['origin']) > 0)) {
			$f =& $filter['origin'];
			if (is_numeric($f['origin'])) {
				$wca = $this->getReferenceTableAlias() . '.extern = \'0\'';
				if (intval($f['origin']) != 0) {
					$wca = $this->getReferenceTableAlias() . '.extern != \'0\'';
				}
				$whereClause[] = $wca;
			}
		}

		// Filter by reviewed
		if (is_array($filter['reviewed']) && (sizeof($filter['reviewed']) > 0)) {
			$f =& $filter['reviewed'];
			if (is_numeric($f['value'])) {
				$wca = $this->getReferenceTableAlias() . '.reviewed = \'0\'';
				if (intval($f['value']) != 0)
					$wca = $this->getReferenceTableAlias() . '.reviewed != \'0\'';
				$whereClause[] = $wca;
			}
		}

		// Filter by borrowed
		if (is_array($filter['borrowed']) && (sizeof($filter['borrowed']) > 0)) {
			$f =& $filter['borrowed'];
			if (is_numeric($f['value'])) {
				$wca = 'LENGTH(' . $this->getReferenceTableAlias() . '.borrowed_by) = \'0\'';
				if (intval($f['value']) != 0)
					$wca = 'LENGTH(' . $this->getReferenceTableAlias() . '.borrowed_by) != \'0\'';
				$whereClause[] = $wca;
			}
		}

		// Filter by in_library
		if (is_array($filter['in_library']) && (sizeof($filter['in_library']) > 0)) {
			$f =& $filter['in_library'];
			if (is_numeric($f['value'])) {
				$wca = $this->getReferenceTableAlias() . '.in_library = \'0\'';
				if (intval($f['value']) != 0)
					$wca = $this->getReferenceTableAlias() . '.in_library != \'0\'';
				$whereClause[] = $wca;
			}
		}

		// Filter by citeid
		if (is_array($filter['citeid']) && (sizeof($filter['citeid']) > 0)) {
			$f =& $filter['citeid'];
			if (is_array($f['ids']) && (sizeof($f['ids']) > 0)) {
				$wca = $this->getReferenceTableAlias() . '.citeid IN (';
				for ($i = 0; $i < sizeof($f['ids']); $i++) {
					if ($i > 0) $wca .= ',';
					$wca .= $GLOBALS['TYPO3_DB']->fullQuoteStr($f['ids'][$i], $this->getReferenceTable());
				}
				$wca .= ')';
				$whereClause[] = $wca;
			}
		}


		// Filter by tags
		if (is_array($filter['tags']) && (sizeof($filter['tags']) > 0)) {
			$f =& $filter['tags'];
			if (is_array($f['words']) && (sizeof($f['words']) > 0)) {
				$wca = array();

				if ($f['rule'] == 0) { // OR
					$wca[] = $this->getFilterSearchFieldsClause($f['words'], array('tags'));
				} else { // AND
					foreach ($f['words'] as $word) {
						$wca[] = $this->getFilterSearchFieldsClause(array($word), array('tags'));
					}
				}

				foreach ($wca as $app) {
					if (strlen($app) > 0) $whereClause[] = $app;
				}
			}
		}


		// Filter by keywords
		if (is_array($filter['keywords']) && (sizeof($filter['keywords']) > 0)) {
			$f =& $filter['keywords'];
			if (is_array($f['words']) && (sizeof($f['words']) > 0)) {
				$wca = array();

				if ($f['rule'] == 0) { // OR
					$wca[] = $this->getFilterSearchFieldsClause($f['words'], array('keywords'));
				} else { // AND
					foreach ($f['words'] as $word) {
						$wca[] = $this->getFilterSearchFieldsClause(array($word), array('keywords'));
					}
				}

				foreach ($wca as $app) {
					if (strlen($app) > 0) $whereClause[] = $app;
				}
			}
		}

		// General keyword search
		if (is_array($filter['all']) && (sizeof($filter['all']) > 0)) {
			$f =& $filter['all'];

			if (is_array($f['words']) && (sizeof($f['words']) > 0)) {
				$wca = array();

				$fields = $this->getPublicationFields();
				$fields[] = 'full_text';
				if (is_array($f['exclude'])) {
					$fields = array_diff($fields, $f['exclude']);
				}

				if ($f['rule'] == 0) { // OR
					$wca[] = $this->getFilterSearchFieldsClause($f['words'], $fields);
				} else { // AND
					foreach ($f['words'] as $word) {
						$wca[] = $this->getFilterSearchFieldsClause(array($word), $fields);
					}
				}

				foreach ($wca as $app) {
					if (strlen($app) > 0) $whereClause[] = $app;
				}

			}
		}

		return $whereClause;
	}


	/**
	 * This function returns the SQL WHERE clause part
	 * for the search for keywords (OR)
	 *
	 * @param array $words An array or words
	 * @param array $fields An array of fields to search in
	 * @return string The WHERE clause string
	 */
	protected function getFilterSearchFieldsClause($words, $fields) {
		$res = '';
		$wca = array();

		// Wildcard words
		$proc_words = array();
		foreach ($words as $word) {
			if (is_array($word)) {
				foreach ($word as $oword) {
					$oword = trim(strval($oword));
					if (strlen($oword) > 0)
						$proc_words[] = $oword;
				}
			} else {
				$oword = trim(strval($word));
				if (strlen($oword) > 0)
					$proc_words[] = $oword;
			}
		}

		// Fields
		$refFields = $this->getReferenceFields();
		$refFields[] = 'full_text';
		foreach ($fields as $field) {
			if (in_array($field, $refFields)) {
				foreach ($proc_words as $word) {
					$word = $GLOBALS['TYPO3_DB']->fullQuoteStr($word, $this->getReferenceTable());
					$wca[] = $this->getReferenceTableAlias() . '.' . $field . ' LIKE ' . $word;
				}
			}
		}

		// Authors
		if (in_array('authors', $fields)) {
			$a_ships = $this->searchAuthorAuthorships($proc_words, $this->pid_list);
			if (sizeof($a_ships) > 0) {
				$uids = array();
				foreach ($a_ships as $as) {
					$uids[] = intval($as['pub_id']);
				}
				$wca[] = $this->getReferenceTableAlias() . '.uid IN (' . implode(',', $uids) . ')';
			}
		}

		if (sizeof($wca) > 0) {
			$res = ' ( ' . implode("\n" . ' OR ', $wca) . ' )';
		}

		return $res;
	}


	/**
	 * Returns a search word object as it is required by the 'all' search
	 * filter argument
	 *
	 * @param string $word
	 * @param string $charset
	 * @param array $wrap
	 * @return string|array The search object (string or array)
	 */
	public function getSearchTerm($word, $charset, $wrap = array('%', '%')) {
		$spec = htmlentities($word, ENT_QUOTES, $charset);
		$words = array($word);
		if ($spec != $word) {
			$words[] = $spec;
		}
		if (is_array($wrap) && (sizeof($wrap) > 0)) {
			foreach ($words as $key => $txt) {
				$words[$key] = strval($wrap[0]) . strval($txt) . strval($wrap[1]);
			}
		}
		if (sizeof($words) == 1) {
			return $words[0];
		}
		return $words;
	}


	/**
	 * This function returns the SQL ORDER clause configured
	 * by the filter
	 *
	 * @return string The ORDER clause string
	 */
	protected function getOrderClause() {
		$orderClause = '';
		foreach ($this->filters as $filter) {
			if (is_array($filter['sorting'])) {
				$sortings =& $filter['sorting'];
				$orderClause = array();
				for ($i = 0; $i < sizeof($sortings); $i++) {
					$s =& $sortings[$i];
					if (isset ($s['field']) && isset ($s['dir'])) {
						$orderClause[] = $s['field'] . ' ' . $s['dir'];
					}
				}
				$orderClause = implode(',', $orderClause);
			}
		}
		return $orderClause;
	}


	/**
	 * This function returns the SQL LIMIT clause configured
	 * by the filter
	 *
	 * @return string The LIMIT clause string
	 */
	protected function getLimitClause() {
		$limitClause = '';
		foreach ($this->filters as $filter) {
			if (is_array($filter['limit'])) {
				if (isset ($filter['limit']['start']) && isset ($filter['limit']['num'])) {
					$limitClause = intval($filter['limit']['start']) . ',' . intval($filter['limit']['num']);
				}
			}
		}
		return $limitClause;
	}


	/**
	 * This function returns the SQL LIMIT clause configured
	 * by the filter
	 *
	 * @param array $fields
	 * @param string $order
	 * @param string $group
	 * @return string The LIMIT clause string
	 */
	protected function getReferenceSelectClause($fields, $order = '', $group = '') {
		if (!is_array($fields)) {
			$fields = array($fields);
		}

		$columns = array();
		$whereClause = $this->getReferenceWhereClause($columns);

		$groupClause = '';
		if (is_string($group))
			$groupClause = strlen($group) ? $group : $this->getReferenceTableAlias() . '.uid';

		$OC = '';
		if (is_string($order))
			$OC = strlen($order) ? $order : $this->getOrderClause();

		$LC = $this->getLimitClause();

		// Find the tables that should be included
		$tables = array($this->t_ref_default);
		foreach ($fields as $field) {
			if (!(strpos($field, $this->authorshipTableAlias) === FALSE))
				$tables[] = $this->t_as_default;
			if (!(strpos($field, $this->getAuthorTableAlias()) === FALSE))
				$tables[] = $this->t_au_default;
		}

		foreach ($columns as $column) {
			if (!(strpos($column, $this->authorshipTableAlias) === False)) {
				$table = $this->t_as_default;
				$table['alias'] = $column;

				$tables[] = $this->t_ref_default;
				$tables[] = $table;
			}
		}

		$q = $this->select_clause_start($fields, $tables);
		if (strlen($whereClause))
			$q .= ' WHERE ' . $whereClause . "\n";
		if (strlen($groupClause))
			$q .= ' GROUP BY ' . $groupClause . "\n";
		if (strlen($OC))
			$q .= ' ORDER BY ' . $OC . "\n";
		if (strlen($LC))
			$q .= ' LIMIT ' . $LC . "\n";
		$q .= ';';

		return $q;
	}


	/**
	 * Checks if a publication that has not the given uid
	 * but the citeId exists in the database. The lookup is restricted
	 * to the current storage folders ($filter['pid'])
	 *
	 * @param string $citeId
	 * @param int $uid ;
	 * @return bool TRUE on existence FALSE otherwise
	 */
	public function citeIdExists($citeId, $uid = -1) {

		if (strlen($citeId) == 0) {
			return FALSE;
		}

		$num = 0;
		$whereClause = array();
		$whereClause[] = 'citeid=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($citeId, $this->getReferenceTable());

		if (is_numeric($uid) && ($uid >= 0)) {
			$whereClause[] = 'uid!=' . "'" . intval($uid) . "'";
		}

		if (sizeof($this->pid_list) > 0) {
			$csv = Utility::implode_intval(',', $this->pid_list);
			$whereClause[] = 'pid IN (' . $csv . ')';
		}

		$whereClause = implode(' AND ', $whereClause);
		$whereClause .= $this->enable_fields($this->getReferenceTable(), '', $this->show_hidden);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(uid)', $this->getReferenceTable(), $whereClause);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

		if (is_array($row)) {
			$num = intval($row['count(uid)']);
		}

		return ($num > 0);
	}


	/**
	 * Returns the number of publications  which match
	 * the filtering criteria
	 *
	 * @return int The number of publications
	 */
	public function getNumberOfPublications() {

		$select = $this->getReferenceSelectClause($this->getReferenceTableAlias() . '.uid', NULL);
		$select = preg_replace('/;\s*$/', '', $select);
		$query = 'SELECT count(pubs.uid) FROM (' . $select . ') pubs;';
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

		if (is_array($row)) {
			return intval($row['count(pubs.uid)']);
		}

		return 0;
	}


	/**
	 * Returns the latest timestamp found in the database
	 *
	 * @return int The publication data from the database
	 */
	public function getLatestTimestamp() {
		$maximalValueFromReferenceTable = 'max(' . $this->getReferenceTableAlias() . '.tstamp)';
		$maximumValueFromAuthorTable = 'max(' . $this->getReferenceTableAlias() . '.tstamp)';

		$query = $this->getReferenceSelectClause(
			$maximalValueFromReferenceTable . ', ' . $maximumValueFromAuthorTable,
			NULL,
			NULL
		);
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

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
	 * @return array A histogram
	 */
	public function getHistogram($field = 'year') {
		$histogram = array();

		$query = $this->getReferenceSelectClause($this->getReferenceTableAlias() . '.' . $field, $this->getReferenceTableAlias() . '.' . $field . ' ASC');
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);

		$cVal = NULL;
		$cNum = NULL;
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$val = $row[$field];
			if ($cVal == $val) {
				$cNum++;
			} else {
				$cVal = $val;
				$histogram[$val] = 1;
				$cNum =& $histogram[$val];
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return $histogram;
	}


	/**
	 * Fetches all author surnames
	 *
	 * @return array An array containing the authors
	 */
	public function getSurnamesOfAllAuthors() {
		$names = array();

		$query = $this->getReferenceSelectClause(
			'distinct(' . $this->getAuthorTableAlias() . '.surname)',
				$this->getAuthorTableAlias() . '.surname ASC',
				$this->getAuthorTableAlias() . '.uid'
		);

		$res = $GLOBALS['TYPO3_DB']->sql_query($query);

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$names[] = $row['surname'];
		}
		return $names;
	}


	/**
	 * Searches and returns authors whose name looks like any of the
	 * words (array)
	 *
	 * @param array $words
	 * @param array $pids
	 * @param array $fields
	 * @return array An array containing the authors
	 */
	protected function searchByAuthor($words, $pids, $fields = array('forename', 'surname')) {
		$all_fields = array('forename', 'surname', 'url');
		$authors = array();
		$whereClause = array();
		$wca = array();
		foreach ($words as $word) {
			$word = trim(strval($word));
			if (strlen($word) > 0) {
				$word = $GLOBALS['TYPO3_DB']->fullQuoteStr($word, $this->getAuthorTable());
				foreach ($all_fields as $field) {
					if (in_array($field, $fields)) {

						if (preg_match('/(^%|^_|[^\\\\]%|[^\\\\]_)/', $word)) {
							$wca[] = $field . ' LIKE ' . $word;
						} else {
							$wca[] = $field . '=' . $word;
						}
					}
				}
			}
		}
		$whereClause[] = '(' . implode(' OR ', $wca) . ')';
		if (is_array($pids)) {
			$csv = Utility::implode_intval(',', $pids);
			$whereClause[] = 'pid IN (' . $csv . ')';
		} else {
			$whereClause[] = 'pid=' . intval($pids);
		}

		$whereClause = implode(' AND ', $whereClause);
		$whereClause .= $this->enable_fields($this->getAuthorTable());

		$field_csv = implode(',', $this->getAuthorAllFields());

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$field_csv,
			$this->getAuthorTable(),
			$whereClause
		);

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$authors[] = $row;
		}

		return $authors;
	}


	/**
	 * Searches and returns the authorships of authors whose name
	 * looks like any of the words (array)
	 *
	 * @param array $words
	 * @param array $pids
	 * @param array $fields
	 * @return array An array containing the authors
	 */
	protected function searchAuthorAuthorships($words, $pids, $fields = array('forename', 'surname')) {
		$authorships = array();
		$authors = $this->searchByAuthor($words, $pids, $fields);
		if (sizeof($authors) > 0) {
			$uids = array();
			foreach ($authors as $author) {
				$uids[] = intval($author['uid']);
			}
			$whereClause = 'author_id IN (' . implode(',', $uids) . ')';
			$whereClause .= $this->enable_fields($this->getAuthorshipTable());

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				$this->getAuthorshipTable(),
				$whereClause
			);

			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$authorships[] = $row;
			}
		}

		return $authorships;
	}


	/**
	 * Fetches the uid(s) of the given auhor.
	 * Checked is against the forename and the surname.
	 *
	 * @param array $author
	 * @param array|int $pids
	 * @return array Not defined
	 */
	public function fetch_author_uids($author, $pids) {
		$uids = array();
		$all_fields = array('forename', 'surname', 'url');

		$whereClause = array();

		foreach ($all_fields as $field) {
			if (array_key_exists($field, $author)) {
				$chk = ' = ';
				$word = $author[$field];
				if (preg_match('/(^%|^_|[^\\\\]%|[^\\\\]_)/', $word)) {
					$chk = ' LIKE ';
				}
				$whereClause[] = $field . $chk . $GLOBALS['TYPO3_DB']->fullQuoteStr($word, $this->getAuthorTable());
			}
		}

		if (sizeof($whereClause) > 0) {
			if (is_array($pids)) {
				$whereClause[] = 'pid IN (' . implode(',', $pids) . ')';
			} else {
				$whereClause[] = 'pid=' . intval($pids);
			}
			$whereClause = implode(' AND ', $whereClause);
			$whereClause .= $this->enable_fields($this->getAuthorTable());

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid,pid',
				$this->getAuthorTable(),
				$whereClause
			);

			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$uids[] = array('uid' => $row['uid'], 'pid' => $row['pid']);
			}
		}

		return $uids;
	}


	/**
	 * Fetches the uids of the auhors in the author filter
	 *
	 * @param array $filter
	 * @return void
	 */
	protected function getFilteredAuthorsUids(&$filter) {
		if (is_array($filter['author']['authors'])) {
			$filter['author']['sets'] = array();
			foreach ($filter['author']['authors'] as &$a) {
				if (!is_numeric($a['uid'])) {
					$pid = $this->pid_list;
					if (isset ($filter['pid']))
						$pid = $filter['pid'];
					$uids = $this->fetch_author_uids($a, $pid);
					for ($i = 0; $i < sizeof($uids); $i++) {
						$uid = $uids[$i];
						if ($i == 0) {
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
					if (sizeof($uids) > 0) {
						$filter['author']['sets'][] = $uids;
					}
				}
			}
		}
	}


	/**
	 * Fetches the authors of a publication
	 *
	 * @param int $pub_id
	 * @return array An array containing author array
	 */
	protected function getAuthorByPublication($pub_id) {
		$authors = array();

		$whereClause = '';

		$whereClause .= $this->authorshipTableAlias . '.pub_id=' . intval($pub_id) . "\n";

		$whereClause .= $this->enable_fields($this->getAuthorshipTable(), $this->authorshipTableAlias);
		$whereClause .= $this->enable_fields($this->getAuthorTable(), $this->getAuthorTableAlias());

		$OC = $this->authorshipTableAlias . '.sorting ASC';

		$field_csv = $this->getAuthorTableAlias() . '.' . implode(',' . $this->getAuthorTableAlias() . '.', $this->getAuthorAllFields());
		$query = $this->select_clause_start(
			array($field_csv, $this->authorshipTableAlias . '.sorting'),
			array($this->t_au_default, $this->t_as_default)
		);
		$query .= ' WHERE ' . $whereClause . "\n";
		$query .= ' ORDER BY ' . $OC . "\n";
		$query .= ';';

		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$authors[] = $row;
		}
		return $authors;
	}


	/**
	 * This retrieves the publication data from the database
	 *
	 * @param int $uid
	 * @return array The publication data from the database
	 */
	public function getPublicationDetails($uid) {

		$whereClause = array();

		$whereClause[] = 'uid = ' . intval($uid);

		if (sizeof($this->pid_list) > 0) {
			$csv = Utility::implode_intval(',', $this->pid_list);
			$whereClause[] = 'pid IN (' . $csv . ')';
		}

		$whereClause = implode(' AND ', $whereClause);
		$whereClause .= $this->enable_fields($this->getReferenceTable(), '', $this->show_hidden);

		$field_csv = implode(',', $this->refAllFields);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$field_csv,
			$this->getReferenceTable(),
			$whereClause
		);

		$publication = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

		if (is_array($publication)) {
			$publication['authors'] = $this->getAuthorByPublication($publication['uid']);
			$publication['mod_key'] = $this->getModificationKey($publication);
		}

		return $publication;
	}


	/**
	 * This initializes the reference fetching.
	 * Executes a select query.
	 *
	 * @return void
	 */
	public function initializeReferenceFetching() {
		$field_csv = $this->getReferenceTableAlias() . '.' . implode(',' . $this->getReferenceTableAlias() . '.', $this->refAllFields);
		$query = $this->getReferenceSelectClause($field_csv);
		$this->setDatabaseResource($GLOBALS['TYPO3_DB']->sql_query($query));
	}


	/**
	 * Returns the number of references that will be fetched
	 *
	 * @return int The number of references
	 */
	public function numberOfReferencesToBeFetched() {
		return $GLOBALS['TYPO3_DB']->sql_num_rows($this->getDatabaseResource());
	}


	/**
	 * Fetches a reference
	 *
	 * @return array A database row
	 */
	public function getReference() {
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($this->getDatabaseResource());
		if ($row) {
			$row['authors'] = $this->getAuthorByPublication($row['uid']);
			$row['mod_key'] = $this->getModificationKey($row);
		}
		return $row;
	}


	/**
	 * Finish reference fetching (clean up)
	 *
	 * @return void
	 */
	public function finalizeReferenceFetching() {
		$GLOBALS['TYPO3_DB']->sql_free_result($this->getDatabaseResource());
	}


	/**
	 * This returns the modification key for a publication
	 *
	 * @param array $publication
	 * @return string The mod_key string
	 */
	protected function getModificationKey($publication) {
		$modificationKey = '';
		foreach ($publication['authors'] as $author) {
			$modificationKey .= $author['surname'];
			$modificationKey .= $author['forename'];
		};
		$modificationKey .= $publication['title'];
		$modificationKey .= strval($publication['crdate']);
		$modificationKey .= strval($publication['tstamp']);
		$hashedModificationKey = sha1($modificationKey);

		return $hashedModificationKey;
	}


	/**
	 * Fetches an authorship
	 *
	 * @param array $authorship
	 * @return null|array The matching authorship row or NULL
	 */
	public function getAuthorships($authorship) {
		$ret = array();
		if (is_array($authorship)) {

			if (isset ($authorship['pub_id']) || isset ($authorship['author_id']) || isset ($authorship['pid'])) {
				$whereClause = array();
				if (isset ($authorship['pub_id'])) {
					$whereClause[] = 'pub_id=' . intval($authorship['pub_id']);
				}
				if (isset ($authorship['author_id'])) {
					$whereClause[] = 'author_id=' . intval($authorship['author_id']);
				}
				if (isset ($authorship['pid'])) {
					$whereClause[] = 'pid=' . intval($authorship['pid']);
				}
				$whereClause = implode(' AND ', $whereClause);
				$whereClause .= $this->enable_fields($this->getAuthorshipTable());

				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					$this->getAuthorshipTable(),
					$whereClause
				);

				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$ret[] = $row;
				}
			}
		}
		return $ret;
	}

	/**
	 * @param string $citationId
	 * @return int
	 */
	public function getUidFromCitationId($citationId) {

		$citationId = filter_var($citationId, FILTER_SANITIZE_STRING);

		$whereClause = array();

		if (sizeof($this->pid_list) > 0) {
			$csv = Utility::implode_intval(',', $this->pid_list);
			$whereClause[] = 'pid IN (' . $csv . ')';
		}

		$whereClause[] = 'citeid = "' . $citationId . '"';

		$whereClause = implode(' AND ', $whereClause);
		$whereClause .= $this->enable_fields($this->getReferenceTable(), '', $this->show_hidden);

		$query = $GLOBALS['TYPO3_DB']->exec_SELECTQuery(
			'uid',
			$this->getReferenceTable(),
			$whereClause,
			'',
			'',
			1
		);

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($query)) {
			$result = $row['uid'];
		}
		return $result;
	}

	/**
	 * @param array $pidList
	 * @return void
	 */
	public function setPidList($pidList) {
		$this->pid_list = $pidList;
	}

	/**
	 * @param $showHidden
	 * @return void
	 */
	public function setShowHidden($showHidden) {
		$this->show_hidden = $showHidden;
	}

	/**
	 * @param string $authorTable
	 */
	public function setAuthorTable($authorTable) {
		$this->authorTable = $authorTable;
	}

	/**
	 * @return string
	 */
	public function getAuthorTable() {
		return $this->authorTable;
	}

	/**
	 * @param string $authorshipTable
	 */
	public function setAuthorshipTable($authorshipTable) {
		$this->authorshipTable = $authorshipTable;
	}

	/**
	 * @return string
	 */
	public function getAuthorshipTable() {
		return $this->authorshipTable;
	}

	/**
	 * @param string $referenceTable
	 */
	public function setReferenceTable($referenceTable) {
		$this->referenceTable = $referenceTable;
	}

	/**
	 * @return string
	 */
	public function getReferenceTable() {
		return $this->referenceTable;
	}


	/**
	 * @param array $publicationFields
	 */
	public function setPublicationFields($publicationFields) {
		$this->publicationFields = $publicationFields;
	}

	/**
	 * @return array
	 */
	public function getPublicationFields() {
		return $this->publicationFields;
	}


	/**
	 * @param array $referenceFields
	 */
	public function setReferenceFields($referenceFields) {
		$this->referenceFields = $referenceFields;
	}

	/**
	 * @return array
	 */
	public function getReferenceFields() {
		return $this->referenceFields;
	}

	/**
	 * @param boolean|\mysqli_result|object $databaseResource
	 */
	public function setDatabaseResource($databaseResource) {
		$this->databaseResource = $databaseResource;
	}

	/**
	 * @return boolean|\mysqli_result|object
	 */
	public function getDatabaseResource() {
		return $this->databaseResource;
	}

	/**
	 * @param boolean $clearCache
	 */
	public function setClearCache($clearCache) {
		$this->clearCache = $clearCache;
	}

	/**
	 * @return boolean
	 */
	public function getClearCache() {
		return $this->clearCache;
	}

	/**
	 * @param array $authorFields
	 */
	public function setAuthorFields($authorFields) {
		$this->authorFields = $authorFields;
	}

	/**
	 * @return array
	 */
	public function getAuthorFields() {
		return $this->authorFields;
	}

	/**
	 * @param array $authorAllFields
	 */
	public function setAuthorAllFields($authorAllFields) {
		$this->authorAllFields = $authorAllFields;
	}

	/**
	 * @return array
	 */
	public function getAuthorAllFields() {
		return $this->authorAllFields;
	}

	/**
	 * @param array $allowedTags
	 */
	public function setAllowedTags($allowedTags) {
		$this->allowedTags = $allowedTags;
	}

	/**
	 * @return array
	 */
	public function getAllowedTags() {
		return $this->allowedTags;
	}

	/**
	 * @param string $referenceTableAlias
	 */
	public function setReferenceTableAlias($referenceTableAlias) {
		$this->referenceTableAlias = $referenceTableAlias;
	}

	/**
	 * @return string
	 */
	public function getReferenceTableAlias() {
		return $this->referenceTableAlias;
	}

	/**
	 * @param string $authorshipTableAlias
	 */
	public function setAuthorshipTableAlias($authorshipTableAlias) {
		$this->authorshipTableAlias = $authorshipTableAlias;
	}

	/**
	 * @return string
	 */
	public function getAuthorshipTableAlias() {
		return $this->authorshipTableAlias;
	}

	/**
	 * @param string $authorTableAlias
	 */
	public function setAuthorTableAlias($authorTableAlias) {
		$this->authorTableAlias = $authorTableAlias;
	}

	/**
	 * @return string
	 */
	public function getAuthorTableAlias() {
		return $this->authorTableAlias;
	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/Classes/Utility/ReferenceReader.php"]) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/Classes/Utility/ReferenceReader.php"]);
}

?>