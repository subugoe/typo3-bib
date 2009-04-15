<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Sebastian Holtermann (sebholt@web.de)
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
 * Plugin 'Publication List' for the 'sevenpack' extension.
 *
 * @author	Sebastian Holtermann <sebholt@web.de>
 * @package TYPO3
 * @subpackage tx_sevenpack
 *
 */


require_once ( PATH_tslib.'class.tslib_pibase.php' );

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/res/class.tx_sevenpack_reference_accessor.php' ) );

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/res/class.tx_sevenpack_utility.php' ) );

class tx_sevenpack_pi1 extends tslib_pibase {

	public $prefixId = 'tx_sevenpack_pi1';		// Same as class name
	public $scriptRelPath = 'pi1/class.tx_sevenpack_pi1.php';	// Path to this script relative to the extension dir.
	public $extKey = 'sevenpack';	// The extension key.

	public $pi_checkCHash = TRUE;

	public $prefixShort = 'tx_sevenpack';	// Get/Post variable prefix.
	public $prefix_pi1 = 'tx_sevenpack_pi1';		// pi1 prefix id

	// Enumeration for list modes
	public $D_SIMPLE  = 0;
	public $D_Y_SPLIT = 1;
	public $D_Y_NAV   = 2;

	// Enumeration for view modes
	public $VIEW_LIST   = 0;
	public $VIEW_SINGLE = 1;
	public $VIEW_DIALOG = 2;

	// Single view modes
	public $SINGLE_SHOW = 0;
	public $SINGLE_EDIT = 1;
	public $SINGLE_NEW  = 2;
	public $SINGLE_CONFIRM_SAVE   = 3;
	public $SINGLE_CONFIRM_DELETE = 4;
	public $SINGLE_CONFIRM_ERASE  = 5;

	// Various dialog modes
	public $DIALOG_SAVE_CONFIRMED   = 1;
	public $DIALOG_DELETE_CONFIRMED = 2;
	public $DIALOG_ERASE_CONFIRMED  = 3;
	public $DIALOG_EXPORT           = 4;
	public $DIALOG_IMPORT           = 5;

	// Enumeration style in the list view
	public $ENUM_PAGE   = 1;
	public $ENUM_ALL    = 2;
	public $ENUM_BULLET = 3;
	public $ENUM_EMPTY  = 4;

	// Widget modes
	public $W_SHOW   = 0;
	public $W_EDIT   = 1;
	public $W_SILENT = 2;
	public $W_HIDDEN = 3;

	// Export modes
	public $EXP_BIBTEX = 1;
	public $EXP_XML    = 2;

	// Import modes
	public $IMP_BIBTEX = 1;
	public $IMP_XML    = 2;

	// Statistic modes
	public $STAT_NONE       = 0;
	public $STAT_TOTAL      = 1;
	public $STAT_YEAR_TOTAL = 2;

	// citeid generation modes
	public $AUTOID_OFF  = 0;
	public $AUTOID_HALF = 1;
	public $AUTOID_FULL = 2;

	// Sorting modes
	public $SORT_DESC = 0;
	public $SORT_ASC  = 1;

	// Database table for publications
	public $template; // HTML templates

	// These are derived/extra configuration values
	public $extConf;

	public $ra;  // The reference database accessor class
	public $fetchRes;
	public $icon_src;
	public $pubYearHist;
	public $pubYears;

	public $pubAllNum;
	public $pubPageNum; // The number of publications on the current page

	public $templateBibTypes = array (); // Initialized in main()

	public $templateBlockTypes = array (
		'YEAR_NAVI_BLOCK', 'PAGE_NAVI_BLOCK', 'EXPORT_BLOCK', 'IMPORT_BLOCK',  
		'NEW_ENTRY_BLOCK', 'YEAR_BLOCK', 'BIBTYPE_BLOCK', 'STATISTIC_BLOCK', 
		'ITEM_BLOCK', 'SPACER_BLOCK' );

