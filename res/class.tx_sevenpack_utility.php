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
	 * Crops a string to a maximal length by cutting in the middle
	 *
	 * @return The string filtered for html output
	 */
	function crop_middle ( $str, $len, $charset = 'UTF-8' ) {
		$res = $str;
		if ( strlen ( $str ) > $len ) {
			$le = ceil ( $len/2.0 );
			$ls = $len - $le;
			$res  = mb_substr  ( $str, 0, $ls, $charset ) . '...';
			$res .= mb_substr  ( $str, strlen ( $str ) - $le, $le, $charset );
		}
		return $res;
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
	 * This function prepares database content fot HTML output
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
	 * This replaces unneccessary tags and prepares the argument string
	 * for html output
	 *
	 * @return The string filtered for html output
	 */
	function filter_pub_html_display ( $str, $hsc = FALSE ) {
		$rand .= strval ( rand() ) . strval ( rand() );
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
	 * Check if the frontend user is in a usergroup
	 *
	 * @return TRUE if the user is in a given group FALSE otherwise
	 */
	function check_fe_user_groups ( $groups, $admin_ok = FALSE ) {
		if ( $admin_ok && is_object( $GLOBALS['BE_USER'] )
		    && $GLOBALS['BE_USER']->isAdmin()
		) return TRUE;
		if ( is_object ( $GLOBALS['TSFE']->fe_user )
		  && is_array ( $GLOBALS['TSFE']->fe_user->user )
		  && is_array ( $GLOBALS['TSFE']->fe_user->groupData ) )
		{
			if ( is_string ( $groups ) ) {
				$groups = strtolower ( $groups );
				if ( !( strpos ( $groups, 'all' ) === FALSE ) )
					return TRUE;
				$groups = tx_sevenpack_utility::explode_intval ( ',', $groups );
			}
			$cur =& $GLOBALS['TSFE']->fe_user->groupData['uid'];
			if ( tx_sevenpack_utility::intval_list_check ( $groups, $cur ) ) {
				return TRUE;
			}
		}
		return FALSE;
	}


	/**
	 * Returns a html input element
	 *
	 * @return The hidden input element
	 */
	function html_input ( $type, $name, $value, $attribs = array() ) {
		$con = '<input type="' . strval ( $type ) . '"' .
			' name="' . strval ( $name ) . '"' .
			' value="' . strval ( $value ) . '"';
		foreach ( $attribs as $a_key => $a_value ) {
			if ( !( $a_value === FALSE ) )
				$con .= ' '.strval($a_key).'="'.strval($a_value).'"';
		}
		$con .= '>';
		return $con;
	}


	/**
	 * Returns a checkbox input
	 *
	 * @return The checkbox input element
	 */
	function html_check_input ( $name, $value, $checked, $attribs = array() ) {
		if ( $checked )
			$attribs['checked'] = 'checked';
		return tx_sevenpack_utility::html_input (
			'checkbox', $name, $value, $attribs );
	}


	/**
	 * Returns a sumit input
	 *
	 * @return The submit input element
	 */
	function html_submit_input ( $name, $value, $attribs = array() ) {
		return tx_sevenpack_utility::html_input (
			'submit', $name, $value, $attribs );
	}


	/**
	 * Returns a image input
	 *
	 * @return The image input element
	 */
	function html_image_input ( $name, $value, $src, $attribs = array() ) {
		$attribs = array_merge ( $attribs, array ( 'src'=>$src ) );
		return tx_sevenpack_utility::html_input (
			'image', $name, $value, $attribs );
	}


	/**
	 * Returns a hidden input
	 *
	 * @return The hidden input element
	 */
	function html_hidden_input ( $name, $value, $attribs = array() ) {
		return tx_sevenpack_utility::html_input ( 
			'hidden', $name, $value, $attribs );
	}


	/**
	 * Returns a text input
	 *
	 * @return The text input element
	 */
	function html_text_input ( $name, $value, $attribs = array() ) {
		return tx_sevenpack_utility::html_input ( 
			'text', $name, $value, $attribs );
	}


	/**
	 * Returns a select input
	 *
	 * @return The select element
	 */
	function html_select_input ( $pairs, $value, $attribs = array() ) {
		$con .= '<select';
		foreach ( $attribs as $a_key => $a_value ) {
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
	 * A layout table the contains all the strings in $rows
	 *
	 * @return The html table code
	 */
	function html_layout_table ( $rows ) {
		$res = '<table class="tx_sevenpack-layout"><tbody>';
		foreach ( $rows as $row ) {
			$res .= '<tr>';
			if ( is_array ( $row ) ) {
				foreach ( $row as $cell ) {
					$res .= '<td>' . strval ( $cell ) . '</td>';
				}
			} else {
				$res .= '<td>' . strval ( $row ) . '</td>';
			}
			$res .= '</tr>';
		}
		$res .= '</tbody></table>';
		return $res;
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
				$res = end ( $stack );
				for ( $ii=1; $ii < sizeof ( $stack ); $ii++ ) {
					$d0 = abs ( $value - $stack[$ii-1] );
					$d1 = abs ( $value - $stack[$ii] );
					if ( $d0 <= $d1 ) {
						$res = $stack[$ii-1];
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

		foreach ( $current as $cur ) {
			if ( in_array ( $cur, $allowed ) )
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
	function explode_intval ( $sep, $str, $noEmpty = TRUE ) {
		$res = array();
		$list = explode ( $sep, $str );
		if ( $noEmpty ) {
			foreach ( $list as $val ) {
				$val = trim ( $val );
				if ( strlen ( $val ) > 0 )
					$res[] = intval ( $val );
			}
		} else {
			$res = tx_sevenpack_utility::intval_array ( $list );
		}
		return $res;
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
