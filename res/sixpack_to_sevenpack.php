<?php


/*
 * This script converts publications in a sixpack database
 * to the sevenpack database.
 */

class tx_sevenpack_normalization {

	var $db_link;
	var $db_host;
	var $db_user;
	var $db_passwd;
	var $db_database;
	var $sql_result;
	var $connected;
	
	var $cruser_id;
	var $sixTable;
	var $refTable;
	var $authorTable;
	var $aShipTable;

	var $authorFields;
	var $aShipFields;

	var $stats;
	var $six_to_seven;

	function tx_sevenpack_normalization ( )
	{
		# Database server info
		$this->db_host   = 'localhost';
		$this->db_user   = 'root';
		$this->db_passwd = 'secret';
		$this->db_database = 'typo3';

		$this->connected = FALSE;
		$this->cruser_id = 19;

		$this->sixTable    = 'tx_sixpack_references';

		$this->refTable    = 'tx_sevenpack_references';
		$this->authorTable = 'tx_sevenpack_authors';
		$this->aShipTable  = 'tx_sevenpack_authorships';
		$this->authorFields = array (
			'forename', 'surname'
		);
		$this->aShipFields = array (
			'pub_id', 'author_id'
		);
	}


	function print_out ( $str, $flush = TRUE ) 
	{
		echo $str;
		if ( $flush )
			flush ( );
	}


	function print_error ( $str ) {
		$str .= "### ERROR ###\n";
		$str .= $str . "\n";
		$str .= "### ERROR ###\n";
		echo $str;
		flush();
	}


	function print_sql_error ( $str ) {
		$str .= 	"\n";
		$str .= "(MySQL) " . mysql_error() . "\n";
		$this->print_error ( $str );
	}


	function sql_query ( $query ) {
		$this->sql_result = mysql_query ( $query, $this->db_link );
		return $this->sql_result;
	}


	function sql_fetch_assoc ( ) {
		return mysql_fetch_assoc ( $this->sql_result );
	}


	function sql_num_rows ( ) {
		return mysql_num_rows ( $this->sql_result );
	}


	function sql_insert_id ( ) {
		return mysql_insert_id ( );
	}


	function sql_escape_string ( $str ) {
		return mysql_real_escape_string ( $str, $this->db_link );
	}


	function sql_free () {
		return mysql_free_result ( $this->sql_result );
	}


	function connect ( ) 
	{
		// Ask the database
		$this->db_link = mysql_connect ( $this->db_host, $this->db_user, $this->db_passwd );
		if ( !$this->db_link )
		{
			$this->print_sql_error ( "MySQL connection failed" );
			return TRUE;
		}
		$this->print_out ( "Connected to database\n" );
		flush();

		// Set encoding
		$query =  "SET NAMES utf8;";
		$query_result = mysql_query ( $query, $this->db_link );
		if ( !$query_result )
		{
			$this->print_sql_error ( "Setting encoding failed" );
			return TRUE;
		}
		$this->print_out ( "Set database connection encoding\n" );

		// Select database
		$ret = mysql_select_db ( $this->db_database );
		if ( !$ret )
		{
			$this->print_sql_error ( "Database selection failed." );
			return TRUE;
		}
		$this->print_out ( "Database selected\n" );

		$this->connected = TRUE;
		return FALSE;
	}


	function disconnect ( ) 
	{
		if ( $this->connected ) {
			mysql_close ( $this->db_link );
			$this->connected = FALSE;
		}
	}