	/**
	 * The main function merges all configuration options and
	 * switches to the appropriate request handler
	 *
	 * @return The plugin HTML content
	 */
	function main ( $content, $conf ) {
		$this->conf = $conf;
		$this->extConf = array();
		$this->pi_setPiVarDefaults ();
		$this->pi_loadLL ();
		$this->extend_ll ( 'EXT:'.$this->extKey.'/locallang_db.xml' );
		$this->pi_initPIflexForm ();

		// Create some configuration shortcuts
		$this->extConf = array ( );
		$extConf =& $this->extConf;
		$this->ra = t3lib_div::makeInstance ( 'tx_sevenpack_reference_accessor' );
		$this->ra->set_cObj ( $this->cObj );
		$rT = $this->ra->refTable;
		$rta = $this->ra->refTableAlias;

		// Initialize bibtype template index
		//t3lib_div::debug ( $this->ra->allBibTypes );
		$this->templateBibTypes[-1] = 'DEFAULT_DATA';
		foreach ( $this->ra->allBibTypes as $k=>$v ) {
			$this->templateBibTypes[$k] = strtoupper($v).'_DATA';
		}

		// Initialize current configuration
		$extConf['additional_link_vars'] = array();
		$extConf['sub_page'] = array();

		// Determine charsets
		$extConf['page_charset'] = tx_sevenpack_utility::accquire_page_charset();
		$extConf['be_charset'] = tx_sevenpack_utility::accquire_be_charset();

		$extConf['view_mode'] = $this->VIEW_LIST;
		$extConf['debug'] = $this->conf['debug'] ? TRUE : FALSE;
		$extConf['ce_links'] = $this->conf['ce_links'] ? TRUE : FALSE;

		//
		// Retrieve FlexForm values
		//
		$ff =& $this->cObj->data['pi_flexform'];
		$fSheet = 'sDEF';
		$extConf['d_mode']          = $this->pi_getFFvalue ( $ff, 'display_mode',   $fSheet );
		$extConf['enum_style']      = $this->pi_getFFvalue ( $ff, 'enum_style',     $fSheet );
		$extConf['show_abstracts']  = $this->pi_getFFvalue ( $ff, 'show_abstracts', $fSheet );
		$extConf['sub_page']['ipp'] = $this->pi_getFFvalue ( $ff, 'items_per_page', $fSheet );
		$extConf['max_authors']     = $this->pi_getFFvalue ( $ff, 'max_authors',    $fSheet );
		$extConf['split_bibtypes']  = $this->pi_getFFvalue ( $ff, 'split_bibtypes', $fSheet );
		$extConf['stat_mode']       = $this->pi_getFFvalue ( $ff, 'stat_mode',      $fSheet );
		$extConf['export_mode']     = $this->pi_getFFvalue ( $ff, 'export_mode',    $fSheet );
		$extConf['date_sorting']    = $this->pi_getFFvalue ( $ff, 'date_sorting',   $fSheet );

		$show_fields = $this->pi_getFFvalue ( $ff, 'show_textfields', $fSheet);
		$show_fields = explode ( ',', $show_fields );
		$extConf['hide_fields'] = array ( 'abstract' => 1, 'annotation' => 1, 'note' => 1, 'keywords' => 1 );
		foreach ( $show_fields as $f ) {
			$field = FALSE;
			switch ( $f ) {
				case 1: $field = 'abstract';   break;
				case 2: $field = 'annotation'; break;
				case 3: $field = 'note';       break;
				case 4: $field = 'keywords';   break;
			}
			if ( $field )
				$extConf['hide_fields'][$field] = 0;
		}
		//t3lib_div::debug ( $extConf['hide_fields'] );

		// Frontend editor setup
		$ecEditor =& $extConf['editor'];
		$fSheet = 's_fe_editor';
		$ecEditor['enabled']          = $this->pi_getFFvalue ( $ff, 'enable_editor',  $fSheet );
		$ecEditor['citeid_gen_new']   = $this->pi_getFFvalue ( $ff, 'citeid_gen_new', $fSheet );
		$ecEditor['citeid_gen_old']   = $this->pi_getFFvalue ( $ff, 'citeid_gen_old', $fSheet );
		$ecEditor['clear_page_cache'] = $this->pi_getFFvalue ( $ff, 'clear_cache',    $fSheet );

		// Overwrite list view configuration from TSsetup
		if ( intval ( $extConf['d_mode'] ) < 0 )
			$extConf['d_mode'] = intval ( $this->conf['display_mode'] );
		if ( intval ( $extConf['enum_style'] ) < 0 )
			$extConf['enum_style'] = intval ( $this->conf['enum_style'] );
		if ( intval ( $extConf['date_sorting'] ) < 0 )
			$extConf['date_sorting'] = intval ( $this->conf['date_sorting'] );
		if ( intval ( $extConf['stat_mode'] ) < 0 )
			$extConf['stat_mode'] = intval ( $this->conf['stat_mode'] );

		if ( array_key_exists ( 'split_bibtypes', $this->conf ) )
			$extConf['split_bibtypes'] = $this->conf['split_bibtypes'] ? TRUE : FALSE;
		if ( array_key_exists ( 'show_abstract', $this->conf ) )
			$extConf['show_abstract'] = $this->conf['show_abstract'] ? TRUE : FALSE;
		if ( array_key_exists ( 'export_mode', $this->conf ) )
			$extConf['export_mode'] = $this->conf['export_mode'];

		if ( intval ( $extConf['sub_page']['ipp'] ) < 0 ) {
			$extConf['sub_page']['ipp'] = intval ( $this->conf['items_per_page'] );
		}
		if ( intval ( $extConf['max_authors'] ) < 0 ) {
			$extConf['max_authors'] = intval ( $this->conf['max_authors'] );
		}

		$extConf['enable_export'] = 0;
		if ( intval ( $extConf['export_mode'] ) > 0 ) {
			if ( isset ( $this->conf['export.']['enable_export'] ) ) {
				$eex = tx_sevenpack_utility::explode_trim_lower ( ',', $this->conf['export.']['enable_export'] );
				if ( in_array ( 'bibtex', $eex ) )
					$extConf['enable_export'] = $extConf['enable_export'] | $this->EXP_BIBTEX;
				if ( in_array ( 'xml', $eex ) )
					$extConf['enable_export'] = $extConf['enable_export'] | $this->EXP_XML;
			}
		}

		// Overwrite editor configuration from TSsetup
		if ( array_key_exists ( 'editor.', $this->conf ) )
			if ( array_key_exists ( 'enabled', $this->conf['editor.'] ) )
				$extConf['editor']['enabled'] = $this->conf['editor.']['enabled'] ? TRUE : FALSE;
		if ( is_array( $this->conf['editor.'] ) ) {
			$eo =& $this->conf['editor.'];
			if ( array_key_exists ( 'citeid_gen_new', $eo ) )
				$extConf['editor']['citeid_gen_new'] = $eo['citeid_gen_new'] ? TRUE : FALSE;
			if ( array_key_exists ( 'citeid_gen_old', $eo ) )
				$extConf['editor']['citeid_gen_old'] = $eo['citeid_gen_old'] ? TRUE : FALSE;
		}

		//
		// Get storage page(s)
		//
		$pid_list = array();
		if ( isset ( $this->conf['pid_list'] ) ) {
			$pid_list = tx_sevenpack_utility::explode_intval ( ',', $this->conf['pid_list'] );
		}
		if ( isset ( $this->cObj->data['pages'] ) ) {
			$tmp = tx_sevenpack_utility::explode_intval ( ',', $this->cObj->data['pages'] );
			$pid_list = array_merge ( $pid_list, $tmp );
		}

		// Remove doubles and zero
		$pid_list = array_unique ( $pid_list );
		if ( in_array ( 0, $pid_list ) ) {
			unset ( $pid_list[array_search(0,$pid_list)] );
		}

		//t3lib_div::debug ( array ( 'pid list conf' => $pid_list) );

		if ( sizeof ( $pid_list ) > 0 ) {
			// Determine the recursive depth
			$extConf['recursive'] = $this->cObj->data['recursive'];
			if ( isset ( $this->conf['recursive'] ) ) {
				$extConf['recursive'] = $this->conf['recursive'];
			}
			$extConf['recursive'] = intval ( $extConf['recursive'] );

			$pid_list = $this->pi_getPidList ( implode ( ',', $pid_list ), $extConf['recursive'] );
			$pid_list = tx_sevenpack_utility::explode_intval ( ',', $pid_list );

			$extConf['pid_list'] = $pid_list;
			$this->ra->pid_list = $pid_list;
		} else {
			return $this->finalize ( $this->error_msg ( 'No storage pid given. Select a Starting point.' ) );
		}

		$this->ra->clear_cache = $extConf['editor']['clear_page_cache'];

		// Adjustments
		switch ( $extConf['d_mode'] ) {
			case $this->D_SIMPLE:
			case $this->D_Y_SPLIT:
			case $this->D_Y_NAV:
				break;
			default:
				$extConf['d_mode'] = $this->D_SIMPLE; // emergency default
		}
		switch ( $extConf['enum_style'] ) {
			case $this->ENUM_PAGE:
			case $this->ENUM_ALL:
			case $this->ENUM_BULLET:
			case $this->ENUM_EMPTY:
				break;
			default:
				$extConf['enum_style'] = $this->ENUM_ALL; // emergency default
		}
		switch ( $extConf['date_sorting'] ) {
			case $this->SORT_DESC:
			case $this->SORT_ASC:
				break;
			default:
				$extConf['date_sorting'] = $this->SORT_DESC; // emergency default
		}
		switch ( $extConf['stat_mode'] ) {
			case $this->STAT_NONE:
			case $this->STAT_TOTAL:
			case $this->STAT_YEAR_TOTAL:
				break;
			default:
				$extConf['stat_mode'] = $this->STAT_TOTAL; // emergency default
		}
		$extConf['sub_page']['ipp'] = max ( intval ( $extConf['sub_page']['ipp'] ), 0 );
		$extConf['sub_page']['ipp'] = max ( intval ( $extConf['sub_page']['ipp'] ), 0 );
		$extConf['max_authors']     = max ( intval ( $extConf['max_authors']     ), 0 );

		// Fetch some configuration from the HTTP request
		if ( array_key_exists ( 'items_per_page', $this->piVars ) ) {
			$extConf['sub_page']['ipp'] = max ( intval ( $this->piVars['items_per_page'] ), 0 );
		}

		// Check if this BE user has edit permissions
		$g_ok = FALSE;
		if ( is_object ( $GLOBALS['BE_USER'] ) ) {
			if ( $GLOBALS['BE_USER']->isAdmin ( ) ) {
				$g_ok = TRUE;
			} else {
				$g_ok = $GLOBALS['BE_USER']->check ( 'tables_modify', $this->ra->refTable );
			}
		}

		// allow FE-user editing from special groups (set via TS)
		//t3lib_div::debug( $g_ok ? 'OK' : 'NO OK' );
		if ( !$g_ok && is_array ( $GLOBALS['TSFE']->fe_user->user ) 
		     && isset ( $this->conf['FE_edit_groups'] )
		     && is_array ( $GLOBALS['TSFE']->fe_user->groupData )
		) {
			$allowed = strtolower ( $this->conf['FE_edit_groups'] );
			$current =& $GLOBALS['TSFE']->fe_user->groupData['uid'];
			if ( tx_sevenpack_utility::intval_list_check ( $allowed, $current ) 
			     || ( !( strpos ( $allowed, 'all' ) === FALSE ) ) ) {
				//t3lib_div::debug( 'FE user ok' );
				$g_ok = TRUE;
			}
		}

		$extConf['edit_mode'] = ( $g_ok && $extConf['editor']['enabled'] );

		// Set the bullet mode
		$extConf['has_enum'] = TRUE;
		if ( ( $extConf['enum_style'] == $this->ENUM_EMPTY ) ) {
			$extConf['has_enum'] = FALSE;
		}

		// Initialize the default filter
		$this->initialize_filters ( );

		// Don't show hidden entries
		$extConf['show_hidden'] = FALSE;
		if ( $extConf['edit_mode'] ) {
			// Hidden entries can only be seen in edit mode
			$extConf['show_hidden'] = TRUE;
			if ( array_key_exists('show_hidden', $this->piVars ) ) {
				if ( !$this->piVars['show_hidden'] ) {
					$extConf['show_hidden'] = FALSE;
				}
			}
		}
		$this->ra->show_hidden = $extConf['show_hidden'];

		//
		// Edit mode specific !!!
		//
		if ( $extConf['edit_mode'] ) {

			// Disable caching in edit mode
			$GLOBALS['TSFE']->set_no_cache();

			// Do an action type evaluation
			if ( is_array ( $this->piVars['action'] ) ) {
				$act_str = implode('', array_keys ( $this->piVars['action'] ) );
				//t3lib_div::debug ( $act_str );
				switch ( $act_str ) {
					case 'new':
						$extConf['view_mode']   = $this->VIEW_SINGLE;
						$extConf['single_mode'] = $this->SINGLE_NEW;
						break;
					case 'edit':
						$extConf['view_mode']   = $this->VIEW_SINGLE;
						$extConf['single_mode'] = $this->SINGLE_EDIT;
						break;
					case 'confirm_save':
						$extConf['view_mode']   = $this->VIEW_SINGLE;
						$extConf['single_mode'] = $this->SINGLE_CONFIRM_SAVE;
						break;
					case 'save':
						$extConf['view_mode']   = $this->VIEW_DIALOG;
						$extConf['dialog_mode'] = $this->DIALOG_SAVE_CONFIRMED;
						break;
					case 'confirm_delete':
						$extConf['view_mode']   = $this->VIEW_SINGLE;
						$extConf['single_mode'] = $this->SINGLE_CONFIRM_DELETE;
						break;
					case 'delete':
						$extConf['view_mode']   = $this->VIEW_DIALOG;
						$extConf['dialog_mode'] = $this->DIALOG_DELETE_CONFIRMED;
						break;
					case 'confirm_erase':
						$extConf['view_mode']   = $this->VIEW_SINGLE;
						$extConf['single_mode'] = $this->SINGLE_CONFIRM_ERASE;
						break;
					case 'erase':
						$extConf['view_mode']   = $this->VIEW_DIALOG;
						$extConf['dialog_mode'] = $this->DIALOG_ERASE_CONFIRMED;
					case 'hide':
						$this->ra->hide_publication ( $this->piVars['uid'], TRUE );
						break;
					case 'reveal':
						$this->ra->hide_publication ( $this->piVars['uid'], FALSE );
						break;
					default:
				}
			}

			// Set unset extConf and piVars single mode
			if ( $extConf['view_mode'] == $this->VIEW_DIALOG ) {
				unset ( $this->piVars['single_mode'] );
			}

			if ( isset($extConf['single_mode']) ) {
				$this->piVars['single_mode'] = $extConf['single_mode'];
			} else if ( isset($this->piVars['single_mode']) ) {
					$extConf['view_mode']   = $this->VIEW_SINGLE;
					$extConf['single_mode'] = $this->piVars['single_mode'];
			}

			// Get icon sources
			$tmpl =& $GLOBALS['TSFE']->tmpl;
			$this->icon_src['new_record'] = 'src="'.$tmpl->getFileName (
				'EXT:t3skin/icons/gfx/new_record.gif' ).'"';
			$this->icon_src['edit'] = 'src="'.$tmpl->getFileName (
				'EXT:t3skin/icons/gfx/edit2.gif' ).'"';
			$this->icon_src['hide'] = 'src="'.$tmpl->getFileName (
				'EXT:t3skin/icons/gfx/button_hide.gif' ).'"';
			$this->icon_src['reveal'] = 'src="'.$tmpl->getFileName (
				'EXT:t3skin/icons/gfx/button_unhide.gif' ).'"';

			// Switch to an import view on demand
			$allImport = intval ( $this->IMP_BIBTEX | $this->IMP_XML );
			if ( isset( $this->piVars['import'] ) && 
			     ( intval ( $this->piVars['import'] ) & $allImport ) ) {
				$extConf['view_mode']   = $this->VIEW_DIALOG;
				$extConf['dialog_mode'] = $this->DIALOG_IMPORT;
			}

		}

		// Switch to an export view on demand
		$piv_exp = intval ( $this->piVars['export'] );
		if ( $piv_exp != 0 )
			if ( intval ( $extConf['export_mode'] ) != 0 )
				if ( ( $piv_exp & $this->extConf['enable_export'] ) != 0 ) {
					$extConf['view_mode']   = $this->VIEW_DIALOG;
					$extConf['dialog_mode'] = $this->DIALOG_EXPORT;
				};

		// Overall publication statistics
		$this->ra->set_filters ( $extConf['filters'] );
		$this->pubYearHist = $this->ra->fetch_histogram ( 'year' );
		$this->pubYears  = array_keys ( $this->pubYearHist );
		$this->pubAllNum = array_sum ( $this->pubYearHist );
		sort ( $this->pubYears );

		//t3lib_div::debug ( $this->pubYearHist );
		//t3lib_div::debug ( $this->pubYears );

		//
		// Determine the year to display
		//
		$extConf['year'] = FALSE;
		$ecYear =& $extConf['year'];
		if ( is_numeric ( $this->piVars['year'] ) )
			$ecYear = intval ( $this->piVars['year'] );
		else
			$ecYear = intval ( date ( 'Y' ) ); // System year

		// The selected year has no publications so select the closest year
		// with at least one publication
		// Set default link variables
		if ( $extConf['d_mode'] == $this->D_Y_NAV ) {
			if ( $this->pubAllNum > 0) {
				$ecYear = tx_sevenpack_utility::find_nearest_int ( $ecYear, $this->pubYears );
			}
			$extConf['additional_link_vars']['year'] = $ecYear;
		}

		$this->pubPageNum = $this->pubAllNum;
		if ( $this->extConf['d_mode'] == $this->D_Y_NAV )
			$this->pubPageNum = $this->pubYearHist[$ecYear];

		//
		// Determine the number of sub pages and the current sub page (zero based)
		//
		$subPage =& $extConf['sub_page'];
		$iPP =& $subPage['ipp'];
		if ( $iPP > 0 ) {
			$subPage['max']     = floor(($this->pubPageNum-1)/$iPP);
			$subPage['current'] = tx_sevenpack_utility::crop_to_range (
				$this->piVars['page'], 0, $subPage['max']);
		} else {
			$subPage['max']     = 0;
			$subPage['current'] = 0;
		}

		//
		// Setup the browse filter
		//
		$extConf['filters']['browse'] = array();
		$br_filter =& $extConf['filters']['browse'];

		// Adjust sorting
		if ( $extConf['split_bibtypes'] ) {
			$dSort = 'DESC';
			if ( $extConf['date_sorting'] == $this->SORT_ASC )
				$dSort = 'ASC';
			$br_filter['sorting'] = array(
				array ( 'field' => $rta.'.bibtype', 'dir' => 'ASC'  ),
				array ( 'field' => $rta.'.year',    'dir' => $dSort ),
				array ( 'field' => $rta.'.month',   'dir' => $dSort ),
				array ( 'field' => $rta.'.day',     'dir' => $dSort ),
				array ( 'field' => $rta.'.state',   'dir' => 'ASC'  ),
				array ( 'field' => $rta.'.sorting', 'dir' => 'ASC'  ),
				array ( 'field' => $rta.'.title',   'dir' => 'ASC'  )
			);
		}

		// Adjust year filter
		if ( ( $extConf['d_mode'] == $this->D_Y_NAV ) && is_numeric ( $ecYear ) ) {
			$br_filter['year'] = array();
			$br_filter['year']['years'] = array ( $ecYear );
		}

		// Adjust the browse filter limit
		if ( $subPage['max'] > 0 ) {
			$br_filter['limit'] = array();
			$br_filter['limit']['start'] = $subPage['current']*$iPP;
			$br_filter['limit']['num'] = $iPP;
		}

		// Setup reference accessor
		$this->ra->set_filters ( $extConf['filters'] );

		//
		// Initialize the html template
		//
		$err = $this->initialize_template ( );
		if ( $err )
			return $this->finalize ( $err );

		//
		// Switch to requested view mode
		//
		switch ( $extConf['view_mode'] ) {
			case $this->VIEW_LIST :
				return $this->finalize ( $this->list_view () );
				break;
			case $this->VIEW_SINGLE :
				return $this->finalize ( $this->single_view () );
				break;
			case $this->VIEW_DIALOG :
				return $this->finalize ( $this->dialog_view () );
				break;
		}

		return $this->finalize ( $this->error_msg ( 'An illegal view mode occured' ) );
	}


