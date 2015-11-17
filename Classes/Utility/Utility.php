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
 * This class provides some utility methods and acts mainly a a namespace.
 */
class Utility
{
    /**
     * Returns the titles of multiple pages.
     *
     * @param array $uids
     *
     * @return string|bool The title string or FALSE
     */
    public static function get_page_titles($uids)
    {
        $titles = [];
        foreach ($uids as $uid) {
            $uid = intval($uid);
            $title = self::get_page_title($uid);
            if ($title) {
                $titles[$uid] = $title;
            }
        }

        return $titles;
    }

    /**
     * Returns the processed title of a page.
     *
     * @param int $uid
     *
     * @return string|bool The title string or FALSE
     */
    public static function get_page_title($uid)
    {
        $title = false;

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'title',
            'pages',
            'uid=' . intval($uid)
        );

        $p_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        if (is_array($p_row)) {
            $title = htmlspecialchars($p_row['title'], true);
            $title .= ' (' . strval($uid) . ')';
        }

        return $title;
    }

    /**
     * Crops a string to a maximal length by cutting in the middle.
     *
     * @param string $str
     * @param int    $len
     * @param string $charset
     *
     * @return string The string filtered for html output
     */
    public static function crop_middle($str, $len, $charset = 'UTF-8')
    {
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
     * This replaces unnecessary tags and prepares the argument string
     * for html output.
     *
     * @param string $content
     * @param bool   $htmlSpecialChars
     * @param string $charset
     *
     * @return string The string filtered for html output
     */
    public static function filter_pub_html_display($content, $htmlSpecialChars = false, $charset = 'UTF-8')
    {
        $rand = strval(rand()) . strval(rand());
        $content = str_replace(['<prt>', '</prt>'], '', $content);

        $LE = '#LE' . $rand . 'LE#';
        $GE = '#GE' . $rand . 'GE#';

        /** @var \Ipf\Bib\Utility\ReferenceReader $referenceReader */
        $referenceReader = GeneralUtility::makeInstance(ReferenceReader::class);

        foreach ($referenceReader->getAllowedTags() as $tag) {
            $content = str_replace('<' . $tag . '>', $LE . $tag . $GE, $content);
            $content = str_replace('</' . $tag . '>', $LE . '/' . $tag . $GE, $content);
        }

        $content = str_replace('<', '&lt;', $content);
        $content = str_replace('>', '&gt;', $content);

        $content = str_replace($LE, '<', $content);
        $content = str_replace($GE, '>', $content);

        $content = str_replace(['<prt>', '</prt>'], '', $content);

        // End of remove not allowed tags

        // Handle illegal ampersands
        if (!(strpos($content, '&') === false)) {
            $content = self::fix_html_ampersand($content);
        }

        $content = self::filter_pub_html($content, $htmlSpecialChars, $charset);

        return $content;
    }

    /**
     * Fixes illegal occurrences of ampersands (&) in html strings
     * Well TYPO3 seems to handle this as well.
     *
     * @param string $str
     *
     * @return string The string filtered for html output
     */
    public static function fix_html_ampersand($str)
    {
        $pattern = '/&(([^;]|$){8})/';
        while (preg_match($pattern, $str)) {
            $str = preg_replace($pattern, '&amp;\1', $str);
        };
        $pattern = '/&([^;]*?[^a-zA-z;][^;$]*(;|$))/';
        while (preg_match($pattern, $str)) {
            $str = preg_replace($pattern, '&amp;\1', $str);
        };
        $str = str_replace('&;', '&amp;;', $str);

        return $str;
    }

    /**
     * This function prepares database content fot HTML output.
     *
     * @param string $content
     * @param bool   $htmlSpecialChars
     * @param string $charset
     *
     * @return string The string filtered for html output
     */
    public static function filter_pub_html($content, $htmlSpecialChars = false, $charset)
    {
        if ($htmlSpecialChars) {
            $content = htmlspecialchars($content, ENT_QUOTES, $charset);
        }

        return $content;
    }

    /**
     * Check if the frontend user is in a usergroup.
     *
     * @param string $groups
     * @param bool   $admin_ok
     *
     * @return bool TRUE if the user is in a given group FALSE otherwise
     */
    public static function check_fe_user_groups($groups, $admin_ok = false)
    {
        if ($admin_ok && is_object($GLOBALS['BE_USER']) && $GLOBALS['BE_USER']->isAdmin()) {
            return true;
        }
        if (is_object($GLOBALS['TSFE']->fe_user)
            && is_array($GLOBALS['TSFE']->fe_user->user)
            && is_array($GLOBALS['TSFE']->fe_user->groupData)
        ) {
            if (is_string($groups)) {
                $groups = strtolower($groups);
                if (!(strpos($groups, 'all') === false)) {
                    return true;
                }
                $groups = GeneralUtility::intExplode(',', $groups);
            }
            if (self::intval_list_check($groups, $GLOBALS['TSFE']->fe_user->groupData['uid'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if an integer in $allowed is in $current.
     *
     * @static
     *
     * @param array|string $allowed
     * @param array|string $current
     *
     * @return bool TRUE if there is an overlap FALSE otherwise
     */
    public static function intval_list_check($allowed, $current)
    {
        if (!is_array($allowed)) {
            $allowed = GeneralUtility::intExplode(',', strval($allowed));
        }
        if (!is_array($current)) {
            $current = GeneralUtility::intExplode(',', strval($current));
        }

        foreach ($current as $cur) {
            if (in_array($cur, $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a checkbox input.
     *
     * @param $name
     * @param $value
     * @param $checked
     * @param array $attributes
     *
     * @return string The checkbox input element
     *
     * @deprecated
     */
    public static function html_check_input($name, $value, $checked, $attributes = [])
    {
        if ($checked) {
            $attributes['checked'] = 'checked';
        }

        return self::html_input('checkbox', $name, $value, $attributes);
    }

    /**
     * Returns a html input element.
     *
     * @param $type
     * @param $name
     * @param $value
     * @param array $attributes
     *
     * @return string The hidden input element
     *
     * @deprecated
     */
    public static function html_input($type, $name, $value, $attributes = [])
    {
        $content = '<input type="' . strval($type) . '"';
        if (strlen($name) > 0) {
            $content .= ' name="' . strval($name) . '"';
        }
        if (strlen($value) > 0) {
            $content .= ' value="' . strval($value) . '"';
        }
        foreach ($attributes as $a_key => $a_value) {
            if (!($a_value === false)) {
                $content .= ' ' . strval($a_key) . '="' . strval($a_value) . '"';
            }
        }
        $content .= '>';

        return $content;
    }

    /**
     * Returns a checkbox input.
     *
     * @param $name
     * @param $value
     * @param $checked
     * @param array $attributes
     *
     * @return string The checkbox input element
     *
     * @deprecated
     */
    public static function html_radio_input($name, $value, $checked, $attributes = [])
    {
        if ($checked) {
            $attributes['checked'] = 'checked';
        }

        return self::html_input('radio', $name, $value, $attributes);
    }

    /**
     * Returns a submit input.
     *
     * @param $name
     * @param $value
     * @param array $attributes
     *
     * @return string The submit input element
     *
     * @deprecated Use FLUIDTEMPLATE when possible
     */
    public static function html_submit_input($name, $value, $attributes = [])
    {
        return self::html_input('submit', $name, $value, $attributes);
    }

    /**
     * Returns a image input.
     *
     * @param $name
     * @param $value
     * @param $src
     * @param array $attributes
     *
     * @return string The image input element
     */
    public static function html_image_input($name, $value, $src, $attributes = [])
    {
        $attributes = array_merge($attributes, ['src' => $src]);

        return self::html_input('image', $name, $value, $attributes);
    }

    /**
     * Returns a hidden input.
     *
     * @param $name
     * @param $value
     * @param array $attributes
     *
     * @return string The hidden input element
     *
     * @deprecated
     */
    public static function html_hidden_input($name, $value, $attributes = [])
    {
        return self::html_input('hidden', $name, $value, $attributes);
    }

    /**
     * Returns a text input.
     *
     * @param $name
     * @param $value
     * @param array $attributes
     *
     * @return string The text input element
     *
     * @deprecated
     */
    public static function html_text_input($name, $value, $attributes = [])
    {
        return self::html_input('text', $name, $value, $attributes);
    }

    /**
     * Returns a select input.
     *
     * @param $pairs
     * @param $value
     * @param array $attributes
     *
     * @return String The select element
     *
     * @deprecated
     */
    public static function html_select_input($pairs, $value, $attributes = [])
    {
        $value = strval($value);
        $content = '<select';
        foreach ($attributes as $a_key => $a_value) {
            if (!($a_value === false)) {
                $content .= ' ' . strval($a_key) . '="' . strval($a_value) . '"';
            }
        }
        $content .= '>';
        foreach ($pairs as $p_value => $p_name) {
            $p_value = strval($p_value);
            $content .= '<option value="' . $p_value . '"';
            if ($p_value == strval($value)) {
                $content .= ' selected="selected"';
            }
            $content .= '>';
            $content .= strval($p_name);
            $content .= '</option>';
        }
        $content .= '</select>';

        return $content;
    }

    /**
     * A layout table the contains all the strings in $rows.
     *
     * @param $rows
     *
     * @return string The html table code
     */
    public static function html_layout_table($rows)
    {
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
     * Counts strings in an array of strings.
     *
     * @param $messages
     *
     * @return array An associative array contatining the input strings and their counts
     */
    public static function string_counter($messages)
    {
        $res = [];
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
     * Crops the first argument to a given range.
     *
     * @param $value
     * @param $min
     * @param $max
     *
     * @return mixed The value fitted into the given range
     */
    public static function crop_to_range($value, $min, $max)
    {
        $value = min(intval($value), intval($max));
        $value = max($value, intval($min));

        return $value;
    }

    /**
     * Finds the nearest integer in a stack.
     * The stack must be sorted.
     *
     * @param $value
     * @param $stack
     *
     * @return mixed The value fitted into the given range
     */
    public static function find_nearest_int($value, $stack)
    {
        $res = $value;
        if (!in_array($value, $stack)) {
            if ($value > end($stack)) {
                $res = end($stack);
            } else {
                if ($value < $stack[0]) {
                    $res = $stack[0];
                } else {
                    // Find nearest
                    $res = end($stack);
                    $stackSize = sizeof($stack);
                    for ($ii = 1; $ii < $stackSize; ++$ii) {
                        $d0 = abs($value - $stack[$ii - 1]);
                        $d1 = abs($value - $stack[$ii]);
                        if ($d0 <= $d1) {
                            $res = $stack[$ii - 1];
                            break;
                        }
                    }
                }
            }
        }

        return $res;
    }

    /**
     * Implodes an array and applies intval to each element.
     *
     * @static
     *
     * @param string $sep
     * @param array  $list
     * @param bool   $noEmpty
     *
     * @return string The imploded array
     */
    public static function implode_intval($sep, $list, $noEmpty = true)
    {
        $res = [];
        if ($noEmpty) {
            foreach ($list as $val) {
                $val = trim($val);
                if (strlen($val) > 0) {
                    $res[] = strval(intval($val));
                }
            }
        } else {
            $res = self::intval_array($list);
        }

        return implode($sep, $res);
    }

    /**
     * Applies intval() to each element of an array.
     *
     * @static
     *
     * @param array $arr
     *
     * @return array The intvaled array
     */
    public static function intval_array($arr)
    {
        $res = [];
        foreach ($arr as $val) {
            $res[] = intval($val);
        }

        return $res;
    }

    /**
     * Explodes a string and applies intval to each element.
     *
     * @deprecated since 1.2.0, will be removed in 1.5.0. Use GeneralUtility::intExplode()
     * @static
     *
     * @param string $sep
     * @param string $str
     * @param bool   $noEmpty
     *
     * @return array The exploded string
     */
    public static function explode_intval($sep, $str, $noEmpty = true)
    {
        GeneralUtility::logDeprecatedFunction();

        return GeneralUtility::intExplode($sep, $str, $noEmpty);
    }

    /**
     * Returns and array with the exploded string and
     * the values trimmed.
     *
     * @deprecated since 1.2.0 will be removed in 1.5.0. Use GeneralUtility::trimExplode()
     *
     * @param string $sep
     * @param string $str
     * @param bool   $noEmpty
     *
     * @return array The exploded string
     */
    public static function explode_trim($sep, $str, $noEmpty = false)
    {
        GeneralUtility::logDeprecatedFunction();

        return GeneralUtility::trimExplode($sep, $str, $noEmpty);
    }

    /**
     * Returns and array with the exploded string and
     * the values trimmed and converted to lowercase.
     *
     * @param $sep
     * @param $str
     * @param bool $noEmpty
     *
     * @return array The exploded string
     */
    public static function explode_trim_lower($sep, $str, $noEmpty = false)
    {
        $res = [];
        $tmp = explode($sep, $str);
        foreach ($tmp as $val) {
            $val = trim($val);
            if ((strlen($val) > 0) || !$noEmpty) {
                $res[] = strtolower($val);
            }
        }

        return $res;
    }

    /**
     * Explodes a string by multiple separators.
     *
     * @deprecated Since 1.2.0, will be removed in 1.5.0. Does not seem to be used
     * @static
     *
     * @param array  $delimiters
     * @param string $str
     *
     * @return array The exploded string
     */
    public static function multi_explode($delimiters, $str)
    {
        GeneralUtility::logDeprecatedFunction();
        if (is_array($delimiters)) {
            $sep = strval($delimiters[0]);
            $delimiterSize = sizeof($delimiters);
            for ($ii = 1; $ii < $delimiterSize; ++$ii) {
                $nsep = strval($sep[$ii]);
                $str = str_replace($nsep, $sep, $str);
            }
        } else {
            $sep = strval($delimiters);
        }

        return explode($sep, $str);
    }

    /**
     * Explodes a string by multiple separators and trims the results.
     *
     * @static
     *
     * @param array  $delimiters
     * @param string $str
     * @param bool   $noEmpty
     *
     * @return array The exploded string
     */
    public static function multi_explode_trim($delimiters, $str, $noEmpty = false)
    {
        if (is_array($delimiters)) {
            $sep = strval($delimiters[0]);
            $delimiterSize = sizeof($delimiters);
            for ($ii = 1; $ii < $delimiterSize; ++$ii) {
                $nsep = strval($delimiters[$ii]);
                $str = str_replace($nsep, $sep, $str);
            }
        } else {
            $sep = strval($delimiters);
        }

        return GeneralUtility::trimExplode($sep, $str, $noEmpty);
    }

    /**
     * Explodes an ' and ' separated author string.
     *
     * @param string $authorString
     *
     * @return bool|array FALSE or the error message array
     */
    public static function explodeAuthorString($authorString)
    {
        $res = [];
        $lst = explode(' and ', $authorString);
        foreach ($lst as $a_str) {
            $name = [];
            $parts = GeneralUtility::trimExplode(',', $a_str, true);
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
     * and $and as the last separator element.
     *
     * @static
     *
     * @param array  $arr
     * @param string $sep
     * @param string $and
     *
     * @return string The imploded array as a string
     */
    public static function implode_and_last($arr, $sep, $and)
    {
        $res = [];
        $size = sizeof($arr);
        $c_idx = $size - 2;
        $a_idx = $size - 1;
        for ($ii = 0; $ii < $size; ++$ii) {
            $res[] = strval($arr[$ii]);
            if ($ii < $c_idx) {
                $res[] = strval($sep);
            } else {
                if ($ii < $a_idx) {
                    $res[] = strval($and);
                }
            }
        }

        return implode('', $res);
    }

    /**
     * Checks if a local file exists.
     *
     * @static
     *
     * @param string $file
     *
     * @return bool FALSE if the file exists TRUE if it does not exist
     */
    public static function check_file_nexist($file)
    {
        if ((strlen($file) > 0) && (substr($file, 0, 10) == 'fileadmin/')) {
            $root = PATH_site;
            if (substr($root, -1, 1) != '/') {
                $root .= '/';
            }
            $file = $root . $file;

            return !file_exists($file);
        }

        return false;
    }
}