	function normalize ( )
	{
		$err = $this->connect();
		if ( $err )
			return $err;

		$this->stats = array();
		$this->stats['authors'] = array ( );
		$this->stats['authors']['present'] = 0;
		$this->stats['authors']['inserted'] = 0;
		$this->stats['authorships']['saved'] = 0;
		$this->stats['authorships']['inserted'] = 0;

		$this->six_to_seven = array ( );
		$sts =& $this->six_to_seven;
		$sts['uid'] = 'uid';
		$sts['pid'] = 'pid';
		$sts['crdate']    = 'crdate';
		$sts['cruser_id'] = 'cruser_id';
		$sts['sorting']   = 'sorting';
		$sts['deleted']   = 'deleted';
		$sts['hidden']    = 'hidden';

		$sts['bibtype']   = 'bibtype';
		$sts['citeid']    = 'citeid';
		#$sts['author']   = 'author';
		$sts['title']     = 'title';
		$sts['journal']   = 'journal';
		$sts['year']      = 'year';
		$sts['volume']    = 'volume';
		$sts['number']    = 'number';
		$sts['pages']     = 'pages';
		$sts['day']       = 'day';
		$sts['month']     = 'month';
		$sts['abstract']    = 'abstract';
		$sts['affiliation'] = 'affiliation';
		$sts['note']        = 'note';
		$sts['annotation']  = 'annotation';
		$sts['keywords']    = 'keywords';
		$sts['file_url']    = 'file_url';
		$sts['misc']        = 'misc';
		$sts['editor']      = 'editor';
		$sts['publisher']   = 'publisher';
		$sts['series']      = 'series';
		$sts['address']     = 'address';
		$sts['edition']     = 'edition';
		$sts['chapter']     = 'chapter';
		$sts['howpublished'] = 'howpublished';
		$sts['booktitle']    = 'booktitle';
		$sts['organization'] = 'organization';
		$sts['school']       = 'school';
		$sts['institution']  = 'institution';
		$sts['state']        = 'state';
		$sts['extern']       = 'extern';

		// Fetch publications
		//$query =  'SELECT uid,pid,crdate,tstamp,cruser_id,deleted,author FROM '.$this->refTable.';' ;

		$query =  'SELECT * FROM '.$this->sixTable.';' ;
		$this->sql_query ( $query );
		if ( !$this->sql_result )
		{
			$this->print_sql_error ( "Fetching publications failed\n" );
			return TRUE;
		} 
		$this->print_out ( "Fetched publications: " . $this->sql_num_rows()."\n" );


		$aPubs = array ( );
		while ( $line = $this->sql_fetch_assoc ( ) )
		{
			$pub = array ( );
			foreach ( $sts as $fsix => $fseven )
			{
				switch ( $fseven ) {
					case 'bibtype':
						$pub[$fseven] = intval($line[$fsix]) + 1;
						break;
					default:
						$pub[$fseven] = $line[$fsix];
				}
			}

			$aPub = $pub;
			// Acquire the author names
			$aPub['authors'] = array();
			$authors = $line['author'];
			$authors = str_replace ( "\n", ' ', $authors );
			$authors = str_replace ( "\r", ' ', $authors );
			$authors = str_replace ( "\t", ' ', $authors );
			$authors = str_replace ( '|', ' and ', $authors );
			$authors = str_replace ( '|', ' and ', $authors );
			$authors = explode ( ' and ', $authors );
			foreach ( $authors as $ad ) 
			{
				$author = array();
				$aa = explode ( ',', $ad );
				$author['sn'] = trim ( $aa[0] );
				unset ( $aa[0] );
				$author['fn'] = trim ( implode ( ', ', $aa ) );
				$aPub['authors'][] = $author;
			}

			//var_dump ( $pub );
			$aPubs[] = $aPub;
		}

		foreach ( $aPubs as $pub ) {
			$this-> insert_sevenpack_pub ( $pub );
		}

		$this->disconnect ( );

		$i_authors =& $this->stats['authors']['inserted'];
		$p_authors =& $this->stats['authors']['present'];
		$u_authors =& $this->stats['authors']['undelete'];

		$i_aShip =& $this->stats['authors']['inserted'];
		$s_aShip =& $this->stats['authors']['saved'];

		//$this->print_out ( "Present authors: ".$p_authors."\n" );
		//$this->print_out ( "Inserted authors: ".$i_authors."\n" );
		//$this->print_out ( "Undeleted authors: ".$u_authors."\n" );
		//$this->print_out ( "Inserted authorships: ".$i_aShip."\n" );
		//$this->print_out ( "Saved authorships: ".$s_aShip."\n" );

	}