	/**
	 * This is the last function called before ouptput
	 *
	 * @return The input string with some extra data
	 */
	function finalize ( $str )
	{
		if ( $this->extConf['debug'] )
			$str .= t3lib_div::view_array (
				array ( 
					'extConf'=>$this->extConf,
					'conf'=>$this->conf,
					'piVars'=>$this->piVars,
					'HTTP_POST_VARS' => $GLOBALS['HTTP_POST_VARS'],
					'HTTP_GET_VARS' => $GLOBALS['HTTP_GET_VARS'],
					//'$this->cObj->data' => $this->cObj->data
				) 
			);
		return $this->pi_wrapInBaseClass ( $str );
	}


	/**
	 * Returns the error message wrapped into a mesage container
	 *
	 * @return The wrapper error message
	 */
	function error_msg ( $str )
	{
		$ret  = '<div class="'.$this->prefixShort.'-warning_box">'."\n";
		$ret .= '<h3>'.$this->prefix_pi1.' error</h3>'."\n";
		$ret .= '<div>'.$str.'</div>'."\n";
		$ret .= '</div>'."\n";
		return $ret;
	}


	/**
	 * This initializes all filters before the browsing filter
	 *
	 * @return FALSE or an error message
	 */
	function initialize_filters ( )
	{
		$this->extConf['filters'] = array();
		$this->initialize_flexform_filter();
		$this->initialize_selection_filter();
	}


	/**
	 * This initializes filter array from the flexform
	 *
	 * @return FALSE or an error message
	 */
	function initialize_flexform_filter ( )
	{
		$rT =& $this->ra->refTable;
		$rta =& $this->ra->refTableAlias;

		// Create and select the flexform filter
		$this->extConf['filters']['flexform'] = array();
		$filter =& $this->extConf['filters']['flexform'];

		// Flexform helpers
		$ff =& $this->cObj->data['pi_flexform'];
		$fSheet = 's_filter';

		// Pid filter
		$filter['pid'] = $this->extConf['pid_list'];

		// Year filter
		if ( $this->pi_getFFvalue ( $ff, 'enable_year', $fSheet ) > 0 ) {
			$f = array();
			$f['years'] = array();
			$f['ranges'] = array();
			$ffStr = $this->pi_getFFvalue ( $ff, 'years', $fSheet );
			$arr = tx_sevenpack_utility::multi_explode_trim ( array ( ',', "\r" , "\n" ), $ffStr, TRUE );
			foreach ( $arr as $y ) {
				$match = array();
				if ( preg_match ( '/^\d+$/', $y, $match ) ) {
					$f['years'][] = intval ( $match[0] );
				} else if ( preg_match ( '/^(\d*)\s*-\s*(\d*)$/', $y, $match ) ) {
					$range = array();
					if ( intval ( $match[1] ) )
						$range['from'] = intval ( $match[1] );
					if ( intval ( $match[2] ) )
						$range['to'] = intval ( $match[2] );
					if ( sizeof ( $range ) )
						$f['ranges'][] = $range;
				}
			}
			if ( ( sizeof ( $f['years'] ) + sizeof ( $f['ranges'] ) ) > 0 ) {
				$filter['year'] = $f;
			}
		}

		// Author filter
		$this->extConf['highlight_authors'] = $this->pi_getFFvalue ( $ff, 'highlight_authors', $fSheet );

		if ( $this->pi_getFFvalue ( $ff, 'enable_author', $fSheet ) != 0 ) {
			$f = array();;
			$f['authors'] = array();
			$f['rule'] = $this->pi_getFFvalue ( $ff, 'author_rule', $fSheet );
			$f['rule'] = intval ( $f['rule'] );

			$authors = $this->pi_getFFvalue ( $ff, 'authors', $fSheet );
			$authors = tx_sevenpack_utility::multi_explode_trim ( array ( "\r" , "\n" ), $authors, TRUE );
			foreach ( $authors as $a ) {
				$parts = t3lib_div::trimExplode ( ',', $a );
				$f['authors'][] = array ( 'sn' => $parts[0], 'fn' => $parts[1] );
			}
			if ( sizeof ( $f['authors'] ) > 0 )
				$filter['author'] = $f;
		}

		// State filter
		if ( $this->pi_getFFvalue ( $ff, 'enable_state', $fSheet ) != 0 ) {
			$f = array();
			$f['states'] = array();
			$states = intval ( $this->pi_getFFvalue ( $ff, 'states', $fSheet ) );

			$j = 1;
			for ( $i=0; $i < sizeof ( $this->ra->allStates ); $i++ ) {
				if ( $states & $j )
					$f['states'][] = $i;
				$j = $j*2;
			}
			if ( sizeof ( $f['states'] ) > 0 )
				$filter['state'] = $f;
		}

		// Bibtype filter
		if ( $this->pi_getFFvalue ( $ff, 'enable_bibtype', $fSheet ) != 0 ) {
			$f = array();
			$f['types'] = array();
			$types = $this->pi_getFFvalue ( $ff, 'bibtypes', $fSheet );
			$types = explode ( ',', $types );
			foreach ( $types as $v ) {
				$v = intval ( $v );
				if ( ( $v >= 0 ) && ( $v < sizeof ( $this->ra->allBibTypes ) ) )
					$f['types'][] = $v;
			}
			if ( sizeof ( $f['types'] ) > 0 )
				$filter['bibtype'] = $f;
		}

		// Origin filter
		if ( $this->pi_getFFvalue ( $ff, 'enable_origin', $fSheet ) != 0 ) {
			$f = array();
			$f['origin'] = $this->pi_getFFvalue ( $ff, 'origins', $fSheet );
			if( $f['origin'] == 1 )
				$f['origin'] = 0; // Legacy value
			else if( $f['origin'] == 2 )
				$f['origin'] = 1; // Legacy value
			$filter['origin'] = $f;
		}

		// Reviewed filter
		if ( $this->pi_getFFvalue ( $ff, 'enable_reviewes', $fSheet ) != 0 ) {
			$f = array();
			$f['value'] = $this->pi_getFFvalue ( $ff, 'reviewes', $fSheet );
			$filter['reviewed'] = $f;
		}

		// In library filter
		if ( $this->pi_getFFvalue ( $ff, 'enable_in_library', $fSheet ) != 0 ) {
			$f = array();
			$f['value'] = $this->pi_getFFvalue ( $ff, 'in_library', $fSheet );
			$filter['in_library'] = $f;
		}

		// Borrowed filter
		if ( $this->pi_getFFvalue ( $ff, 'enable_borrowed', $fSheet ) != 0 ) {
			$f = array();
			$f['value'] = $this->pi_getFFvalue ( $ff, 'borrowed', $fSheet );
			$filter['borrowed'] = $f;
		}

		// Citeid filter
		if ( $this->pi_getFFvalue ( $ff, 'enable_citeid', $fSheet ) != 0 ) {
			$f = array();
			$ids = $this->pi_getFFvalue ( $ff, 'citeids', $fSheet);
			if ( strlen ( $ids ) > 0 ) {
				$ids = tx_sevenpack_utility::multi_explode_trim ( array ( ',', "\r" , "\n" ), $ids, TRUE );
				$f['ids'] = array_unique ( $ids );
				$filter['citeid'] = $f;
			}
		}

		// Keywords filter
		if ( $this->pi_getFFvalue ( $ff, 'enable_keywords', $fSheet) ) {
			$f = array();
			$f['rule'] = $this->pi_getFFvalue ( $ff, 'keywords_rule', $fSheet);
			$f['rule'] = intval ( $f['rule'] );
			$kw = $this->pi_getFFvalue ( $ff, 'keywords', $fSheet);
			if ( strlen ( $kw ) > 0 ) {
				$f['words'] = tx_sevenpack_utility::multi_explode_trim ( array ( ',', "\r" , "\n" ), $kw, TRUE );
				$filter['keywords'] = $f;
			}
		}

		// General keyword search
		if ( $this->pi_getFFvalue ( $ff, 'enable_search_all', $fSheet) ) {
			$f = array();
			$f['rule'] = $this->pi_getFFvalue ( $ff, 'search_all_rule', $fSheet);
			$f['rule'] = intval ( $f['rule'] );
			$kw = $this->pi_getFFvalue ( $ff, 'search_all', $fSheet);
			if ( strlen ( $kw ) > 0 ) {
				$f['words'] = tx_sevenpack_utility::multi_explode_trim ( array ( ',', "\r" , "\n" ), $kw, TRUE );
				$filter['all'] = $f;
			}
		}

		//t3lib_div::debug ( array ( 'pid list final' => $pid_list) );

		//
		// Sorting
		//
		$dSort = 'DESC';
		if ( $this->extConf['date_sorting'] == $this->SORT_ASC )
			$dSort = 'ASC';
		$filter['sorting'] = array (
			array ( 'field' => $rta.'.year',    'dir' => $dSort ),
			array ( 'field' => $rta.'.month',   'dir' => $dSort ),
			array ( 'field' => $rta.'.day',     'dir' => $dSort ),
			array ( 'field' => $rta.'.bibtype', 'dir' => 'ASC'  ),
			array ( 'field' => $rta.'.state',   'dir' => 'ASC'  ),
			array ( 'field' => $rta.'.sorting', 'dir' => 'ASC'  ),
			array ( 'field' => $rta.'.title',   'dir' => 'ASC'  )
		);

		//t3lib_div::debug ( $filter );

	}


