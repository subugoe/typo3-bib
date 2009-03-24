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


class tx_sevenpack_pi3 extends tslib_pibase {

	public $prefixId      = 'tx_sevenpack_pi3';  // Same as class name
	public $scriptRelPath = 'pi1/class.tx_sevenpack_pi3.php';  // Path to this script relative to the extension dir.
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

		// create template helper object
		$this->tmpl_obj = $this->getClass('tmpl');

		// get flexform-Values
		$this->ffData = array(
											'template' => $this->pi_getFFvalue( $this->cObj->data['pi_flexform'], 'template' ),
		);


		$content = $this->tmpl_obj->fillTemplate(array(
									 'action'         => $this->pi_getPageLink($GLOBALS['TSFE']->id),
									 'list_years'     => $list_years,
									 'options_years'  => $options_years,
									 'list_initials'  => $list_initials,
									 'options_limits' => $options_limits,
			), 'template_search');

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


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi3/class.tx_sevenpack_pi3.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi3/class.tx_sevenpack_pi3.php"]);
}

?>