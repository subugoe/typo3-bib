<?php
namespace Ipf\Bib\Utility;


/**
 * This class provides some utility methods and acts mainly a a namespace
 *
 * @author Sebastian Holtermann
 */
class Utility {


	/**
	 * Returns the processed title of a page
	 *
	 * @return The title string or FALSE
	 */
	static public function get_page_title($uid) {
		$title = FALSE;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title', 'pages', 'uid=' . intval($uid));
		$p_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		if (is_array($p_row)) {
			$title = htmlspecialchars($p_row['title'], TRUE);
			$title .= ' (' . strval($uid) . ')';
		}
		return $title;
	}


	/**
	 * Returns the titles of multiple pages
	 *
	 * @return The title string or FALSE
	 */
	static public function get_page_titles($uids) {
		$titles = array();
		foreach ($uids as $uid) {
			$uid = intval($uid);
			$title = \Ipf\Bib\Utility\Utility::get_page_title($uid);
			if ($title) {
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
	static function crop_middle($str, $len, $charset = 'UTF-8') {
		$res = $str;
		if (strlen($str) > $len) {
			$le = ceil($len / 2.0);
			$ls = $len - $le;
			$res = mb_substr($str, 0, $ls, $charset) . '...';
			$res .= mb_substr($str, strlen($str) - $le, $le, $charset);
		}
		return $res;
	}


	/**
	 * Fixes illegal occurences of ampersands (&) in html strings
	 * Well TYPO3 seems to handle this as well
	 *
	 * @return The string filtered for html output
	 */
	static public function fix_html_ampersand($str) {
		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array( 'pre: ' => $str ) );

		$pattern = '/&(([^;]|$){8})/';
		while (preg_match($pattern, $str)) {
			$str = preg_replace($pattern, '&amp;\1', $str);
		};
		$pattern = '/&([^;]*?[^a-zA-z;][^;$]*(;|$))/';
		while (preg_match($pattern, $str)) {
			$str = preg_replace($pattern, '&amp;\1', $str);
		};
		$str = str_replace('&;', '&amp;;', $str);

		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array( 'post: ' => $str ) );
		return $str;
	}


	/**
	 * Check if the frontend user is in a usergroup
	 *
	 * @return TRUE if the user is in a given group FALSE otherwise
	 */
	static public function check_fe_user_groups($groups, $admin_ok = FALSE) {
		if ($admin_ok && is_object($GLOBALS['BE_USER'])
				&& $GLOBALS['BE_USER']->isAdmin()
		) return TRUE;
		if (is_object($GLOBALS['TSFE']->fe_user)
				&& is_array($GLOBALS['TSFE']->fe_user->user)
				&& is_array($GLOBALS['TSFE']->fe_user->groupData)
		) {
			if (is_string($groups)) {
				$groups = strtolower($groups);
				if (!(strpos($groups, 'all') === FALSE))
					return TRUE;
				$groups = \Ipf\Bib\Utility\Utility::explode_intval(',', $groups);
			}
			$cur =& $GLOBALS['TSFE']->fe_user->groupData['uid'];
			if (\Ipf\Bib\Utility\Utility::intval_list_check($groups, $cur)) {
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
	static public function html_input($type, $name, $value, $attribs = array()) {
		$con = '<input type="' . strval($type) . '"';
		if (strlen($name) > 0) {
			$con .= ' name="' . strval($name) . '"';
		}
		if (strlen($value) > 0) {
			$con .= ' value="' . strval($value) . '"';
		}
		foreach ($attribs as $a_key => $a_value) {
			if (!($a_value === FALSE))
				$con .= ' ' . strval($a_key) . '="' . strval($a_value) . '"';
		}
		$con .= '>';
		return $con;
	}


	/**
	 * Returns a checkbox input
	 *
	 * @return The checkbox input element
	 */
	function html_check_input($name, $value, $checked, $attribs = array()) {
		if ($checked)
			$attribs['checked'] = 'checked';
		return \Ipf\Bib\Utility\Utility::html_input(
			'checkbox', $name, $value, $attribs);
	}


	/**
	 * Returns a checkbox input
	 *
	 * @return The checkbox input element
	 */
	function html_radio_input($name, $value, $checked, $attribs = array()) {
		if ($checked)
			$attribs['checked'] = 'checked';
		return \Ipf\Bib\Utility\Utility::html_input(
			'radio', $name, $value, $attribs);
	}

	/**
	 * Returns a sumit input
	 *
	 * @return The submit input element
	 */
	static public function html_submit_input($name, $value, $attribs = array()) {
		return \Ipf\Bib\Utility\Utility::html_input(
			'submit', $name, $value, $attribs);
	}


	/**
	 * Returns a image input
	 *
	 * @return The image input element
	 */
	static public function html_image_input($name, $value, $src, $attribs = array()) {
		$attribs = array_merge($attribs, array('src' => $src));
		return \Ipf\Bib\Utility\Utility::html_input(
			'image', $name, $value, $attribs);
	}


	/**
	 * Returns a hidden input
	 *
	 * @return The hidden input element
	 */
	static public function html_hidden_input($name, $value, $attribs = array()) {
		return \Ipf\Bib\Utility\Utility::html_input(
			'hidden', $name, $value, $attribs);
	}


	/**
	 * Returns a text input
	 *
	 * @return The text input element
	 */
	static public function html_text_input($name, $value, $attribs = array()) {
		return \Ipf\Bib\Utility\Utility::html_input(
			'text', $name, $value, $attribs);
	}


	/**
	 * Returns a select input
	 *
	 * @return String The select element
	 */
	static public function html_select_input($pairs, $value, $attribs = array()) {
		$value = strval($value);
		$con .= '<select';
		foreach ($attribs as $a_key => $a_value) {
			if (!($a_value === FALSE))
				$con .= ' ' . strval($a_key) . '="' . strval($a_value) . '"';
		}
		$con .= '>' . "\n";
		foreach ($pairs as $p_value => $p_name) {
			$p_value = strval($p_value);
			$con .= '<option value="' . $p_value . '"';
			if ($p_value == strval($value)) {
				$con .= ' selected="selected"';
			}
			$con .= '>';
			$con .= strval($p_name);
			$con .= '</option>' . "\n";
		}
		$con .= '</select>' . "\n";
		return $con;
	}


	/**
	 * A layout table the contains all the strings in $rows
	 *
	 * @return The html table code
	 */
	static public function html_layout_table($rows) {
		$res = '<table class="tx_bib-layout"><tbody>';
		foreach ($rows as $row) {
			$res .= '<tr>';
			if (is_array($row)) {
				foreach ($row as $cell) {
					$res .= '<td>' . strval($cell) . '</td>';
				}
			} else {
				$res .= '<td>' . strval($row) . '</td>';
			}
			$res .= '</tr>';
		}
		$res .= '</tbody></table>';
		return $res;
	}


	/**
	 * Counts strings in an array of strings
	 *
	 * @return An associative array contatining the input strings and their counts
	 */
	static public function string_counter($messages) {
		$res = array();
		foreach ($messages as $msg) {
			$msg = strval($msg);
			if (array_key_exists($msg, $res)) {
				$res[$msg] += 1;
			} else {
				$res[$msg] = 1;
			}
		}
		return $res;
	}


	/**
	 * Crops the first argument to a given range
	 *
	 * @return The value fitted into the given range
	 */
	static public function crop_to_range($value, $min, $max) {
		$value = min(intval($value), intval($max));
		$value = max($value, intval($min));
		return $value;
	}


	/**
	 * Finds the nearest integer in a stack.
	 * The stack must be sorted
	 *
	 * @return The value fitted into the given range
	 */
	static public function find_nearest_int($value, $stack) {
		$res = $value;
		if (!in_array($value, $stack)) {
			if ($value > end($stack)) {
				$res = end($stack);
			} else if ($value < $stack[0]) {
				$res = $stack[0];
			} else {
				// Find nearest
				$res = end($stack);
				for ($ii = 1; $ii < sizeof($stack); $ii++) {
					$d0 = abs($value - $stack[$ii - 1]);
					$d1 = abs($value - $stack[$ii]);
					if ($d0 <= $d1) {
						$res = $stack[$ii - 1];
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
	static public function intval_list_check($allowed, $current) {
		if (!is_array($allowed))
			$allowed = \Ipf\Bib\Utility\Utility::explode_intval(',', strval($allowed));
		if (!is_array($current))
			$current = \Ipf\Bib\Utility\Utility::explode_intval(',', strval($current));

		foreach ($current as $cur) {
			if (in_array($cur, $allowed))
				return TRUE;
		}
		return FALSE;
	}


	/**
	 * Applies intval() to each element of an array
	 *
	 * @return The intvaled array
	 */
	static public function intval_array($arr) {
		$res = array();
		foreach ($arr as $val)
			$res[] = intval($val);
		return $res;
	}


	/**
	 * Implodes an array and applies intval to each element
	 *
	 * @return array The imploded array
	 */
	static public function implode_intval($sep, $list, $noEmpty = TRUE) {
		$res = array();
		if ($noEmpty) {
			foreach ($list as $val) {
				$val = trim($val);
				if (strlen($val) > 0)
					$res[] = strval(intval($val));
			}
		} else {
			$res = \Ipf\Bib\Utility\Utility::intval_array($list);
		}

		return implode($sep, $res);
	}


	/**
	 * Explodes a string and applies intval to each element
	 *
	 * @return array The exploded string
	 */
	static public function explode_intval($sep, $str, $noEmpty = TRUE) {
		$res = array();
		$list = explode($sep, $str);
		if ($noEmpty) {
			foreach ($list as $val) {
				$val = trim($val);
				if (strlen($val) > 0)
					$res[] = intval($val);
			}
		} else {
			$res = \Ipf\Bib\Utility\Utility::intval_array($list);
		}
		return $res;
	}


	/**
	 * Returns and array with the exploded string and
	 * the values trimmed
	 *
	 * @return array The exploded string
	 */
	static public function explode_trim($sep, $str, $noEmpty = FALSE) {
		$res = array();
		$tmp = explode($sep, $str);
		foreach ($tmp as $val) {
			$val = trim($val);
			if ((strlen($val) > 0) || !$noEmpty)
				$res[] = $val;
		}
		return $res;
	}


	/**
	 * Returns and array with the exploded string and
	 * the values trimmed and converted to lowercase
	 *
	 * @return array The exploded string
	 */
	static public function explode_trim_lower($sep, $str, $noEmpty = FALSE) {
		$res = array();
		$tmp = explode($sep, $str);
		foreach ($tmp as $val) {
			$val = trim($val);
			if ((strlen($val) > 0) || !$noEmpty)
				$res[] = strtolower($val);
		}
		return $res;
	}


	/**
	 * Explodes a string by multiple separators
	 *
	 * @return array The exploded string
	 */
	static public function multi_explode($seps, $str) {
		if (is_array($seps)) {
			$sep = strval($seps[0]);
			for ($ii = 1; $ii < sizeof($seps); $ii++) {
				$nsep = strval($sep[$ii]);
				$str = str_replace($nsep, $sep, $str);
			}
		} else {
			$sep = strval($seps);
		}
		return explode($sep, $str);
	}


	/**
	 * Explodes a string by multiple separators and trims the results
	 *
	 * @return The exploded string
	 */
	static public function multi_explode_trim($seps, $str, $noEmpty = FALSE) {
		if (is_array($seps)) {
			$sep = strval($seps[0]);
			for ($ii = 1; $ii < sizeof($seps); $ii++) {
				$nsep = strval($seps[$ii]);
				$str = str_replace($nsep, $sep, $str);
			}
		} else {
			$sep = strval($seps);
		}
		return \Ipf\Bib\Utility\Utility::explode_trim($sep, $str, $noEmpty);
	}


	/**
	 * Explodes an ' and ' separated author string
	 *
	 * @return bool|String FALSE or the error message array
	 */
	static public function explode_author_str($str) {
		$res = array();
		$lst = explode(' and ', $str);
		foreach ($lst as $a_str) {
			$name = array();
			$parts = \Ipf\Bib\Utility\Utility::explode_trim(',', $a_str, TRUE);
			if (sizeof($parts) > 1) {
				$name['forename'] = $parts[1];
			}
			if (sizeof($parts) > 0) {
				$name['surname'] = $parts[0];
				$res[] = $name;
			}
		}
		return $res;
	}


	/**
	 * Implodes an array with $sep as separator
	 * and $and as the last separator element
	 *
	 * @return String The imploded array as a string
	 */
	static public function implode_and_last($arr, $sep, $and) {
		$res = array();
		$size = sizeof($arr);
		$c_idx = $size - 2;
		$a_idx = $size - 1;
		for ($ii = 0; $ii < $size; $ii++) {
			$res[] = strval($arr[$ii]);
			if ($ii < $c_idx) $res[] = strval($sep);
			else if ($ii < $a_idx) $res[] = strval($and);
		}
		return implode('', $res);
	}


	/**
	 * Checks if a local file exists
	 *
	 * @return FALSE if the file exists TRUE if it does not exist
	 */
	static public function check_file_nexist($file) {
		//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( 'check_file_nexist ' => $file ) );
		if ((strlen($file) > 0) &&
				(substr($file, 0, 10) == 'fileadmin/')
		) {
			$root = PATH_site;
			if (substr($root, -1, 1) != '/') {
				$root .= '/';
			}
			$file = $root . $file;
			return !file_exists($file);
		}
		return FALSE;
	}

}

?>