	/**
	 * This initializes the selction filter array from the piVars
	 *
	 * @return FALSE or an error message
	 */
	function initialize_selection_filter ( )
	{
		if ( !$this->conf['allow_selection'] )
			return FALSE;

		$this->extConf['filters']['selection'] = array();
		$filter =& $this->extConf['filters']['selection'];

		// Publication ids
		if ( is_string ( $this->piVars['search']['ref_ids'] ) ) {
			$ids = $this->piVars['search']['ref_ids'];
			$ids = tx_sevenpack_utility::explode_intval ( ',', $ids );

			if( sizeof ( $ids ) > 0 ) {
				$filter['uid'] = $ids;
			}
		}

		// General search
		if ( is_string ( $this->piVars['search']['all'] ) ) {
			$words = $this->piVars['search']['all'];
			$words = tx_sevenpack_utility::explode_trim ( ',', $words, TRUE );
			if ( sizeof ( $words ) > 0 ) {
				$filter['all']['words'] = $words;
				$filter['all']['rule'] = 1; // AND
				$rule = strtoupper ( trim ( $this->piVars['search']['all_rule'] ) );
				if ( strpos ( $rule, 'AND' ) === FALSE ) {
					$filter['all']['rule'] = 0; // OR
				}
			}
		}
	}


	/** 
	 * Initializes an array which contains the subparts of the
	 * html template.
	 *
	 * @return TRUE on error, FALSE otherwise
	 */
	function initialize_template ()
	{
		// Allready initialized?
		if ( isset ( $this->template['VIEW'] ) ) 
			return FALSE;

		$err = FALSE;

		$this->extConf['template'] = $this->conf['template'];
		$file =& $this->extConf['template'];

		$tmpl = $this->cObj->fileResource($file);
		if ( strlen ( $tmpl ) ) {
			//t3lib_div::debug (array('file:' => $file, 'code: '=>$tmpl));

			$this->template = array();
			foreach ( $this->templateBibTypes as $t ) {
				$this->template[$t] = $this->cObj->getSubpart ( $tmpl,'###'.$t.'###' );
			}
			foreach ( $this->templateBlockTypes as $t ) {
				$this->template[$t] = $this->cObj->getSubpart ( $tmpl, '###'.$t.'###' );
			}

			$this->template['VIEW'] = $this->cObj->getSubpart ( $tmpl, '###LIST_VIEW###' );

			//t3lib_div::debug ($this->template);
			if ( !strlen ( $this->template['VIEW'] ) )
				$err = 'No or empty ###LIST_VIEW### tag was found in the template file '.$file;
		} else {
			$err = 'The template file '.$file.' is not readable or empty';
		}

		if ( $err )
			$err = $this->error_msg ( $err );

		return $err;
	}


	/** 
	 * Extend the $this->LOCAL_LANG label with another language set
	 *
	 * @return void
	 */
	function extend_ll ( $file )
	{
		if ( !is_array ( $this->extConf['LL_ext'] ) )
			$this->extConf['LL_ext'] = array();
		if ( !in_array ( $file, $this->extConf['LL_ext'] ) ) {

			//t3lib_div::debug ( 'Loading language file ' . $file );
			$tmpLang = t3lib_div::readLLfile ( $file, $this->LLkey );
			$this->LOCAL_LANG['default'] = array_merge ( 
				$this->LOCAL_LANG['default'], $tmpLang['default'] );
			//t3lib_div::debug ( $this->LLkey );
			//t3lib_div::debug ( $tmpLang );

			if ( $this->LLkey != 'default' ) {
				$this->LOCAL_LANG[$this->LLkey] = array_merge ( 
					$this->LOCAL_LANG[$this->LLkey] , $tmpLang[$this->LLkey] );
			}
			$this->extConf['LL_ext'][] = $file;
		}
		//t3lib_div::debug ( $this->LOCAL_LANG );
	}


	/** 
	 * Get the string in the local language to a given key .
	 *
	 * @return The string in the local language
	 */
	function get_ll ( $key, $alt = '', $hsc = FALSE )
	{
		return $this->pi_getLL ( $key, $alt, $hsc );
	}


	/**
	 * Composes a link of an url an some attributes
	 *
	 * @return The link (HTML <a> element)
	 */
	function compose_link ( $url, $content, $attribs = NULL )
	{
		$lstr = '<a href="'.$url.'"';
		if ( is_array ( $attribs ) ) {
			foreach ( $attribs as $k => $v ) {
				$lstr .= ' ' . $k . '="' . $v . '"';
			}
		}
		$lstr .= '>'.$content.'</a>';
		return $lstr;
	}


	/**
	 * Wraps the content into a link to the current page with
	 * extra link arguments given in the array $vars
	 *
	 * @return The link to the current page
	 */
	function get_link ( $content, $vars = array(), $auto_cache = TRUE, $attribs = NULL )
	{
		$url = $this->get_link_url ( $vars , $auto_cache );
		return $this->compose_link ( $url, $content, $attribs );
	}


	/**
	 * Same as get_link but returns just the URL
	 *
	 * @return The url
	 */
	function get_link_url ( $vars = array(), $auto_cache = TRUE, $current_record = TRUE )
	{
		if ( $this->extConf['edit_mode'] ) $auto_cache = FALSE;

		$vars = array_merge ( $this->extConf['additional_link_vars'], $vars );
		$vars = array ( $this->prefix_pi1 => $vars );

		$record = '';
		if ( $this->extConf['ce_links'] && $current_record )
			$record = "#c".strval ( $this->cObj->data['uid'] );

		$this->pi_linkTP ( 'x', $vars, $auto_cache );
		$url = $this->cObj->lastTypoLinkUrl . $record;

		$url = preg_replace ( '/&([^;]{8})/', '&amp;\\1', $url );
		return $url;
	}


	/**
	 * Same as get_link() but for edit mode links
	 *
	 * @return The link to the current page
	 */
	function get_edit_link ( $content, $vars = array(), $auto_cache = TRUE, $attribs = array() )
	{
		$url = $this->get_edit_link_url ( $vars , $auto_cache );
		return $this->compose_link ( $url, $content, $attribs );
	}


	/**
	 * Same as get_link_url() but for edit mode urls
	 *
	 * @return The url
	 */
	function get_edit_link_url ( $vars = array(), $auto_cache = TRUE, $current_record = TRUE )
	{
		$pv =& $this->piVars;
		$keep = array ( 'uid', 'single_mode', 'editor' );
		foreach ( $keep as $k ) {
			$pvar =& $pv[$k];
			if ( is_string ( $pvar ) || is_array ( $pvar ) || is_numeric ( $pvar ) ) {
				$vars[$k] = $pvar;
			}
		}
		return $this->get_link_url ( $vars, $auto_cache, $current_record );
	}


	/**
	 * This function composes the html-view of a set of publications
	 *
	 * @return The list view
	 */
	function list_view ()
	{
		$this->setup_year_navi ();  // setup year navigation element
		$this->setup_page_navi ();  // setup page navigation element
		$this->setup_new_entry ();  // setup new entry button
		$this->setup_statistic ();  // setup statistic element
		$this->setup_export_links ();  // setup export links
		$this->setup_import_links ();  // setup import link
		$this->setup_items (); // setup Items
		$this->setup_spacer ();  // setup new entry button

		//t3lib_div::debug ( $this->template['VIEW'] );

		return $this->template['VIEW'];
	}


	/** 
	 * Returns the year navigation bar
	 *
	 * @return A HTML string with the year navigation bar
	 */
	function setup_year_navi ()
	{
		$naviStr = '';
		$hasStr = '';
		$cObj =& $this->cObj;

		if ( ($this->extConf['d_mode'] == $this->D_Y_NAV) && $this->pubAllNum && (sizeof($this->pubYears) > 1) )  {

			$cfg = array();
			$cfgSel = array();
			if ( is_array ( $this->conf['yearNav.'] ) ) {
				$cfg =& $this->conf['yearNav.'];
				if ( is_array ( $cfg['selection.'] ) )
					$cfgSel =& $cfg['selection.'];
			}

			$sel = array(
				'prev' => array(),
				'cur'  => array(),
				'next' => array(),
			);

			$pubYears =& $this->pubYears;

			// The year selector
			$ys = '';
			$ys .= '<form name="'.$this->prefix_pi1.'-year_select_form" ';
			$ys .= 'action="'.$this->get_link_url ( array ( 'year' => '' ), FALSE ).'"';
			$ys .= ' method="post"';
			$ys .= strlen ( $cfg['form_class'] ) ? ' class="'.$cfg['form_class'].'"' : '';
			$ys .= '>' . "\n";
		
			$pairs = array();
			foreach ( array_reverse( $pubYears ) as $y )
				$pairs[$y] = $y;
			$attribs = array (
				'name'     => $this->prefix_pi1.'[year]',
				'onchange' => 'this.form.submit()'
			);
			if ( strlen ( $cfg['select_class'] ) > 0 )
				$attribs['class'] = $cfg['select_class'];
			$ys .= tx_sevenpack_utility::html_select_input ( 
				$pairs, $this->extConf['year'], $attribs );

			$ys .= '<input type="submit"';
			$ys .= ' name="'.$this->prefix_pi1.'[action][select_year]"';
			$ys .= ' value="'.$this->get_ll ( 'button_go' ).'"';
			$ys .= strlen ( $cfg['input_class'] ) ? ' class="'.$cfg['input_class'].'"' : '';
			$ys .= '/>' . "\n";
			$ys .= '</form>';

			// Setermine ranges of year navigation bar
			$idxMax = sizeof ( $pubYears ) - 1;

			// Number of years to display in the selection
			$numSel = 3;
			if ( array_key_exists ( 'years', $cfgSel ) )
				$numSel = intval ( $cfgSel['years'] );

			$numLR = ($numSel % 2) ? ($numSel - 1) / 2 : $numSel / 2;

			// Determine selection indices
			$idxCur = intval(array_search($this->extConf['year'], $pubYears));

			$idx1 = $idxCur - $numLR;
			if ( $idx1 < 0 ) {
				$idx1 = 0;
				$numLR = $numLR + ($numLR - $idxCur);
			}
			$idx2 = ($idxCur + $numLR);
			if ( $idx2 > $idxMax ) {
				$idx2 = $idxMax;
				$numLR += $numLR - ($idxMax - $idxCur);
				$idx1 = max ( 0,  $idxCur - $numLR );
			}

			// Generate year navigation bar
			if ( ($idx1 > 0) && strlen ( $cfgSel['more_below'] ) ) {
				$sel['prev'][] = $cObj->stdWrap ( $cfgSel['more_below'],
					$cfgSel['more_below.'] );
			}

			$yearLinkTitle = $this->get_ll ( 'yearNav_yearLinkTitle', '%y', TRUE );
			for ( $i = $idx1; $i <= $idx2; $i++ )  {
				$year = strval ( $pubYears[$i] );
				$link = $this->get_link ( $year, array('year'=>$year,'page'=>''), TRUE, 
					array ( 'title'=>str_replace ( '%y', strval($year), $yearLinkTitle ) ) );
				if ( $i < $idxCur ) {
					$key  = 'prev'; 
					$wrap = $cfgSel['below.'];
				} else if ( $i > $idxCur ) {
					$key  = 'next'; 
					$wrap = $cfgSel['above.']; 
				} else {
					$key  = 'cur'; 
					$wrap = $cfgSel['current.'];
					$link = $year;
				}
				if ( is_array ( $wrap ) )
					$sel[$key][] = $cObj->stdWrap ( $link, $wrap );
				else
					$sel[$key][] = $link;
			}

			if ( ($idx2 < $idxMax) && strlen ( $cfgSel['more_above'] ) ) {
				$sel['next'][] = $cObj->stdWrap ( $cfgSel['more_above'],
					$cfgSel['more_above.'] );
			}

			// Year separator
			$sep = '&nbsp;';
			if ( array_key_exists ( 'separator', $cfgSel  ) )
				$sep = $cObj->stdWrap ( $cfgSel['separator'], $cfgSel['separator.'] );

			// Setup the translator
			// Selection
			$translator = array (
				'###SEL_PREV###'    => implode($sep, $sel['prev']),
				'###SEL_CURRENT###' => (sizeof($sel['prev'])?$sep:'').implode($spacer, $sel['cur']).(sizeof($sel['next'])?$sep:''),
				'###SEL_NEXT###'    => implode($sep, $sel['next']),
				'###YEAR_SELECT###' => $ys
			);
			// Labels
			$translator['###NAVI_LABEL###'] = $cObj->stdWrap (
				$this->get_ll ( 'yearNav_label' ), $cfg['label.']);

			//t3lib_div::debug ( $translator );

			// Treat the template
			$t_str = $this->enum_condition_block ( $this->template['YEAR_NAVI_BLOCK'] );
			$naviStr = $cObj->substituteMarkerArrayCached ( $t_str, $translator );

			$hasStr = array ( '', '' );
		}

		$this->template['VIEW'] = $cObj->substituteSubpart ( 
			$this->template['VIEW'], '###HAS_YEAR_NAVI###', $hasStr );

		$this->template['VIEW'] = $cObj->substituteMarker (
			$this->template['VIEW'], '###YEAR_NAVI###', $naviStr );
	}