	function insert_sevenpack_pub ( $pub ) {

		$i_authors =& $this->stats['authors']['inserted'];
		$p_authors =& $this->stats['authors']['present'];
		$u_authors =& $this->stats['authors']['undelete'];

		$i_aShip =& $this->stats['authors']['inserted'];
		$s_aShip =& $this->stats['authors']['saved'];

		if ( $this->insert_references ( $pub ) )
			return TRUE;

		// Insert all authors as deleted
		//$this->print_out ( "Inserting all authors as deleted\n" );
		$authors =& $pub['authors'];
		foreach ( $authors as &$a ) 
		{
			$a['deleted'] = 1;
			$a['cruser_id'] = $this->cruser_id;
			$a['pid'] = $pub['pid'];
			$uid = $this->fetch_author_uid ( $a );
			if ( $uid < 0 )
			{
				$uid = $this->insert_author ( $a );
				if ( $uid >=0 )
				{
					$a['uid'] = $uid;
					$i_authors++;
				}
			}
			else
			{
				$a['uid'] = $uid;
				$p_authors++;
			}
		}

		$u_authors = 0;

		// Undelete some authors
		//$this->print_out ( "Undeleting some authors\n" );
		if ( !$pub['deleted'] )
		{
			foreach ( $authors as &$a ) 
			{
				$a['deleted'] = 0;
				if ( !$this->save_author ( $a ) )
				{
					$u_authors++;
				}
			}
		}


		$i_aShip = 0;
		$s_aShip = 0;

		// Insert or update authorships
		//$this->print_out ( "Inserting authorships\n" );
		if ( is_numeric ( $pub['uid'] ) && ( $pub['uid'] >= 0 ) )
		{
			$aSort = 0;
			foreach ( $authors as &$a ) 
			{
				if ( is_numeric ( $a['uid'] ) && ( $a['uid'] >= 0 ) )
				{
					$aSort += 16;
					$aShip = array();
					$aShip['pub_id'] = intval ( $pub['uid'] );
					$aShip['author_id'] = intval ( $a['uid'] );
					$aShip['sorting'] = $aSort;

					$aShip['pid'] = $pub['pid'];
					$aShip['cruser_id'] = $this->cruser_id;
					$aShip['deleted'] = 0;
					if ( $a['deleted'] )
						$aShip['deleted'] = 1;
					$uid = $this->fetch_authorship_uid ( $aShip['pub_id'], $aShip['author_id'] );
					if ( $uid < 0 )
					{
						$uid = $this->insert_authorship ( $aShip );
						if ( $uid >= 0 )
							$i_aShip++;
					}
					else
					{
						$aShip['uid'] = $uid;
						if ( !$this->save_authorship ( $aShip ) )
							$s_aShip++;
					}
				}
			}
		}

		return FALSE;
	}


	function insert_references ( $pub ) {
		$keys = array ( );
		foreach ( $this->six_to_seven as $key=>$val ) {
			$keys[] = $val;
		}

		$q  = 'INSERT INTO '.$this->refTable;
		$q .= ' ('.implode(',', $keys).')';
		$q .= ' VALUES (';
		foreach ( $keys as $k )
		{
			$q .= "'".$this->sql_escape_string($pub[$k])."'";
			if ( $k != end($keys) )
				$q .= ',';
		}
		$q .= ');';
		//$this->print_out ( $q."\n" );
		if ( $this->sql_query ( $q ) )
			return FALSE;
		else {
			$this->print_sql_error ( 'Save author' );
			$this->print_error ( $q."\n" );
		}
		return TRUE;
	}


