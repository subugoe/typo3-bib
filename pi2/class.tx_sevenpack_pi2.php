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

/**
 * Plugin 'Publication Filter' for the 'sevenpack' extension.
 *
 * @author	Marco Ziesing <mz@puremedia-online.de>
 * @package TYPO3
 * @subpackage tx_sevenpack
 *
 */


require_once(PATH_tslib.'class.tslib_pibase.php');

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/res/class.tx_sevenpack_reference_accessor.php' ) );

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/res/class.tx_sevenpack_utility.php' ) );


class tx_sevenpack_pi2 extends tslib_pibase {

	public $prefixId      = 'tx_sevenpack_pi2';  // Same as class name
	public $scriptRelPath = 'pi1/class.tx_sevenpack_pi2.php';  // Path to this script relative to the extension dir.
	public $extKey        = 'sevenpack';  // The extension key.
	public $pi_checkCHash = true;


	public $ra;  // The reference database accessor class
	public $fetchRes;
	public $icon_src;
	public $pubYearHist;
	public $pubYears;

	public $pubAllNum;
	public $pubPageNum; // The number of publications on the current page


	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf)	{
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		#$this->extend_ll ( 'EXT:'.$this->extKey.'/locallang_db.xml' );
		$this->pi_initPIflexForm();

		// Create some configuration shortcuts
		$this->extConf = array ( );
		$extConf       =& $this->extConf;
		$this->ra      = t3lib_div::makeInstance ( 'tx_sevenpack_reference_accessor' );
		$this->ra->set_cObj ( $this->cObj );
		$rT            = $this->ra->refTable;
		$rta           = $this->ra->refTableAlias;
		$pi1Vars_in    = t3lib_div::GPvar( 'tx_sevenpack_pi1' ) ? t3lib_div::GPvar( 'tx_sevenpack_pi1' ) : array();
		unset($pi1Vars_in['submit']);
		$pi1Vars_out   = array( 'tx_sevenpack_pi1' => $pi1Vars_in );

		// create template helper object
		$this->tmpl_obj = $this->getClass('tmpl');

		// get additional language file
		$this->tmpl_obj->extendLL($this, 'locallang.xml');

		// get flexform-Values
		$this->ffData = array(
											'template' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'template' ),
											'references_per_page' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'references_per_page' )
		);

		$references_per_page =
		$this->ffData['references_per_page'] ? explode(',', $this->ffData['references_per_page']) : explode(',', $this->conf['references_per_page']);

		//
		// create list of years
		//

		// Overall publication statistics
		$this->ra->set_filter ( $extConf['filter'] );
		$this->pubYearHist = $this->ra->fetch_histogram ( 'year' );
		$this->pubYears    = array_keys ( $this->pubYearHist );
		$this->pubAllNum   = array_sum ( $this->pubYearHist );
		sort ( $this->pubYears );

		//t3lib_div::debug ( $this->pubYearHist, 'pubYearHist' );
		//t3lib_div::debug ( $this->pubYears, 'pubYears' );

		//
		// Determine the year to display
		//
		$extConf['year'] = FALSE;
		$ecYear =& $extConf['year'];
		if ( is_numeric ( $pi1Vars_in['year'] ) )
		$ecYear = intval ( $pi1Vars_in['year'] );
		else
		$ecYear = intval ( date ( 'Y' ) ); // System year

		// The selected year has no publications so select the closest year
		// with at least one publication
		// Set default link variables
		if ( $extConf['d_mode'] == $this->D_Y_NAV ) {
			if ( $this->pubAllNum && !in_array ( $ecYear, $this->pubYears ) ) {
				if ( $ecYear > end ( $this->pubYears ) ) {
					$ecYear = end ( $this->pubYears );
				} else if ( $ecYear < $this->pubYears[0] ) {
					$ecYear = $this->pubYears[0];
				} else {
					for ( $i=1; $i<sizeof($this->pubYears); $i++ ) {
						$d0 = abs ( $ecYear - $this->pubYears[$i-1] );
						$d1 = abs ( $ecYear - $this->pubYears[$i] );
						if ( $d0 <= $d1 ) {
							$ecYear = $this->pubYears[$i-1];
							break;
						}
					}
				}
			}
			$extConf['additional_link_vars']['year'] = $ecYear;
		}

		// create list items
		for ( $i=0; $i<count($this->pubYears); $i++ ) {
			$url_params = array('tx_' . $this->extKey . '_pi1' . '[year]' => $this->pubYears[$i]);
			$array_years[$i]['item'] = $this->pi_linkToPage($this->pubYears[$i], $GLOBALS['TSFE']->id, '', array_merge($pi1Vars_out, $url_params));
			if($this->pubYears[$i] == $ecYear)
				$array_years[$i]['item'] = $this->cObj->addParams($array_years[$i]['item'], array('class' => 'selected'));
		}

		$list_years = $this->tmpl_obj->fillTemplate(array(
											'list_items' => $this->tmpl_obj->fillTemplate($array_years, 'template_list_item')
			), 'template_olist');

		// create select options
		for ( $i=0; $i<count($this->pubYears); $i++ ) {
			$array_years[$i]['item'] = $this->pubYears[$i];
			$array_years[$i]['selected'] = ( $this->pubYears[$i] == $ecYear) ? 'selected="selected"' : '';
		}

		$options_years = $this->tmpl_obj->fillTemplate($array_years, 'template_option');


		//
		// create list of initials
		//
		$array_initials = array();

		for ($i = ord("A"); $i <= ord("Z"); $i++) {
			$url_params = array('tx_' . $this->extKey . '_pi1' . '[initial]' => chr($i));
			$array_initials[$i]['item'] = $this->pi_linkToPage(chr($i), $GLOBALS['TSFE']->id, '', array_merge($pi1Vars_out, $url_params));
			if($pi1Vars_in['initial'] == chr($i))
				$array_initials[$i]['item'] = $this->cObj->addParams($array_initials[$i]['item'], array('class' => 'selected'));
		}

		$list_initials = $this->tmpl_obj->fillTemplate(array(
												'list_items' => $this->tmpl_obj->fillTemplate(&$array_initials, 'template_list_item')
			), 'template_olist');

		// create limiter
		for( $i=0; $i<count($references_per_page); $i++) {
			$limit_items[$i]['item']     = $references_per_page[$i];
			$limit_items[$i]['selected'] = ( $limit_items[$i]['item'] == $pi1Vars_in['limit'] ) ? 'selected="selected"' : '';
		}

		$options_limits = $this->tmpl_obj->fillTemplate(&$limit_items, 'template_option');

		// set status of checkboxes
		$checked_show_keywords  = $pi1Vars_in['show_keywords']  ? 'checked="checked"' : '';
		$checked_show_abstracts = $pi1Vars_in['show_abstracts'] ? 'checked="checked"' : '';

		$content = $this->tmpl_obj->fillTemplate(array(
									 'action'                 => $this->pi_getPageLink($GLOBALS['TSFE']->id),
									 'list_years'             => $list_years,
									 'options_years'          => $options_years,
									 'list_initials'          => $list_initials,
									 'options_limits'         => $options_limits,
									 'checked_show_keywords'  => $checked_show_keywords,
									 'checked_show_abstracts' => $checked_show_abstracts,
			), 'template_filter');

		return $this->pi_wrapInBaseClass($content);
	}


	function getClass($class, $extKey = '')
	{
		$extKey = (!empty($extKey)) ? $extKey : $this->extKey;
		require_once(t3lib_extMgm::extPath($extKey).'res/class.tx_'.$extKey.'_'.$class.'.php');
		$classHandle = 'tx_'.$extKey.'_'.$class;
		$class = new $classHandle;
		$class->extKey = $extKey;
		$class->cObj = $this->cObj;
		$class->prefixId = $this->prefixId;
		$class->conf = $this->conf;

		return $class;
	}

}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi2/class.tx_sevenpack_pi2.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi2/class.tx_sevenpack_pi2.php"]);
}

?>