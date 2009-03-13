<?php
if ( !isset($GLOBALS['TSFE']) )
	die ('This file is not meant to be executed');

/**
 * This class provides the reference database interface
 * and some utility methods
 *
 * @author Sebastian Holtermann
 */
class tx_sevenpack_reference_accessor {

	protected $filter;
	protected $cObj;

	public $dbRes;
	public $clear_cache;
	protected $error;

	public $refTable    = 'tx_sevenpack_references';
	public $authorTable = 'tx_sevenpack_authors';
	public $aShipTable  = 'tx_sevenpack_authorships';

	public $refTableAlias    = 't_ref';
	public $authorTableAlias = 'tx_sevenpack_authors';
	public $aShipTableAlias  = 'tx_sevenpack_authorships';

	public $t_ref_default = array ( );
	public $t_as_default = array ( );
	public $t_au_default = array ( );


	/**
	 * These are the publication relevant fields 
	 * that can be found in a php publication array.
	 * Typo3 special fields like pid or uid are not listed here
	 */
	public $pubFields = array (
		'bibtype', 'citeid', 'authors', 'title', 'journal', 'year',
		'month', 'day', 'volume', 'number', 'pages', 'abstract',
		'affiliation', 'note', 'annotation', 'keywords',
		'file_url', 'misc', 'editor', 'publisher', 'series',
		'address', 'edition', 'chapter', 'howpublished',
		'booktitle', 'organization', 'school', 'institution',
		'state', 'type', 'ISBN', 'DOI',
		'extern', 'reviewed', 'in_library', 'borrowed_by'
	);


	/**
	 * These are the publication relevant fields 
	 * that can be found in the reference table $this->refTable.
	 * Typo3 special fields like pid or uid are not listed here
	 */
	public $refFields = array (
		'bibtype', 'citeid', 'title', 'journal', 'year',
		'month', 'day', 'volume', 'number', 'pages', 'abstract',
		'affiliation', 'note', 'annotation', 'keywords',
		'file_url', 'misc', 'editor', 'publisher', 'series',
		'address', 'edition', 'chapter', 'howpublished',
		'booktitle', 'organization', 'school', 'institution',
		'state', 'type', 'ISBN', 'DOI',
		'extern', 'reviewed', 'in_library', 'borrowed_by'
	);


	public $allBibTypes = array (
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
		16 => 'poster'
	);