	function fetch_author_uid ( $author ) {
		$uid = -1;
		if ( is_array ( $author ) ) 
		{
			$WC = '';
			if ( isset ( $author['sn'] ) )
				$WC .= ' surname='."'".$this->sql_escape_string($author['sn'])."'";
			if ( isset ( $author['fn'] ) )
				$WC .= ' AND forename='."'".$this->sql_escape_string($author['fn'])."'";
			if ( isset ( $author['pid'] ) )
				$WC .= ' AND pid='.intval ( $author['pid'] );

			if ( strlen ( $WC ) )
			{
				$WC = preg_replace ( '/^\s*AND\s*/', '', $WC );

				$query =  'SELECT uid FROM '.$this->authorTable.' WHERE '.$WC.';' ;
				if ( !$this->sql_query ( $query ) )
				{
					$this->print_sql_error($query);
				}
				else
				{
					$row = $this->sql_fetch_assoc ();
					if ( is_array ( $row ) )
						$uid = intval ( $row['uid'] );
				}
			}
		}
		return $uid;
	}


	function fetch_author ( $uid ) {
		$query =  'SELECT * FROM '.$this->authorTable.' WHERE uid='.intval ( $uid ).' AND deleted=0;' ;
		$this->sql_query ( $query );
		return $this->sql_fetch_assoc ( );
	}


	function delete_author ( $uid ) {
		$query =  'DELETE FROM '.$this->authorTable.' WHERE uid='.intval ( $uid );
		$this->sql_query ( $query );
		return $this->sql_fetch_assoc ( );
	}


	function save_author ( $author ) {
		if ( is_array ( $author ) ) 
		{
			if ( array_key_exists ( 'sn', $author ) 
			     && array_key_exists ( 'fn', $author ) 
			     && ( strlen ( $author['sn'] ) || strlen ( $author['fn'] ) ) )
			{
				if ( isset ( $author['uid'] ) )
				{
					$fields = array();
					if ( isset ( $author['fn'] ) )
						$fields['forename'] = $author['fn'];
					if ( isset ( $author['sn'] ) )
						$fields['surname'] = $author['sn'];
					if ( isset ( $author['deleted'] ) )

						$fields['deleted'] = $author['deleted'];
					if ( isset ( $author['pid'] ) )
						$fields['pid'] = $author['pid'];

					if ( sizeof ( $fields ) )
					{
						$fields['tstamp'] = time();
						$keys = array_keys($fields);

						$q  = 'UPDATE '.$this->authorTable;
						$q .= ' SET ';
						foreach ( $keys as $k )
						{
							$q .= $k."='".$this->sql_escape_string($fields[$k])."'";
							if ( $k != end($keys) )
								$q .= ', ';
						}
						$q .= ' WHERE uid='.intval($author['uid']).';';
						//$this->print_out ( $q."\n" );
						if ( $this->sql_query ( $q ) )
							return FALSE;
						else
							$this->print_sql_error ( 'Save author' );
					}
				}
				else
				{
					$this->print_out ( "Inserting instead of saving\n" );
					return $this->insert_author ( $author );
				}
			}
		}
		return TRUE;
	}


	function insert_author ( $author ) {
		$uid = -1;
		if ( is_array ( $author ) ) 
		{
			if ( array_key_exists ( 'sn', $author ) 
			     && array_key_exists ( 'fn', $author ) 
			     && ( strlen ( $author['sn'] ) || strlen ( $author['fn'] ) ) ) 
			{
				// Default values
				$fields = array();
				$fields['forename'] = $author['fn'];
				$fields['surname']  = $author['sn'];

				$fields['crdate']   = time();
				$fields['tstamp']   = time();
				$fields['cruser_id'] = intval ( $author['cruser_id'] );
				$fields['deleted']  = intval ( $author['deleted'] );
				$fields['pid']      = intval ( $author['pid'] );

				$keys = array_keys ( $fields );

				$q  = 'INSERT INTO '.$this->authorTable;
				$q .= ' ('.implode(',', $keys).')';
				$q .= ' VALUES (';
				foreach ( $keys as $k )
				{
					$q .= "'".$this->sql_escape_string($fields[$k])."'";
					if ( $k != end($keys) )
						$q .= ',';
				}
				$q .= ');';
				//$this->print_out ( $q."\n" );
				if ( $this->sql_query ( $q ) )
					$uid = $this->sql_insert_id ( );
				else
					$this->print_sql_error ( 'Insert author' );
			}
		}
		return $uid;
	}