	/**
	 * Sets up the page navigation element in the 
	 * HTML-template
	 *
	 * @return void
	 */
	function setup_page_navi ()
	{
		$naviStr = '';
		$hasStr = '';
		$cObj =& $this->cObj;

		// Retrive page indices (numbers)
		$subPage =& $this->extConf['sub_page'];
		$iPP =& intval ( $subPage['ipp'] );

		if ( ( $iPP > 0 ) && ( $this->pubPageNum > $iPP ) ) {

			$cfg = array();
			$cfgSel = array();
			$cfgNav = array();
			if ( is_array ( $this->conf['pageNav.'] ) ) {
				$cfg =& $this->conf['pageNav.'];
				if ( is_array ( $cfg['selection.'] ) )
					$cfgSel =& $cfg['selection.'];
				if ( is_array ( $cfg['navigation.'] ) )
					$cfgNav =& $cfg['navigation.'];
			}

			$sel  = array(
				'prev' => array(),
				'cur'  => array(),
				'next' => array()
			);
			$navi = array();

			// Number of years to display in the selection
			$numSel = 3;
			if ( array_key_exists ( 'pages', $cfgSel ) )
				$numSel = intval ( $cfgSel['pages'] );

			$numLR = ($numSel % 2) ? ($numSel - 1) / 2 : $numSel / 2;

			// Determine selection indices
			$idxMin = 0;
			$idxCur =& $subPage['current'];
			$idxMax =& $subPage['max'];

			$idx1 = $idxCur - $numLR;
			if ( $idx1 < $idxMin ) {
				$idx1 = 0;
				$numLR = $numLR + ($numLR - $idxCur);
			}
			$idx2 = ($idxCur + $numLR);
			if ( $idx2 > $idxMax ) {
				$idx2 = $idxMax;
				$numLR += $numLR - ($idxMax - $idxCur);
				$idx1 = max ( $idxMin,  $idxCur - $numLR );
			}

			$navi['begin'] = $this->get_ll ( 'pageNav_begin', 'begin', TRUE );
			$navi['prev']  = $this->get_ll ( 'pageNav_previous', 'previous', TRUE  );
			$navi['next']  = $this->get_ll ( 'pageNav_next', 'next', TRUE  );
			$navi['last']  = $this->get_ll ( 'pageNav_last', 'last', TRUE  );

			$naviTitle['begin'] = $this->get_ll ( 'pageNav_beginLinkTitle', 'begin', TRUE );
			$naviTitle['prev']  = $this->get_ll ( 'pageNav_previousLinkTitle', 'previous', TRUE  );
			$naviTitle['next']  = $this->get_ll ( 'pageNav_nextLinkTitle', 'next', TRUE  );
			$naviTitle['last']  = $this->get_ll ( 'pageNav_lastLinkTitle', 'last', TRUE  );
			$naviTitle['page']  = $this->get_ll ( 'pageNav_pageLinkTitle', 'begin', TRUE );

			if ( $idxCur > $idxMin ) {
				$navi['begin'] = $this->get_link ( $navi['begin'], 
					array('page'=>''), TRUE, array('title'=>$naviTitle['begin']) );
				$navi['prev'] = $this->get_link ( $navi['prev'], 
					array('page'=>max($idxCur-1, 0)),
					TRUE, array('title'=>$naviTitle['prev']) );
			}

			if ( ($idx1 > $idxMin) && strlen ( $cfgSel['more_below'] ) ) {
				$sel['prev'][] = $cObj->stdWrap ( $cfgSel['more_below'],
					$cfgSel['more_below.'] );
			}

			for ( $i = $idx1; $i <= $idx2; $i++ )  {
				$ip_str = strval($i+1);
				$link = $this->get_link ( $ip_str, array('page'=>$i), 
					TRUE, array('title'=>str_replace('%p', $ip_str, $naviTitle['page'])) );
				if ( $i < $idxCur ) {
					$key  = 'prev';
					$wrap =  $cfgSel['below.'];
				} else if( $i > $idxCur ) {
					$key  = 'next';
					$wrap =  $cfgSel['above.'];
				} else {
					$key  = 'cur';
					$wrap = $cfgSel['current.'];
					$link = strval($i+1);
				}
				$sel[$key][] = $cObj->stdWrap ( $link, $wrap );
			}

			if ( ($idx2 < $idxMax) && strlen ( $cfgSel['more_above'] ) ) {
				$sel['next'][] = $cObj->stdWrap ( $cfgSel['more_above'],
					$cfgSel['more_above.'] );
			}

			if ( $idxCur < $idxMax ) {
				$navi['next'] = $this->get_link ( $navi['next'] ,
					array('page'=>min($idxCur+1, $idxMax)),
					TRUE, array('title'=>$naviTitle['next']) );
				$navi['last'] = $this->get_link ( $navi['last'],
					array('page'=>$idxMax ),
					TRUE, array('title'=>$naviTitle['last']) );
			}

			// Wrap
			$navi['prev'] = $cObj->stdWrap ( $navi['prev'], $cfgNav['previous.'] );
			$navi['next'] = $cObj->stdWrap ( $navi['next'], $cfgNav['next.'] );
			if ( ( $idxMax + 1 ) > $numSel ) {
				$navi['begin'] = $cObj->stdWrap ( $navi['begin'], 
					$cfgNav['begin.'] );
				$navi['last'] = $cObj->stdWrap ( $navi['last'], 
					$cfgNav['last.'] );
			} else {
				$navi['begin'] = '';
				$navi['last'] = '';
			}

			// Page separator
			$sepSel = '&nbsp;';
			if ( array_key_exists ( 'separator', $cfgSel  ) )
				$sepSel = $cObj->stdWrap ( $cfgSel['separator'], $cfgSel['separator.'] );

			// Navigation separator
			$sepNav = '&nbsp;';
			if ( array_key_exists ( 'separator', $cfgNav  ) )
				$sepNav = $cObj->stdWrap ( $cfgNav['separator'], $cfgNav['separator.'] );

			// Replace separator
			$navi['begin'] = str_replace('###SEPARATOR###', $sepNav, $navi['begin']);
			$navi['prev'] = str_replace('###SEPARATOR###', $sepNav, $navi['prev']);
			$navi['next'] = str_replace('###SEPARATOR###', $sepNav, $navi['next']);
			$navi['last'] = str_replace('###SEPARATOR###', $sepNav, $navi['last']);

			// Setup the translator
			// Selection and Navigation
			$translator = array (
				'###SEL_PREV###'    => implode($sepSel, $sel['prev']),
				'###SEL_CURRENT###' => (sizeof($sel['prev'])?$sepSel:'').implode($spacer, $sel['cur']).(sizeof($sel['next'])?$sepSel:''),
				'###SEL_NEXT###'    => implode($sepSel, $sel['next']),
				'###NAVI_BACKWARDS###' => $navi['begin'].$navi['prev'],
				'###NAVI_FORWARDS###'  => $navi['next'].$navi['last']
			);

			// Labels
			$translator['###NAVI_LABEL###'] = $cObj->stdWrap (
				$this->get_ll ( 'pageNav_label' ), $cfg['label.']);

			// Treat the template
			$t_str = $this->enum_condition_block ( $this->template['PAGE_NAVI_BLOCK'] );
			$naviStr = $cObj->substituteMarkerArrayCached ( $t_str, $translator );

			$hasStr = array ( '','' );
		}

		$this->template['VIEW'] = $cObj->substituteSubpart ( 
			$this->template['VIEW'], '###HAS_PAGE_NAVI###', $hasStr );

		$this->template['VIEW'] = $cObj->substituteMarker (
			$this->template['VIEW'], '###PAGE_NAVI###', $naviStr );
	}


	/** 
	 * Setup the add-new-entry element in the
	 * HTML-template
	 *
	 * @return void
	 */
	function setup_new_entry ()
	{
		$linkStr = '';
		$hasStr = '';

		if ( $this->extConf['edit_mode'] )  {
			$tmpl = $this->enum_condition_block ( $this->template['NEW_ENTRY_BLOCK'] );
			$linkStr = $this->get_new_manipulator ( );
			$linkStr = $this->cObj->substituteMarker ( $tmpl, '###NEW_ENTRY###', $linkStr );
			$hasStr = array ( '','' );
			//t3lib_div::debug ( $linkStr );
		}

		$this->template['VIEW'] = $this->cObj->substituteSubpart (
			$this->template['VIEW'], '###HAS_NEW_ENTRY###', $hasStr );

		$this->template['VIEW'] = $this->cObj->substituteMarker (
			$this->template['VIEW'], '###NEW_ENTRY###', $linkStr );
	}