	public $allStates = array (
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
	function tx_sevenpack_reference_accessor ( ) {
		$this->dbRes = NULL;
		$this->filter = array();
		$this->filter['pid'] = array();
		$this->clear_cache = FALSE;
		$this->error = FALSE;

		$this->t_ref_default['table'] = $this->refTable;
		$this->t_ref_default['alias'] = $this->refTableAlias;

		$this->t_as_default['table'] = $this->aShipTable;
		$this->t_as_default['alias'] = $this->aShipTableAlias;

		$this->t_au_default['table'] = $this->authorTable;
		$this->t_au_default['alias'] = $this->authorTableAlias;
	}


	/**
	 * set the cObject
	 *
	 * @return void
	 */
	function set_cObj ( &$cObj ) {
		$this->cObj =& $cObj;
	}


	/**
	 * Returns the error message and resets it.
	 * The returned message is either a string or FALSE
	 *
	 * @return The last error message
	 */
	function error_message ( ) {
		$err = $this->error;
		$this->error = FALSE;
		return $err;
	}


	/** 
	 * Same as error_message() but returns a html version
	 *
	 * @return The last error message
	 */
	function html_error_message ( ) {
		$err = $this->error_message();
		$err = str_replace ( "\n", "<br/>\n", $err );
		return $err;
	}


	/** 
	 * Clears the page cache of all selected pages
	 *
	 * @return void
	 */
	function clear_page_cache ( ) {
		if ( $this->clear_cache ) {
			$tce = t3lib_div::makeInstance ( 't3lib_TCEmain' );
			$tce->start ( array(), array() );

			// Find storage cache clear requests
			foreach ( $this->filter['pid'] as $p) {
				$tsc = $tce->getTCEMAIN_TSconfig ( $p );
				if ( isset ( $tsc['clearCacheCmd'] ) ) {
					//t3lib_div::debug ( array ( 'clearCacheCmd' => $tsc ) );
					$tce->clear_cacheCmd ( $tsc['clearCacheCmd'] );
				}
			}

			// Clear this page cache
			$tce->clear_cacheCmd( strval ( $GLOBALS['TSFE']->id ) );
		}
	}


	/**
	 * This changes the character set of a publication (array)
	 *
	 * @return The character set adjusted publication
	 */
	function change_pub_charset ( $pub, $cs_from, $cs_to ) {
		if ( is_array ( $pub ) && strlen ( $cs_from ) && strlen ( $cs_to )
		     && ( $cs_from != $cs_to ) ) {
			$cs =& $GLOBALS['TSFE']->csConvObj;
			$keys = array_keys ( $pub );
			foreach ( $keys as $k ) {
				$v =& $pub[$k];
				switch ( $k ) {
					case 'authors':
						if ( is_array ( $v ) ) {
							foreach ( $v as &$a ) {
								$a['fn'] = $cs->conv ( $a['fn'], $cs_from, $cs_to );
								$a['sn'] = $cs->conv ( $a['sn'], $cs_from, $cs_to );
							}
						}
					default:
						if ( is_string ( $v ) )
							$v = $cs->conv ( $v, $cs_from, $cs_to );
				}
			}
		}
		return $pub;
	}


	/**
	 * This sets the filter which will be asked for most
	 * query compositions
	 *
	 * @return Not defined
	 */
	function set_filter ( $filter ) {
		if ( is_array ( $filter ) ) {
			if ( !is_array ( $filter['pid'] ) )
				$filter['pid'] = explode ( ',', strval($filter['pid']) );
			$this->filter = $filter;
			$this->fetch_author_filter_uids ();
		}
	}


	function enable_fields ( $table, $alias = '', $show_hidden = FALSE ) {
		$ret = '';
		if ( strlen ( $alias ) == 0 )
			$alias = $table;
		if ( isset ( $this->cObj ) ) {
			$ret = $this->cObj->enableFields ( $table, $show_hidden ? 1 : 0 );
		} else {
			$ret = ' AND ' . $table . '.deleted=0';
		}
		if ( $alias != $table )
			$ret = str_replace ( $table, $alias, $ret );
		return $ret;
	}


	/**
	 * This function returns the SQL SELECT clause
	 * beginning with all the joins configured in $tables
	 * included
	 *
	 * @return The SELECT clause beginning
	 */
	function select_clause_start ( $fields, $tables ) {
		$SC = '';
		if ( is_array ( $fields ) && is_array ( $tables ) ) {
			$rT =& $this->refTable;
			$aT =& $this->authorTable;
			$sT =& $this->aShipTable;

			$base =& $tables[0];
			$joins = '';
			$aliases = array ( $base['alias'] );
			for ( $i=1; $i < sizeof ( $tables ); $i++ ) {
				$prev = $tables[$i-1];
				$cur = $tables[$i];

				if ( (($prev['table'] == $rT) && ($cur['table'] == $aT)) ||
				     (($prev['table'] == $aT) && ($cur['table'] == $rT)) ) {
					$joins .= $this->get_sql_join ( $prev, $this->t_as_default, $aliases );
					$joins .= $this->get_sql_join ( $this->t_as_default, $cur, $aliases );
				} else {
					$joins .= $this->get_sql_join ( $prev, $cur, $aliases );
				}
			}

			$SC  = 'SELECT '.implode ( ',', $fields )."\n";
			$SC .= ' FROM '.$base['table'].' '.$base['alias']."\n";
			$SC .= $joins;
		}
		return $SC;
	}


	/**
	 * This function returns a SQL JOIN string for the requested
	 * table if it has not yet been joined with the requested alias
	 *
	 * @return The WHERE clause string
	 */
	function get_sql_join ( $table, $join, &$aliases ) {
		$rT =& $this->refTable;
		$aT =& $this->authorTable;
		$sT =& $this->aShipTable;

		$js = '';

		if ( in_array ( $join['alias'], $aliases ) )
			return '';

		// The match fields
		$t_mf = '';
		$j_mf = '';

		switch ( $table['table'] ) {
			case $rT:
				switch ( $join['table'] ) {
					case $sT:
						$t_mf = 'uid';
						$j_mf = 'pub_id';
						break;
				}
				break;
			case $sT:
				switch ( $join['table'] ) {
					case $rT:
						$t_mf = 'pub_id';
						$j_mf = 'uid';
						break;
					case $aT:
						$t_mf = 'author_id';
						$j_mf = 'uid';
						break;
				}
				break;
			case $aT:
				switch ( $join['table'] ) {
					case $sT:
						$t_mf = 'uid';
						$j_mf = 'author_id';
						break;
				}
				break;
		}

		$aliases[] = $join['alias'];
		$js .= ' INNER JOIN ' . $join['table'] . ' AS ' . $join['alias'];
		$js .= ' ON ' . $table['alias'] . '.' . $t_mf;
		$js .= '=' . $join['alias'] . '.' . $j_mf;
		$js .= "\n";

		return $js;
	}


	/**
	 * This function returns the SQL WHERE clause configured
	 * by the filter
	 *
	 * @return The WHERE clause string
	 */
	function get_reference_where_clause ( ) {
		$filter =& $this->filter;

		$rT = $this->refTable;
		$sT = $this->aShipTable;

		$rta = $this->refTableAlias;
		$ata = $this->authorTableAlias;
		$sta = $this->aShipTableAlias;

		$WC = '';

		// Filter by UID
		if ( is_array ( $filter['uid'] ) && sizeof ( $filter['uid'] ) ) {
			$WC .= ' AND '.$rta.'.uid IN ('.implode ( ',', $filter['uid'] ).')'."\n";
		}

		// Filter by storage PID
		if ( is_array ( $filter['pid'] ) && sizeof ( $filter['pid'] ) ) {
			$WC .= ' AND '.$rta.'.pid IN ('.implode ( ',', $filter['pid'] ).')'."\n";
		}

		// Filter by year
		$f =& $filter['year'];
		if ( $f && $f['enabled'] ) {
			$YWC = '';
			// years
			if ( is_array ( $f['years'] ) && sizeof ( $f['years'] ) ) {
				$YWC .= '  '.$rta.'.year IN ('.implode ( ',', $f['years'] ).')'."\n";
			}
			// ranges
			if ( is_array ( $f['ranges'] ) && sizeof ( $f['ranges'] ) ) {
				$ra =& $f['ranges'];
				if ( sizeof ( $ra ) ) {
					for ( $i=0; $i < sizeof($ra); $i++ ) {
						$r =& $ra[$i];
						$both = (isset ( $r['from'] ) && isset ( $r['to'] )) ? TRUE : FALSE;
						if ( strlen ( $YWC ) )
							$YWC .= ' OR ';
						if ( $both )
							$YWC .= '(';
						if ( isset ( $r['from'] ) )
							$YWC .= ' '.$rta.'.year >= '.intval ( $r['from'] );
						if ( $both )
							$YWC .= ' AND';
						if ( isset ( $r['to'] ) )
							$YWC .= ' '.$rta.'.year <= '.intval ( $r['to'] );
						if ( $both )
							$YWC .= ')';
						$YWC .= "\n";
					}
				}
			}
			$WC .= ' AND ( '."\n";
			$WC .= $YWC.' )';
		}

		// Filter by authors
		$f =& $filter['author'];
		//t3lib_div::debug ( $f );
		if ( $f && $f['enabled'] && sizeof ( $f['authors'] ) ) {
			$authors =& $f['authors'];
			if ( $f['rule'] == 1 ) {
				// AND
				if ( is_array ( $f['sets'] ) && ( sizeof ( $f['sets'] ) > 0 ) ) {
					$set_arr = array();
					for ( $i=0; $i<sizeof($f['sets']); $i++ ) {
						$set = $f['sets'][$i];
						$uid_arr = array();
						foreach ( $set as $a ) {
							$uid_arr[] = $a['uid'];
						}
						$num = ($i>0) ? strval($i+1) : '';
						$set_arr[] = $sta.$num.'.author_id IN (' . implode ( ',', $uid_arr ) . ')' .
							$this->enable_fields ( $sT, $sta.$num );
					}
					if ( sizeof ( $set_arr ) > 0 ) {
						foreach ( $set_arr as $add ) {
							$WC .= ' AND '.$add;
						}
					} else {
						$WC .= ' AND FALSE'."\n";
					}
				} else {
					$WC .= ' AND FALSE'."\n";
				}
			} else {
				// OR
				if ( sizeof ( $authors ) ) {
					$aUid = array();
					foreach ( $authors as $a ) {
						if ( is_numeric ( $a['uid'] ) )
							$aUid[] = intval ( $a['uid'] );
					}
					if ( sizeof ( $aUid ) ) {
						$WC .= ' AND '.$sta.'.author_id IN ('.implode ( ',', $aUid ).')';
						$WC .= $this->enable_fields ( $sT, $sta )."\n";
					} else {
						$WC .= ' AND FALSE'."\n";
					}
				}
			}
		}

		// Filter by bibtype
		$f =& $filter['bibtype'];
		if ( $f && $f['enabled'] && is_array ( $f['types'] ) ) {
			if ( sizeof ( $f['types'] ) ) {
				$WC .= ' AND '.$rta.'.bibtype IN (' . implode ( ',', $f['types'] ) .')'."\n";
			}
		}

		// Filter by publication state
		$f =& $filter['state'];
		if ( $f && $f['enabled'] && is_array ( $f['states'] ) ) {
			if ( sizeof ( $f['states'] ) ) {
				$WC .= ' AND '.$rta.'.state IN (' . implode ( ',', $f['states'] ) .')'."\n";
			}
		}

		// Filter by origin
		$f =& $filter['origin'];
		if ( $f && $f['enabled'] ) {
			$WC .= ' AND (';
			if ( intval ( $f['origin'] ) <= 1 )
				$WC .= $rta.'.extern='."'0'";
			else
				$WC .= $rta.'.extern='."'1'";
			$WC .= ')'."\n";
		}

		// Filter by reviewed
		$f =& $filter['reviewed'];
		if ( $f && $f['enabled'] ) {
			$WC .= ' AND (';
			if ( intval ( $f['value'] ) == 0 )
				$WC .= $rta.'.reviewed='."'0'";
			else
				$WC .= $rta.'.reviewed='."'1'";
			$WC .= ')'."\n";
		}

		// Filter by borrowed
		$f =& $filter['borrowed'];
		if ( $f && $f['enabled'] ) {
			$WC .= ' AND (';
			if ( intval ( $f['value'] ) == 0 )
				$WC .= 'LENGTH('.$rta.'.borrowed_by)='."'0'";
			else
				$WC .= 'LENGTH('.$rta.'.borrowed_by)!='."'0'";
			$WC .= ')'."\n";
		}

		// Filter by in library
		$f =& $filter['in_library'];
		if ( $f && $f['enabled'] ) {
			$WC .= ' AND (';
			if ( intval ( $f['value'] ) == 0 )
				$WC .= $rta.'.in_library='."'0'";
			else
				$WC .= $rta.'.in_library='."'1'";
			$WC .= ')'."\n";
		}

		// Filter by citeid
		$f =& $filter['citeid'];
		if ( $f && $f['enabled'] && sizeof($f['ids']) ) {
			$WC .= ' AND '.$rta.'.citeid IN (';
			for ( $i=0; $i < sizeof ( $f['ids'] ); $i++ )  {
				if ( $i > 0 )
					$WC .= ',';
				$WC .= $GLOBALS['TYPO3_DB']->fullQuoteStr ( $f['ids'][$i], $rT );
			}
			$WC .= ')';
		}

		// Typo3 fields
		$WC .= $this->enable_fields ( $rT, $rta, $filter['show_hidden'] );

		// Remove AND at the beginning
		$WC = preg_replace ( '/^\s*AND\s*/i', '', $WC );

		//t3lib_div::debug ( array ('WHERE clause filter: ' => $filter ) );
		//t3lib_div::debug ( array ('WHERE clause: ' => $WC ) );
		return $WC;
	}


	/**
	 * This function returns the SQL ORDER clause configured
	 * by the filter
	 *
	 * @return The ORDER clause string
	 */
	function get_order_clause (  ) {
		$filter =& $this->filter;
		$OC = '';
		if ( is_array ( $filter['sorting'] ) ) {
			$sortings =& $filter['sorting'];
			$OC = '';
			for ( $i=0; $i<sizeof($sortings); $i++ ) {
				$s =& $sortings[$i];
				if ( isset( $s['field'] ) && isset ( $s['dir'] ) ) {
					if ( $i > 0 )
						$OC .= ',';
					$OC .= $s['field'].' '.$s['dir'] . "\n";
				}
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
	function get_limit_clause ( ) {
		$filter =& $this->filter;
		$LC = '';
		if ( is_array ( $filter['limit'] ) ) {
			$l =& $filter['limit'];
			if ( isset( $l['start'] ) && isset ( $l['num'] ) ) {
				$LC .= intval($l['start']).','.intval($l['num']);
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
	function get_reference_select_clause ( $fields, $order = '', $group = '' ) {
		if ( !is_array ( $fields ) )
			$fields = array ( $fields );
		$rta =& $this->refTableAlias;
		$sta =& $this->aShipTableAlias;
		$ata =& $this->authorTableAlias;

		$WC = $this->get_reference_where_clause ( );
		$GC = '';
		if ( is_string ( $group ) )
			$GC = strlen ( $group ) ? $group : $rta.'.uid';
		$OC = '';
		if ( is_string ( $order ) )
			$OC = strlen ( $order ) ? $order : $this->get_order_clause ( );
		$LC = $this->get_limit_clause ( );

		// Find the tables that should be included
		$tables = array ( $this->t_ref_default );
		foreach ( $fields as $f ) {
			if ( ! ( strpos ( $f , $sta ) === FALSE ) )
				$tables[] = $this->t_as_default;
			if ( ! ( strpos ( $f , $ata ) === FALSE ) )
				$tables[] = $this->t_au_default;
		}
		if ( $this->filter['author']['enabled'] && !in_array ( $this->t_as_default, $tables ) )
			$tables[] = $this->t_as_default;

		if ( $this->filter['author']['rule'] == 1 ) {
			for ( $i=1; $i < sizeof($this->filter['author']['sets']); $i++ ) {
				$table = $this->t_as_default;
				$table['alias'] .= strval ( $i+1 );
				$tables[] = $this->t_ref_default;
				$tables[] = $table;
			}
		}

		//t3lib_div::debug ( $tables );

		$q  = $this->select_clause_start ( $fields, $tables );
		if ( strlen ( $WC ) )
			$q .= ' WHERE '. $WC."\n";
		if ( strlen ( $GC ) )
			$q .= ' GROUP BY '.$GC."\n";
		if ( strlen ( $OC ) )
			$q .= ' ORDER BY '.$OC."\n";
		if ( strlen ( $LC ) )
			$q .= ' LIMIT '.$LC."\n";
		$q .= ';';
		//t3lib_div::debug ( array ( 'ref select clause: ' => $q ) );
		return $q;
	}


	/** 
	 * Checks if a publication that has not the given uid
	 * but the citeid exists in the database. The lookup is restricted
	 * to the currend storage folders ($filter['pid'])
	 *
	 * @return TRUE on existance FALSE otherwise
	 */
	function citeid_exists ( $citeid, $uid = -1 ) {
		$num = 0;
		$WC  = 'citeid='.$GLOBALS['TYPO3_DB']->fullQuoteStr ( $citeid, $this->refTable );
		$WC .= $this->enable_fields ( $this->refTable, '', $filter['show_hidden'] );
		if ( is_numeric ( $uid ) && ( $uid >= 0 ) )
			$WC .= ' AND uid!='."'".intval($uid)."'";
		if ( sizeof ( $this->filter['pid'] ) )
			$WC .= ' AND pid IN ('.implode(',',$this->filter['pid']).")";

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( 'count(uid)', $this->refTable, $WC );
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc ( $res );
		if ( is_array ( $row ) )
			$num = intval ( $row['count(uid)'] );

		return ($num > 0) ? TRUE : FALSE;
	}


	/** 
	 * Returns the number of publications  which match
	 * the filtering criteria
	 * 
	 * @return The number of publications
	 */
	function fetch_num () {
		$rta =& $this->refTableAlias;

		$select = $this->get_reference_select_clause ( $rta.'.uid', NULL );
		$select = preg_replace ( '/;\s*$/', '', $select );
		$query = 'SELECT count(pubs.uid) FROM ('.$select.') pubs;';
		$res = $GLOBALS['TYPO3_DB']->sql_query ( $query );
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc ( $res );
		if ( is_array ( $row ) )
			return intval ( $row['count(pubs.uid)'] );
		return 0;
	}


	/**
	 * Returns the latest timestamp found in the database
	 *
	 * @return The publication data from the database
	 */
	function fetch_max_tstamp ( ) {
		$max_rT = 'max('.$this->refTableAlias.'.tstamp)';
		$max_aT = 'max('.$this->authorTableAlias.'.tstamp)';

		$query = $this->get_reference_select_clause ( 
			$max_rT.', '.$max_aT, NULL, NULL );
		$res = $GLOBALS['TYPO3_DB']->sql_query ( $query );
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc ( $res );

		if ( is_array ( $row ) ) {
			//t3lib_div::debug ($row);
			return max ( $row );
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
	function fetch_histogram ( $field = 'year' ) {
		$histo = array();
		$rta =& $this->refTableAlias;

		$query = $this->get_reference_select_clause ( $rta.'.'.$field, $rta.'.'.$field.' ASC' );
		$res = $GLOBALS['TYPO3_DB']->sql_query ( $query );

		$cVal = NULL;
		$cNum = NULL;
		while ( $row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc ( $res ) )  {
			$val = $row[$field];
			if ( $cVal == $val )
				$cNum++;
			else {
				$cVal = $val;
				$histo[$val] = 1;
				$cNum =& $histo[$val];
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result ( $res );

		//t3lib_div::debug ($histo);
		return $histo;
	}


	/**
	 * Fetches the uid(s) of the given auhor.
	 * Checked is against the forename and the surname.
	 *
	 * @return Not defined
	 */
	function fetch_author_uids ( $author, $pids, $exact=TRUE ) {
		$uids = array();
		$a =& $author;
		$db =& $GLOBALS['TYPO3_DB'];
		$aT =& $this->authorTable;

		$WC = '';
		$check_fn = FALSE;
		$check_sn = FALSE;
		if ( isset ( $a['fn'] ) && ( strlen ( $a['fn'] ) || $exact ) )
			$check_fn = TRUE;
		if ( isset ( $a['sn'] ) && ( strlen ( $a['sn'] ) || $exact ) )
			$check_sn = TRUE;
		if ( $check_fn )
			$WC .= ' AND forename='.$db->fullQuoteStr ( $a['fn'], $aT )."\n";
		if ( $check_sn )
			$WC .= ' AND surname='.$db->fullQuoteStr ( $a['sn'], $aT )."\n";
		if ( strlen ( $WC ) ) {
			$WC .= $this->enable_fields ( $aT )."\n";
			if ( is_array ( $pids ) ) {
				$WC .= ' AND pid IN ('.implode ( ',', $pids ).')'."\n";
			} else {
				$WC .= ' AND pid='.intval ( $pids )."\n";
			}
			$WC = preg_replace ( '/^\s*AND\s*/', '', $WC );
			//t3lib_div::debug ($WC);
			$res = $db->exec_SELECTquery ( 'uid,pid,surname,forename', $aT, $WC );
			while ( $row = $db->sql_fetch_assoc ( $res ) ) {
				if ( !$check_fn || ($row['forename'] == $a['fn']) ) {
					if ( !$check_sn || ($row['surname'] == $a['sn']) ) {
						$uids[] = array ( 'uid' => $row['uid'], 'pid' => $row['pid'] );
						if ( $exact )
							break;
					}
				}
			}
		}
		//t3lib_div::debug ( array ( 'uids' => $uids ) );
		return $uids;
	}


	/**
	 * Fetches the uids of the auhors in the author filter
	 *
	 * @return Not defined
	 */
	function fetch_author_filter_uids ( ) {
		//t3lib_div::debug ('Fetching author uids');
		if ( is_array ( $this->filter['author']['authors'] ) ) {
			$filter =& $this->filter['author'];
			$authors =& $filter['authors'];
			$filter['sets'] = array();
			foreach ( $authors as &$a ) {
				if ( !isset ( $a['uid'] ) ) {
					$uids = $this->fetch_author_uids ( $a, $this->filter['pid'], FALSE );
					for ( $i=0; $i<sizeof($uids); $i++ ) {
						$uid = $uids[$i];
						if ( $i == 0 ) {
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
					if ( sizeof ( $uids ) ) {
						$filter['sets'][] = $uids;
					}
				}
			}
			//t3lib_div::debug ($filter);
		}
	}


	/**
	 * Fetches the authors of a publication
	 *
	 * @return An array containing author array
	 */
	function fetch_pub_authors ( $pub_id ) {
		$authors = array ( );
		$sta =& $this->aShipTableAlias;
		$ata =& $this->authorTableAlias;

		//$pid_lst = implode ( ',', $this->filter['pid'] );

		$WC = '';

		$WC .= $sta.'.pub_id='.intval ( $pub_id )."\n";
		//$WC .= ' AND '.$sta.'.pid='.$ata.'.pid'."\n";
		$WC .= $this->enable_fields ( $this->aShipTable, $sta );
		$WC .= $this->enable_fields ( $this->authorTable, $ata );

		$OC = $sta.'.sorting ASC';

		$q  = $this->select_clause_start ( array ( $ata.'.*', $sta.'.sorting' ), 
			array ( $this->t_au_default, $this->t_as_default ) );
		$q .= ' WHERE '. $WC."\n";
		$q .= ' ORDER BY '.$OC."\n";
		$q .= ';';
		//t3lib_div::debug ($q);
		$res = $GLOBALS['TYPO3_DB']->sql_query ( $q );
		while ( $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc ( $res ) ) {
			$a = array();
			$a['uid'] = $row['uid'];
			$a['pid'] = $row['pid'];
			$a['fn']  = $row['forename'];
			$a['sn']  = $row['surname'];
			$a['url'] = $row['url'];
			$a['sorting'] = $row['sorting'];
			$authors[] = $a;
		}
		return $authors;
	}


	/**
	 * This retrieves the publication data from the database
	 *
	 * @return The publication data from the database
	 */
	function fetch_db_pub ( $uid ) {
		$WC  = "uid='".intval($uid)."'";
		$WC .= $this->enable_fields ( $this->refTable, '', $this->filter['show_hidden'] );
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( '*', $this->refTable, $WC );
		$pub = $GLOBALS['TYPO3_DB']->sql_fetch_assoc ( $res );
		if ( is_array ( $pub ) ) {
			$pub['authors'] = $this->fetch_pub_authors ( $pub['uid'] );
			$pub['mod_key'] = $this->modification_key ( $pub );
		}
		//t3lib_div::debug ( $pub );
		return $pub;
	}


	/**
	 * This initializes the reference fetching.
	 * Executes a select query.
	 *
	 * @return Not defined
	 */
	function mFetch_initialize ( ) {
		$rta =& $this->refTableAlias;
		$query = $this->get_reference_select_clause ( $rta.'.*' );
		$this->dbRes = $GLOBALS['TYPO3_DB']->sql_query ( $query );
	}


	/** 
	 * Returns the number of references that will be fetched
	 *
	 * @return The number of references
	 */
	function mFetch_num ( ) {
		return $GLOBALS['TYPO3_DB']->sql_num_rows ( $this->dbRes );
	}


	/** 
	 * Fetches a reference
	 *
	 * @return A database row
	 */
	function mFetch ( ) {
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc ( $this->dbRes );
		if ( $row ) {
			$row['authors'] = $this->fetch_pub_authors ( $row['uid'] );
			$row['mod_key'] = $this->modification_key ( $row );
		}
		return $row;
	}


	/** 
	 * Finish reference fetching (clean up)
	 *
	 * @return void
	 */
	function mFetch_finish ( ) {
		$GLOBALS['TYPO3_DB']->sql_free_result ( $this->dbRes );
	}


	/**
	 * This returns the modification key for a publication
	 * 
	 * @return The mod_key string
	 */
	function modification_key ( $pub ) {
		$key = '';
		foreach ( $pub['authors'] as $a ) {
			$key .= $a['sn'];
			$key .= $a['fn'];
		};
		$key .= $pub['title'];
		$key .= strval ( $pub['crdate'] );
		$key .= strval ( $pub['tstamp'] );
		$sha = sha1 ( $key );
		//t3lib_div::debug ( array ( 'key' => $key, 'sha' => $sha ) );
		return $sha;
	}


	/**
	 * This function updates a publication with all data
	 * found in the HTTP request
	 * 
	 * @return TRUE on error FALSE otherwise
	 */
	function save_publication ( $pub ) {
		if ( !is_array ( $pub ) )
			return TRUE;

		$new = False;
		$uid = -1;
		$db =& $GLOBALS['TYPO3_DB'];
		$rT =& $this->refTable;
		//t3lib_div::debug ( $pub );

		// Fetch reference from DB
		$pub_db = NULL;
		if ( is_numeric ( $pub['uid'] ) ) {
			$pub_db = $this->fetch_db_pub ( intval ( $pub['uid'] ) );
			if ( is_array ( $pub_db ) ) {
				$uid = intval ( $pub_db['uid'] );
			} else {
				$this->error = 'The publication reference could not be updated' .
					' because it does not exist in the database (anymore?).';
				$this->ref_log ( $this->error, $pub['uid'], 1 );
				return TRUE;
			}
		}

		// Select first pid from list if no one is present
		if ( !is_numeric ( $pub['pid'] ) ) {
			if ( is_array ( $pub_db ) )
				$pub['pid'] = intval ( $pub_db['pid'] );
			else
				$pub['pid'] = $this->filter['pid'][0];
		}

		$refRow = array ( );
		// Copy reference fiels
		foreach ( $this->refFields as $f ) {
			switch ( $f ) {
				default:
					if ( array_key_exists ( $f, $pub ) )
						$refRow[$f] = $pub[$f];
			}
		}

		// Add Typo3 fields
		$refRow['pid']    = intval ( $pub['pid'] );
		$refRow['tstamp'] = time();
		$refRow['hidden'] = intval ( $pub['hidden'] );

		$query = '';
		if ( $uid >= 0 ) {
			if ( $pub['mod_key'] == $pub_db['mod_key'] ) {
				// t3lib_div::debug ( array ('updating'=>$refRow ));
				$WC = 'uid=' . intval ( $uid );
				$db->exec_UPDATEquery ( $rT, $WC, $refRow );
			} else {
				$this->error = 'The publication reference could not be updated' .
					' because the modification key does not match.' . "\n";
				$this->error .= ' Maybe someone edited this reference meanwhile.';
				$this->ref_log ( $this->error, $uid, 1 );
				return TRUE;
			}
		} else {
			$new = TRUE;
			// t3lib_div::debug ( array ('saving'=>$refRow ));
			$refRow['crdate']    = $refRow['tstamp'];
			$refRow['cruser_id'] = $GLOBALS['BE_USER']->user['uid'];
			$db->exec_INSERTquery ( $rT, $refRow );
			$uid = $db->sql_insert_id ( );
			if ( $uid > 0 ) {
				//t3lib_div::debug ( array ('Inserted publication'=>$refRow ) );
			} else {
				$this->error = 'A publication reference could not be inserted into the database';
				$this->log ( $this->error, 2 );
				return TRUE;
			}
		}

		if ( $uid > 0 ) {
			$ret = $this->save_publication_authors ( $uid, $pub['pid'], $pub['authors'] );
			if ( $ret )
				return TRUE;
		}

		if ( $new )
			$this->ref_log ( 'A new publication reference was inserted (pid=' . $pub['pid'] . ')', $uid );
		else
			$this->ref_log ( 'A publication reference was modified', $uid );
		return FALSE;
	}


	/**
	 * Saves the authors of a publication
	 *
	 * @return The uid of the inserted author
	 */
	function save_publication_authors ( $pub_uid, $pid, $authors ) {
		$db =& $GLOBALS['TYPO3_DB'];

		// Fetches missing author uids and
		// inserts new authors on demand
		$sort = 0;
		foreach ( $authors as &$author ) {
			// Set new sorting value
			$sort += 1;
			$author['sorting'] = $sort;

			if ( !is_numeric ( $author['uid'] ) ) {
				$uids = $this->fetch_author_uids ( $author, $pid, TRUE );
				//t3lib_div::debug ( array ('author'=>$author, 'uids'=>$uids ) );

				if ( sizeof ( $uids ) > 0 ) {
					$author['uid'] = $uids[0]['uid'];
				} else {
					// Insert missing author
					$ia = $author;
					$ia['pid'] = intval ( $pid );
					
					$author['uid'] = $this->insert_author ( $ia );
					if ( $author['uid'] > 0 ) {
						//t3lib_div::debug ( array ('Inserted author'=>$author ) );
					} else {
						$this->error = 'An author could not be inserted into the database';
						$this->log ( $this->error, 1 );
						return TRUE;
					}
				}
			}
		}

		$db_aships = $this->fetch_authorships ( array ( 'pub_id' => $pub_uid ) );

		$as_delete = array();
		$as_new = sizeof ( $authors ) - sizeof ( $db_aships );
		if ( $as_new < 0 ) {
			// This deletes the first authorships
			$as_new = abs ( $as_new );
			for ( $ii = 0; $ii < $as_new; $ii++ ) {
				$as_delete[] = $db_aships[$ii]['uid'];
			}
			//t3lib_div::debug ( array ('as_delete'=>$as_delete ) );
			$this->delete_authorships ( $as_delete );
			$db_aships = array_slice ( $db_aships, $as_new );
			//t3lib_div::debug ( array ('db_aships'=>$db_aships ) );
			$as_new = 0;
		}
		$as_present = sizeof ( $authors ) - $as_new;

		// Inserts new and updates old authorships
		for ( $ii = 0; $ii < sizeof ( $authors ); $ii++ ) {
			$author =& $authors[$ii];
			if ( is_numeric ( $author['uid'] ) ) {
				$as = array();
				$as['pid'] = intval ( $pid );
				$as['pub_id'] = intval ( $pub_uid );
				$as['author_id'] = intval ( $author['uid'] );
				$as['sorting'] = $author['sorting'];

				if ( $ii < $as_present ) {
					// There are present authorships - Update authorship
					$as_uid = $db_aships[$ii]['uid'];
					$db->exec_UPDATEquery ( $this->aShipTable, 'uid='.intval($as_uid), $as );
				} else {
					// No more present authorships - Insert authorship
					$as_uid = $db->exec_INSERTquery ( $this->aShipTable, $as );
					if ( $as_uid > 0 ) {
						//t3lib_div::debug ( array ('Inserted authorship'=>$as ) );
					} else {
						$this->error = 'An authorship could not be inserted into the database';
						$this->log ( $this->error, 1 );
						return TRUE;
					}
				}
			}
		}

	}


	/**
	 * Inserts an author
	 *
	 * @return The uid of the inserted author
	 */
	function insert_author ( $author ) {
		$ia = array();
		$ia['forename'] = $author['fn'];
		$ia['surname']  = $author['sn'];
		$ia['url']      = $author['url'];
		$ia['pid']      = intval ( $author['pid'] );

		$ia['tstamp'] = time();
		$ia['crdate'] = time();
		$ia['cruser_id'] = $GLOBALS['BE_USER']->user['uid'];

		$GLOBALS['TYPO3_DB']->exec_INSERTquery ( $this->authorTable, $ia );
		$a_uid = $GLOBALS['TYPO3_DB']->sql_insert_id ( );
		return $a_uid;
	}


	/**
	 * Deletes an authorship
	 *
	 */
	function delete_authorship ( $uid ) {
		//t3lib_div::debug ( array ('Deleting authorship: '=>$as_id ) );
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery ( $this->aShipTable, 
			'uid='.intval($uid).' AND deleted=0', array ( 'deleted'=>1 ) );
	}


	/**
	 * Deletes some authorships
	 *
	 */
	function delete_authorships ( $uids ) {
		$uid_list = '';
		for ( $ii = 0; $ii < sizeof ( $uids ); $ii++ ) {
			if ( $ii > 0 )
				$uid_list .= ',';
			$uid_list .= intval ( $uids[$ii] );
		}
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery ( $this->aShipTable, 
			'uid IN ('.$uid_list.') AND deleted=0', array ( 'deleted'=>1 ) );
	}


	/**
	 * Fetches an authorship
	 *
	 * @return The matching authorship row or NULL
	 */
	function fetch_authorships ( $aShip ) {
		$ret = array();
		if ( is_array ( $aShip ) ) {
			//t3lib_div::debug ( array ('fetching authorship: '=>$aShip ) );
			if ( isset ( $aShip['pub_id'] ) || isset ( $aShip['author_id'] ) || isset ( $aShip['pid'] ) ) {
				$WC = array();
				if ( isset ( $aShip['pub_id'] ) ) {
					$WC[] = 'pub_id=' . intval ( $aShip['pub_id'] );
				}
				if ( isset ( $aShip['author_id'] ) ) {
					$WC[] = 'author_id=' . intval ( $aShip['author_id'] );
				}
				if ( isset ( $aShip['pid'] ) ) {
					$WC[] = 'pid=' . intval ( $aShip['pid'] );
				}
				$WC = implode ( ' AND ', $WC );
				$WC .= $this->enable_fields ( $this->aShipTable );
				//t3lib_div::debug ( array ( 'WC: ' => $WC ) );
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( '*', $this->aShipTable, $WC );
				while ( $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc ( $res ) )
					$ret[] = $row;
			}
		}
		return $ret;
	}


	/**
	 * Sets or unsets the hidden flag in the database entry
	 *
	 * @return void
	 */
	function hide_publication ( $uid, $hidden=TRUE ) {
		$uid = intval ( $uid );
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery (
			$this->refTable, 'uid=' . strval ( $uid ) ,
			array ( 'hidden'=>($hidden?'1':'0'),'tstamp'=>time() ) );
		$this->ref_log ( 'A publication reference was ' . ($hidden ? 'hidden' : 'revealed'), $uid );
		$this->clear_page_cache ( );
	}


	/**
	 * Sets the deleted flag in the database entry.
	 * Only the reference and the authorships get deleted.
	 * The author stays untouched even if he/her has no authorship
	 * after this anymore.
	 *
	 * @return Not defined
	 */
	function delete_publication ( $uid, $mod_key ) {
		$deleted = 1;

		$uid = intval ( $uid );
		$db_pub = $this->fetch_db_pub ( $uid );
		if ( is_array ( $db_pub ) ) {
			if ( $db_pub['mod_key'] == $mod_key ) {
				// Delete authorships
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery ( $this->aShipTable, 
					'pub_id='.intval($uid).' AND deleted=0',
					array ( 'deleted'=>$deleted ) );
		
				// Delete reference
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery ( $this->refTable, 
					'uid='.intval($uid).' AND deleted=0',
					array ( 'deleted'=>$deleted,'tstamp'=>time() ) );

				$this->clear_page_cache ( );

				$this->ref_log ( 'A publication reference was deleted', $uid );

			} else {
				$this->error = 'The publication reference could not be deleted' .
					' because the modification key does not match.' . "\n";
				$this->error .= ' Maybe someone edited this reference meanwhile.';
				$this->ref_log ( $this->error, $uid ,1 );
				return TRUE;
			}
		} else {
			$this->error = 'The publication reference could not be deleted' .
				' because it does not exist in the database.';
			$this->ref_log ( $this->error, $uid ,1 );
			return TRUE;
		}

		return FALSE;
	}


	/**
	 * Removes the entry from the database.
	 * The entry must have been marked deleted beforehand.
	 * This erases the referenc and the authorships but not the author
	 *
	 * @return Not defined
	 */
	function erase_publication ( $uid ) {

		// Delete authorships
		$GLOBALS['TYPO3_DB']->exec_DELETEquery (
			$this->aShipTable, 'pub_id='.intval($uid).' AND deleted!=0' );

		// Delete reference
		$GLOBALS['TYPO3_DB']->exec_DELETEquery (
			$this->refTable, 'uid='.intval($uid).' AND deleted!=0' );

		$this->ref_log ( 'A publication reference was erased', $uid );

		return FALSE;
	}


	/**
	 * Writes a log entry
	 *
	 * @return void
	 */
	function log ( $message, $error = 0 ) {
		$GLOBALS['BE_USER']->simplelog ( $message, 'sevenpack', $error );
	}


	/**
	 * Writes a log entry
	 *
	 * @return void
	 */
	function ref_log ( $message, $uid, $error = 0 ) {
		$message = $message . ' (' . $this->refTable  . ':' . intval ( $uid ) . ')';
		$this->log ( $message, $error );
	}
}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/res/class.tx_sevenpack_reference_accessor.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/res/class.tx_sevenpack_reference_accessor.php"]);
}

?>
