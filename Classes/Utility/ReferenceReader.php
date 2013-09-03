<?php
namespace Ipf\Bib\Utility;

if (!isset($GLOBALS['TSFE']))
	die ('This file is not meant to be executed');


/**
 * This class provides the reference database interface
 * and some utility methods
 *
 * @author Sebastian Holtermann
 * @author Ingo Pfennigstorf
 */
class ReferenceReader {

	protected $filter;
	protected $cObj;

	public $dbRes;
	public $clear_cache;
	public $pid_list;
	public $show_hidden; // Show hidden references

	/**
	 * @var string
	 */
	public $referenceTable = 'tx_bib_domain_model_reference';

	/**
	 * @var string
	 */
	public $authorTable = 'tx_bib_domain_model_author';

	/**
	 * @var string
	 */
	public $authorshipTable = 'tx_bib_domain_model_authorships';

	/**
	 * @var string
	 */
	public $referenceTableAlias = 't_ref';

	/**
	 * @var string
	 */
	public $authorTableAlias = 't_authors';

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

	// The following tags are allowed in a reference string
	public $allowed_tags = array('em', 'strong', 'sup', 'sub');


	/**
	 * These are the author relevant fields
	 * that can be found in the reference table $this->authorTable.
	 * TYPO3 special fields like pid or uid are not listed here
	 * @var array
	 */
	public $authorFields = array(
		'surname', 'forename', 'url', 'fe_user_id'
	);

	public $authorAllFields;