	/** 
	 * Setup the statistic element in the
	 * HTML-template
	 *
	 * @return void
	 */
	function setup_statistic ()
	{
		$str = '';
		$hasStr = '';

		$mode = intval ( $this->extConf['stat_mode'] );

		if ( ( $mode != $this->STAT_NONE) && $this->pubAllNum ) {

			$cfg = array();
			if ( is_array ( $this->conf['stat.'] ) )
				$cfg =& $this->conf['stat.'];

			$str = $this->enum_condition_block ( $this->template['STATISTIC_BLOCK'] );
			$label = '';
			$translator = array();
			$exports = array();

			if ( ( $this->extConf['d_mode'] != $this->D_Y_NAV ) && 
				( $mode == $this->STAT_YEAR_TOTAL ) )
				$mode = $this->STAT_TOTAL;

			$year = intval ( $this->extConf['year'] );
			$total_str = $this->cObj->stdWrap ( strval ( $this->pubAllNum ), $cfg['value_total.'] );
			$year_str = $this->cObj->stdWrap ( strval ( $this->pubYearHist[$year] ), $cfg['value_year.'] );

			switch ( $mode ) {
				case $this->STAT_TOTAL:
					$label = $this->get_ll ( 'stat_total_label', 'total', TRUE );
					$stat_str = $total_str;
					break;
				case $this->STAT_YEAR_TOTAL:
					$label = $this->get_ll ( 'stat_year_total_label', 'this year', TRUE );
					$stat_str = $year_str . ' / ' . $total_str;
					break;
			}

			//t3lib_div::debug ( $mode );
			//t3lib_div::debug ( $this->STAT_YEAR_TOTAL );
			//t3lib_div::debug ( $stat_str );

			// Export label
			$translator['###LABEL###']     = $this->cObj->stdWrap ( $label,    $cfg['label.']  );
			$translator['###STATISTIC###'] = $this->cObj->stdWrap ( $stat_str, $cfg['values.'] );

			$str = $this->cObj->substituteMarkerArrayCached ( $str, $translator, array() );
			$hasStr = array ( '','' );
		}

		$this->template['VIEW'] = $this->cObj->substituteSubpart ( 
			$this->template['VIEW'], '###HAS_STATISTIC###', $hasStr );

		$this->template['VIEW'] = $this->cObj->substituteMarker (
			$this->template['VIEW'], '###STATISTIC###', $str);
	}


	/** 
	 * Setup the export-link element in the
	 * HTML-template
	 *
	 * @return void
	 */
	function setup_export_links ()
	{
		$str = '';
		$hasStr = '';

		if ( ( $this->extConf['enable_export'] != 0 ) && ( $this->pubAllNum > 0) )  {

			$cfg = array();
			if ( is_array ( $this->conf['export.'] ) )
				$cfg =& $this->conf['export.'];

			$str = $this->enum_condition_block ( $this->template['EXPORT_BLOCK'] );
			$translator = array();
			$exports = array();

			// Export bibtex
			if ( $this->extConf['enable_export'] & $this->EXP_BIBTEX ) {
				$title = $this->get_ll ( 'export_bibtexLinkTitle', 'bibtex', TRUE );
				$link = $this->get_link ( $this->get_ll ( 'export_bibtex' ), array ( 'export'=>$this->EXP_BIBTEX ), 
						FALSE, array ( 'title' => $title ) );
				$exports[] = $this->cObj->stdWrap ( $link, $cfg['bibtex.'] );
			}

			// Export xml
			if ( $this->extConf['enable_export'] & $this->EXP_XML ) {
				$title = $this->get_ll ( 'export_xmlLinkTitle', 'xml' ,TRUE );
				$link = $this->get_link ( $this->get_ll ( 'export_xml' ), array('export'=>$this->EXP_XML), 
						FALSE, array ( 'title' => $title ) );
				$exports[] = $this->cObj->stdWrap ( $link, $cfg['xml.'] );
			}

			$sep = '&nbsp;';
			if ( array_key_exists ( 'separator', $cfg ) )
				$sep = $this->cObj->stdWrap ( $cfg['separator'], $cfg['separator.'] );

			// Export label
			$translator['###LABEL###'] = $this->cObj->stdWrap ( 
				$this->get_ll ( $cfg['label'] ), $cfg['label.'] );
			$translator['###EXPORTS###'] = implode ( $sep, $exports );
 
			$str = $this->cObj->substituteMarkerArrayCached ( $str, $translator, array() );
			$hasStr = array ( '','' );
		}

		$this->template['VIEW'] = $this->cObj->substituteSubpart ( 
			$this->template['VIEW'], '###HAS_EXPORT###', $hasStr );

		$this->template['VIEW'] = $this->cObj->substituteMarker (
			$this->template['VIEW'], '###EXPORT###', $str);
	}


	/** 
	 * Setup the import-link element in the
	 * HTML-template
	 *
	 * @return void
	 */
	function setup_import_links ()
	{
		$str = '';
		$hasStr = '';

		if ( $this->extConf['edit_mode'] )  {

			$cfg = array();
			if ( is_array ( $this->conf['import.'] ) )
				$cfg =& $this->conf['import.'];

			$str = $this->enum_condition_block ( $this->template['IMPORT_BLOCK'] );
			$translator = array();
			$imports = array();

			// Import bibtex
			$title = $this->get_ll ( 'import_bibtexLinkTitle', 'bibtex', TRUE );
			$link = $this->get_link ( $this->get_ll ( 'import_bibtex' ), array('import'=>$this->IMP_BIBTEX), 
					FALSE, array ( 'title' => $title ) );
			$imports[] = $this->cObj->stdWrap ( $link, $cfg['bibtex.'] );

			// Import xml
			$title = $this->get_ll ( 'import_xmlLinkTitle', 'xml', TRUE );
			$link = $this->get_link ( $this->get_ll ( 'import_xml' ), array('import'=>$this->IMP_XML), 
					FALSE, array ( 'title' => $title ) );
			$imports[] = $this->cObj->stdWrap ( $link, $cfg['xml.'] );

			$sep = '&nbsp;';
			if ( array_key_exists ( 'separator', $cfg ) )
				$sep = $this->cObj->stdWrap ( $cfg['separator'], $cfg['separator.'] );

			// Import label
			$translator['###LABEL###'] = $this->cObj->stdWrap ( 
				$this->get_ll ( $cfg['label'] ), $cfg['label.'] );
			$translator['###IMPORTS###'] = implode ( $sep, $imports );
 
			$str = $this->cObj->substituteMarkerArrayCached ( $str, $translator, array() );
			$hasStr = array ( '','' );
		}

		$this->template['VIEW'] = $this->cObj->substituteSubpart ( 
			$this->template['VIEW'], '###HAS_IMPORT###', $hasStr );

		$this->template['VIEW'] = $this->cObj->substituteMarker (
			$this->template['VIEW'], '###IMPORT###', $str);
	}


	/** 
	 * Returns the html interpretation of the publication
	 * item as it is defined in the html template
	 *
	 * @return HTML string for a single item in the list view
	 */
	function get_item_html ( $pub, $templ )
	{
		//t3lib_div::debug ( array ( 'get_item_html($pub)' => $pub ) );
		$translator = array();
		$now = time();
		$cObj =& $this->cObj;
		$conf =& $this->conf;

		// Load publication data into cObj
		$cObj_restore = $cObj->data;
		$cObj->data = $pub;

		$bib_str = $this->ra->allBibTypes[$pub['bibtype']];
		$data_wrap = array ( '', '' );

		// Prepare processed row data
		$pdata = array();
		foreach ( $this->ra->refFields as $f ) {
			if ( !$this->extConf['hide_fields'][$f] )
				$pdata[$f] = tx_sevenpack_utility::filter_pub_html_display ( $pub[$f] );
		}

		// Preformat some data
		// Bibtype
		$pdata['bibtype'] = $this->get_ll (
			$this->ra->refTable.'_bibtype_I_'.$pdata['bibtype'],
			'Unknown bibtype: '.$pdata['bibtype'], TRUE ) ;
		// Extern
		$pdata['extern'] = ( $pub['extern'] == 0 ? '' : 'extern' );

		// Day
		if ( ($pub['day'] > 0) && ($pub['day'] <= 31) ) {
			$pdata['day'] = strval ( $pub['day'] );
		} else {
			$pdata['day'] = '';
		}

		// Month
		if ( ($pub['month'] > 0) && ($pub['month'] <= 12) ) {
			$tme = mktime ( 0, 0, 0, intval ( $pub['month'] ), 15, 2008 );
			$pdata['month'] = $tme;
		} else {
			$pdata['month'] = '';
		}

		// File/URL
		$url_config = array ( 
			'DOI' => $pdata['DOI'],
			'hide_file_ext' => $conf['restrictions.']['file_url.']['hide_file_ext'],
			'FE_user_groups' => $conf['restrictions.']['file_url.']['FE_user_groups']
		);
		$pdata['file_url'] = tx_sevenpack_utility::setup_file_url( $pdata['file_url'], $url_config );
		$cObj->data['file_url'] = htmlspecialchars_decode ( $pdata['file_url'], ENT_QUOTES );


		// State
		switch ( $pdata['state'] ) {
			case 0 :  
				$pdata['state'] = ''; 
				break;
			default : 
				$pdata['state'] = $this->get_ll (
				$this->ra->refTable.'_state_I_'.$pdata['state'],
				'Unknown state: '.$pdata['state'], TRUE ) ;
		}

		// Format the author string
		$pdata['authors'] = $this->get_item_authors_html ( $pub['authors'] );

		if ( ( strlen ( $pub['citeid'] ) == 0 )
		     && $this->extConf['edit_mode']
		     && ($conf['editor.']['warnings.']['m_citeid'] > 0) ) {
			$str = ' <div class="'.$this->prefixShort.'-missing_data">Citeid missing!</div>';
			$data_wrap[1] = $str . $data_wrap[1];
		}

		// Prepare the translator
		// Remove empty field marker from the template
		foreach ( $this->ra->pubFields as $f ) {
			$upStr = strtoupper ( $f );
			$hasStr = '';
			$val   = trim ( strval ( $pdata[$f] ) );

			$translator['###'.$upStr.'###'] = '';

			if ( strlen ( $val ) > 0 )  {
				// Do some special treatment for certain fields
				$charset = strtoupper ( $this->extConf['be_charset'] );
				switch ( $f ) {
					case 'file_url':
						$val = preg_replace ( '/&([^;]{8})/', '&amp;\\1', $val );
						// Cut the displayed string in the middle
						if ( isset ( $conf['max_url_string_length'] ) ) {
							$ml = abs ( intval ( $conf['max_url_string_length'] ) );
							if ( strlen ( $val ) > $ml ) {
								$le = ceil ( $ml/2.0 );
								$ls = $ml - $le;
								$str = mb_substr  ( $val, 0, $ls  , $charset ) . '...';
								$val = $str . mb_substr  ( $val, strlen ( $val ) - $le, $le  , $charset );
							}
						}
						break;
					default:
				}

				// Wrap field bibtype and default
				$stdWrap = $conf['field.'][$f.'.'];
				if ( is_array ( $conf['field.'][$bib_str.'.'][$f.'.'] ) )
					$stdWrap = $conf['field.'][$bib_str.'.'][$f.'.'];
				//t3lib_div::debug ( $stdWrap );
				$val = $cObj->stdWrap ( $val, $stdWrap );

				if ( strlen ( $val ) > 0 ) {
					$hasStr =  array ( '', '' );
					$translator['###'.$upStr.'###'] = $val;
				}
			}

			$templ = $cObj->substituteSubpart ( $templ, '###HAS_'.$upStr.'###', $hasStr );
		}

		$tmp = $cObj->stdWrap ( 'XxXx', $conf['reference.'] );
		$tmp = explode ( 'XxXx', $tmp );
		$data_wrap[0] = $tmp[0] . $data_wrap[0];
		if ( sizeof ( $tmp ) > 1  )
			$data_wrap[1] .= $tmp[1];

		// Embrace hidden references with wrap
		if ( ($pub['hidden'] != 0 ) && is_array ( $conf['editor.']['hidden.'] ) ) {
			$tmp = $cObj->stdWrap ( 'XxXx', $conf['editor.']['hidden.'] );
			$tmp = explode ( 'XxXx', $tmp );
			$data_wrap[0] = $tmp[0] . $data_wrap[0];
			if ( sizeof ( $tmp ) > 1  )
				$data_wrap[1] .= $tmp[1];
		}

		// Replace labels
		$l_trans = array ( );
		$l_trans['###LABEL_ABSTRACT###']   = $cObj->stdWrap ( $this->get_ll ( 'label_abstract' ),  $conf['label.']['abstract.']  );
		$l_trans['###LABEL_ANNOTATION###'] = $cObj->stdWrap ( $this->get_ll ( 'label_annotation' ), $conf['label.']['annotation.'] );
		$l_trans['###LABEL_EDITION###']    = $cObj->stdWrap ( $this->get_ll ( 'label_edition' ),   $conf['label.']['edition.']   );
		$l_trans['###LABEL_EDITOR###']     = $cObj->stdWrap ( $this->get_ll ( 'label_editor' ),    $conf['label.']['editor.']    );
		$l_trans['###LABEL_ISBN###']       = $cObj->stdWrap ( $this->get_ll ( 'label_isbn' ),      $conf['label.']['ISBN.']      );
		$l_trans['###LABEL_KEYWORDS###']   = $cObj->stdWrap ( $this->get_ll ( 'label_keywords' ),  $conf['label.']['keywords.']  );
		$l_trans['###LABEL_NOTE###']       = $cObj->stdWrap ( $this->get_ll ( 'label_note' ),      $conf['label.']['note.']      );
		$l_trans['###LABEL_OF###']         = $cObj->stdWrap ( $this->get_ll ( 'label_of' ),        $conf['label.']['of.']        );
		$l_trans['###LABEL_PAGE###']       = $cObj->stdWrap ( $this->get_ll ( 'label_page' ),      $conf['label.']['page.']      );
		$l_trans['###LABEL_PUBLISHER###']  = $cObj->stdWrap ( $this->get_ll ( 'label_publisher' ), $conf['label.']['publisher.'] );
		$l_trans['###LABEL_VOLUME###']     = $cObj->stdWrap ( $this->get_ll ( 'label_volume' ),    $conf['label.']['volume.']    );

		$templ = $cObj->substituteMarkerArrayCached ( $templ, $translator );
		$templ = $cObj->substituteMarkerArrayCached ( $templ, $l_trans );

		// Wrap elements with an anchor
		$url_wrap = array ( '', '' );
		if ( strlen ( $pdata['file_url'] ) > 0 ) {
			$url_wrap = $cObj->typolinkWrap ( array ( 'parameter' => $pdata['file_url'] ) );
		}
		$templ = $cObj->substituteSubpart ( $templ, '###URL_WRAP###', $url_wrap );
		$templ = $cObj->substituteSubpart ( $templ, '###REFERENCE_WRAP###', $data_wrap );

		// remove empty divs
		$templ = preg_replace ( "/<div[^>]*>[\s\r\n]*<\/div>/", "\n", $templ );
		// remove multiple line breaks
		$templ = preg_replace ( "/\n+/", "\n", $templ );
		//t3lib_div::debug ( $templ );

		$cObj->data = $cObj_restore;

		return $templ;
	}


