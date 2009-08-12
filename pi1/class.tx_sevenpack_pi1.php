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
	public $VIEW_EDITOR = 2;
	public $VIEW_DIALOG = 3;

	// Editor view modes
	public $EDIT_SHOW = 0;
	public $EDIT_EDIT = 1;
	public $EDIT_NEW  = 2;
	public $EDIT_CONFIRM_SAVE   = 3;
	public $EDIT_CONFIRM_DELETE = 4;
	public $EDIT_CONFIRM_ERASE  = 5;

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
	public $ENUM_FILE_ICON = 5;

	// Widget modes
	public $W_SHOW   = 0;
	public $W_EDIT   = 1;
	public $W_SILENT = 2;
	public $W_HIDDEN = 3;

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
	public $item_tmpl; // HTML templates

	// These are derived/extra configuration values
	public $extConf;

	public $ra;  // The reference database accessor class
	public $fetchRes;
	public $icon_src = array();

	// Statistics
	public $stat;

	public $label_translator = array();


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
		$extConf =& $this->extConf;
		$this->ra = t3lib_div::makeInstance ( 'tx_sevenpack_reference_accessor' );
		$this->ra->set_cObj ( $this->cObj );

		// Initialize current configuration
		$extConf['link_vars'] = array();
		$extConf['sub_page'] = array();

		$extConf['view_mode'] = $this->VIEW_LIST;
		$extConf['debug'] = $this->conf['debug'] ? TRUE : FALSE;
		$extConf['ce_links'] = $this->conf['ce_links'] ? TRUE : FALSE;


		//
		// Retrieve general FlexForm values
		//
		$ff =& $this->cObj->data['pi_flexform'];
		$fSheet = 'sDEF';
		$extConf['d_mode']          = $this->pi_getFFvalue ( $ff, 'display_mode',   $fSheet );
		$extConf['enum_style']      = $this->pi_getFFvalue ( $ff, 'enum_style',     $fSheet );
		$extConf['show_nav_search'] = $this->pi_getFFvalue ( $ff, 'show_search',    $fSheet );
		$extConf['show_nav_author'] = $this->pi_getFFvalue ( $ff, 'show_authors',   $fSheet );
		$extConf['show_nav_pref']   = $this->pi_getFFvalue ( $ff, 'show_pref',      $fSheet );
		$extConf['sub_page']['ipp'] = $this->pi_getFFvalue ( $ff, 'items_per_page', $fSheet );
		$extConf['max_authors']     = $this->pi_getFFvalue ( $ff, 'max_authors',    $fSheet );
		$extConf['split_bibtypes']  = $this->pi_getFFvalue ( $ff, 'split_bibtypes', $fSheet );
		$extConf['stat_mode']       = $this->pi_getFFvalue ( $ff, 'stat_mode',      $fSheet );
		$extConf['show_nav_export'] = $this->pi_getFFvalue ( $ff, 'export_mode',    $fSheet );
		$extConf['date_sorting']    = $this->pi_getFFvalue ( $ff, 'date_sorting',   $fSheet );

		$show_fields = $this->pi_getFFvalue ( $ff, 'show_textfields', $fSheet);
		$show_fields = explode ( ',', $show_fields );
		$extConf['hide_fields'] = array ( 'abstract' => 1, 'annotation' => 1, 
			'note' => 1, 'keywords' => 1, 'tags' => 1 );
		foreach ( $show_fields as $f ) {
			$field = FALSE;
			switch ( $f ) {
				case 1: $field = 'abstract';   break;
				case 2: $field = 'annotation'; break;
				case 3: $field = 'note';       break;
				case 4: $field = 'keywords';   break;
				case 5: $field = 'tags';       break;
			}
			if ( $field ) $extConf['hide_fields'][$field] = 0;
		}
		//t3lib_div::debug ( $extConf['hide_fields'] );

		// Configuration by TypoScript selected
		if ( intval ( $extConf['d_mode'] ) < 0 )
			$extConf['d_mode'] = intval ( $this->conf['display_mode'] );
		if ( intval ( $extConf['enum_style'] ) < 0 )
			$extConf['enum_style'] = intval ( $this->conf['enum_style'] );
		if ( intval ( $extConf['date_sorting'] ) < 0 )
			$extConf['date_sorting'] = intval ( $this->conf['date_sorting'] );
		if ( intval ( $extConf['stat_mode'] ) < 0 )
			$extConf['stat_mode'] = intval ( $this->conf['statNav.']['mode'] );

		if ( intval ( $extConf['sub_page']['ipp'] ) < 0 ) {
			$extConf['sub_page']['ipp'] = intval ( $this->conf['items_per_page'] );
		}
		if ( intval ( $extConf['max_authors'] ) < 0 ) {
			$extConf['max_authors'] = intval ( $this->conf['max_authors'] );
		}

		// Character set
		$extConf['charset'] = array ( 'upper' => 'UTF-8', 'lower' => 'utf-8' );
		if ( strlen ( $this->conf['charset'] ) > 0 ) {
			$extConf['charset']['upper'] = strtoupper ( $this->conf['charset'] );
			$extConf['charset']['lower'] = strtolower ( $this->conf['charset'] );
		}

		//
		// Frontend editor configuration
		//
		$ecEditor =& $extConf['editor'];
		$fSheet = 's_fe_editor';
		$ecEditor['enabled']          = $this->pi_getFFvalue ( $ff, 'enable_editor',  $fSheet );
		$ecEditor['citeid_gen_new']   = $this->pi_getFFvalue ( $ff, 'citeid_gen_new', $fSheet );
		$ecEditor['citeid_gen_old']   = $this->pi_getFFvalue ( $ff, 'citeid_gen_old', $fSheet );
		$ecEditor['clear_page_cache'] = $this->pi_getFFvalue ( $ff, 'clear_cache',    $fSheet );

		// Overwrite editor configuration from TSsetup
		if ( is_array( $this->conf['editor.'] ) ) {
			$eo =& $this->conf['editor.'];
			if ( array_key_exists ( 'enabled', $eo ) )
				$extConf['editor']['enabled'] = $eo['enabled'] ? TRUE : FALSE;
			if ( array_key_exists ( 'citeid_gen_new', $eo ) )
				$extConf['editor']['citeid_gen_new'] = $eo['citeid_gen_new'] ? TRUE : FALSE;
			if ( array_key_exists ( 'citeid_gen_old', $eo ) )
				$extConf['editor']['citeid_gen_old'] = $eo['citeid_gen_old'] ? TRUE : FALSE;
		}
		$this->ra->clear_cache = $extConf['editor']['clear_page_cache'];


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
			$extConf['pid_list'] = array ( intval ( $GLOBALS['TSFE']->id ) );
			//return $this->finalize ( $this->error_msg ( 'No storage pid given. Select a Starting point.' ) );
		}

		//
		// Adjustments
		//
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
			case $this->ENUM_FILE_ICON:
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
		$extConf['max_authors']     = max ( intval ( $extConf['max_authors']     ), 0 );


		//
		// Search navi
		//
		if ( $extConf['show_nav_search'] ) {
			$extConf['dynamic'] = TRUE;
			$extConf['search_navi'] = array();
			$aconf =& $extConf['search_navi'];
			$aconf['obj'] =& $this->get_navi_instance ( 'tx_sevenpack_navi_search' );
			$aconf['obj']->hook_init();
		}

		//
		// Year navi
		//
		if ( $extConf['d_mode'] == $this->D_Y_NAV ) {
			$extConf['show_nav_year'] = TRUE;
		}

		//
		// Author navi
		//
		if ( $extConf['show_nav_author'] ) {
			$extConf['dynamic'] = TRUE;
			$extConf['author_navi'] = array();
			$aconf =& $extConf['author_navi'];
			$aconf['obj'] =& $this->get_navi_instance ( 'tx_sevenpack_navi_author' );
			$aconf['obj']->hook_init();
		}


		//
		// Preference navi
		//
		if ( $extConf['show_nav_pref'] ) {
			$extConf['pref_navi'] = array();
			$aconf =& $extConf['pref_navi'];
			$aconf['obj'] =& $this->get_navi_instance ( 'tx_sevenpack_navi_pref' );
			$aconf['obj']->hook_init();
		}


		//
		// Statistic navi
		//
		if ( intval ( $this->extConf['stat_mode'] ) != $this->STAT_NONE ) {
			$extConf['show_nav_stat'] = TRUE;
		}


		//
		// Export navi
		//
		if ( $extConf['show_nav_export'] ) {
			$extConf['export_navi'] = array();
			$econf =& $extConf['export_navi'];

			// Check group restrictions
			$groups = $this->conf['export.']['FE_groups_only'];
			$fe_ok = TRUE;
			if ( strlen ( $groups ) > 0 ) {
				$fe_ok = tx_sevenpack_utility::check_fe_user_groups ( $groups );
			}
			//t3lib_div::debug ( array ( $groups, $fe_ok ) );

			// Acquire export modes
			$modes = $this->conf['export.']['enable_export'];
			if ( strlen ( $modes ) > 0 ) {
				$modes = tx_sevenpack_utility::explode_trim_lower ( 
					',', $modes, TRUE );
			}

			// Add export modes
			$econf['modes'] = array();
			$mm =& $econf['modes'];
			if ( is_array ( $modes ) && $fe_ok ) {
				$mod_all = array ( 'bibtex', 'xml' );
				$mm = array_intersect ( $mod_all, $modes );
			}

			if ( sizeof ( $mm ) == 0 ) {
				$extConf['show_nav_export'] = FALSE;
			} else {
				$pvar = trim ( $this->piVars['export'] );
				if ( ( strlen ( $pvar ) > 0 ) && in_array ( $pvar, $mm ) ) {
					$econf['do'] = $pvar;
				}
			}
		}


		//
		// Enable Enable the edit mode
		// Check if this BE user has edit permissions
		//
		$be_ok = FALSE;
		if ( is_object ( $GLOBALS['BE_USER'] ) ) {
			if ( $GLOBALS['BE_USER']->isAdmin() )
				$be_ok = TRUE;
			else
				$be_ok = $GLOBALS['BE_USER']->check ( 'tables_modify', $this->ra->refTable );
		}

		// allow FE-user editing from special groups (set via TS)
		$fe_ok = FALSE;
		if ( !$be_ok && isset ( $this->conf['FE_edit_groups'] ) ) {
			$groups = $this->conf['FE_edit_groups'];
			if ( tx_sevenpack_utility::check_fe_user_groups ( $groups ) )
				$fe_ok = TRUE;
		}

		//t3lib_div::debug( array ( 'Edit mode' => array ( 'BE' => $be_ok, 'FE' => $fe_ok ) ) );
		$extConf['edit_mode'] = ( ($be_ok || $fe_ok) && $extConf['editor']['enabled'] );

		// Set the enumeration mode
		$extConf['has_enum'] = TRUE;
		if ( ( $extConf['enum_style'] == $this->ENUM_EMPTY ) ) {
			$extConf['has_enum'] = FALSE;
		}

		// Initialize data display restrictions
		$this->init_restrictions();

		// Initialize icons
		$this->init_list_icons();

		// Initialize the default filter
		$this->init_filters();

		// Don't show hidden entries
		$extConf['show_hidden'] = FALSE;
		if ( $extConf['edit_mode'] ) {
			$extConf['show_hidden'] = TRUE;
		}
		$this->ra->show_hidden = $extConf['show_hidden'];


		//
		// Edit mode specific !!!
		//
		if ( $extConf['edit_mode'] ) {

			// Disable caching in edit mode
			$GLOBALS['TSFE']->set_no_cache();

			// Load edit labels
			$this->extend_ll ( 'EXT:'.$this->extKey.'/pi1/locallang_editor.xml' );

			// Do an action type evaluation
			if ( is_array ( $this->piVars['action'] ) ) {
				$act_str = implode('', array_keys ( $this->piVars['action'] ) );
				//t3lib_div::debug ( $act_str );
				switch ( $act_str ) {
					case 'new':
						$extConf['view_mode']   = $this->VIEW_EDITOR;
						$extConf['editor_mode'] = $this->EDIT_NEW;
						break;
					case 'edit':
						$extConf['view_mode']   = $this->VIEW_EDITOR;
						$extConf['editor_mode'] = $this->EDIT_EDIT;
						break;
					case 'confirm_save':
						$extConf['view_mode']   = $this->VIEW_EDITOR;
						$extConf['editor_mode'] = $this->EDIT_CONFIRM_SAVE;
						break;
					case 'save':
						$extConf['view_mode']   = $this->VIEW_DIALOG;
						$extConf['dialog_mode'] = $this->DIALOG_SAVE_CONFIRMED;
						break;
					case 'confirm_delete':
						$extConf['view_mode']   = $this->VIEW_EDITOR;
						$extConf['editor_mode'] = $this->EDIT_CONFIRM_DELETE;
						break;
					case 'delete':
						$extConf['view_mode']   = $this->VIEW_DIALOG;
						$extConf['dialog_mode'] = $this->DIALOG_DELETE_CONFIRMED;
						break;
					case 'confirm_erase':
						$extConf['view_mode']   = $this->VIEW_EDITOR;
						$extConf['editor_mode'] = $this->EDIT_CONFIRM_ERASE;
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

			// Set unset extConf and piVars editor mode
			if ( $extConf['view_mode'] == $this->VIEW_DIALOG ) {
				unset ( $this->piVars['editor_mode'] );
			}

			if ( isset ( $extConf['editor_mode'] ) ) {
				$this->piVars['editor_mode'] = $extConf['editor_mode'];
			} else if ( isset ( $this->piVars['editor_mode'] ) ) {
					$extConf['view_mode']   = $this->VIEW_EDITOR;
					$extConf['editor_mode'] = $this->piVars['editor_mode'];
			}

			// Initialize edit icons
			$this->init_edit_icons();

			// Switch to an import view on demand
			$allImport = intval ( $this->IMP_BIBTEX | $this->IMP_XML );
			if ( isset( $this->piVars['import'] ) && 
			     ( intval ( $this->piVars['import'] ) & $allImport ) ) {
				$extConf['view_mode']   = $this->VIEW_DIALOG;
				$extConf['dialog_mode'] = $this->DIALOG_IMPORT;
			}

		}

		// Switch to an export view on demand
		if ( is_string ( $extConf['export_navi']['do'] ) ) {
			$extConf['view_mode']   = $this->VIEW_DIALOG;
			$extConf['dialog_mode'] = $this->DIALOG_EXPORT;
		}

		// Switch to a single view on demand
		if ( is_numeric ( $this->piVars['show_uid'] ) ) {
			$extConf['view_mode'] = $this->VIEW_SINGLE;
			$extConf['single_view']['uid'] = intval ( $this->piVars['show_uid'] );
			unset ( $this->piVars['editor_mode'] );
			unset ( $this->piVars['dialog_mode'] );
		}


		//
		// Search navigation setup
		//
		if ( $extConf['show_nav_search'] ) {
			$extConf['search_navi']['obj']->hook_filter();
		}


		//
		// Fetch publication statistics
		//
		$this->stat = array();
		$this->ra->set_filters ( $extConf['filters'] );
		//t3lib_div::debug ( $extConf['filters'] );


		//
		// Author navigation hook
		//
		if ( $extConf['show_nav_author'] ) {
			$extConf['author_navi']['obj']->hook_filter();
		}


		//
		// Year navigation
		//
		if ( $extConf['show_nav_year'] ) {
			// Fetch a year histogram
			$hist = $this->ra->fetch_histogram ( 'year' );
			$this->stat['year_hist'] = $hist;
			$this->stat['years'] = array_keys ( $hist );
			sort ( $this->stat['years'] );

			$this->stat['num_all'] = array_sum ( $hist );
			$this->stat['num_page'] = $this->stat['num_all'];

			//
			// Determine the year to display
			//
			$extConf['year'] = intval ( date ( 'Y' ) ); // System year
			//$extConf['year'] = 'all'; // All years
			$ecYear =& $extConf['year'];
	
			$pvar = strtolower ( $this->piVars['year'] );
			if ( is_numeric ( $pvar ) ) {
				$ecYear = intval ( $pvar );
			} else  {
				if ( $pvar == 'all' ) {
					$ecYear = $pvar;
				}
			}

			if ( $ecYear == 'all' ) {
				if ( $this->conf['yearNav.']['selection.']['all_year_split'] ) {
					$extConf['split_years'] = TRUE;
				}
			}


			// The selected year has no publications so select the closest year
			if ( ( $this->stat['num_all'] > 0 ) && is_numeric ( $ecYear ) ) {
				$ecYear = tx_sevenpack_utility::find_nearest_int ( 
					$ecYear, $this->stat['years'] );
			}
			// Append default link variable
			$extConf['link_vars']['year'] = $ecYear;

			if ( is_numeric ( $ecYear ) ) {
				// Adjust num_page
				$this->stat['num_page'] = $this->stat['year_hist'][$ecYear];

				// Adjust year filter
				$extConf['filters']['br_year'] = array();
				$br_filter =& $extConf['filters']['br_year'];
				$br_filter['year'] = array();
				$br_filter['year']['years'] = array ( $ecYear );
			}

		}

		//
		// Determine number of publications
		//
		if ( !is_numeric ( $this->stat['num_all'] ) ) {
			$this->stat['num_all'] = $this->ra->fetch_num ( );
			$this->stat['num_page'] = $this->stat['num_all'];
		}

		//
		// Page navigation
		//
		$subPage =& $extConf['sub_page'];
		$subPage['max']     = 0;
		$subPage['current'] = 0;
		$iPP = $subPage['ipp'];

		if ( $iPP > 0 ) {
			$subPage['max']     = floor ( ( $this->stat['num_page']-1 ) / $iPP );
			$subPage['current'] = tx_sevenpack_utility::crop_to_range (
				$this->piVars['page'], 0, $subPage['max'] );
		}

		if ( $subPage['max'] > 0 ) {
			$extConf['show_nav_page'] = TRUE;

			$extConf['filters']['br_page'] = array();
			$br_filter =& $extConf['filters']['br_page'];

			// Adjust the browse filter limit
			$br_filter['limit'] = array();
			$br_filter['limit']['start'] = $subPage['current']*$iPP;
			$br_filter['limit']['num'] = $iPP;
		}


		//
		// The sort filter
		//
		$extConf['filters']['sort'] = array();
		$extConf['filters']['sort']['sorting'] = array();
		$sort_f =& $extConf['filters']['sort']['sorting'];

		// Default sorting
		$dSort = 'DESC';
		if ( $this->extConf['date_sorting'] == $this->SORT_ASC ) {
			$dSort = 'ASC';
		}
		$rta =& $this->ra->refTableAlias;
		$sort_f = array (
			array ( 'field' => $rta.'.year',    'dir' => $dSort ),
			array ( 'field' => $rta.'.month',   'dir' => $dSort ),
			array ( 'field' => $rta.'.day',     'dir' => $dSort ),
			array ( 'field' => $rta.'.bibtype', 'dir' => 'ASC'  ),
			array ( 'field' => $rta.'.state',   'dir' => 'ASC'  ),
			array ( 'field' => $rta.'.sorting', 'dir' => 'ASC'  ),
			array ( 'field' => $rta.'.title',   'dir' => 'ASC'  )
		);

		// Adjust sorting for bibtype split
		if ( $extConf['split_bibtypes'] ) {
			if ( $extConf['d_mode'] == $this->D_SIMPLE ) {
				$sort_f = array (
					array ( 'field' => $rta.'.bibtype', 'dir' => 'ASC'  ),
					array ( 'field' => $rta.'.year',    'dir' => $dSort ),
					array ( 'field' => $rta.'.month',   'dir' => $dSort ),
					array ( 'field' => $rta.'.day',     'dir' => $dSort ),
					array ( 'field' => $rta.'.state',   'dir' => 'ASC'  ),
					array ( 'field' => $rta.'.sorting', 'dir' => 'ASC'  ),
					array ( 'field' => $rta.'.title',   'dir' => 'ASC'  )
				);
			} else {
				$sort_f = array (
					array ( 'field' => $rta.'.year',    'dir' => $dSort ),
					array ( 'field' => $rta.'.bibtype', 'dir' => 'ASC'  ),
					array ( 'field' => $rta.'.month',   'dir' => $dSort ),
					array ( 'field' => $rta.'.day',     'dir' => $dSort ),
					array ( 'field' => $rta.'.state',   'dir' => 'ASC'  ),
					array ( 'field' => $rta.'.sorting', 'dir' => 'ASC'  ),
					array ( 'field' => $rta.'.title',   'dir' => 'ASC'  )
				);
			}
		}

		// Setup reference accessor
		//t3lib_div::debug ( $this->stat );
		//t3lib_div::debug ( $extConf['filters'] );
		$this->ra->set_filters ( $extConf['filters'] );

		//
		// Disable navigations om demand
		//
		if ( $this->stat['num_all'] == 0 )
			$extConf['show_nav_export'] = FALSE;
		if ( $this->stat['num_page'] == 0 )
			$extConf['show_nav_stat'] = FALSE;

		//
		// Initialize the html templates
		//
		$err = $this->init_template ( );
		if ( sizeof ( $err ) > 0 ) {
			$bad = '';
			foreach ( $err as $msg )
				$bad .= $this->error_msg ( $msg );
			return $this->finalize ( $bad );
		}

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
			case $this->VIEW_EDITOR :
				return $this->finalize ( $this->editor_view () );
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
					'extConf' => $this->extConf,
					'conf' => $this->conf,
					'piVars' => $this->piVars,
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
	 * This initializes field restrictions
	 *
	 * @return void
	 */
	function init_restrictions ( )
	{
		$this->extConf['restrict'] = array();
		$rest =& $this->extConf['restrict'];

		$cfg_rest =& $this->conf['restrictions.'];
		if ( !is_array ( $cfg_rest ) ) {
			return;
		}

		// This is a nested array containing fields
		// that may have restrictions
		$fields = array ( 
			'ref' => array(), 
			'author' => array() 
		);
		$all_fields = array();
		// Acquire field configurations
		foreach ( $cfg_rest as $table => $data ) {
			if ( is_array ( $data ) ) {
				$t_fields = array ( );
				$table = substr ( $table, 0, -1 );

				switch ( $table ) {
					case 'ref':
						$all_fields =& $this->ra->refFields;
						break;
					case 'authors':
						$all_fields =& $this->ra->authorFields;
						break;
					default:
						continue;
				}

				foreach ( $data as $t_field => $t_data ) {
					if ( is_array ( $t_data ) ) {
						$t_field = substr ( $t_field, 0, -1 );
						if ( in_array ( $t_field, $all_fields ) ) {
							$fields[$table][] = $t_field;
						}
					}
				}
			}
		}

		// Process restriction requests
		foreach ( $fields as $table => $fields ) {
			$rest[$table] = array();
			$d_table = $table . '.';
			foreach ( $fields as $field ) {
				$d_field = $field . '.';
				$rcfg = $cfg_rest[$d_table][$d_field];

				// Hide all
				$all = ( $rcfg['hide_all'] != 0 );

				// Hide on string extensions
				$ext = tx_sevenpack_utility::explode_trim_lower ( 
					',', $rcfg['hide_file_ext'], TRUE );

				// Reveal on FE user groups
				$groups = strtolower ( $rcfg['FE_user_groups'] );
				if ( strpos ( $groups, 'all' ) === FALSE ) {
					$groups = tx_sevenpack_utility::explode_intval ( ',', $groups );
				} else {
					$groups = 'all';
				}

				if ( $all || ( sizeof ( $ext ) > 0 ) ) {
					$rest[$table][$field] = array (
						'hide_all' => $all,
						'hide_ext' => $ext,
						'fe_groups' => $groups
					);
				}
			}
		}

		//t3lib_div::debug ( $rest );
	}


	/**
	 * This initializes all filters before the browsing filter
	 *
	 * @return FALSE or an error message
	 */
	function init_filters ( )
	{
		$this->extConf['filters'] = array();
		$this->init_flexform_filter();
		$this->init_selection_filter();
	}


	/**
	 * This initializes filter array from the flexform
	 *
	 * @return FALSE or an error message
	 */
	function init_flexform_filter ( )
	{
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
				if ( strpos ( $y, '-' ) === FALSE ) {
					if ( is_numeric ( $y ) )
						$f['years'][] = intval ( $y );
				} else {
					$range = array();
					$elms = tx_sevenpack_utility::explode_trim ( '-', $y, FALSE );
					if ( is_numeric ( $elms[0] ) )
						$range['from'] = intval ( $elms[0] );
					if ( is_numeric ( $elms[1] ) )
						$range['to'] = intval ( $elms[1] );
					if ( sizeof ( $range ) > 0 )
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
				$author = array();
				if ( strlen ( $parts[0] ) > 0 )
					$author['surname'] = $parts[0];
				if ( strlen ( $parts[1] ) > 0 )
					$author['forename'] = $parts[1];
				if ( sizeof ( $author ) > 0 )
					$f['authors'][] = $author;
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
		if ( $this->pi_getFFvalue ( $ff, 'enable_keywords', $fSheet ) ) {
			$f = array();
			$f['rule'] = $this->pi_getFFvalue ( $ff, 'keywords_rule', $fSheet);
			$f['rule'] = intval ( $f['rule'] );
			$kw = $this->pi_getFFvalue ( $ff, 'keywords', $fSheet);
			if ( strlen ( $kw ) > 0 ) {
				$words = tx_sevenpack_utility::multi_explode_trim ( array ( ',', "\r" , "\n" ), $kw, TRUE );
				foreach ( $words as &$word ) {
					$word = $this->ra->search_word ( $word, $this->extConf['charset']['upper'] );
				}
				$f['words'] = $words;
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
				$words = tx_sevenpack_utility::multi_explode_trim ( array ( ',', "\r" , "\n" ), $kw, TRUE );
				foreach ( $words as &$word ) {
					$word = $this->ra->search_word ( $word, $this->extConf['charset']['upper'] );
				}
				$f['words'] = $words;
				$filter['all'] = $f;
			}
		}

		//t3lib_div::debug ( array ( 'pid list final' => $pid_list) );
		//t3lib_div::debug ( $filter );

	}


	/**
	 * This initializes the selction filter array from the piVars
	 *
	 * @return FALSE or an error message
	 */
	function init_selection_filter ( )
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
	 * Initializes an array which contains subparts of the
	 * html templates.
	 *
	 * @return TRUE on error, FALSE otherwise
	 */
	function init_template ()
	{
		$err = array();

		// Allready initialized?
		if ( isset ( $this->template['LIST_VIEW'] ) )
			return $err;

		$this->template = array();
		$this->item_tmpl = array();

		// List blocks
		$list_blocks = array (
			'YEAR_BLOCK', 'BIBTYPE_BLOCK', 'SPACER_BLOCK' 
		);

		// Bibtype data blocks
		$bib_types = array ();
		foreach ( $this->ra->allBibTypes as $val ) {
			$bib_types[] = strtoupper ( $val ) . '_DATA';
		}
		$bib_types[] = 'DEFAULT_DATA';
		$bib_types[] = 'ITEM_BLOCK';

		// Misc navigation blocks
		$navi_blocks = array ( 'EXPORT_NAVI_BLOCK', 
			'IMPORT_NAVI_BLOCK', 'NEW_ENTRY_NAVI_BLOCK' );

		// Fetch the template file list
		$tlist =& $this->conf['templates.'];
		if ( !is_array ( $tlist ) ) {
			$err[] = 'HTML templates are not set in TypoScript';
			return $err;
		}

		$info = array (
			'main' => array (
				'file' => $tlist['main'],
				'parts' => array ( 'LIST_VIEW' )
			),
			'list_blocks' => array (
				'file' => $tlist['list_blocks'],
				'parts' => $list_blocks
			),
			'list_items' => array (
				'file' => $tlist['list_items'],
				'parts' => $bib_types,
				'no_warn' => TRUE
			),
			'navi_misc' => array (
				'file' => $tlist['navi_misc'],
				'parts' => $navi_blocks,
			)
		);

		//t3lib_div::debug( $info );

		foreach ( $info as $key => $val ) {
			if ( strlen ( $val['file'] ) == 0 ) {
				$err[] = 'HTML template file for \'' . $key . '\' is not set' ;
				continue;
			}
			$tmpl = $this->cObj->fileResource ( $val['file'] );
			if ( strlen ( $tmpl ) == 0 ) {
				$err[] = 'The HTML template file \'' . $val['file'] . '\' for \'' . $key . 
					'\' is not readable or empty';
				continue;
			}
			foreach ( $val['parts'] as $part ) {
				$ptag = '###' . $part . '###';
				$pstr = $this->cObj->getSubpart ( $tmpl, $ptag );
				// Error message
				if ( ( strlen ( $pstr ) == 0 ) && !$val['no_warn'] ) {
					 $err[] = 'The subpart \'' . $ptag . '\' in the HTML template file \''
						 . $val['file'] . '\' is empty';
				}
				$this->template[$part] = $pstr;
			}
		}

		//t3lib_div::debug( array ( $this->template ) );

		return $err;
	}


	/** 
	 * Initialize the edit icons
	 *
	 * @return void
	 */
	function init_edit_icons ()
	{
		$list = array ();
		$more = $this->conf['edit_icons.'];
		if ( is_array ( $more ) )
			$list = array_merge ( $list, $more );

		$tmpl =& $GLOBALS['TSFE']->tmpl;
		foreach ( $list as $key => $val ) {
			$this->icon_src[$key] = $tmpl->getFileName ( $base . $val );
		}
	}


	/** 
	 * Initialize the list view icons
	 *
	 * @return void
	 */
	function init_list_icons ()
	{
		$list = array ( 
			'default' => 'EXT:cms/tslib/media/fileicons/default.gif' );
		$more = $this->conf['file_icons.'];
		if ( is_array ( $more ) ) {
			$list = array_merge ( $list, $more );
		}

		$tmpl =& $GLOBALS['TSFE']->tmpl;
		$this->icon_src['files'] = array();
		$ic =& $this->icon_src['files'];
		foreach ( $list as $key => $val ) {
			$ic['.'.$key] = $tmpl->getFileName ( $val );
		}
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
			foreach ( $this->LOCAL_LANG as $lang => $list ) {
				foreach ( $list as $key => $word ) {
					$tmpLang[$lang][$key] = $word;
				}
			}
			$this->LOCAL_LANG = $tmpLang;

			if ( $this->altLLkey ) {
				$tmpLang = t3lib_div::readLLfile ( $file, $this->altLLkey );
				foreach ( $this->LOCAL_LANG as $lang => $list ) {
					foreach ( $list as $key => $word ) {
						$tmpLang[$lang][$key] = $word;
					}
				}
				$this->LOCAL_LANG = $tmpLang;
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

		$vars = array_merge ( $this->extConf['link_vars'], $vars );
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
		$keep = array ( 'uid', 'editor_mode', 'editor' );
		foreach ( $keep as $k ) {
			$pvar =& $pv[$k];
			if ( is_string ( $pvar ) || is_array ( $pvar ) || is_numeric ( $pvar ) ) {
				$vars[$k] = $pvar;
			}
		}
		return $this->get_link_url ( $vars, $auto_cache, $current_record );
	}


	/**
	 * Returns an instance of a navigation bar class
	 *
	 * @return The url
	 */
	function get_navi_instance ( $type )
	{
		$file = 'EXT:'.$this->extKey.'/pi1/class.' . $type . '.php';
		require_once ( $GLOBALS['TSFE']->tmpl->getFileName ( $file ) );
		$obj = t3lib_div::makeInstance ( $type );
		$obj->initialize ( $this );
		return $obj;
	}


	/**
	 * This function prepares database content fot HTML output
	 *
	 * @return The string filtered for html output
	 */
	function filter_pub_html ( $str, $hsc = FALSE ) {
		$charset = $this->extConf['charset']['upper'];
		if ( $hsc ) 
			$str = htmlspecialchars ( $str, ENT_QUOTES, $charset );

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

		// Handle illegal ampersands
		if ( !( strpos ( $str, '&' ) === FALSE ) ) {
			$str = tx_sevenpack_utility::fix_html_ampersand ( $str );
		}

		$str = $this->filter_pub_html ( $str, $hsc );
		return $str;
	}


	/**
	 * This function composes the html-view of a set of publications
	 *
	 * @return The list view
	 */
	function list_view ()
	{
		$this->setup_search_navi ();  // setup year navigation
		$this->setup_year_navi ();  // setup year navigation
		$this->setup_author_navi (); // setup author navigation
		$this->setup_pref_navi ();  // setup preferences navigation
		$this->setup_page_navi ();  // setup page navigation

		$this->setup_new_entry_navi ();  // setup new entry button

		$this->setup_export_navi ();  // setup export links
		$this->setup_import_navi ();  // setup import link
		$this->setup_statistic_navi ();  // setup statistic element

		$this->setup_spacer ();  // setup spacer
		$this->setup_top_navigation ();  // setup page navigation element

		$this->setup_items (); // setup the publication items

		//t3lib_div::debug ( $this->template['LIST_VIEW'] );

		return $this->template['LIST_VIEW'];
	}


	/** 
	 * Returns the year navigation bar
	 *
	 * @return A HTML string with the year navigation bar
	 */
	function setup_search_navi ()
	{
		$trans = array();
		$hasStr = '';
		$cObj =& $this->cObj;

		if ( $this->extConf['show_nav_search'] ) {
			$trans = $this->extConf['search_navi']['obj']->translator();
			$hasStr = array ( '', '' );

			if ( strlen ( $trans['###SEARCH_NAVI_TOP###'] ) > 0 )
				$this->extConf['has_top_navi'] = TRUE;
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $cObj->substituteSubpart ( $tmpl, '###HAS_SEARCH_NAVI###', $hasStr );
		$tmpl = $cObj->substituteMarkerArrayCached ( $tmpl, $trans );
	}


	/** 
	 * Returns the year navigation bar
	 *
	 * @return A HTML string with the year navigation bar
	 */
	function setup_year_navi ()
	{
		$trans = array();
		$hasStr = '';
		$cObj =& $this->cObj;

		if ( $this->extConf['show_nav_year'] ) {
			$obj = $this->get_navi_instance ( 'tx_sevenpack_navi_year' );

			$trans = $obj->translator();
			$hasStr = array ( '', '' );

			if ( strlen ( $trans['###YEAR_NAVI_TOP###'] ) > 0 )
				$this->extConf['has_top_navi'] = TRUE;
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $cObj->substituteSubpart ( $tmpl, '###HAS_YEAR_NAVI###', $hasStr );
		$tmpl = $cObj->substituteMarkerArrayCached ( $tmpl, $trans );
	}


	/**
	 * Sets up the author navigation bar
	 *
	 * @return void
	 */
	function setup_author_navi ()
	{
		$trans = array();
		$hasStr = '';
		$cObj =& $this->cObj;

		if ( $this->extConf['show_nav_author'] ) {
			$trans = $this->extConf['author_navi']['obj']->translator();
			$hasStr = array ( '', '' );

			if ( strlen ( $trans['###AUTHOR_NAVI_TOP###'] ) > 0 )
				$this->extConf['has_top_navi'] = TRUE;
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $cObj->substituteSubpart ( $tmpl, '###HAS_AUTHOR_NAVI###', $hasStr );
		$tmpl = $cObj->substituteMarkerArrayCached ( $tmpl, $trans );
	}


	/**
	 * Sets up the page navigation bar
	 *
	 * @return void
	 */
	function setup_page_navi ()
	{
		$trans = array();
		$hasStr = '';
		$cObj =& $this->cObj;

		if ( $this->extConf['show_nav_page'] ) {
			$obj = $this->get_navi_instance ( 'tx_sevenpack_navi_page' );

			$trans = $obj->translator();
			$hasStr = array ( '', '' );

			if ( strlen ( $trans['###PAGE_NAVI_TOP###'] ) > 0 )
				$this->extConf['has_top_navi'] = TRUE;
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $cObj->substituteSubpart ( $tmpl, '###HAS_PAGE_NAVI###', $hasStr );
		$tmpl = $cObj->substituteMarkerArrayCached ( $tmpl, $trans );
	}


	/**
	 * Sets up the preferences navigation bar
	 *
	 * @return void
	 */
	function setup_pref_navi ()
	{
		$trans = array();
		$hasStr = '';
		$cObj =& $this->cObj;

		if ( $this->extConf['show_nav_pref'] ) {
			$trans = $this->extConf['pref_navi']['obj']->translator();
			$hasStr = array ( '', '' );

			if ( strlen ( $trans['###PREF_NAVI_TOP###'] ) > 0 )
				$this->extConf['has_top_navi'] = TRUE;
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $cObj->substituteSubpart ( $tmpl, '###HAS_PREF_NAVI###', $hasStr );
		$tmpl = $cObj->substituteMarkerArrayCached ( $tmpl, $trans );
	}


	/** 
	 * Setup the add-new-entry element
	 *
	 * @return void
	 */
	function setup_new_entry_navi ()
	{
		$linkStr = '';
		$hasStr = '';

		if ( $this->extConf['edit_mode'] )  {
			$tmpl = $this->enum_condition_block ( $this->template['NEW_ENTRY_NAVI_BLOCK'] );
			$linkStr = $this->get_new_manipulator ( );
			$linkStr = $this->cObj->substituteMarker ( $tmpl, '###NEW_ENTRY###', $linkStr );
			$hasStr = array ( '','' );
			//t3lib_div::debug ( $linkStr );
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $this->cObj->substituteSubpart ( $tmpl, '###HAS_NEW_ENTRY###', $hasStr );
		$tmpl = $this->cObj->substituteMarker ( $tmpl, '###NEW_ENTRY###', $linkStr );
	}


	/** 
	 * Setup the statistic element
	 *
	 * @return void
	 */
	function setup_statistic_navi ()
	{
		$trans = array();
		$hasStr = '';
		$cObj =& $this->cObj;

		if ( $this->extConf['show_nav_stat'] ) {
			$obj = $this->get_navi_instance ( 'tx_sevenpack_navi_stat' );

			$trans = $obj->translator();
			$hasStr = array ( '', '' );

			if ( strlen ( $trans['###STAT_NAVI_TOP###'] ) > 0 )
				$this->extConf['has_top_navi'] = TRUE;
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $this->cObj->substituteSubpart ( $tmpl, '###HAS_STAT_NAVI###', $hasStr );
		$tmpl = $cObj->substituteMarkerArrayCached ( $tmpl, $trans );
	}


	/** 
	 * Setup the export-link element 
	 *
	 * @return void
	 */
	function setup_export_navi ()
	{
		$str = '';
		$hasStr = '';

		if ( $this->extConf['show_nav_export'] )  {

			$cfg = array();
			if ( is_array ( $this->conf['export.'] ) )
				$cfg =& $this->conf['export.'];
			$extConf =& $this->extConf['export_navi'];

			$exports = array();

			// Export label
			$label = $this->get_ll ( $cfg['label'] );
			$label = $this->cObj->stdWrap ( $label, $cfg['label.'] );

			$mod_all = array ( 'bibtex', 'xml' );

			foreach ( $mod_all as $mod ) {
				if ( in_array ( $mod, $extConf['modes'] ) ) {
					$title = $this->get_ll ( 'export_' . $mod . 'LinkTitle', $mod, TRUE );
					$txt = $this->get_ll ( 'export_' . $mod );
					$link = $this->get_link ( $txt, array ( 'export' => $mod ), 
						FALSE, array ( 'title' => $title ) );
					$link = $this->cObj->stdWrap ( $link, $cfg[$mod . '.'] );
					$exports[] = $link;
				}
			}

			$sep = '&nbsp;';
			if ( array_key_exists ( 'separator', $cfg ) )
				$sep = $this->cObj->stdWrap ( $cfg['separator'], $cfg['separator.'] );

			// Export string
			$exports = implode ( $sep, $exports );

			// The translator
			$trans = array();
			$trans['###LABEL###'] = $label;
			$trans['###EXPORTS###'] = $exports;
 
			$block = $this->enum_condition_block ( $this->template['EXPORT_NAVI_BLOCK'] );
			$block = $this->cObj->substituteMarkerArrayCached ( $block, $trans, array() );
			$hasStr = array ( '','' );
		}

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $this->cObj->substituteSubpart ( $tmpl, '###HAS_EXPORT###', $hasStr );
		$tmpl = $this->cObj->substituteMarker ( $tmpl, '###EXPORT###', $block );
	}


	/** 
	 * Setup the import-link element in the
	 * HTML-template
	 *
	 * @return void
	 */
	function setup_import_navi ()
	{
		$str = '';
		$hasStr = '';

		if ( $this->extConf['edit_mode'] )  {

			$cfg = array();
			if ( is_array ( $this->conf['import.'] ) )
				$cfg =& $this->conf['import.'];

			$str = $this->enum_condition_block ( $this->template['IMPORT_NAVI_BLOCK'] );
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

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $this->cObj->substituteSubpart ( $tmpl, '###HAS_IMPORT###', $hasStr );
		$tmpl = $this->cObj->substituteMarker ( $tmpl, '###IMPORT###', $str );
	}


	/** 
	 * Setup the top navigation block
	 *
	 * @return void
	 */
	function setup_top_navigation ()
	{
		$hasStr = '';
		if ( $this->extConf['has_top_navi'] ) {
			$hasStr = array ( '', '' );
		}
		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $this->cObj->substituteSubpart ( $tmpl, '###HAS_TOP_NAVI###', $hasStr );
	}


	/** 
	 * Prepares database publication data for displaying
	 *
	 * @return The procesed publication data array
	 */
	function prepare_pub_display ( $pub, &$warnings = array(), $show_hidden = false ) {

		// The error list
		$d_err = array();

		// Prepare processed row data
		$pdata = $pub;
		foreach ( $this->ra->refFields as $f ) {
			$pdata[$f] = $this->filter_pub_html_display ( $pdata[$f] );
		}

		// Preformat some data
		// Bibtype
		$pdata['bibtype_short'] = $this->ra->allBibTypes[$pdata['bibtype']];
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

		// Reviewed
		if ( $pub['reviewed'] > 0 ) {
			$pdata['reviewed'] = $this->get_ll ( 'label_yes', 'Yes', TRUE ) ;
		} else {
			$pdata['reviewed'] = $this->get_ll ( 'label_no', 'Yes', TRUE ) ;
		}

		// In library
		if ( $pub['in_library'] > 0 ) {
			$pdata['in_library'] = $this->get_ll ( 'label_yes', 'Yes', TRUE ) ;
		} else {
			$pdata['in_library'] = $this->get_ll ( 'label_no', 'Yes', TRUE ) ;
		}

		//
		// Copy field values
		//
		$charset = $this->extConf['charset']['upper'];
		$url_max = 40;
		if ( is_numeric ( $this->conf['max_url_string_length'] ) > 0 ) {
			$url_max = intval ( $this->conf['max_url_string_length'] );
		}

		// Iterate through reference fields
		foreach ( $this->ra->refFields as $f ) {
			// Trim string
			$val = trim ( strval ( $pdata[$f] ) );

			// Check restrictions
			if ( strlen ( $val ) > 0 )  {
				if ( $this->check_field_restriction ( 'ref', $f, $val, $show_hidden ) ) {
					$val = '';
					$pdata[$f] = $val;
				}
			}

			// Treat some fields
			if ( strlen ( $val ) > 0 )  {
				switch ( $f ) {
					case 'file_url':
					case 'web_url':
					case 'web_url2':
						$val = tx_sevenpack_utility::fix_html_ampersand ( $val );
						$pdata[$f] = $val;
						$pdata[$f.'_short'] = tx_sevenpack_utility::crop_middle ( 
							$val, $url_max, $charset );
						break;
					case 'DOI':
						$pdata[$f] = $val;
						$pdata['DOI_url'] = 'http://dx.doi.org/' . $val;
					default:
						$pdata[$f] = $val;
				}
			}
		}

		// Multi fields 
		$multi = array ( 
			'authors' => $this->ra->authorFields 
		);
		foreach ( $multi as $table => $fields ) {
			$elms =& $pdata[$table];
			if ( !is_array ( $elms ) ) {
				continue;
			}
			foreach ( $elms as &$elm ) {
				foreach ( $fields as $field ) {
					$val = $elm[$field];
					// Check restrictions
					if ( strlen ( $val ) > 0 )  {
						if ( $this->check_field_restriction ( $table, $field, $val ) ) {
							$val = '';
							$elm[$field] = $val;
						}
					}
					//t3lib_div::debug ( array ( 'field' => $field, 'value' => $val ) );
					//t3lib_div::debug ( array ( 'elm' => $elm ) );
				}
			}
		}

		// Format the author string
		$pdata['authors'] = $this->get_item_authors_html ( $pdata['authors'] );

		// Editors
		if ( strlen ( $pdata['editor'] ) > 0 ) {
			$editors = tx_sevenpack_utility::explode_author_str ( $pdata['editor'] );
			$lst = array();
			foreach ( $editors as $ed ) {
				$app = '';
				if ( strlen ( $ed['forename'] ) > 0 ) $app .= $ed['forename'] . ' ';
				if ( strlen ( $ed['surname'] ) > 0 ) $app .= $ed['surname'];
				$app = $this->cObj->stdWrap ( $app, $this->conf['field.']['editor_each.'] );
				$lst[] = $app;
			}

			$and = ' ' . $this->get_ll ( 'label_and', 'and', TRUE ) . ' ';
			$pdata['editor'] = tx_sevenpack_utility::implode_and_last (
				$lst, ', ', $and );
		}

		// Automatic url
		$order = tx_sevenpack_utility::explode_trim ( ',', $this->conf['auto_url_order'], TRUE );
		$pdata['auto_url'] = $this->get_auto_url ( $pdata, $order );
		$pdata['auto_url_short'] = tx_sevenpack_utility::crop_middle (
			$pdata['auto_url'], $url_max, $charset );

		//
		// Do data checks
		//
		if ( $this->extConf['edit_mode'] ) {
			$w_cfg =& $this->conf['editor.']['list.']['warnings.'];
			//
			// Local file does not exist
			//
			$type = 'file_nexist';
			if ( $w_cfg[$type] ) {
				$msg = $this->get_ll ( 'editor_error_file_nexist' );
				$file = $pub['file_url'];
				//t3lib_div::debug ( $file );
				$err = tx_sevenpack_utility::check_file_nexist ( $file, $type, $msg );
				if ( is_array ( $err ) )
					$d_err[] = $err;
			}
		}

		$warnings = $d_err;
		//t3lib_div::debug ( $warnings );

		return $pdata;
	}


	/** 
	 * Prepares the cObj->data array for a reference
	 *
	 * @return The procesed publication data array
	 */
	function prepare_pub_cObj_data ( $pdata ) {
		// Item data
		$this->cObj->data = $pdata;
		$data =& $this->cObj->data;
		// Needed since stdWrap/Typolink applies htmlspecialchars to url data
		$data['file_url'] = htmlspecialchars_decode ( $pdata['file_url'], ENT_QUOTES );
		$data['web_url'] = htmlspecialchars_decode ( $pdata['web_url'], ENT_QUOTES );
		$data['web_url2'] = htmlspecialchars_decode ( $pdata['web_url2'], ENT_QUOTES );
		$data['DOI_url'] = htmlspecialchars_decode ( $pdata['DOI_url'], ENT_QUOTES );
		$data['auto_url'] = htmlspecialchars_decode ( $pdata['auto_url'], ENT_QUOTES );
	}


	/** 
	 * Returns the html interpretation of the publication
	 * item as it is defined in the html template
	 *
	 * @return HTML string for a single item in the list view
	 */
	function get_item_html ( $pdata, $templ )
	{
		//t3lib_div::debug ( array ( 'get_item_html($pdata)' => $pdata ) );
		$translator = array();
		$cObj =& $this->cObj;
		$conf =& $this->conf;

		$bib_str = $pdata['bibtype_short'];
		$all_base = 'rnd' . strval ( rand() ) . 'rnd';
		$all_wrap = $all_base;

		// Prepare the translator
		// Remove empty field marker from the template
		$fields = $this->ra->pubFields;
		$fields[] = 'file_url_short';
		$fields[] = 'web_url_short';
		$fields[] = 'web_url2_short';
		$fields[] = 'auto_url';
		$fields[] = 'auto_url_short';
		foreach ( $fields as $f ) {
			$upStr = strtoupper ( $f );
			$tkey = '###'.$upStr.'###';
			$hasStr = '';
			$translator[$tkey] = '';

			$val = strval ( $pdata[$f] );

			if ( strlen ( $val ) > 0 )  {
				// Wrap default or by bibtype
				$stdWrap = array();
				$stdWrap = $conf['field.'][$f.'.'];
				if ( is_array ( $conf['field.'][$bib_str.'.'][$f.'.'] ) )
					$stdWrap = $conf['field.'][$bib_str.'.'][$f.'.'];
				//t3lib_div::debug ( $stdWrap );
				if ( isset ( $stdWrap['single_view_link'] ) ) {
					$val = $this->get_link ( $val, array ( 'show_uid' => strval ( $pdata['uid'] ) ) );
				}
				$val = $cObj->stdWrap ( $val, $stdWrap );

				if ( strlen ( $val ) > 0 ) {
					$hasStr =  array ( '', '' );
					$translator[$tkey] = $val;
				}
			}

			$templ = $cObj->substituteSubpart ( $templ, '###HAS_'.$upStr.'###', $hasStr );
		}

		// Reference wrap
		$all_wrap = $cObj->stdWrap ( $all_wrap, $conf['reference.'] );

		// Embrace hidden references with wrap
		if ( ( $pdata['hidden'] != 0 ) && is_array ( $conf['editor.']['list.']['hidden.'] ) ) {
			$all_wrap = $cObj->stdWrap ( $all_wrap, $conf['editor.']['list.']['hidden.'] );
		}

		$templ = $cObj->substituteMarkerArrayCached ( $templ, $translator );
		$templ = $cObj->substituteMarkerArrayCached ( $templ, $this->label_translator );

		// Wrap elements with an anchor
		$url_wrap = array ( '', '' );
		if ( strlen ( $pdata['file_url'] ) > 0 ) {
			$url_wrap = $cObj->typolinkWrap ( array ( 'parameter' => $pdata['auto_url'] ) );
		}
		$templ = $cObj->substituteSubpart ( $templ, '###URL_WRAP###', $url_wrap );

		$all_wrap = explode ( $all_base, $all_wrap );
		$templ = $cObj->substituteSubpart ( $templ, '###REFERENCE_WRAP###', $all_wrap );

		// remove empty divs
		$templ = preg_replace ( "/<div[^>]*>[\s\r\n]*<\/div>/", "\n", $templ );
		// remove multiple line breaks
		$templ = preg_replace ( "/\n+/", "\n", $templ );
		//t3lib_div::debug ( $templ );

		return $templ;
	}


	/** 
	 * Returns the authors string for a publication
	 *
	 * @return void
	 */
	function get_item_authors_html ( $authors ) {
		$res = '';
		$charset = $this->extConf['charset']['upper'];

		// Load publication data into cObj
		$cObj =& $this->cObj;
		$cObj_restore = $cObj->data;

		// Format the author string$this->
		$and = ' '.$this->get_ll ( 'label_and', 'and', TRUE ).' ';

		$max_authors = abs ( intval ( $this->extConf['max_authors'] ) );
		$last_author = sizeof ( $authors ) - 1;
		$cut_authors = FALSE;
		if ( ( $max_authors > 0 ) && ( sizeof ( $authors ) > $max_authors ) ) {
			$cut_authors = TRUE;
			if ( sizeof ( $authors ) == ( $max_authors + 1 ) ) {
				$last_author = $max_authors - 2;
			} else {
				$last_author = $max_authors - 1;
			}
			$and = '';
		}
		$last_author = max ( $last_author, 0 );
		
		//t3lib_div::debug ( array ( 'authors' => $authors, 'max_authors' => $max_authors, 'last_author' => $last_author ) );

		$hl_authors = $this->extConf['highlight_authors'] ? TRUE : FALSE;

		$link_fields  = $this->extConf['author_sep'];
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

		$icon_img =& $this->extConf['author_icon_img'];

		$elements = array();
		// Iterate through authors
		for ( $i_a=0; $i_a<=$last_author; $i_a++ ) {
			$a =& $authors[$i_a];
			//t3lib_div::debug ( $a );

			// Init cObj data
			$cObj->data = $a;
			$cObj->data['url'] = htmlspecialchars_decode ( $a['url'], ENT_QUOTES );

			// The forename
			$a_fn = trim ( $a['forename'] );
			if ( strlen ( $a_fn ) > 0 ) {
				$a_fn = $this->filter_pub_html_display ( $a_fn );
				$a_fn = $this->cObj->stdWrap ( $a_fn, $this->conf['authors.']['forename.'] );
			}

			// The surname
			$a_sn = trim ( $a['surname'] );
			if ( strlen ( $a_sn ) > 0 ) {
				$a_sn = $this->filter_pub_html_display ( $a_sn );
				$a_sn = $this->cObj->stdWrap ( $a_sn, $this->conf['authors.']['surname.'] );
			}

			// The link icon
			$cr_link = FALSE;
			$a_icon = '';
			foreach ( $this->extConf['author_lfields'] as $field ) {
				$val = trim ( strval ( $a[$field] ) );
				if ( ( strlen ( $val ) > 0 ) && ( $val != '0' ) ) {
					$cr_link = TRUE;
					break;
				}
			}
			if ( $cr_link && ( strlen ( $icon_img ) > 0 ) ) {
				$wrap = $this->conf['authors.']['url_icon.'];
				if ( is_array ( $wrap ) ) {
					if ( is_array ( $wrap['typolink.'] ) ) {
						$title = $this->get_ll ( 'link_author_info', 'Author info', TRUE );
						$wrap['typolink.']['title'] = $title;
					}
					$a_icon = $this->cObj->stdWrap ( $icon_img, $wrap );
				}
			}

			// Compose names
			$a_str = str_replace ( 
				array ( '###FORENAME###', '###SURNAME###', '###URL_ICON###' ), 
				array ( $a_fn, $a_sn, $a_icon ), $a_tmpl );

 			// apply stdWrap
			$stdWrap = $this->conf['field.']['author.'];
			if ( is_array ( $this->conf['field.'][$bib_str.'.']['author.'] ) ) {
				$stdWrap = $this->conf['field.'][$bib_str.'.']['author.'];
			}
			$a_str = $this->cObj->stdWrap ( $a_str, $stdWrap );

			// Wrap the filtered authors with a highlightning class on demand
			if ( $hl_authors ) {
				foreach ( $filter_authors as $fa ) {
					if ( $a['surname'] == $fa['surname'] ) {
						if ( !$fa['forename'] || ($a['forename'] == $fa['forename']) ) {
							$a_str = $this->cObj->stdWrap ( 
								$a_str, $this->conf['authors.']['highlight.'] );
							break;
						}
					}
				}
			}

			// Append author name
			$elements[] = $a_str;

			// Append 'et al.'
			if ( $cut_authors && ( $i_a == $last_author ) ) {
				// Append et al.
				$et_al = $this->get_ll ( 'label_et_al', 'et al.', TRUE );
				$et_al = ( strlen ( $et_al ) > 0 ) ? ' '.$et_al : '';

				if ( strlen ( $et_al ) > 0 ) {
					$wrap = FALSE;
	
					// Highlight "et al." on demand
					if ( $hl_authors ) {
						for ( $j = $last_author + 1; $j < sizeof ( $authors ); $j++ ) {
							$a_et = $authors[$j];
							foreach ( $filter_authors as $fa ) {
								if ( $a_et['surname'] == $fa['surname'] ) {
									if ( !$fa['forename'] 
										|| ( $a_et['forename'] == $fa['forename'] ) ) 
									{
										$wrap = $this->conf['authors.']['highlight.'];
										$j = sizeof ( $authors );
										break;
									}
								}
							}
						}
					}
	
					if ( is_array ( $wrap ) ) {
						$et_al = $this->cObj->stdWrap ( $app, $wrap );
					}
					$wrap = $this->conf['authors.']['et_al.'];
					$et_al = $this->cObj->stdWrap ( $et_al, $wrap );
					$elements[] = $et_al;
				}
			}
		}

		//t3lib_div::debug ( $elements );
		$res = tx_sevenpack_utility::implode_and_last ( $elements, $a_sep, $and );

		// Restore cObj data
		$cObj->data = $cObj_restore;

		return $res;
	}


	/** 
	 * Setup items in the html-template
	 *
	 * @return void
	 */
	function prepare_item_setup ()
	{
		$cObj =& $this->cObj;
		$conf =& $this->conf;

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
		$this->extConf['author_lfields'] = 'url';
		if ( isset ( $conf['authors.']['url_icon_fields'] ) ) {
			$this->extConf['author_lfields'] = 
				tx_sevenpack_utility::explode_trim ( ',', 
					$conf['authors.']['url_icon_fields'], TRUE );
		}

		// Acquire author url icon
		$src = trim ( $this->conf['authors.']['url_icon_file'] );
		$img = '';
		if ( strlen ( $src ) > 0 ) {
			$src = $GLOBALS['TSFE']->tmpl->getFileName ( $src );
			$src = htmlspecialchars ( $src, ENT_QUOTES, $charset );
			$alt = $this->get_ll ( 'img_alt_person', 'Author image', TRUE );
			$img = '<img';
			$img .= ' src="' . $src . '"';
			$img .= ' alt="' . $alt . '"';
			$class =& $this->conf['authors.']['url_icon_class'];
			if ( is_string ( $class ) ) {
				$img .= ' class="' . $class . '"';
			}
			$img .= '/>';
		}
		$this->extConf['author_icon_img'] = $img;

	}


	/** 
	 * Setup items in the html-template
	 *
	 * @return void
	 */
	function setup_items ()
	{
		$items = array();

		// Time measurment
		//$t_start = microtime( TRUE );

		// Aliases
		$ra =& $this->ra;
		$cObj =& $this->cObj;
		$conf =& $this->conf;
		$filters =& $this->extConf['filters'];

		// Store cObj data
		$cObj_restore = $cObj->data;

		$this->prepare_item_setup();

		// Initialize the label translator
		$this->label_translator = array();
		$lt =& $this->label_translator;
		$labels = array (
			'abstract',
			'annotation',
			'chapter',
			'doc_number',
			'doi',
			'edition',
			'editor',
			'ISBN',
			'ISSN',
			'keywords',
			'tags',
			'note',
			'of_series',
			'page',
			'publisher',
			'references',
			'report_number',
			'volume',
		);

		foreach ( $labels as $label ) {
			$up = strtoupper ( $label );
			$val = $this->get_ll ( 'label_'.$label );
			$val = $cObj->stdWrap ( $val, $conf['label.'][$label.'.'] );
			$lt['###LABEL_'.$up.'###'] = $val;
		}

		// block templates
		$item_tmpl = array();
		$item_block = $this->enum_condition_block ( $this->template['ITEM_BLOCK'] );
		$year_block = $this->enum_condition_block ( $this->template['YEAR_BLOCK'] );
		$bib_block = $this->enum_condition_block ( $this->template['BIBTYPE_BLOCK'] );

		// Initialize the enumeration template
		$eid = 'page';
		switch ( intval ( $this->extConf['enum_style'] ) ) {
			case $this->ENUM_ALL:
				$eid = 'all'; break;
			case $this->ENUM_BULLET:
				$eid = 'bullet'; break;
			case $this->ENUM_EMPTY:
				$eid = 'empty'; break;
			case $this->ENUM_FILE_ICON:
				$eid = 'file_icon'; break;
		}
		$enum_base = strval ( $conf['enum.'][$eid] );
		$enum_wrap = $conf['enum.'][$eid.'.'];

		// Warning cfg
		$w_cfg =& $this->conf['editor.']['list.']['warn_box.'];
		$ed_mode = $this->extConf['edit_mode'];

		if ( $this->extConf['d_mode'] == $this->D_Y_SPLIT ) {
			$this->extConf['split_years'] = TRUE;
		}

		// Database accessor initialization
		$ra->mFetch_initialize();

		// Determine publication numbers
		$pubs_before = 0;
		if ( $this->extConf['d_mode'] == $this->D_Y_NAV ) {
			foreach ( $this->stat['year_hist'] as $y => $n ) {
				if ( $y == $this->extConf['year'] )
					break;
				$pubs_before += $n;
			}
		}

		$prevBibType = -1;
		$prevYear = -1;

		// Initialize counters
		$limit_start = intval ( $filters['br_page']['limit']['start'] );
		$i_page = $this->stat['num_page'] - $limit_start;
		$i_page_delta = -1;
		if ( $this->extConf['date_sorting'] == $this->SORT_ASC ) {
			$i_page = $limit_start + 1;
			$i_page_delta = 1;
		}

		$i_subpage = 1;
		$i_bibtype = 1;

		// Start the fetch loop
		while ( $pub = $ra->mFetch ( ) )  {
			// Get prepared publication data
			$warnings = array();
			$pdata = $this->prepare_pub_display ( $pub, $warnings );

			// Item data
			$this->prepare_pub_cObj_data ( $pdata );

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
			$tmpl = $item_tmpl[$pdata['bibtype']];
			if ( strlen ( $tmpl ) == 0 ) {
				$key = strtoupper ( $pdata['bibtype_short'] ) . '_DATA';
				$tmpl = $this->template[$key];
				if ( strlen ( $tmpl ) == 0 )
					$data_block = $this->template['DEFAULT_DATA'];
				$tmpl = $cObj->substituteMarker ( $item_block,
					'###ITEM_DATA###', $tmpl );
				$item_tmpl[$pdata['bibtype']] = $tmpl;
			}

			// Initialize the translator
			$translator = array();

			$enum = $enum_base;
			$enum = str_replace ( '###I_ALL###', strval ( $i_all ), $enum );
			$enum = str_replace ( '###I_PAGE###', strval ( $i_page ), $enum );
			if ( !( strpos( $enum, '###FILE_URL_ICON###' ) === FALSE ) ) {
				$repl = $this->get_file_url_icon ( $pub['file_url'] );
				$enum = str_replace ( '###FILE_URL_ICON###', $repl, $enum );
			}
			$translator['###ENUM_NUMBER###'] = $cObj->stdWrap ( $enum, $enum_wrap );

			// Row classes
			$eo = $evenOdd ? 'even' : 'odd';

			$translator['###ROW_CLASS###'] = $conf['classes.'][$eo];

			$translator['###NUMBER_CLASS###'] = $this->prefixShort.'-enum';
			//$translator['###TITLECLASS###'] = $this->prefix_pi1.'-bibtitle';

			// Manipulators
			$translator['###MANIPULATORS###'] = '';
			$manip_edit = '';
			$manip_hide = '';
			$manip_all = array();
			$subst_sub = '';
			if ( $ed_mode )  {
				$subst_sub = array ( '', '' );
				$manip_all[] = $this->get_edit_manipulator ( $pub );
				$manip_all[] = $this->get_hide_manipulator ( $pub );
				$manip_all = tx_sevenpack_utility::html_layout_table ( array ( $manip_all ) );

				$translator['###MANIPULATORS###'] = $cObj->stdWrap (
					$manip_all, $conf['editor.']['list.']['manipulators.']['all.']
				);
			}

			$tmpl = $cObj->substituteSubpart ( $tmpl, '###HAS_MANIPULATORS###', $subst_sub );

			// Year separator label
			if ( $this->extConf['split_years'] && ( $pub['year'] != $prevYear ) )  {
				$yearStr = $cObj->stdWrap ( strval ( $pub['year'] ), $conf['label.']['year.'] );
				$items[] = $cObj->substituteMarker ( $year_block, '###YEAR###', $yearStr );
				$prevBibType = -1;
			}

			// Bibtype separator label
			if ( $this->extConf['split_bibtypes'] && ($pub['bibtype'] != $prevBibType) )  {
				$bibStr = $cObj->stdWrap (
					$this->get_ll ( 'bibtype_plural_'.$pub['bibtype'], $pub['bibtype'], TRUE ),
					$conf['label.']['bibtype.']
				);
				$items[] = $cObj->substituteMarker ( $bib_block, '###BIBTYPE###', $bibStr );
			}

			// Append string for item data
			$append = '';
			if ( ( sizeof ( $warnings ) > 0 ) && $ed_mode ) {
				foreach ( $warnings as $err ) {
					$append .= $cObj->stdWrap ( $err['msg'], $w_cfg['msg.'] );
				}
				$append = $cObj->stdWrap ( $append,
						$w_cfg['all_wrap.'] );
			}
			$translator['###ITEM_APPEND###'] = $append;


			// Apply translator
			$tmpl = $cObj->substituteMarkerArrayCached ( $tmpl, $translator );

			// Pass to item processor
			$items[] = $this->get_item_html ( $pdata, $tmpl );

			// Update counters
			$i_page += $i_page_delta;
			$i_subpage++;
			$i_bibtype++;

			$prevBibType = $pub['bibtype'];
			$prevYear = $pub['year'];
		}

		// clean up
		$ra->mFetch_finish();

		// Restore cObj data
		$cObj->data = $cObj_restore;

		$items = implode ( '', $items );

		$hasStr = '';
		$no_items = '';
		if ( strlen ( $items ) > 0 ) {
			$hasStr = array ( '', '' );
		} else {
			$no_items = strval ( $this->extConf['post_items'] );
			if ( strlen ( $no_items ) == 0 ) {
				$no_items = $this->get_ll ( 'label_no_items' );
			}
			$no_items = $cObj->stdWrap ( $no_items, $conf['label.']['no_items.'] );
		}

		// Time measurment
		//$t_diff = microtime(TRUE) - $t_start;
		//$items = '<h3>'.$t_diff.'</h3>'.$items;

		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $cObj->substituteSubpart ( $tmpl, '###HAS_ITEMS###', $hasStr );
		$tmpl = $cObj->substituteMarkerArrayCached ( $tmpl, $this->label_translator );
		$tmpl = $cObj->substituteMarker ( $tmpl, '###NO_ITEMS###', $no_items );
		$tmpl = $cObj->substituteMarker ( $tmpl, '###ITEMS###', $items );
	}


	/**
	 * Returns the new entry button
	 */
	function get_new_manipulator ( ) {
		$label = $this->get_ll ( 'manipulators_new', 'New', TRUE );
		$imgSrc = 'src="'.$this->icon_src['new_record'].'"';
		$img = '<img '.$imgSrc.' alt="'.$label.'" ' . 
			'class="'.$this->prefixShort.'-new_icon" />';

		$res = $this->get_link ( $img, array('action'=>array('new'=>1)), TRUE, array('title'=>$label) );
		$res . $this->cObj->stdWrap ( $res, $this->conf['editor.']['list.']['manipulators.']['new.'] );
		return $res;
	}


	/**
	 * Returns the edit button
	 */
	function get_edit_manipulator ( $pub ) {
		// The edit button
		$label = $this->get_ll ( 'manipulators_edit', 'Edit', TRUE );
		$imgSrc = 'src="'.$this->icon_src['edit'].'"';
		$img = '<img '.$imgSrc.' alt="'.$label.'" ' . 
			'class="'.$this->prefixShort.'-edit_icon" />';

		$res = $this->get_link ( $img, 
			array ( 'action'=>array('edit'=>1),'uid'=>$pub['uid'] ), 
			TRUE, array ( 'title'=>$label ) );

		$res = $this->cObj->stdWrap ( $res, $this->conf['editor.']['list.']['manipulators.']['edit.'] );

		return $res;
	}


	/**
	 * Returns the hide button
	 */
	function get_hide_manipulator ( $pub ) {
		if ( $pub['hidden'] == 0 )  {
			$label = $this->get_ll ( 'manipulators_hide', 'Hide', TRUE );
			$imgSrc = 'src="'.$this->icon_src['hide'].'"';
			$action = array('hide'=>1);
		}  else  {
			$label = $this->get_ll ( 'manipulators_reveal', 'Reveal', TRUE );
			$imgSrc = 'src="'.$this->icon_src['reveal'].'"';
			$action = array('reveal'=>1);
		}

		$img = '<img '.$imgSrc.' alt="'.$label.'" ' . 
			'class="'.$this->prefixShort.'-hide_icon" />';
		$res = $this->get_link ( $img, 
			array ( 'action'=>$action, 'uid'=>$pub['uid'] ), 
			TRUE, array('title'=>$label) );

		$res = $this->cObj->stdWrap ( $res, $this->conf['editor.']['list.']['manipulators.']['hide.'] );

		return $res;
	}


	/**
	 * Returns TRUE if the field/value combination is restricted
	 * and should not be displayed
	 *
	 * @return TRUE (restricted) or FALSE (not restricted)
	 */
	function check_field_restriction ( $table, $field, $value, $show_hidden = false ) {
		// No value no restriction
		if ( strlen ( $value ) == 0 ) {
			return FALSE;
		}

		// Field is hidden
		if ( !$show_hidden && $this->extConf['hide_fields'][$field] ) {
			return TRUE;
		}

		// Check if local file does not exist
		if ( $field == 'file_url' ) {
			$err = tx_sevenpack_utility::check_file_nexist ( $value );
			if ( is_array ( $err ) ) {
				return TRUE;
			}
		}

		// Are there restrictions at all?
		$rest =& $this->extConf['restrict'][$table];
		if ( !is_array ( $rest ) || ( sizeof ( $rest ) == 0 ) ) {
			return FALSE;
		}

		// Check Field restrictions
		if ( is_array ( $rest[$field] ) ) {
			$rcfg =& $rest[$field];

			// Show by default
			$show = TRUE;

			// Hide on 'hide all'
			if ( $rcfg['hide_all'] ) {
				$show = FALSE;
			}

			// Hide if any extensions matches
			if ( $show && is_array ( $rcfg['hide_ext'] ) ) {
				foreach ( $rcfg['hide_ext'] as $ext ) {
					// Sanitize input
					$len = strlen ( $ext );
					if ( ( $len > 0 ) && ( strlen ( $value ) >= $len ) ) {
						$uext = strtolower ( substr ( $value, -$len ) );
						//t3lib_div::debug( array ( 'ext: ' => $ext, 'uext: ' => $uext ) );
						if ( $uext == $ext ) {
							$show = FALSE;
							break;
						}
					}
				}
			}

			// Enable if usergroup matches
			if ( !$show && isset ( $rcfg['fe_groups'] ) ) {
				$groups = $rcfg['fe_groups'];
				if ( tx_sevenpack_utility::check_fe_user_groups ( $groups ) )
					$show = TRUE;
			}

			// Restricted !
			if ( !$show ) {
				//t3lib_div::debug ( array ( 'Restrticted' => $field ) );
				return TRUE;
			}
		}

		return FALSE;
	}


	/**
	 * Prepares the virtual auto_url from the data and field order
	 *
	 * @return The generated url
	 */
	function get_auto_url ( $pdata, $order ) {
		//t3lib_div::debug( array ( 'Order: ' => $order ) );
		$url = '';

		foreach ( $order as $field ) {
			if ( strlen ( $url ) > 0 )
				break;
			$data = trim ( strval ( $pdata[$field] ) );
			if ( strlen ( $data ) > 0 ) {
				$rest = $this->check_field_restriction ( 'ref', $field, $data );
				if ( !is_array ( $rest ) ) {
					$url = $data;
					if ( $field == 'DOI' ) {
						$url = $pdata['DOI_url'];
					}
				}
			}
		}
		//t3lib_div::debug ( array ( 'auto_url: ' => $url ) );
		return $url;
	}


	/**
	 * Returns the file url icon
	 */
	function get_file_url_icon ( $url ) {
		$res = '';

		$def = FALSE;
		$sources =& $this->icon_src['files'];

		$src = strval ( $sources['.empty_default'] );
		$alt = 'default';
		if ( strlen ( $url ) > 0 ) {
			$src = $sources['.default'];

			foreach ( $sources as $ext => $file  ) {
				$len = strlen ( $ext );
				if ( strlen ( $url ) >= $len ) {
					$sub = strtolower ( substr ( $url, -$len ) );
					if ( $sub == $ext ) {
						$src = $file;
						$alt = substr ( $ext, 1 );
						break;
					}
				}
			}

		} else {
			// NOOP
		}

		if ( strlen ( $src ) > 0 ) {
			$img = '<img src="' . $src . '"';
			$img .= ' alt="' . $alt . '"';
			$class = $this->conf['enum.']['file_icon_class'];
			if ( is_string ( $class ) ) {
				$img .= ' class="' . $class . '"';
			}
			$img .= '/>';
		} else {
			$img = '&nbsp;';
		}

		$wrap = $this->conf['enum.']['file_icon_image.'];
		if ( is_array ( $wrap ) ) {
			if ( is_array ( $wrap['typolink.'] ) ) {
				$title = $this->get_ll ( 'link_get_file', 'Get file', TRUE );
				$wrap['typolink.']['title'] = $title;
			}
			$img = $this->cObj->stdWrap ( $img, $wrap );
		}
		//t3lib_div::debug ( array ( 'wrap' => $wrap ) );
		$res .= $img;

		//t3lib_div::debug ( array ( 'image: ' => $res ) );
		return $res;
	}


	/** 
	 * Removes the enumeration condition block
	 * or just the block markers
	 *
	 * @return void
	 */
	function enum_condition_block ( $templ ) 
	{
		$sub = $this->extConf['has_enum'] ? array() : '';
		$templ = $this->cObj->substituteSubpart ( 
			$templ, '###HAS_ENUM###', $sub );
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
		$tmpl =& $this->template['LIST_VIEW'];
		$tmpl = $this->cObj->substituteMarker ( $tmpl, '###SPACER###', $t_str );
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
	 * This loads the editor view
	 *
	 * @return The editor view
	 */
	function editor_view ()
	{
		require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
			'EXT:'.$this->extKey.'/pi1/class.tx_sevenpack_editor_view.php' ) );
		$sv = t3lib_div::makeInstance ( 'tx_sevenpack_editor_view' );
		$sv->initialize ( $this );
		return $sv->editor_view();
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
					'EXT:'.$this->extKey.'/pi1/class.tx_sevenpack_editor_view.php' ) );
				$sv = t3lib_div::makeInstance( 'tx_sevenpack_editor_view' );
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
		$mode = $this->extConf['export_navi']['do'];
		$title = $this->get_ll ( 'export_title' );
		$con .= '<h2>'.$title.'</h2>'."\n";

		$exp = FALSE;
		$label = '';
		$eclass = '';
		switch ( $mode ) {
			case 'bibtex':
				$eclass = 'tx_sevenpack_exporter_bibtex';
				$label = 'export_bibtex';
				break;
			case 'xml':
				$eclass = 'tx_sevenpack_exporter_xml';
				$label = 'export_xml';
				break;
			default:
				return $this->error_msg ( 'Unknown export mode' );
		}

		// Create instance
		require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
			'EXT:'.$this->extKey.'/pi1/class.' . $eclass . '.php' ) );
		$exp = t3lib_div::makeInstance ( $eclass );
		$label = $this->get_ll ( $label, $label, TRUE );

		if ( is_object ( $exp ) ) {
			$exp->initialize ( $this );

			$dynamic = $this->conf['export.']['dynamic'] ? TRUE : FALSE;
			if ( $this->extConf['dynamic'] )
				$dynamic = TRUE;
			$exp->dynamic = $dynamic;

			if ( $exp->export () ) {
				$con .= $this->error_msg ( $exp->error );
			} else {
				if ( $dynamic ) {

					// Dump the export data and exit
					$exp_file = $exp->file_name;
					header ( 'Content-Type: text/plain' );
					header ( 'Content-Disposition: attachment; filename="' . $exp_file . '"');
					header ( 'Cache-Control: no-cache, must-revalidate' );
					echo $exp->data;
					exit ( );

				} else {
					// Create link to file
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