	/**
	 * These are the publication relevant fields
	 * that can be found in the reference table $this->refTable.
	 * TYPO3 special fields like pid or uid are not listed here
	 * @var array
	 */
	public $refFields = array(
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
		'web_url2',
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
	 * that can be found in the reference table $this->refTable.
	 * including the important TYPO3 specific fields
	 */
	public $refAllFields;


	/**
	 * These are the publication relevant fields
	 * that can be found in a php publication array.
	 * TYPO3 special fields like pid or uid are not listed here
	 */
	public $pubFields;


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
		19 => 'report'
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
	 * @return void
	 */
	public function __construct() {
		$this->dbRes = NULL;
		$this->filters = array();
		$this->clear_cache = FALSE;
		$this->pid_list = array();
		$this->show_hidden = FALSE;

		$this->t_ref_default['table'] = $this->referenceTable;
		$this->t_ref_default['alias'] = $this->referenceTableAlias;

		$this->t_as_default['table'] = $this->authorshipTable;
		$this->t_as_default['alias'] = $this->authorshipTableAlias;

		$this->t_au_default['table'] = $this->authorTable;
		$this->t_au_default['alias'] = $this->authorTableAlias;

		// setup authorAllFields
		$this->authorAllFields = array(
			'uid', 'pid', 'tstamp', 'crdate', 'cruser_id'
		);
		$this->authorAllFields = array_merge($this->authorAllFields, $this->authorFields);

		// setup refAllFields
		$typo3_fields = array(
			'uid', 'pid', 'hidden', 'tstamp', 'sorting', 'crdate', 'cruser_id'
		);
		$this->refAllFields = array_merge($typo3_fields, $this->refFields);

		// setup pubFields
		$this->pubFields = $this->refFields;
		$this->pubFields[] = 'authors';

		// setup pubAllFields
		$this->pubAllFields = array_merge($typo3_fields, $this->pubFields);
	}


	/**
	 * set the cObject
	 *
	 * @return void
	 */
	function set_cObj(&$cObj) {
		$this->cObj =& $cObj;
	}


	/**
	 * Clears the page cache of all selected pages
	 *
	 * @return void
	 */
	function clear_page_cache() {
		if ($this->clear_cache) {
			/** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
			$dataHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$clear_cache = array();

			$backendUser = $GLOBALS['BE_USER'];
			if (is_object($backendUser) || is_array($backendUser->user)) {
				$dataHandler->start(array(), array(), $backendUser);
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
		} else {
			//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( 'Not clearing cache' );
		}
	}


	/**
	 * This changes the character set of a publication (array)
	 *
	 * @return The character set adjusted publication
	 */
	function change_pub_charset($pub, $cs_from, $cs_to) {
		if (is_array($pub) && strlen($cs_from) && strlen($cs_to)
				&& ($cs_from != $cs_to)
		) {
			$cs =& $GLOBALS['TSFE']->csConvObj;
			$keys = array_keys($pub);
			foreach ($keys as $k) {
				$v =& $pub[$k];
				switch ($k) {
					case 'authors':
						if (is_array($v)) {
							foreach ($v as &$a) {
								$a['forename'] = $cs->conv($a['forename'], $cs_from, $cs_to);
								$a['surname'] = $cs->conv($a['surname'], $cs_from, $cs_to);
							}
						}
					default:
						if (is_string($v))
							$v = $cs->conv($v, $cs_from, $cs_to);
				}
			}
		}
		return $pub;
	}


	/**
	 * This appends a filter to the filter list
	 *
	 * @return Not defined
	 */
	function append_filter($filter) {
		if (is_array($filter)) {
			if (!is_array($filter['pid']))
				if (is_string($filter['pid']))
					$filter['pid'] = explode(',', strval($filter['pid']));
			$this->fetch_author_filter_uids($filter);
			$this->filters[] = $filter;
		}
	}


	/**
	 * This sets the filter which will be asked for most
	 * query compositions
	 *
	 * @return void
	 */
	function set_filter($filter) {
		$this->filters = array();
		$this->append_filter($filter);
	}


	/**
	 * This sets the filters which will be asked for most
	 * query compositions
	 *
	 * @return void
	 */
	function set_filters($filters) {
		$this->filters = array();
		foreach ($filters as $filter) {
			$this->append_filter($filter);
		}
	}


	/**
	 * Returns the where clause part for a table
	 *
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
	 * @return The SELECT clause beginning
	 */
	protected function select_clause_start($fields, $tables) {
		$selectClause = '';
		if (is_array($fields) && is_array($tables)) {
			$referenceTable =& $this->referenceTable;
			$authorTable =& $this->authorTable;
			$authorshipTable =& $this->authorshipTable;

			$base =& $tables[0];
			$joins = '';
			$aliases = array($base['alias']);
			for ($i = 1; $i < sizeof($tables); $i++) {
				$previous = $tables[$i - 1];
				$current = $tables[$i];

				if ((($previous['table'] == $referenceTable) && ($current['table'] == $authorTable)) ||
						(($previous['table'] == $authorTable) && ($current['table'] == $referenceTable))
				) {
					$joins .= $this->get_sql_join($previous, $this->t_as_default, $aliases);
					$joins .= $this->get_sql_join($this->t_as_default, $current, $aliases);
				} else {
					$joins .= $this->get_sql_join($previous, $current, $aliases);
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
	 * @return string The WHERE clause string
	 */
	function get_sql_join($table, $join, &$aliases) {
		$referenceTable =& $this->referenceTable;
		$authorTable =& $this->authorTable;
		$authorshipTable =& $this->authorshipTable;

		$joinStatement = '';

		if (in_array($join['alias'], $aliases))
			return '';

		// The match fields
		$tableMatchField = '';
		$joinMatchField = '';

		switch ($table['table']) {
			case $referenceTable:
				switch ($join['table']) {
					case $authorshipTable:
						$tableMatchField = 'uid';
						$joinMatchField = 'pub_id';
						break;
				}
				break;
			case $authorshipTable:
				switch ($join['table']) {
					case $referenceTable:
						$tableMatchField = 'pub_id';
						$joinMatchField = 'uid';
						break;
					case $authorTable:
						$tableMatchField = 'author_id';
						$joinMatchField = 'uid';
						break;
				}
				break;
			case $authorTable:
				switch ($join['table']) {
					case $authorshipTable:
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
	 * @return string The WHERE clause string
	 */
	protected function get_reference_where_clause(&$columns) {

		$referenceTable = $this->referenceTable;
		$authorshipTable = $this->authorshipTable;
		$referenceTableAlias = $this->referenceTableAlias;
		$authorshipTableAlias = $this->authorshipTableAlias;

		$WCA = array();
		$columns = array();
		$runvar = array(
			'columns' => array(),
			'aShip_count' => 0,
		);

		// Get where parts for each filter
		foreach ($this->filters as $filter) {
			$parts = $this->get_filter_wc_parts($filter, $runvar);
			$WCA = array_merge($WCA, $parts);
		}

		$whereClause = implode(' AND ', $WCA);

		if (strlen($whereClause) > 0) {
			$columns = array_merge(array($referenceTableAlias), $runvar['columns']);
			$columns = array_unique($columns);

			foreach ($columns as &$column) {
				$column = preg_replace('/\.[^\.]*$/', '', $column);
				if (!(strpos($column, $referenceTableAlias) === FALSE)) {
					$whereClause .= $this->enable_fields($referenceTable, $column, $this->show_hidden);
				}
				if (!(strpos($column, $authorshipTableAlias) === FALSE)) {
					$whereClause .= $this->enable_fields($authorshipTable, $column);
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
	 *
	 * @return array The WHERE clause parts in an array
	 */
	function get_filter_wc_parts($filter, &$runvar) {

		$referenceTable = $this->referenceTable;
		$authorshipTable = $this->authorshipTable;
		$referenceTableAlias = $this->referenceTableAlias;
		$authorshipTableAlias = $this->authorshipTableAlias;

		$columns =& $runvar['columns'];
		$aShip_count =& $runvar['aShip_count'];

		$whereClause = array();

		// Filter by UID
		if (isset ($filter['FALSE'])) {
			$whereClause[] = 'FALSE';
			return $whereClause;
		}

		// Filter by UID
		if (is_array($filter['uid']) && (sizeof($filter['uid']) > 0)) {
			$csv = \Ipf\Bib\Utility\Utility::implode_intval(',', $filter['uid']);
			$whereClause[] = $referenceTableAlias . '.uid IN (' . $csv . ')';
		}

		// Filter by storage PID
		if (is_array($filter['pid']) && (sizeof($filter['pid']) > 0)) {
			$csv = \Ipf\Bib\Utility\Utility::implode_intval(',', $filter['pid']);
			$whereClause[] = $referenceTableAlias . '.pid IN (' . $csv . ')';
		}

		// Filter by year
		if (is_array($filter['year']) && (sizeof($filter['year']) > 0)) {
			$f =& $filter['year'];
			$wca = '';
			// years
			if (is_array($f['years']) && (sizeof($f['years']) > 0)) {
				$csv = \Ipf\Bib\Utility\Utility::implode_intval(',', $f['years']);
				$wca .= ' ' . $referenceTableAlias . '.year IN (' . $csv . ')' . "\n";
			}
			// ranges
			if (is_array($f['ranges']) && sizeof($f['ranges'])) {
				$ra =& $f['ranges'];
				if (sizeof($ra)) {
					for ($i = 0; $i < sizeof($ra); $i++) {
						$r =& $ra[$i];
						$both = (isset ($r['from']) && isset ($r['to'])) ? TRUE : FALSE;
						if (strlen($wca))
							$wca .= ' OR ';
						if ($both)
							$wca .= '(';
						if (isset ($r['from']))
							$wca .= $referenceTableAlias . '.year >= ' . intval($r['from']);
						if ($both)
							$wca .= ' AND ';
						if (isset ($r['to']))
							$wca .= $referenceTableAlias . '.year <= ' . intval($r['to']);
						if ($both)
							$wca .= ')';
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
							$col_num = $aShip_count;
							$aShip_count += 1;
							$column = $authorshipTableAlias . (($col_num > 0) ? strval($col_num) : '');
							$wc_set[] = $column . '.author_id IN (' . $uid_lst . ')';
							$columns[] = $column;
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
						$col_num = $aShip_count;
						$aShip_count += 1;
						$column = $authorshipTableAlias . (($col_num > 0) ? strval($col_num) : '');
						$whereClause[] = $column . '.author_id IN (' . $uid_lst . ')';
						$columns[] = $column;
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
				$csv = \Ipf\Bib\Utility\Utility::implode_intval(',', $f['types']);
				$whereClause[] = $referenceTableAlias . '.bibtype IN (' . $csv . ')';
			}
		}

		// Filter by publication state
		if (is_array($filter['state']) && (sizeof($filter['state']) > 0)) {
			$f =& $filter['state'];
			if (is_array($f['states']) && (sizeof($f['states']) > 0)) {
				$csv = \Ipf\Bib\Utility\Utility::implode_intval(',', $f['states']);
				$whereClause[] = $referenceTableAlias . '.state IN (' . $csv . ')';
			}
		}

		// Filter by origin
		if (is_array($filter['origin']) && (sizeof($filter['origin']) > 0)) {
			$f =& $filter['origin'];
			if (is_numeric($f['origin'])) {
				$wca = $referenceTableAlias . '.extern = \'0\'';
				if (intval($f['origin']) != 0)
					$wca = $referenceTableAlias . '.extern != \'0\'';
				$whereClause[] = $wca;
			}
		}

		// Filter by reviewed
		if (is_array($filter['reviewed']) && (sizeof($filter['reviewed']) > 0)) {
			$f =& $filter['reviewed'];
			if (is_numeric($f['value'])) {
				$wca = $referenceTableAlias . '.reviewed = \'0\'';
				if (intval($f['value']) != 0)
					$wca = $referenceTableAlias . '.reviewed != \'0\'';
				$whereClause[] = $wca;
			}
		}

		// Filter by borrowed
		if (is_array($filter['borrowed']) && (sizeof($filter['borrowed']) > 0)) {
			$f =& $filter['borrowed'];
			if (is_numeric($f['value'])) {
				$wca = 'LENGTH(' . $referenceTableAlias . '.borrowed_by) = \'0\'';
				if (intval($f['value']) != 0)
					$wca = 'LENGTH(' . $referenceTableAlias . '.borrowed_by) != \'0\'';
				$whereClause[] = $wca;
			}
		}

		// Filter by in_library
		if (is_array($filter['in_library']) && (sizeof($filter['in_library']) > 0)) {
			$f =& $filter['in_library'];
			if (is_numeric($f['value'])) {
				$wca = $referenceTableAlias . '.in_library = \'0\'';
				if (intval($f['value']) != 0)
					$wca = $referenceTableAlias . '.in_library != \'0\'';
				$whereClause[] = $wca;
			}
		}

		// Filter by citeid
		if (is_array($filter['citeid']) && (sizeof($filter['citeid']) > 0)) {
			$f =& $filter['citeid'];
			if (is_array($f['ids']) && (sizeof($f['ids']) > 0)) {
				$wca = $referenceTableAlias . '.citeid IN (';
				for ($i = 0; $i < sizeof($f['ids']); $i++) {
					if ($i > 0) $wca .= ',';
					$wca .= $GLOBALS['TYPO3_DB']->fullQuoteStr($f['ids'][$i], $referenceTable);
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
					$wca[] = $this->get_filter_search_fields_clause($f['words'], array('tags'));
				} else { // AND
					foreach ($f['words'] as $word) {
						$wca[] = $this->get_filter_search_fields_clause(array($word), array('tags'));
					}
				}

				//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'wca' => $wca ) );
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
					$wca[] = $this->get_filter_search_fields_clause($f['words'], array('keywords'));
				} else { // AND
					foreach ($f['words'] as $word) {
						$wca[] = $this->get_filter_search_fields_clause(array($word), array('keywords'));
					}
				}

				//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'wca' => $wca ) );
				foreach ($wca as $app) {
					if (strlen($app) > 0) $whereClause[] = $app;
				}
			}
		}

		// General keyword search
		if (is_array($filter['all']) && (sizeof($filter['all']) > 0)) {
			$f =& $filter['all'];
			//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( $f );
			if (is_array($f['words']) && (sizeof($f['words']) > 0)) {
				$wca = array();

				$fields = $this->pubFields;
				$fields[] = 'full_text';
				if (is_array($f['exclude'])) {
					$fields = array_diff($fields, $f['exclude']);
				}
				//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( $fields );

				if ($f['rule'] == 0) { // OR
					$wca[] = $this->get_filter_search_fields_clause($f['words'], $fields);
				} else { // AND
					foreach ($f['words'] as $word) {
						$wca[] = $this->get_filter_search_fields_clause(array($word), $fields);
					}
				}

				//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'wca' => $wca ) );
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
	 * @param $words An array or words
	 * @param $fields An array of fields to search in
	 * @return The WHERE clause string
	 */
	function get_filter_search_fields_clause($words, $fields) {
		$rT =& $this->referenceTable;
		$rta =& $this->referenceTableAlias;
		$res = '';
		$wca = array();

		// Flatten word array

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
		$refFields = $this->refFields;
		$refFields[] = 'full_text';
		foreach ($fields as $field) {
			if (in_array($field, $refFields)) {
				foreach ($proc_words as $word) {
					$word = $GLOBALS['TYPO3_DB']->fullQuoteStr($word, $rT);
					$wca[] = $rta . '.' . $field . ' LIKE ' . $word;
				}
			}
		}

		// Authors
		if (in_array('authors', $fields)) {
			$a_ships = $this->search_author_authorships($proc_words, $this->pid_list);
			if (sizeof($a_ships) > 0) {
				$uids = array();
				foreach ($a_ships as $as) {
					$uids[] = intval($as['pub_id']);
				}
				$wca[] = $rta . '.uid IN (' . implode(',', $uids) . ')';
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
	 * @return The search object (string or array)
	 */
	function search_word($word, $charset, $wrap = array('%', '%')) {
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
		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'search words' => $words ) );
		if (sizeof($words) == 1) {
			return $words[0];
		}
		return $words;
	}


	/**
	 * This function returns the SQL ORDER clause configured
	 * by the filter
	 *
	 * @return The ORDER clause string
	 */
	function get_order_clause() {
		$db =& $GLOBALS['TYPO3_DB'];
		$rT = $this->referenceTable;
		$OC = '';
		foreach ($this->filters as $filter) {
			if (is_array($filter['sorting'])) {
				$sortings =& $filter['sorting'];
				$OC = array();
				for ($i = 0; $i < sizeof($sortings); $i++) {
					$s =& $sortings[$i];
					if (isset ($s['field']) && isset ($s['dir'])) {
						$OC[] = $s['field'] . ' ' . $s['dir'];
					}
				}
				$OC = implode(',', $OC);
			}
		}
		return $OC;
	}


	/**
	 * This function returns the SQL LIMIT clause configured
	 * by the filter
	 *
	 * @return The LIMIT clause string
	 */
	function get_limit_clause() {
		$LC = '';
		foreach ($this->filters as $filter) {
			if (is_array($filter['limit'])) {
				$l =& $filter['limit'];
				if (isset ($l['start']) && isset ($l['num'])) {
					$LC = intval($l['start']) . ',' . intval($l['num']);
				}
			}
		}
		return $LC;
	}


	/**
	 * This function returns the SQL LIMIT clause configured
	 * by the filter
	 *
	 * @return The LIMIT clause string
	 */
	function get_reference_select_clause($fields, $order = '', $group = '') {
		if (!is_array($fields))
			$fields = array($fields);
		$rta =& $this->referenceTableAlias;
		$sta =& $this->authorshipTableAlias;
		$ata =& $this->authorTableAlias;

		$columns = array();
		$WC = $this->get_reference_where_clause($columns);

		$GC = '';
		if (is_string($group))
			$GC = strlen($group) ? $group : $rta . '.uid';

		$OC = '';
		if (is_string($order))
			$OC = strlen($order) ? $order : $this->get_order_clause();

		$LC = $this->get_limit_clause();

		// Find the tables that should be included
		$tables = array($this->t_ref_default);
		foreach ($fields as $field) {
			if (!(strpos($field, $sta) === FALSE))
				$tables[] = $this->t_as_default;
			if (!(strpos($field, $ata) === FALSE))
				$tables[] = $this->t_au_default;
		}

		foreach ($columns as $column) {
			if (!(strpos($column, $sta) === False)) {
				$table = $this->t_as_default;
				$table['alias'] = $column;

				$tables[] = $this->t_ref_default;
				$tables[] = $table;
			}
		}

		$q = $this->select_clause_start($fields, $tables);
		if (strlen($WC))
			$q .= ' WHERE ' . $WC . "\n";
		if (strlen($GC))
			$q .= ' GROUP BY ' . $GC . "\n";
		if (strlen($OC))
			$q .= ' ORDER BY ' . $OC . "\n";
		if (strlen($LC))
			$q .= ' LIMIT ' . $LC . "\n";
		$q .= ';';

		return $q;
	}


	/**
	 * Checks if a publication that has not the given uid
	 * but the citeid exists in the database. The lookup is restricted
	 * to the currend storage folders ($filter['pid'])
	 *
	 * @return TRUE on existance FALSE otherwise
	 */
	function citeid_exists($citeid, $uid = -1) {
		if (strlen($citeid) == 0) return FALSE;
		$num = 0;
		$db =& $GLOBALS['TYPO3_DB'];
		$WC = array();
		$WC[] = 'citeid=' . $db->fullQuoteStr($citeid, $this->referenceTable);
		if (is_numeric($uid) && ($uid >= 0)) {
			$WC[] = 'uid!=' . "'" . intval($uid) . "'";
		}
		if (sizeof($this->pid_list) > 0) {
			$csv = \Ipf\Bib\Utility\Utility::implode_intval(',', $this->pid_list);
			$WC[] = 'pid IN (' . $csv . ')';
		}
		$WC = implode(' AND ', $WC);
		$WC .= $this->enable_fields($this->referenceTable, '', $this->show_hidden);

		$res = $db->exec_SELECTquery('count(uid)', $this->referenceTable, $WC);
		$row = $db->sql_fetch_assoc($res);
		if (is_array($row)) {
			$num = intval($row['count(uid)']);
		}

		return ($num > 0);
	}


	/**
	 * Returns the number of publications  which match
	 * the filtering criteria
	 *
	 * @return The number of publications
	 */
	function fetch_num() {
		$rta =& $this->referenceTableAlias;

		$select = $this->get_reference_select_clause($rta . '.uid', NULL);
		$select = preg_replace('/;\s*$/', '', $select);
		$query = 'SELECT count(pubs.uid) FROM (' . $select . ') pubs;';
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		if (is_array($row))
			return intval($row['count(pubs.uid)']);
		return 0;
	}


	/**
	 * Returns the latest timestamp found in the database
	 *
	 * @return The publication data from the database
	 */
	function fetch_max_tstamp() {
		$max_rT = 'max(' . $this->referenceTableAlias . '.tstamp)';
		$max_aT = 'max(' . $this->authorTableAlias . '.tstamp)';

		$query = $this->get_reference_select_clause(
			$max_rT . ', ' . $max_aT, NULL, NULL);
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

		if (is_array($row)) {
			//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ($row);
			return max($row);
		}
		return 0;
	}


	/**
	 * Returns a publication histogram to a given key.
	 * I.e. the number of publications per year if year
	 * is the requested key.
	 *
	 * @return A histogram
	 */
	public function fetch_histogram($field = 'year') {
		$histo = array();
		$rta =& $this->referenceTableAlias;

		$query = $this->get_reference_select_clause($rta . '.' . $field, $rta . '.' . $field . ' ASC');
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);

		$cVal = NULL;
		$cNum = NULL;
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$val = $row[$field];
			if ($cVal == $val)
				$cNum++;
			else {
				$cVal = $val;
				$histo[$val] = 1;
				$cNum =& $histo[$val];
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ($histo);
		return $histo;
	}


	/**
	 * Fetches all author surnames
	 *
	 * @return An array containing the authors
	 */
	function fetch_author_surnames() {
		$aT =& $this->authorTable;
		$ata =& $this->authorTableAlias;
		$names = array();

		$query = $this->get_reference_select_clause('distinct(' . $ata . '.surname)', $ata . '.surname ASC', $ata . '.uid');
		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug( $query );
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
	 * @return An array containing the authors
	 */
	function search_authors($words, $pids, $fields = array('forename', 'surname')) {
		$aT =& $this->authorTable;
		$all_fields = array('forename', 'surname', 'url');
		$authors = array();
		$WC = array();
		$wca = array();
		foreach ($words as $word) {
			$word = trim(strval($word));
			if (strlen($word) > 0) {
				$word = $GLOBALS['TYPO3_DB']->fullQuoteStr($word, $aT);
				foreach ($all_fields as $field) {
					if (in_array($field, $fields))
						//\TYPO3\CMS\Core\Utility\GeneralUtility::debug( $word );
					if (preg_match('/(^%|^_|[^\\\\]%|[^\\\\]_)/', $word)) {
						//\TYPO3\CMS\Core\Utility\GeneralUtility::debug( 'Wildcard' );
						$wca[] = $field . ' LIKE ' . $word;
					} else {
						$wca[] = $field . '=' . $word;
					}
				}
			}
		}
		$WC[] = '(' . implode(' OR ', $wca) . ')';
		if (is_array($pids)) {
			$csv = \Ipf\Bib\Utility\Utility::implode_intval(',', $pids);
			$WC[] = 'pid IN (' . $csv . ')';
		} else {
			$WC[] = 'pid=' . intval($pids);
		}

		$WC = implode(' AND ', $WC);
		$WC .= $this->enable_fields($aT);

		$field_csv = implode(',', $this->authorAllFields);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($field_csv, $aT, $WC);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$authors[] = $row;
		}

		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'authors' => $authors ) );
		return $authors;
	}


	/**
	 * Searches and returns the authorships of authors whose name
	 * looks like any of the words (array)
	 *
	 * @return An array containing the authors
	 */
	function search_author_authorships($words, $pids, $fields = array('forename', 'surname')) {
		$sT =& $this->authorshipTable;
		$ships = array();
		$authors = $this->search_authors($words, $pids, $fields);
		if (sizeof($authors) > 0) {
			$uids = array();
			foreach ($authors as $author) {
				$uids[] = intval($author['uid']);
			}
			$WC = 'author_id IN (' . implode(',', $uids) . ')';
			$WC .= $this->enable_fields($sT);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $sT, $WC);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$ships[] = $row;
			}
		}

		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'authorships' => $authors ) );
		return $ships;
	}


	/**
	 * Fetches the uid(s) of the given auhor.
	 * Checked is against the forename and the surname.
	 *
	 * @return Not defined
	 */
	function fetch_author_uids($author, $pids) {
		$uids = array();
		$all_fields = array('forename', 'surname', 'url');
		$db =& $GLOBALS['TYPO3_DB'];
		$aT =& $this->authorTable;

		$WC = array();

		foreach ($all_fields as $field) {
			if (array_key_exists($field, $author)) {
				$chk = ' = ';
				$word = $author[$field];
				if (preg_match('/(^%|^_|[^\\\\]%|[^\\\\]_)/', $word)) {
					//\TYPO3\CMS\Core\Utility\GeneralUtility::debug( 'Wildcard' );
					$chk = ' LIKE ';
				}
				$WC[] = $field . $chk . $db->fullQuoteStr($word, $aT);
			}
		}

		if (sizeof($WC) > 0) {
			if (is_array($pids)) {
				$WC[] = 'pid IN (' . implode(',', $pids) . ')';
			} else {
				$WC[] = 'pid=' . intval($pids);
			}
			$WC = implode(' AND ', $WC);
			$WC .= $this->enable_fields($aT);
			//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( $WC );
			$res = $db->exec_SELECTquery('uid,pid', $aT, $WC);
			while ($row = $db->sql_fetch_assoc($res)) {
				$uids[] = array('uid' => $row['uid'], 'pid' => $row['pid']);
			}
		}
		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'uids' => $uids ) );
		return $uids;
	}


	/**
	 * Fetches the uids of the auhors in the author filter
	 *
	 * @return Not defined
	 */
	function fetch_author_filter_uids(&$filter) {
		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ('Fetching author uids');
		if (is_array($filter['author']['authors'])) {
			$a_filter =& $filter['author'];
			$authors =& $filter['author']['authors'];
			$a_filter['sets'] = array();
			foreach ($authors as &$a) {
				//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( $a );
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
							$authors[] = $aa;
						}
					}
					if (sizeof($uids) > 0) {
						$a_filter['sets'][] = $uids;
					}
				}
			}
			//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ($filter);
		}
	}


	/**
	 * Fetches the authors of a publication
	 *
	 * @return An array containing author array
	 */
	function fetch_pub_authors($pub_id) {
		$authors = array();
		$sta =& $this->authorshipTableAlias;
		$ata =& $this->authorTableAlias;

		$WC = '';

		$WC .= $sta . '.pub_id=' . intval($pub_id) . "\n";
		//$WC .= ' AND '.$sta.'.pid='.$ata.'.pid'."\n";
		$WC .= $this->enable_fields($this->authorshipTable, $sta);
		$WC .= $this->enable_fields($this->authorTable, $ata);

		$OC = $sta . '.sorting ASC';

		$field_csv = $ata . '.' . implode(',' . $ata . '.', $this->authorAllFields);
		$q = $this->select_clause_start(array($field_csv, $sta . '.sorting'),
			array($this->t_au_default, $this->t_as_default));
		$q .= ' WHERE ' . $WC . "\n";
		$q .= ' ORDER BY ' . $OC . "\n";
		$q .= ';';
		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ($q);
		$res = $GLOBALS['TYPO3_DB']->sql_query($q);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$authors[] = $row;
		}
		return $authors;
	}


	/**
	 * This retrieves the publication data from the database
	 *
	 * @return The publication data from the database
	 */
	function fetch_db_pub($uid) {
		$WC = "uid='" . intval($uid) . "'";
		$WC .= $this->enable_fields($this->referenceTable, '', $this->show_hidden);
		$field_csv = implode(',', $this->refAllFields);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($field_csv, $this->referenceTable, $WC);
		$pub = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		if (is_array($pub)) {
			$pub['authors'] = $this->fetch_pub_authors($pub['uid']);
			$pub['mod_key'] = $this->modification_key($pub);
		}
		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( $pub );
		return $pub;
	}


	/**
	 * This initializes the reference fetching.
	 * Executes a select query.
	 *
	 * @return Not defined
	 */
	function mFetch_initialize() {
		$rta =& $this->referenceTableAlias;
		$field_csv = $rta . '.' . implode(',' . $rta . '.', $this->refAllFields);
		$query = $this->get_reference_select_clause($field_csv);
		$this->dbRes = $GLOBALS['TYPO3_DB']->sql_query($query);
	}


	/**
	 * Returns the number of references that will be fetched
	 *
	 * @return The number of references
	 */
	function mFetch_num() {
		return $GLOBALS['TYPO3_DB']->sql_num_rows($this->dbRes);
	}


	/**
	 * Fetches a reference
	 *
	 * @return A database row
	 */
	function mFetch() {
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($this->dbRes);
		if ($row) {
			$row['authors'] = $this->fetch_pub_authors($row['uid']);
			$row['mod_key'] = $this->modification_key($row);
		}
		return $row;
	}


	/**
	 * Finish reference fetching (clean up)
	 *
	 * @return void
	 */
	function mFetch_finish() {
		$GLOBALS['TYPO3_DB']->sql_free_result($this->dbRes);
	}


	/**
	 * This returns the modification key for a publication
	 *
	 * @return The mod_key string
	 */
	function modification_key($pub) {
		$key = '';
		foreach ($pub['authors'] as $a) {
			$key .= $a['surname'];
			$key .= $a['forename'];
		};
		$key .= $pub['title'];
		$key .= strval($pub['crdate']);
		$key .= strval($pub['tstamp']);
		$sha = sha1($key);
		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'key' => $key, 'sha' => $sha ) );
		return $sha;
	}


	/**
	 * Fetches an authorship
	 *
	 * @return The matching authorship row or NULL
	 */
	function fetch_authorships($aShip) {
		$ret = array();
		if (is_array($aShip)) {
			//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ('fetching authorship: '=>$aShip ) );
			if (isset ($aShip['pub_id']) || isset ($aShip['author_id']) || isset ($aShip['pid'])) {
				$WC = array();
				if (isset ($aShip['pub_id'])) {
					$WC[] = 'pub_id=' . intval($aShip['pub_id']);
				}
				if (isset ($aShip['author_id'])) {
					$WC[] = 'author_id=' . intval($aShip['author_id']);
				}
				if (isset ($aShip['pid'])) {
					$WC[] = 'pid=' . intval($aShip['pid']);
				}
				$WC = implode(' AND ', $WC);
				$WC .= $this->enable_fields($this->authorshipTable);
				//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'WC: ' => $WC ) );
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->authorshipTable, $WC);
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))
					$ret[] = $row;
			}
		}
		return $ret;
	}


}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/Classes/Utility/ReferenceReader.php"]) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/Classes/Utility/ReferenceReader.php"]);
}

?>