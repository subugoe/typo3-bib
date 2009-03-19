<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2008 puremedia (info@puremedia-online.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
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
***************************************************************/

require_once(PATH_tslib."class.tslib_pibase.php");

/**
 * Class for template handling.
 *
 * @author  Sebastian Bauer <sb@puremedia-online.de>
 * @author  Marco Ziesing <mz@puremedia-online.de>
 */


class tx_sevenpack_tmpl extends tslib_pibase
{

  /**
   * fill template markers with relevant data
   *
   * definition of $content array - each element looks like
   *   'part' => the content part to be used,
   *   'data' => array - each element looks like
   *     'NAME_OF_MARKER' => string content
   *
   * @param array         $records: persons
   * @param string        $template_code: html template
   * @return string        $markerArray: markers from html template
   */
  function fillTemplate($content_raw, $template_subpart, $tmpl_file = '')
  {
    // init variables
    $markerArray = array();
    $subpartMarkerArray = array();
    $subpartArray = array();
    $content = '';

    // get the template
    $template_file = (!empty($tmpl_file)) ? $tmpl_file : $this->conf['templateFile'];
    $template_code_all = $this->cObj->fileResource($template_file);

    // get the parts out of the template
    $template_code = $this->cObj->getSubpart($template_code_all, '###' . strtoupper($template_subpart) . '###');

    // prepare markerArray
    if (is_array($content_raw)) {
      $content_keys = array_keys($content_raw);

      // repeated content (multi-dimensional array)
      if (is_array($content_raw[$content_keys[0]])) {
        foreach ($content_raw as $singleRecord) {
          foreach ($singleRecord as $key => $value) {
            $marker = '###' . strtoupper($key) . '###';
            $markerArray[$marker] = $value;
          }

          // fill template
          $content .= $this->cObj->substituteMarkerArrayCached($template_code, $markerArray);
        }

        // exactly one content
      } else {
        $singleRecord = $content_raw;

        foreach ($singleRecord as $key => $value) {
          $marker = '###' . strtoupper($key) . '###';
          $markerArray[$marker] = $value;
        }

        // fill template
        $content = $this->cObj->substituteMarkerArrayCached($template_code, $markerArray);
      }
    }

    return $content;
  }


  /**
   * Extends the locallanguage data with file from extension path
   *
   * @param string filename of locallanguage definition
   */
  function extendLL(&$obj, $file) {
    $basePath = t3lib_extMgm::extPath($obj->extKey) . $file;
    $tempLOCAL_LANG = t3lib_div::readLLfile($basePath, $obj->LLkey);

    // array_merge with new array first, so a value in locallang (or typoscript) can overwrite values from extending file
    // merge is done manually to avoid array in array
    foreach($obj->LOCAL_LANG as $langkey_key => $langkey_value) {
      foreach($langkey_value as $lang_key => $lang_value) {
        $tempLOCAL_LANG[$langkey_key][$lang_key] = $lang_value;
      }
    }
    $obj->LOCAL_LANG = $tempLOCAL_LANG;

    //$this->LOCAL_LANG = array_merge_recursive($tempLOCAL_LANG, is_array($this->LOCAL_LANG) ? $this->LOCAL_LANG : array());
    if ($obj->altLLkey)    {
      $tempLOCAL_LANG = t3lib_div::readLLfile($basePath, $obj->altLLkey);
      foreach($obj->LOCAL_LANG as $langkey_key => $langkey_value) {
        foreach($langkey_value as $lang_key => $lang_value) {
          $tempLOCAL_LANG[$langkey_key][$lang_key] = $lang_value;
        }
      }
      //$this->LOCAL_LANG = array_merge_recursive($tempLOCAL_LANG, is_array($this->LOCAL_LANG) ? $this->LOCAL_LANG : array());
      $obj->LOCAL_LANG = $tempLOCAL_LANG;
    }
  }


    /**
     * Prepares the pager elements.
     *
     * @param int $count The count of elements available for paging.
     * @param int $limit How much elements should appear on one site?
     * @param int $page Which page is shown? (default 1)
     * @return array Each element of the array represents one pager part. Can be "arrows" or "placeholder".
     */
    function create_pager($count, $limit, $page = 1)
    {
     $this->pi_loadLL();
     $this->extendLL($this,'locallang.xml');
  
        // calculate count of pages (if limit and count > 0)
        if (!is_numeric($count) || !is_numeric($limit) || 0 >= $limit) return array();
        $pages = ceil($count / $limit);
        if ($limit <= 0 || $count <= 0 || $pages < 2) return array();

        // arrays to hold the three pager fragments <- 1 2 3 ... 6 7 8 ... 14 15 16 ->
        $pager_left = array();
        $pager_right = array();
        $pager_active = array();

        // init pager fragments
        for ($i = 1; $i <= $pages && $i <=3; $i++)
            $pager_left[] = $i;
        for ($i = $pages - 2; $i <= $pages; $i++)
            if ($i > 0) $pager_right[] = $i;
        if ($page >= 1 && $page <= $pages) $pager_active[] = $page;
        if ($page > 1 && $page <= $pages)
            $pager_active[] = $page - 1;
        if ($page < $pages)
            $pager_active[] = $page + 1;

        // merge pager fragments (left or right will be empty if not needed)
        if (0 === count($pager_active) || max($pager_left) >= min($pager_active) - 1) {
            $pager_active = array_merge($pager_left, $pager_active);
            $pager_left = array();
        }
        if (min($pager_right) <= max($pager_active) + 1) {
            $pager_active = array_merge($pager_active, $pager_right);
            $pager_right = array();
        }

        // make active content unique and sort it (could be merged with left and/or right)
        $pager_active = array_unique($pager_active);
        sort($pager_active);

        /**
         * array to hold the pager array
         *
         * Each element of the array is like the following:
         * array (
         *    'page' => page name (Zur&uuml;ck) or number,
         *    'active' => marker for active page,
         *    'link' => is this element a link?,
         *    'realpage' => especially for the "arrows" - shows to the next / prev page
         *    )
         */
        $returning_array = array();

        // build associative array for return
        if ($pages)
            if (1 == $page) {
                $returning_array[] = array(
                    'page' => $this->pi_getLL('pagebrowser_back'),
                    'active' => false,
                    'link' => false
                    );
            } else {
                $returning_array[] = array(
                    'page' => $this->pi_getLL('pagebrowser_back'),
                    'active' => false,
                    'link' => true,
                    'realpage' => $page - 1
                    );
            }
        for ($i = 0; $i < count($pager_left); $i++) {
            $returning_array[] = array(
                'page' => $pager_left[$i],
                'active' => false,
                'link' => true
                );
        }
        if (count($pager_left)) $returning_array[] = array(
                'page' => $this->pi_getLL('pagebrowser_dots'),
                'link' => false
            );
        for ($i = 0; $i < count($pager_active); $i++) {
            $state_active = false;
            $state_link = true;
            if ($page == $pager_active[$i]) {
                $state_active = true;
                $state_link = false;
            }
            $returning_array[] = array(
                'page' => $pager_active[$i],
                'active' => $state_active,
                'link' => $state_link
                );
        }
        if (count($pager_right)) $returning_array[] = array(
                'page' => $this->pi_getLL('pagebrowser_dots'),
                'link' => false
            );
        for ($i = 0; $i < count($pager_right); $i++) {
            $returning_array[] = array(
                'page' => $pager_right[$i],
                'active' => false,
                'link' => true
                );
        }
        if ($pages)
            if ($pages == $page) {
                $returning_array[] = array(
                    'page' => $this->pi_getLL('pagebrowser_next'),
                    'active' => false,
                    'link' => false
                    );
            } else {
                $returning_array[] = array(
                    'page' => $this->pi_getLL('pagebrowser_next'),
                    'active' => false,
                    'link' => true,
                    'realpage' => $page + 1
                    );
            }

        return $returning_array;
    }

    /**
     * Builds the complete pager ready to paste into a HTML template.
     *
     * Each element of the parameter is like the following:
     * array (
     *    'page' => page name (Zur&uuml;ck) or number,
     *    'active' => marker for active page,
     *    'link' => is this element a link?,
     *    'realpage' => especially for the "arrows" - shows to the next / prev page
     *    )
     *
     * @param array $pager_array
     * @return string ready to paste into a HTML template
     */
    function get_pager($pager_array_raw)
    {
        $pager_array = array();
        $pager_string = '';
        if (count($pager_array_raw)) $pager_string = '<ul id="pager">';
        for ($i = 0; $i < count($pager_array_raw); $i++) {
            $pager_string = '';
            if ($pager_array_raw[$i]['link']) $pager_string .= '<a href="' . $pager_array_raw[$i]['url'] . '">';
            $pager_string .= $pager_array_raw[$i]['page'];
            if ($pager_array_raw[$i]['link']) $pager_string .= '</a>';
            $pager_array[] = array('pager_item' => $pager_string);
        }

        return $pager_array;
    }

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/res/class.tx_sevenpack_tmpl.php'])    {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sevenpack/res/class.tx_sevenpack_tmpl.php']);
}
