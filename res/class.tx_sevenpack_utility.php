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
	 * This does character conversions on database content
	 *
	 * @return The string filtered for html output
	 */
	function filter_pub_html ( $str, $hsc = FALSE ) {
		if ( $hsc ) 
			$str = htmlspecialchars ( $str, ENT_QUOTES, strtoupper ( $be_charset ) );

		// Character conversion
		//$be_charset = strtolower ( $this->extConf['be_charset'] );
		//$fe_charset = strtolower ( $this->extConf['page_charset'] );
		//if ( strcmp ( $be_charset, $fe_charset ) != 0 ) {
		//	$cs =& $GLOBALS['TSFE']->csConvObj;
		//	$str = $cs->conv ( $str, $be_charset, $fe_charset );
		//}
		return $str;
	}


	/**
	 * Fixes illegal occurences of ampersands (&) in html strings
	 *
	 * @return The string filtered for html output
	 */
	function fix_html_ampersand ( $str ) {
		//t3lib_div::debug ( $str );
		$str = str_replace( '& ', '&amp; ', $str );
		$pattern = '/&(([^;]|&|$){8})/';
		while ( preg_match ( $pattern, $str ) ) {
			$str = preg_replace ( $pattern, '&amp;\1', $str );
		};
		//t3lib_div::debug ( $str );
		return $str;
	}


	/**
	 * This replaces unneccessary tags and prepares the argument string
	 * for html output
	 *
	 * @return The string filtered for html output
	 */
	function filter_pub_html_display ( $str, $hsc = FALSE ) {
		$rand = rand();
		$str = str_replace( array ( '<prt>', '</prt>' ), '', $str );

		// Keep the following tags
		$tags =& $this->ra->allowed_tags;

		$LE = '#LE'.$rand.'LE#';
		$GE = '#GE'.$rand.'GE#';

		foreach ( $tags as $tag ) {
			$str = str_replace( '<'.$tag.'>',  $LE.    $tag.$GE, $str );
			$str = str_replace( '</'.$tag.'>', $LE.'/'.$tag.$GE, $str );
		}

		if ( !( strpos ( $str, '&' ) === FALSE ) ) {
			$str = tx_sevenpack_utility::fix_html_ampersand ( $str );
		}

		$str = str_replace( '<', '&lt;', $str );
		$str = str_replace( '>', '&gt;', $str );

		$str = str_replace( $LE, '<', $str );
		$str = str_replace( $GE, '>', $str );

		$str = str_replace( array ( '<prt>', '</prt>' ), '', $str );

		$str = tx_sevenpack_utility::filter_pub_html ( $str, $hsc );
		return $str;
	}


	/**
	 * Prepares the file_url from the database string
	 * and a configuration array
	 *
	 * @return The title string or FALSE
	 */
	function setup_file_url ( $url, $config = array() ) {

		//t3lib_div::debug ( $GLOBALS['TSFE']->fe_user );
		if ( ( strlen ( $url ) > 0 ) && ( strlen ( $config['hide_file_ext'] ) > 0) ) {

			$show = TRUE;

			// Disable url if file extensions matches
			if ( strlen ( $config['hide_file_ext'] ) > 0 ) {
				$check_ext = tx_sevenpack_utility::explode_trim_lower( ',', $config['hide_file_ext'] );
				foreach ( $check_ext as $ext ) {
					// Sanitize input
					$ext = strtolower ( trim ( $ext ) );
					$len = strlen ( $ext );
					if ( ( $len > 0 ) && ( strlen ( $url ) >= $len ) ) {
						$uext = strtolower ( substr ( $url, -$len ) );
						//t3lib_div::debug ( $ext );
						//t3lib_div::debug ( $uext );
						if ( $uext == $ext ) {
							$show = FALSE;
							break;
						}
					}
				}
			}

			// Enable url if usergroup matches
			if ( !$show && is_object ( $GLOBALS['TSFE']->fe_user ) ) {
				$check_grp = strtolower ( trim ( $config['fe_user_groups'] ) );
				if ( strpos ( $check_grp, 'all' ) === FALSE ) {

					// Iterate through usergroups
					$check_grp = tx_sevenpack_utility::explode_intval ( ',', $check_grp );
					if ( is_array ( $GLOBALS['TSFE']->fe_user->groupData )  ) {
						foreach ( $check_grp as $grp ) {
							//t3lib_div::debug ( $grp );
							//t3lib_div::debug ( $GLOBALS['TSFE']->fe_user->groupData['uid'] );
							if ( in_array ( $grp, $GLOBALS['TSFE']->fe_user->groupData['uid'] ) ) {
								$show = TRUE;
								break;
							}
						}
					}

				} else {

					// All logged in usergroups
					if ( is_array ( $GLOBALS['TSFE']->fe_user->user ) )
						$show = TRUE;
				}
			}

			if ( !$show )
				$url = '';
		}

		// Generate DOI url
		if ( strlen ( $url ) == 0 ) {
			if ( strlen ( $config['DOI'] ) > 0 ) {
				$url = 'http://dx.doi.org/' . 
					tx_sevenpack_utility::filter_pub_html_display ( $config['DOI'] );
			}
		}

		return $url;
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
	 * Crops the first argument to a given range
	 * 
	 * @return The value fitted into the given range
	 */
	function crop_to_range ( $value, $min, $max )
	{
		$value = min ( intval ( $value ), intval ( $max ) );
		$value = max ( $value, intval ( $min ) );
		return $value;
	}


	/** 
	 * Finds the nearest integer in a stack.
	 * The stack must be sorted
	 * 
	 * @return The value fitted into the given range
	 */
	function find_nearest_int ( $value, $stack )
	{
		$res = $value;
		if ( !in_array ( $value, $stack ) ) {
			if ( $value > end ( $stack ) ) {
				$res = end ( $stack );
			} else if ( $value < $stack[0] ) {
				$res = $stack[0];
			} else {
				// Find nearest
				for ( $i=1; $i < sizeof ( $stack ); $i++ ) {
					$d0 = abs ( $value - $stack[$i-1] );
					$d1 = abs ( $value - $stack[$i] );
					if ( $d0 <= $d1 ) {
						$res = $stack[$i-1];
						break;
					}
				}
			}
		}
		return $res;
	}


	/**
	 * Applies intval() to each element of an array
	 *
	 * @return The intvaled array
	 */
	function intval_array ( $arr ) {
		$res = array();
		foreach ( $arr as $val )
			if ( is_numeric ( $val ) )
				$res[] = intval ( $val );
		return $res;
	}


	/**
	 * Implodes an array and applies intval to each element
	 *
	 * @return The imploded array
	 */
	function implode_intval ( $sep, $list ) {
		$res = tx_sevenpack_utility::intval_array ( $list );
		return implode ( $sep, $res );
	}


	/**
	 * Explodes a string and applies intval to each element
	 *
	 * @return The imploded array
	 */
	function explode_intval ( $sep, $str ) {
		$res = explode ( $sep, $str );
		return tx_sevenpack_utility::intval_array ( $res );
	}


	/**
	 * Returns and array with the exploded string and 
	 * the values trimed
	 *
	 * @return The exploded string
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