	/** 
	 * Returns the authors string for a publication
	 *
	 * @return void
	 */
	function get_item_authors_html ( $authors ) {
		$res = '';

		// Load publication data into cObj
		$cObj =& $this->cObj;
		$cObj_restore = $cObj->data;

		// Format the author string$this->
		$and   = ' '.$this->get_ll ( 'label_and', 'and', TRUE ).' ';

		$max_authors = abs ( intval ( $this->extConf['max_authors'] ) );
		$last_author = sizeof ( $authors ) - 1;
		$cut_authors = FALSE;
		if ( ( $max_authors > 0 ) && ( sizeof ( $authors ) > $max_authors ) ) {
			$cut_authors = TRUE;
			if ( sizeof($authors) == ( $max_authors + 1 ) ) {
				$last_author = $max_authors - 2;
			} else {
				$last_author = $max_authors - 1;
			}
		}
		$last_author = max ( $last_author, 0 );
		
		//t3lib_div::debug ( array ( 'authors' => $authors, 'max_authors' => $max_authors, 'last_author' => $last_author ) );

		$hl_authors = $this->extConf['highlight_authors'] ? TRUE : FALSE;

		$a_sep  = $this->extConf['author_sep'];
		$a_tmpl = $this->extConf['author_tmpl'];

		$filter_authors = array();
		if ( $hl_authors ) {
			// Collect filter authors
			foreach ( $this->extConf['filters'] as $filter ) {
				if ( is_array( $filter['author']['authors'] ) ) {
					$filter_authors = array_merge ( 
						$filter_authors, $filter['author']['authors'] );
				}
			}
		}
		//t3lib_div::debug ( $filter_authors );

		for ( $i_a=0; $i_a<=$last_author; $i_a++ ) {
			$a =& $authors[$i_a];
			//t3lib_div::debug ( $a );

			$cObj->data = $a;
			$cObj->data['url'] = htmlspecialchars_decode ( $a['url'], ENT_QUOTES );

			// The forename
			$a_fn = trim ( $a['fn'] );
			if ( strlen ( $a_fn ) > 0 ) {
				$a_fn = tx_sevenpack_utility::filter_pub_html_display ( $a_fn );
				$a_fn = $this->cObj->stdWrap ( $a_fn, $this->conf['authors.']['forename.'] );
			}

			// The surname
			$a_sn = trim ( $a['sn'] );
			if ( strlen ( $a_sn ) > 0 ) {
				$a_sn = tx_sevenpack_utility::filter_pub_html_display ( $a_sn );
				$a_sn = $this->cObj->stdWrap ( $a_sn, $this->conf['authors.']['surname.'] );
			}

			// Compose names and apply stdWrap
			$a_str = str_replace ( 
				array ( '###FORENAME###', '###SURNAME###' ), 
				array ( $a_fn, $a_sn ), $a_tmpl );
			$stdWrap = $this->conf['field.']['author.'];
			if ( is_array ( $this->conf['field.'][$bib_str.'.']['author.'] ) )
				$stdWrap = $this->conf['field.'][$bib_str.'.']['author.'];
			$a_str = $this->cObj->stdWrap ( $a_str, $stdWrap );

			// Wrap the filtered authors with a highlightning class on demand
			if ( $hl_authors ) {
				foreach ( $filter_authors as $fa ) {
					if ( ($a['sn'] == $fa['sn']) && ( !$fa['fn'] || ($a['fn'] == $fa['fn']) ) ) {
						$a_str = $this->cObj->stdWrap ( 
							$a_str, $this->conf['authors.']['highlight.'] );
						break;
					}
				}
			}

			// Append author name
			$res .= $a_str;

			// Append an author separator or "et al."
			$app = '';
			if ( $i_a < ($last_author-1) ) {
				$app = $a_sep;
			} else {
				if ( $cut_authors ) {
					$app = $a_sep;
					if ( $i_a == $last_author ) {

						// Append et al.
						$et_al = $this->get_ll ( 'label_et_al', 'et al.', TRUE );
						$app = ( strlen ( $et_al ) > 0 ) ? ' '.$et_al : '';

						// Highlight "et al." on demand
						if ( $hl_authors ) {
							for ( $j = $last_author + 1; $j < sizeof ( $authors ); $j++ ) {
								$a_et = $authors[$j];
								foreach ( $filter_authors as $fa ) {
									if ( ($a_et['sn'] == $fa['sn']) && ( !$fa['fn'] || ($a_et['fn'] == $fa['fn']) ) ) {
										$app = $this->cObj->stdWrap ( $app, $this->conf['authors.']['highlight.'] );
										$j = sizeof ( $authors );
										break;
									}
								}
							}
						}

					}
				} elseif ( $i_a < $last_author ) {
					$app = $and;
				}
			}

			$res .= $app;
		}

		// Restore cObj data
		$cObj->data = $cObj_restore;

		return $res;
	}


