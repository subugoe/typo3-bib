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
	 * Well Typo3 seems to handle this as well
	 *
	 * @return The string filtered for html output
	 */
	function fix_html_ampersand ( $str ) {
		//t3lib_div::debug ( array( 'pre: ' => $str ) );
		
		$pattern = '/&(([^;]|$){8})/';
		while ( preg_match ( $pattern, $str ) ) {
			$str = preg_replace ( $pattern, '&amp;\1', $str );
		};
		$pattern = '/&([^;]*?[^a-zA-z;][^;$]*(;|$))/';
		while ( preg_match ( $pattern, $str ) ) {
			$str = preg_replace ( $pattern, '&amp;\1', $str );
		};
		$str = str_replace( '&;', '&amp;;', $str );
		
		//t3lib_div::debug ( array( 'post: ' => $str ) );
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

		// Remove not allowed tags
		// Keep the following tags
		$tags =& $this->ra->allowed_tags;

		$LE = '#LE'.$rand.'LE#';
		$GE = '#GE'.$rand.'GE#';

		foreach ( $tags as $tag ) {
			$str = str_replace( '<'.$tag.'>',  $LE.    $tag.$GE, $str );
			$str = str_replace( '</'.$tag.'>', $LE.'/'.$tag.$GE, $str );
		}

		$str = str_replace( '<', '&lt;', $str );
		$str = str_replace( '>', '&gt;', $str );

		$str = str_replace( $LE, '<', $str );
		$str = str_replace( $GE, '>', $str );

		$str = str_replace( array ( '<prt>', '</prt>' ), '', $str );

		// End of remove not allowed tags

		// Typo3 seems to handle illegal ampersands
		//if ( !( strpos ( $str, '&' ) === FALSE ) ) {
		//	$str = tx_sevenpack_utility::fix_html_ampersand ( $str );
		//}

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
				$check_ext = tx_sevenpack_utility::explode_trim_lower ( ',', $config['hide_file_ext'] );
				foreach ( $check_ext as $ext ) {
					// Sanitize input
					$ext = strtolower ( trim ( $ext ) );
					$len = strlen ( $ext );
					if ( ( $len > 0 ) && ( strlen ( $url ) >= $len ) ) {
						$uext = strtolower ( substr ( $url, -$len ) );
						//t3lib_div::debug ( 'ext' => $ext, 'uext' => $uext );
						if ( $uext == $ext ) {
							$show = FALSE;
							break;
						}
					}
				}
			}

			// Enable url if usergroup matches
			if ( !$show && is_object ( $GLOBALS['TSFE']->fe_user ) 
			     && is_array ( $GLOBALS['TSFE']->fe_user->user ) 
			) {
				$allowed = strtolower ( trim ( $config['FE_user_groups'] ) );
				if ( strpos ( $allowed, 'all' ) === FALSE ) {
					if ( is_array ( $GLOBALS['TSFE']->fe_user->groupData )  ) {
						// Check group membership
						$show = tx_sevenpack_utility::intval_list_check (
							$allowed, $GLOBALS['TSFE']->fe_user->groupData['uid'] );
					}
				} else {
					// All logged in usergroups
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
			if ( !( $a_value === FALSE ) )
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
	 * Returns true if an integer in $allowed is in $current
	 *
	 * @return TRUE if there is an overlap FALSE otherwise
	 */
	function intval_list_check ( $allowed, $current ) {
		if ( !is_array ( $allowed ) )
			$allowed = tx_sevenpack_utility::explode_intval ( ',', strval ( $allowed ) );
		if ( !is_array ( $current ) )
			$current = tx_sevenpack_utility::explode_intval ( ',', strval ( $current ) );

		$inter = array_intersect ( $allowed, $current );
		//t3lib_div::debug( array ( 'alw' => $allowed, 'cur' => $current, 'match' => $inter ) );
		if ( sizeof ( $inter ) > 0 ) {
			return TRUE;
		}
		return FALSE;
	}


	/**
	 * Applies intval() to each element of an array
	 *
	 * @return The intvaled array
	 */
	function intval_array ( $arr ) {
		$res = array();
		foreach ( $arr as $val )
			$res[] = intval ( $val );
		return $res;
	}


	/**
	 * Implodes an array and applies intval to each element
	 *
	 * @return The imploded array
	 */
	function implode_intval ( $sep, $list, $noEmpty = TRUE ) {
		$res = array();
		if ( $noEmpty ) {
			foreach ( $list as $val ) {
				$val = trim ( $val );
				if ( strlen ( $val ) > 0 )
					$res[] = strval ( intval ( $val ) );
			}
		} else {
			$res = tx_sevenpack_utility::intval_array ( $list );
		}

		return implode ( $sep, $res );
	}


	/**
	 * Explodes a string and applies intval to each element
	 *
	 * @return The exploded string
	 */
	function explode_intval ( $sep, $str ) {
		$res = explode ( $sep, $str );
		return tx_sevenpack_utility::intval_array ( $res );
	}


	/**
	 * Returns and array with the exploded string and 
	 * the values trimmed
	 *
	 * @return The exploded string
	 */
	function explode_trim ( $sep, $str, $noEmpty = FALSE ) {
		$res = array();
		$tmp = explode ( $sep, $str );
		foreach ( $tmp as $val ) {
			$val = trim ( $val );
			if ( ( strlen ( $val ) > 0 ) || !$noEmpty )
				$res[] = $val;
		}
		return $res;
	}


	/**
	 * Returns and array with the exploded string and 
	 * the values trimmed and converted to lowercase
	 *
	 * @return The exploded string
	 */
	function explode_trim_lower ( $sep, $str, $noEmpty = FALSE ) {
		$res = array();
		$tmp = explode ( $sep, $str );
		foreach ( $tmp as $val ) {
			$val = trim ( $val );
			if ( ( strlen ( $val ) > 0 ) || !$noEmpty )
				$res[] = strtolower ( $val );
		}
		return $res;
	}


	/**
	 * Explodes a string by multiple separators
	 *
	 * @return The exploded string
	 */
	function multi_explode ( $seps, $str ) {
		if ( is_array ( $seps ) ) {
			$sep = strval ( $seps[0] );
			for ( $ii = 1; $ii < sizeof ( $seps ); $ii++ ) {
				$nsep = strval ( $sep[$ii] );
				$str = str_replace ( $nsep, $sep, $str );
			}
		} else {
			$sep = strval ( $seps );
		}
		return explode ( $sep, $str );
	}


	/**
	 * Explodes a string by multiple separators and trims the results
	 *
	 * @return The exploded string
	 */
	function multi_explode_trim ( $seps, $str, $noEmpty = FALSE ) {
		if ( is_array ( $seps ) ) {
			$sep = strval ( $seps[0] );
			for ( $ii = 1; $ii < sizeof ( $seps ); $ii++ ) {
				$nsep = strval ( $seps[$ii] );
				$str = str_replace ( $nsep, $sep, $str );
			}
		} else {
			$sep = strval ( $seps );
		}
		return tx_sevenpack_utility::explode_trim ( $sep, $str, $noEmpty );
	}

}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/res/class.tx_sevenpack_utility.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/res/class.tx_sevenpack_utility.php"]);
}

?>
