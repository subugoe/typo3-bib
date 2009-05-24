<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');


class tx_sevenpack_citeid_generator {

	public $pi1;
	public $ra;
	public $charset;


	function initialize ( $pi1 ) {
		$this->ra =& $pi1->ra;
		$this->charset = $pi1->extConf['charset']['upper'];
	}


	/** 
	 * Generates a cite id for the publication in piVars['DATA']
	 *
	 * @return The generated id
	 */
	function generateId ( $row ) {
		$id = $this->generateBasicId ( $row );
		$tmpId = $id;

		$uid = -1;
		if ( array_key_exists ( 'uid', $row ) && ($row['uid'] >= 0) )
			$uid = intval ( $row['uid'] );

		$num = 1;
		while ( $this->ra->citeid_exists ( $tmpId, $uid ) ) {
			$num++;
			$tmpId = $id.'_'.$num;
		}

		return $tmpId;
	}


	function generateBasicId ( $row ) {
		$authors = $row['authors'];
		$editors = tx_sevenpack_utility::explode_author_str ( $row['editor'] );

		$persons = array ( $authors, $editors );

		//t3lib_div::debug ( $persons );

		$id = '';
		foreach ( $persons as $list ) {
			if ( strlen ( $id ) == 0 ) {
				if ( sizeof ( $list ) > 0 ) {
					$pp =& $list[0];
					$a_str = '';
					if ( strlen ( $pp['surname'] ) > 0 )
						$a_str = $pp['surname'];
					else if ( strlen ( $pp['forename'] ) )
						$a_str = $pp['forename'];
					if ( strlen ( $a_str ) > 0 )
						$id = $this->simplified_string ( $a_str );
				}
			}
			for ( $i=1; $i < sizeof ( $list ); $i++ ) {
				$pp =& $list[$i];
				$a_str = '';
				if ( strlen ( $pp['surname'] ) > 0 )
					$a_str = $pp['surname'];
				else if ( strlen ( $pp['forename'] ) )
					$a_str = $pp['forename'];
				if ( strlen ( $a_str ) > 0 ) {
					$id .= mb_substr ( 
						$this->simplified_string ( $a_str ), 0, 1, $this->charset );
				}
			}
		}

		if ( strlen ( $id ) == 0 ) {
			$id = t3lib_div::shortMD5 ( serialize ( $row ) );
		}
		if ( $row['year'] > 0 )
			$id .= $row['year'];

		return $this->simplified_string ( $id );
	}


	/** 
	 * Replaces all special characters and HTML sequences in a string to
	 * characters that are allowed in a citation id
	 *
	 * @return The simplified string
	 */
	function simplified_string ( $id ) {
		// Replace some special characters with ASCII characters
		$id = htmlentities ( $id, ENT_QUOTES, $this->charset );
		$id = str_replace ( '&amp;', '&', $id );
		$id = preg_replace ( '/&(\w)\w{1,7};/', '$1', $id );
		//t3lib_div::debug ( $id );

		// Replace remaining special characters with ASCII characters
		$tmpId = '';
		for ( $i=0; $i < mb_strlen ( $id, $this->charset ); $i++ ) {
			$c = mb_substr ( $id, $i, 1, $this->charset );
			if ( ctype_alnum($c) || ($c == '_') ) {
				$tmpId .= $c;
			}
		}
		return $tmpId;
	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_citeid_generator.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_citeid_generator.php"]);
}

?>
