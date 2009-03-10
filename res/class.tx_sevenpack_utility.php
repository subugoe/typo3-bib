<?php
if ( !isset($GLOBALS['TSFE']) )
	die ('This file is not meant to be executed');

/**
 * This class provides some utility methods and acts mainly a a namespace
 *
 * @author Sebastian Holtermann
 */
class tx_sevenpack_utility {


	/**
	 * Returns the page character set
	 *
	 * @return The lowercase character set string
	 */
	function accquire_page_charset () {
		// Determine page charset
		$charset = 'iso-8859-1';
		if ( isset($GLOBALS['TSFE']->config['config']['renderCharset']) )
			$charset = strtolower ( $GLOBALS['TSFE']->config['config']['renderCharset'] );
		if ( isset($GLOBALS['TSFE']->config['config']['metaCharset']) )
			$charset = strtolower ( $GLOBALS['TSFE']->config['config']['metaCharset'] );

		return $charset;
	}


	/**
	 * Returns the backend character set
	 *
	 * @return The lowercase character set string
	 */
	function accquire_be_charset () {
		// Determine backend charset
		$charset = 'iso-8859-1';
		if ( strlen ( $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ) )
			$charset = strtolower ( $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] );

		return $charset;
	}


	/**
	 * Returns the processed title of a page
	 *
	 * @return The title string or FALSE
	 */
	function get_page_title ( $uid ) {
		$title = FALSE;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery ( 'title', 'pages', 'uid='.intval( $uid ) );
		$p_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc ( $res );
		if ( is_array ( $p_row ) ) {
			$title = htmlspecialchars ( $p_row['title'], TRUE );
			$title .= ' (' . strval ( $uid ) . ')';
		}
		return $title;
	}


	/**
	 * Returns the titles of multiple pages
	 *
	 * @return The title string or FALSE
	 */
	function get_page_titles ( $uids ) {
		$titles = array();
		foreach ( $uids as $uid ) {
			$uid = intval ( $uid );
			$title = tx_sevenpack_utility::get_page_title ( $uid );
			if ( $title ) {
				$titles[$uid] = $title;
			}
		}
		return $titles;
	}


	/**
	 * Returns a select input
	 *
	 * @return The title string or FALSE
	 */
	function html_select_input ( $pairs, $value, $select_attribs ) {
		$con .= '<select';
		foreach ( $select_attribs as $a_key => $a_value ) {
			$con .= ' ' . strval ( $a_key ) . '="' . strval ( $a_value ) . '"';
		}
		$con .= '>' . "\n";
		foreach ( $pairs as $p_value => $p_name ) {
			$con .= '<option value="' . $p_value . '"';
			if ( strval ( $p_value ) == strval ( $value ) ) {
				$con .= ' selected="selected"';
			}
			$con .= '>';
			$con .= $p_name;
			$con .= '</option>'."\n"; 
		}
		$con .= '</select>'."\n";
		return $con;
	}


	/**
	 * Returns and array with the exploded string and 
	 * the values trimed
	 *
	 * @return The title string or FALSE
	 */
	function explode_trim_lower ( $sep, $str ) {
		$res = explode ( $sep, $str );
		foreach ( $res as $key => &$val ) {
			$val = trim ( $val );
			if ( strlen ( $val ) == 0 )
				unset ( $res[$key] );
			else
				$val = strtolower ( $val );
		}
		return $res;
	}

}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/res/class.tx_sevenpack_utility.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/res/class.tx_sevenpack_utility.php"]);
}

?>