	/** 
	 * Setup items in the html-template
	 *
	 * @return void
	 */
	function setup_items ()
	{
		$items = '';
		$hasStr = '';

		// Aliases
		$ra =& $this->ra;
		$cObj =& $this->cObj;
		$conf =& $this->conf;
		$filters =& $this->extConf['filters'];

		// The author name template
		$this->extConf['author_tmpl'] = '###FORENAME### ###SURNAME###';
		if ( isset ( $conf['authors.']['template'] ) ) {
			$this->extConf['author_tmpl'] = $cObj->stdWrap ( 
				$conf['authors.']['template'], $conf['authors.']['template.'] 
			);
		}
		$this->extConf['author_sep'] = ', ';
		if ( isset ( $conf['authors.']['separator'] ) ) {
			$this->extConf['author_sep'] = $cObj->stdWrap ( 
				$conf['authors.']['separator'], $conf['authors.']['separator.'] 
			);
		}

		// Database accessor initialization
		$ra->mFetch_initialize();
		$dSort =& $this->extConf['date_sorting'];

		$limit_start = intval ( $filters['browse']['limit']['start'] );
		$i_page = $this->pubPageNum - $limit_start;
		$i_page_delta = -1;
		if ( $dSort == $this->SORT_ASC ) {
			$i_page = $limit_start + 1;
			$i_page_delta = 1;
		}

		$prevBibType = -1;
		$prevYear = -1;
		$pubs_before = 0;
		if ( $this->extConf['d_mode'] == $this->D_Y_NAV ) {
			foreach ( $this->pubYearHist as $y => $n ) {
				if ( $y == $this->extConf['year'] )
					break;
				$pubs_before += $n;
			}
		}

		// Some counters
		$i_subpage = 1;
		$i_bibtype = 1;
		while ( $pub = $ra->mFetch ( ) )  {
			$translator = array();

			// All publications counter
			$i_all = $pubs_before + $i_page;

			// Determine evenOdd
			if ( $this->extConf['split_bibtypes'] ) {
				if ( $pub['bibtype'] != $prevBibType )
					$i_bibtype = 1;
				$evenOdd = $i_bibtype % 2;
			} else {
				$evenOdd = $i_subpage % 2;
			}

			// Setup the item template
			$templID = $this->templateBibTypes[$pub['bibtype']];
			$data_block = $this->template[$templID];
			if ( strlen ( $data_block ) == 0 )
				$data_block = $this->template['DEFAULT_DATA'];
			$templ = $cObj->substituteMarker ( $this->template['ITEM_BLOCK'],
				'###ITEM_DATA###', $data_block );

			$templ = $this->enum_condition_block ( $templ );

			// Create a template translator dictionary
			switch ( intval ( $this->extConf['enum_style'] ) ) {
				case $this->ENUM_ALL:
					$translator['###ENUM_NUMBER###'] = $cObj->stdWrap ( strval ( $i_all ), 
						$conf['enum.']['all.'] );
					break;
				case $this->ENUM_BULLET:
					$translator['###ENUM_NUMBER###'] = $cObj->stdWrap ( '&bull;', $conf['enum.']['bullet.'] );
					break;
				case $this->ENUM_EMPTY:
					$translator['###ENUM_NUMBER###'] = $cObj->stdWrap ( '', $conf['enum.']['empty.'] );
					break;
				default:
					$translator['###ENUM_NUMBER###'] = $cObj->stdWrap ( strval ( $i_page ), $conf['enum.']['page.'] );
			}

			// Row classes
			if ( $evenOdd )
				$translator['###ROW_CLASS###'] = $conf['classes.']['even'];
			else
				$translator['###ROW_CLASS###'] = $conf['classes.']['odd'];

			$translator['###NUMBER_CLASS###'] = $this->prefixShort.'-enum';
			//$translator['###TITLECLASS###'] = $this->prefix_pi1.'-bibtitle';

			// Manipulators
			$translator['###MANIPULATORS###'] = '';
			$manip_edit = '';
			$manip_hide = '';
			$manip_all = array();
			$subst_sub = '';
			if ( $this->extConf['edit_mode'] )  {
				$subst_sub = array ( '', '' );
				$manip_all[] = $this->get_edit_manipulator ( $pub );
				$manip_all[] = $this->get_hide_manipulator ( $pub );
				$manip_all = $this->get_layout_table ( array ( $manip_all ) );

				$translator['###MANIPULATORS###'] = $cObj->stdWrap (
					$manip_all, $conf['editor.']['manipulators.']['all.']
				);
			}

			$templ = $cObj->substituteSubpart ( $templ,
				'###HAS_MANIPULATORS###', $subst_sub );

			// Year separators
			if ( ($this->extConf['d_mode'] == $this->D_Y_SPLIT) && ( $pub['year'] != $prevYear ) )  {
				$yearStr = $cObj->stdWrap ( strval ( $pub['year'] ), $conf['label.']['year.'] );
				$t_str = $this->enum_condition_block ( $this->template['YEAR_BLOCK'] );
				$items .= $cObj->substituteMarker ( $t_str, '###YEAR###', $yearStr );
				$prevBibType = -1;
			}

			// Bibtype separators
			if ( $this->extConf['split_bibtypes'] && ($pub['bibtype'] != $prevBibType) )  {
				$bibStr = $cObj->stdWrap (
					$this->get_ll ( 'bibtype_plural_'.$pub['bibtype'], $pub['bibtype'], TRUE ),
					$conf['label.']['bibtype.']
				);
				$t_str = $this->enum_condition_block ( $this->template['BIBTYPE_BLOCK'] );
				$items .= $cObj->substituteMarker ( $t_str, '###BIBTYPE###', $bibStr );
			}

			// Item data
			$templ = $cObj->substituteMarkerArrayCached ( $templ, $translator, array() );

			$items .= $this->get_item_html ( $pub, $templ );

			$i_page += $i_page_delta;
			$i_subpage++;
			$i_bibtype++;

			$prevBibType = $pub['bibtype'];
			$prevYear = $pub['year'];
		}

		// clean up
		$ra->mFetch_finish();

		if ( strlen ( $items ) )
			$hasStr = array ( '', '' );

		$this->template['VIEW'] = $cObj->substituteSubpart (
			$this->template['VIEW'], '###HAS_ITEMS###', $hasStr );

		// Treat template
		$this->template['VIEW'] = $cObj->substituteMarker (
			$this->template['VIEW'], '###ITEMS###', $items );
	}


	/**
	 * A layout table the contains all the strings in $rows
	 */
	function get_layout_table ( $rows ) {
		// The edit button
		$res = '<table class="'.$this->prefixShort.'-layout"><tbody>';
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
	 * Returns the edit button
	 */
	function get_new_manipulator ( ) {
		$label = $this->get_ll ( 'manipulators_new', 'New', TRUE );
		$imgSrc = $this->icon_src['new_record'];
		$img = '<img '.$imgSrc.' alt="'.$label.'" ' . 
			'class="'.$this->prefixShort.'-new_icon" />';

		$res = $this->get_link ( $img, array('action'=>array('new'=>1)), TRUE, array('title'=>$label) );
		$res . $this->cObj->stdWrap ( $res, $this->conf['editor.']['manipulators.']['new.'] );
		return $res;
	}


	/**
	 * Returns the edit button
	 */
	function get_edit_manipulator ( $pub ) {
		// The edit button
		$label = $this->get_ll ( 'manipulators_edit', 'Edit', TRUE );
		$imgSrc = $this->icon_src['edit'];
		$img = '<img '.$imgSrc.' alt="'.$label.'" ' . 
			'class="'.$this->prefixShort.'-edit_icon" />';

		$res = $this->get_link ( $img, 
			array ( 'action'=>array('edit'=>1),'uid'=>$pub['uid'] ), 
			TRUE, array ( 'title'=>$label ) );

		$res = $this->cObj->stdWrap ( $res, $this->conf['editor.']['manipulators.']['edit.'] );

		return $res;
	}


	/**
	 * Returns the hide button
	 */
	function get_hide_manipulator ( $pub ) {
		if ( $pub['hidden'] == 0 )  {
			$label = $this->get_ll ( 'manipulators_hide', 'Hide', TRUE );
			$imgSrc = $this->icon_src['hide'];
			$action = array('hide'=>1);
		}  else  {
			$label = $this->get_ll ( 'manipulators_reveal', 'Reveal', TRUE );
			$imgSrc = $this->icon_src['reveal'];
			$action = array('reveal'=>1);
		}

		$img = '<img '.$imgSrc.' alt="'.$label.'" ' . 
			'class="'.$this->prefixShort.'-hide_icon" />';
		$res = $this->get_link ( $img, 
			array ( 'action'=>$action, 'uid'=>$pub['uid'] ), 
			TRUE, array('title'=>$label) );

		$res = $this->cObj->stdWrap ( $res, $this->conf['editor.']['manipulators.']['hide.'] );

		return $res;
	}


	/** 
	 * Removes the enumeration condition block
	 * or just the block markers
	 *
	 * @return void
	 */
	function enum_condition_block ( $templ ) {
		if ( $this->extConf['has_enum'] ) {
			$templ = $this->cObj->substituteSubpart ( $templ,
				'###HAS_ENUM###', array ( '', '' ) );
		} else {
			$templ = $this->cObj->substituteSubpart ( $templ,
				'###HAS_ENUM###', '' );
		}
		return $templ;
	}


	/** 
	 * Setup the BibTex export link in the template
	 *
	 * @return void
	 */
	function setup_spacer ()
	{
		$t_str = $this->enum_condition_block ( $this->template['SPACER_BLOCK'] );
		$this->template['VIEW'] = $this->cObj->substituteMarker (
			$this->template['VIEW'], '###SPACER###', $t_str );
	}


	/** 
	 * This loads the single view
	 *
	 * @return The single view
	 */
	function single_view ()
	{
		require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
			'EXT:'.$this->extKey.'/pi1/class.tx_sevenpack_single_view.php' ) );
		$sv = t3lib_div::makeInstance ( 'tx_sevenpack_single_view' );
		$sv->initialize ( $this );
		return $sv->single_view();
	}


	/** 
	 * This switches to the requested dialog
	 *
	 * @return The requested dialog
	 */
	function dialog_view ( )
	{
		$con = '';
		switch ( $this->extConf['dialog_mode'] ) {
			case $this->DIALOG_EXPORT :
				$con .= $this->export_dialog ( );
				break;
			case $this->DIALOG_IMPORT :
				$con .= $this->import_dialog ( );
				break;
			default :
				require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
					'EXT:'.$this->extKey.'/pi1/class.tx_sevenpack_single_view.php' ) );
				$sv = t3lib_div::makeInstance('tx_sevenpack_single_view');
				$sv->initialize ( $this );
				$con .= $sv->dialog_view();
		}
		$con .= '<p>';
		$con .= $this->get_link ( $this->get_ll ( 'link_back_to_list' ) );
		$con .= '</p>'."\n";
		return $con;
	}


	/** 
	 * The export dialog
	 *
	 * @return The export dialog
	 */
	function export_dialog ( )
	{
		$con = '';
		$title = $this->get_ll ( 'export_title' );
		$con .= '<h2>'.$title.'</h2>'."\n";
		$mode = $this->piVars['export'];
		$label = 'export';

		if ( $mode > 0 ) {
			$exp = FALSE;
			switch ( $mode ) {
				case $this->EXP_BIBTEX:
					require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
						'EXT:'.$this->extKey.'/pi1/class.tx_sevenpack_exporter_bibtex.php' ) );
					$exp = t3lib_div::makeInstance ( 'tx_sevenpack_exporter_bibtex' );
					$label = $this->get_ll ( 'export_bibtex' );
					break;
				case $this->EXP_XML:
					require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
						'EXT:'.$this->extKey.'/pi1/class.tx_sevenpack_exporter_xml.php' ) );
					$exp = t3lib_div::makeInstance ( 'tx_sevenpack_exporter_xml' );
					$label = $this->get_ll ( 'export_xml' );
					break;
			}
			
			if ( is_object ( $exp ) ) {
				$exp->initialize ( $this );
				if ( $exp->export () ) {
					$con .= $this->error_msg ( $exp->error );
				} else {
					$link = $this->cObj->getTypoLink ( $exp->file_name,
						$exp->get_file_rel() );
					$con .= '<ul><li><div>';
					$con .= $link;
					if ( $exp->file_new )
						$con .= ' (' . $this->get_ll ( 'export_file_new' ) . ')';
					$con .= '</div></li>';
					$con .= '</ul>' . "\n";
				}
			}
		} else {
			$con .= $this->error_msg ( 'Unknown export mode' );
		}

		return $con;
	}


	/** 
	 * The import dialog
	 *
	 * @return The import dialog
	 */
	function import_dialog ()
	{
		$con = '';
		$title = $this->get_ll ( 'import_title' );
		$con .= '<h2>'.$title.'</h2>'."\n";
		$mode = $this->piVars['import'];

		if ( ( $mode == $this->IMP_BIBTEX ) || ( $mode == $this->IMP_XML ) ) {

			$importer = FALSE;

			switch ( $mode ) {
				case $this->IMP_BIBTEX:
					require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
						'EXT:'.$this->extKey.'/pi1/class.tx_sevenpack_importer_bibtex.php' ) );
					$importer = t3lib_div::makeInstance ( 'tx_sevenpack_importer_bibtex' );
					break;
				case $this->IMP_XML:
					require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
						'EXT:'.$this->extKey.'/pi1/class.tx_sevenpack_importer_xml.php' ) );
					$importer = t3lib_div::makeInstance ( 'tx_sevenpack_importer_xml' );
					break;
			}
			$importer->initialize ( $this );
			$con .= $importer->import();
		} else {
			$con .= $this->error_msg ( 'Unknown import mode' );
		}

		return $con;
	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_pi1.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_pi1.php"]);
}

?>