	function fetch_authorship ( $uid ) {
		$query =  'SELECT * FROM '.$this->aShipTable.' WHERE uid='.intval ( $uid ).';' ;
		$this->sql_query ( $query );
		return $this->sql_fetch_assoc ( );
	}


	function fetch_authorship_match ( $pub_id, $author_id ) {
		$query  = 'SELECT * FROM '.$this->aShipTable;
		$query .= ' WHERE pub_id='.intval($pub_id).' AND author_id='.intval($author_id).';' ;
		$this->sql_query ( $query );
		return $this->sql_fetch_assoc ( );
	}


	function fetch_authorship_uid ( $pub_id, $author_id ) {
		$uid = -1;
		$query  = 'SELECT uid FROM '.$this->aShipTable;
		$query .= ' WHERE pub_id='.intval($pub_id).' AND author_id='.intval($author_id).';' ;
		$this->sql_query ( $query );
		$row = $this->sql_fetch_assoc ( );
		if ( is_array ( $row ) )
			$uid = intval ( $row['uid'] );
		return $uid;
	}


	function insert_authorship ( $aShip ) {
		$uid = -1;
		if ( is_array ( $aShip ) ) 
		{
			if ( is_numeric ( $aShip['pub_id'] ) && is_numeric ( $aShip['author_id'] )  )
			{
				// Default values
				$fields = array();
				$fields['pub_id']    = $aShip['pub_id'];
				$fields['author_id'] = $aShip['author_id'];
				$fields['sorting']   = intval ( $aShip['sorting'] );

				$fields['deleted']  = intval ( $aShip['deleted'] );

				$keys = array_keys ( $fields );

				$q  = 'INSERT INTO '.$this->aShipTable;
				$q .= ' ('.implode(',', $keys).')';
				$q .= ' VALUES (';
				foreach ( $keys as $k )
				{
					$q .= "'".$this->sql_escape_string($fields[$k])."'";
					if ( $k != end($keys) )
						$q .= ',';
				}
				$q .= ');';
				//$this->print_out ( $q."\n" );
				if ( $this->sql_query ( $q ) )
					$uid = $this->sql_insert_id ( );
				else
					$this->print_sql_error ( 'Insert authorship' );
			}
		}
		return $uid;
	}


	function save_authorship ( $aShip ) {
		if ( is_array ( $aShip ) ) 
		{
			if ( array_key_exists ( 'pub_id', $aShip ) 
			     && array_key_exists ( 'author_id', $aShip ) 
			     && is_numeric ( $aShip['pub_id'] ) && is_numeric ( $aShip['author_id'] )  )
			{
				if ( isset ( $aShip['uid'] ) )
				{
					$fields = array();
					if ( isset ( $aShip['pub_id'] ) )
						$fields['pub_id'] = intval ( $aShip['pub_id'] );
					if ( isset ( $aShip['author_id'] ) )
						$fields['author_id'] = intval ( $aShip['author_id'] );
					if ( array_key_exists ( 'sorting', $aShip ) )
						$fields['sorting'] = intval ( $aShip['sorting'] );

					if ( isset ( $aShip['deleted'] ) )
						$fields['deleted'] = intval ( $aShip['deleted'] );

					if ( sizeof ( $fields ) )
					{
						$keys = array_keys ( $fields );

						$q  = 'UPDATE '.$this->aShipTable;
						$q .= ' SET ';
						foreach ( $keys as $k )
						{
							$q .= $k."='".$this->sql_escape_string($fields[$k])."'";
							if ( $k != end($keys) )
								$q .= ', ';
						}
						$q .= ' WHERE uid='.intval($aShip['uid']).';';
						//$this->print_out ( $q."\n" );
						if ( $this->sql_query ( $q ) )
							return FALSE;
						else
							$this->print_sql_error ( 'Save author' );
					}
				}
				else
				{
					$this->print_out ( "Inserting instead of saving\n" );
					return $this->insert_authorship ( $aShip );
				}
			}
		}
		return TRUE;
	}

}

$n = new tx_sevenpack_normalization();
$n->normalize();

?>
