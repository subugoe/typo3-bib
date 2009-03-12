<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/pi1/class.tx_sevenpack_citeid_generator.php' ) );

class tx_sevenpack_single_view {

	public $pi1; // Plugin 1
	public $ra;  // Reference accessor
	public $TCA; // The TCA
	public $LLPrefix = 'editor_';
	public $idGenerator = FALSE;

	public $is_new = FALSE;
	public $is_new_first = FALSE;

	function initialize ( $pi1 ) {
		$this->pi1 =& $pi1;
		$this->ra =& $pi1->ra;
		$this->ra->clear_cache = $this->pi1->extConf['editor']['clear_page_cache'];
		// Load editor language data
		$this->pi1->extend_ll ( 'EXT:'.$this->pi1->extKey.'/pi1/locallang_editor.xml' );

		// Create an instance of the citeid generator
		if ( isset ( $this->pi1->conf['editor.']['citeid_generator_file'] ) ) {
			$ext_file = $GLOBALS['TSFE']->tmpl->getFileName ( 
				$this->pi1->conf['editor.']['citeid_generator_file'] );
			if ( file_exists ( $ext_file ) ) {
				require_once ( $ext_file );
				$this->idGenerator = t3lib_div::makeInstance ( 'tx_sevenpack_citeid_generator_ext' );
			}
		} else {
			$this->idGenerator = t3lib_div::makeInstance ( 'tx_sevenpack_citeid_generator' );
		}
		$this->idGenerator->initialize ( $pi1 );
	}


	/** 
	 * Get the string in the local language to a given key from
	 * the database language file
	 *
	 * @return The string in the local language
	 */
	function get_ll ( $key, $alt = '', $hsc = FALSE )
	{
		return $this->pi1->get_ll ( $key, $alt, $hsc );
	}


	function get_db_ll ( $key, $alt = '', $hsc = FALSE )
	{
		$key = str_replace ( 'LLL:EXT:sevenpack/locallang_db.xml:', '', $key );
		return $this->pi1->get_ll ( $key, $alt, $hsc );
	}


	/** 
	 * The single view/editor can show a single publication entry
	 * and allows to edit, delete or save it.
	 *
	 * @return A single publication viewer/editor
	 */
	function single_view () {
		//t3lib_div::debug ($GLOBALS["HTTP_POST_VARS"]);

		// --- check whether the BE user is authorized
		if ( !$this->pi1->extConf['edit_mode'] )  {
			$con .= 'ERROR: You are not authorized to edit the publication database.';
			return $con;
		}

		$pi1 =& $this->pi1;
		$single_mode = $pi1->extConf['single_mode'];
		$preId =& $pi1->prefixId;
		$preSh =& $pi1->prefixShort;
		$edConf =& $pi1->conf['editor.'];
		$edExtConf =& $pi1->extConf['editor'];

		$pub = array(); // The publication data
		$con = ''; // Content
		$preCon = ''; // Pre content
		$uid = -1;
		$dataValid = TRUE;
		$btn_class = $preSh.'-editor_button';
		$btn_del_class = $preSh.'-delete_button';

		// Determine widget mode
		switch ( $single_mode ) {
			case $pi1->SINGLE_SHOW :
				$w_mode = $pi1->W_SHOW;
				break;
			case $pi1->SINGLE_EDIT :
			case $pi1->SINGLE_NEW :
				$w_mode = $pi1->W_EDIT;
				break;
			case $pi1->SINGLE_CONFIRM_SAVE :
			case $pi1->SINGLE_CONFIRM_DELETE :
			case $pi1->SINGLE_CONFIRM_ERASE :
				$w_mode = $pi1->W_SILENT;
				break;
			default :
				$w_mode = $pi1->W_SHOW;
		}

		// include $TCA
		$tcaFile = $GLOBALS['TCA'][$this->ra->refTable]['ctrl']['dynamicConfigFile'];
		include_once ( $tcaFile );
		$this->TCA = $TCA;

		// determine entry uid
		if ( array_key_exists( 'uid', $pi1->piVars ) ) {
			$uid = intval ( $pi1->piVars['uid'] );
		}

		switch ( $single_mode ) {
			case $pi1->SINGLE_SHOW :
				$title = $this->get_ll ( $this->LLPrefix.'title_view' );
				break;
			case $pi1->SINGLE_EDIT :
				$title = $this->get_ll ( $this->LLPrefix.'title_edit' );
				if ( $uid >= 0 ) {
					$pub = $this->ra->fetch_db_pub ( $uid );
					if ( !$pub )
						return $pi1->error_msg ( 'No publication with uid: ' . $uid );
				} else {
					return $pi1->error_msg ( 'No publication id given' );
				}
				break;
			case $pi1->SINGLE_NEW :
				$title = $this->get_ll ( $this->LLPrefix.'title_new' );
				break;
			case $pi1->SINGLE_CONFIRM_DELETE :
				$title = $this->get_ll ( $this->LLPrefix.'title_confirm_delete' );
				break;
			case $pi1->SINGLE_CONFIRM_ERASE :
				$title = $this->get_ll ( $this->LLPrefix.'title_confirm_erase' );
				break;
			default:
				break;
		}

		// merge in data from HTTP request
		$pub = array_merge ( $pub, $this->get_http_ref () );

		$this->is_new = TRUE;
		$this->is_new_first = TRUE;
		if ( $uid >= 0 ) {
			$pub['uid'] = $uid;
			$this->is_new = FALSE;
			$this->is_new_first = FALSE;
		}
		if ( is_array ( $pi1->piVars['DATA']['pub'] ) ) {
			$this->is_new_first = FALSE;
		}

		// Set default bibtype to aticle
		if ( $this->is_new_first && ( $pub['bibtype'] == 0 ) ) {
			$pub['bibtype'] = array_search ( 'article', $this->ra->allBibTypes );
		}

		// Set current year for new entries
		if ( $this->is_new && ( $w_mode == $pi1->W_EDIT ) && ( $pub['year'] == 0 ) ) {
			if ( isset ( $pi1->extConf['year'] ) )
				$pub['year'] = intval ( $pi1->extConf['year'] );
			else
				$pub['year'] = intval ( date ( 'Y' ) );
		}

		// Generate cite id if requested
		$genID = FALSE;
		$genIDRequest = FALSE;
		if ( is_array ( $pi1->piVars['action'] ) )
			if ( array_key_exists ( 'generate_id', $pi1->piVars['action'] ) )
				$genIDRequest = TRUE;
		if ( $this->is_new ) {
			switch ( $edExtConf['citeid_gen_new'] ) {
				case $pi1->AUTOID_FULL:
					$genID = TRUE;
					break;
				case $pi1->AUTOID_HALF:
					if ( $genIDRequest )
						$genID = TRUE;
					break;
				default: break;
			}
		} else {
			if ( $genIDRequest &&
			     ( $edExtConf['citeid_gen_old'] == $pi1->AUTOID_HALF ) )
				$genID = TRUE;
		}

		if ( $genID ) {
			$pub['citeid'] = $this->idGenerator->generateId ( $pub );
		}

		// Load default values for very new entries
		if ( $this->is_new_first ) {
			if ( is_array ( $edConf['field_default.'] ) ) {
				foreach ( $this->ra->refFields as $field ) {
					if ( array_key_exists ( $field, $edConf['field_default.'] ) )
						$pub[$field] = strval ( $edConf['field_default.'][$field] );
				}
			}
		}

		// Evaluate actions
		if ( is_array ( $pi1->piVars['action'] ) ) {
			//t3lib_div::debug ( $pi1->piVars['action'] );
			$actions =& $pi1->piVars['action'];

			// Raise author
			if ( is_numeric ( $actions['raise_author'] )  ) {
				$num = intval ( $actions['raise_author'] );
				if ( ( $num > 0 ) && ( $num < sizeof ( $pub['authors'] ) ) ) {
					$tmp = $pub['authors'][$num-1];
					$pub['authors'][$num-1] = $pub['authors'][$num];
					$pub['authors'][$num] = $tmp;
				}
			}

			// Lower author
			if ( is_numeric ( $actions['lower_author'] )  ) {
				$num = intval ( $actions['lower_author'] );
				if ( ( $num >= 0 ) && ( $num < ( sizeof ( $pub['authors'] ) - 1 ) ) ) {
					$tmp = $pub['authors'][$num+1];
					$pub['authors'][$num+1] = $pub['authors'][$num];
					$pub['authors'][$num] = $tmp;
				}
			}

			if ( isset( $pi1->piVars['action']['more_authors'] ) ) {
				$pi1->piVars['editor']['numAuthors'] += 1;
			}
			if ( isset( $pi1->piVars['action']['less_authors'] ) ) {
				$pi1->piVars['editor']['numAuthors'] -= 1;
			}

		}

		// Determine the number of authors
		$pi1->piVars['editor']['numAuthors'] = max (
			$pi1->piVars['editor']['numAuthors'], $edConf['numAuthors'], 
			sizeof ( $pub['authors'] ), 1 );

		// Edit button
		$btn_edit = '';
		if ( $single_mode == $pi1->SINGLE_CONFIRM_SAVE ) {
			$btn_edit =	'<input type="submit" ';
			if ( $this->is_new )
				$btn_edit .= 'name="'.$preId.'[action][new]" ';
			else
				$btn_edit .= 'name="'.$preId.'[action][edit]" ';
			$btn_edit .= 'value="'.$this->get_ll($this->LLPrefix.'btn_edit').
				'" class="'.$btn_class.'"/>';
		}

		// Syntax help button
		$btn_help = '';
		if ( $w_mode == $pi1->W_EDIT ) {
			$url = $GLOBALS['TSFE']->tmpl->getFileName (
				'EXT:sevenpack/doc/syntax.xhtml' );
			$btn_help = '<span class="'.$btn_class.'">'.
				'<a href="'.$url.'" target="_blank">'.
				$this->get_ll ( $this->LLPrefix.'btn_syntax_help').'</a></span>';
		}

		$fields = $this->get_edit_fields ( $pub['bibtype'] );

		// Data validation
		if ( $single_mode == $pi1->SINGLE_CONFIRM_SAVE ) {
			$d_err = $this->validate_data ( $pub, $fields );
			$title = $this->get_ll ( $this->LLPrefix.'title_confirm_save' );

			if ( sizeof ( $d_err ) > 0 ) {
				$dataValid = FALSE;
				$preCon .= '<div class="'.$preSh.'-warning_box">' . "\n";
				$preCon .= '<h3>';
				$preCon .= $this->get_ll ( $this->LLPrefix.'error_title') . "\n";
				$preCon .= '</h3>'."\n";
				$preCon .= $this->validation_error_string ( $d_err );
				$preCon .= $btn_edit;
				$preCon .= '</div>' . "\n";
			}
		}

		// Cancel button
		$btn_cancel = '<span class="'.$btn_class.'">' . $pi1->get_link ( 
			$this->get_ll ( $this->LLPrefix.'btn_cancel'), 
			$pi1->editClear ) . '</span>';

		// Generate Citeid button
		$btn_gen_id = '';
		if ( $w_mode == $pi1->W_EDIT ) {
			$btn_gen_id =	'<input type="submit" ' . 
				'name="'.$preId.'[action][generate_id]" ' . 
				'value="'.$this->get_ll($this->LLPrefix.'btn_generate_id').
				'" class="'.$btn_class.'"/>';
		}

		// Update button
		$btn_update = '';
		$btn_update_name = $preId.'[action][update_form]';
		$btn_update_value = $this->get_ll($this->LLPrefix.'btn_update_form');
		if ( $w_mode == $pi1->W_EDIT ) {
			$btn_update =	'<input type="submit"' .
				' name="'.$btn_update_name.'"' .
				' value="'.$btn_update_value.'"' .
				' class="'.$btn_class.'"/>';
		}

		// Save button
		$btn_save = '';
		if ( $dataValid ) {
			if ( $w_mode == $pi1->W_EDIT )
				$btn_save = '[action][confirm_save]';
			if ( $single_mode == $pi1->SINGLE_CONFIRM_SAVE )
				$btn_save = '[action][save]';
			if ( strlen ( $btn_save ) > 0 ) {
				$btn_save = '<input type="submit" name="'.$preId.$btn_save.'" '.
					'value="'.$this->get_ll($this->LLPrefix.'btn_save').
					'" class="'.$btn_class.'"/>';
			}
		}

		// Delete button
		$btn_delete = '';
		if ( !$this->is_new ) {
			if ( ($single_mode != $pi1->SINGLE_SHOW) &&
			     ($single_mode != $pi1->SINGLE_CONFIRM_SAVE) )
				$btn_delete = '[action][confirm_delete]';
			if ( $single_mode == $pi1->SINGLE_CONFIRM_DELETE )
				$btn_delete = '[action][delete]';
			if ( strlen($btn_delete) ) {
				$btn_delete = '<input type="submit" name="'.$preId.$btn_delete.'" '.
						'value="'.$this->get_ll($this->LLPrefix.'btn_delete').
						'" class="'.$btn_class.' '.$btn_del_class.'"/>';
			}
		}

		// Write title
		$con .= '<h2>'.$title.'</h2>'."\n";

		// Write initial form tag
		$form_name = $preId . '_ref_data_form';
		$con .= '<form name="' . $form_name . '"';
		$con .= ' action="'.$pi1->get_link_url().'" method="post"';
		$con .= '>' . "\n";
		$con .= $preCon;

		// Invisible 'uid' and 'mod_key' field
		if ( !$this->is_new ) {
			$con .= '<input type="hidden" name="'.$preId.'[DATA][pub][uid]" ';
			$con .= 'value="'.$uid.'"/>' . "\n";
			if ( isset ( $pub['mod_key'] ) ) {
				$con .= '<input type="hidden" name="'.$preId.'[DATA][pub][mod_key]" ';
				$con .= 'value="'.htmlspecialchars ( $pub['mod_key'], ENT_QUOTES ).'"/>' . "\n";
			}
		}

		// Javascript for automatic submitting
		$con .= '<script type="text/javascript">' . "\n";
		$con .= '/* <![CDATA[ */' . "\n";
 		$con .= 'function click_update_button() {' . "\n";
		//$con .= "  alert('click_update_button');" . "\n";
		$con .= "  var btn = document.getElementsByName('" . $btn_update_name . "')[0];" . "\n";
		//$con .= "  alert(btn);" . "\n";
		$con .= '  btn.click();' . "\n";
		$con .= '  return;' . "\n";
		$con .= '}' . "\n";
		$con .= '/* ]]> */' . "\n";
		$con .= '</script>' . "\n";

		// Begin of the editor box
		$con .= '<div class="'.$preSh.'-editor">' . "\n";

		// Top buttons
		$con .= '<div class="'.$preSh.'-editor_button_box">';
		$con .= '<span class="'.$preSh.'-box_right">';
		$con .= $btn_delete;
		$con .= '</span>';
		$con .= '<span class="'.$preSh.'-box_left">';
		$con .= $btn_save . $btn_edit . $btn_cancel . $btn_help;
		$con .= '</span>';
		$con .= '</div>';

		$fieldTypes = array ( 'required', 'optional', 'other', 'library', 'typo3' );
		array_unshift ( $fields['required'], 'bibtype' );

		foreach ( $fieldTypes as $ft ) {
			$class_str = ' class="'.$preSh.'-editor_'.$ft.'"';

			if ( sizeof ( $fields[$ft] ) > 0 ) {
				$con .= '<h3>';
				$con .= $this->get_ll ( $this->LLPrefix.'fields_'.$ft );
				$con .= '</h3>';

				$con .= '<table class="'.$preSh.'-editor_fields">' . "\n";
				$con .= '<tbody>' . "\n";
				foreach ( $fields[$ft] as $f ) {

					// Field name
					$label = '';
					switch ( $f ) {
						case 'authors':
							$label = $this->get_ll ( $this->ra->authorTable . '_' . $f );
							break;
						case 'year':
							$label = $this->get_ll ( 'editor_year_month_day' );
							break;
						case 'month':
						case 'day':
							break;
						default:
							$label = $this->get_ll ( $this->ra->refTable . '_' . $f );
					}

					// Field value widget
					$widget = '';
					switch ( $f ) {
						case 'citeid':
							if ( $edExtConf['citeid_gen_new'] ==  $pi1->AUTOID_FULL ) {
								$widget .= $this->get_widget ( $f, $pub[$f],
									( $w_mode == $pi1->W_EDIT ) ? $pi1->W_SILENT : $w_mode );
							} else {
								$widget .= $this->get_widget ( $f, $pub[$f], $w_mode );
							}
							// Add the id generation button
							if ( $this->is_new ) {
								if ( $edExtConf['citeid_gen_new'] == $pi1->AUTOID_HALF )
									$widget .= $btn_gen_id;
							} else {
								if ( $edExtConf['citeid_gen_old'] == $pi1->AUTOID_HALF )
									$widget .= $btn_gen_id;
							}
							break;
						case 'year':
							$widget .= $this->get_widget ( 'year',  $pub['year'],  $w_mode );
							$widget .= ' - ';
							$widget .= $this->get_widget ( 'month', $pub['month'], $w_mode );
							$widget .= ' - ';
							$widget .= $this->get_widget ( 'day',   $pub['day'],   $w_mode );
							break;
						case 'month':
						case 'day':
							break;
						default:
							$widget .= $this->get_widget ( $f, $pub[$f], $w_mode );
					}
					if ( $f == 'bibtype' ) {
						$widget .= $btn_update;
					}

					if ( ( strlen ( $label ) + strlen ( $widget ) ) > 0 ) {
						if ( is_array ( $edConf['field_labels.'] ) )
							$label = $pi1->cObj->stdWrap ( $label, $edConf['field_labels.'] );
						if ( is_array ( $edConf['field_widgets.'] ) )
							$widget = $pi1->cObj->stdWrap ( $widget, $edConf['field_widgets.'] );
						$con .= '<tr>';
						$con .= '<th' . $class_str . '>' . $label  . '</th>' . "\n";
						$con .= '<td' . $class_str . '>' . $widget . '</td>' . "\n";
						$con .= '</tr>' . "\n";
					}
				}
				$con .= '</tbody>' . "\n";
				$con .= '</table>' . "\n";
			}
		}

		// Footer Buttons
		$con .= '<div class="'.$preSh.'-editor_button_box">';
		$con .= '<span class="'.$preSh.'-box_right">';
		$con .= $btn_delete;
		$con .= '</span>';
		$con .= '<span class="'.$preSh.'-box_left">';
		$con .= $btn_save . $btn_edit . $btn_cancel;
		$con .= '</span>';
		$con .= '</div>';

		$con .= '</div>' . "\n";
		$con .= '</form>';

		return $con;
	}


	/** 
	 * Depending on the bibliography type this function returns what fields 
	 * are required and what are optional according to BibTeX
	 *
	 * @return An array with subarrays with field lists for
	 */
	function get_edit_fields ( $bibType )
	{
		$fields = array ();
		$type_str = $bibType;
		if ( is_numeric ( $bibType ) )
			$type_str = $this->ra->allBibTypes[$bibType];

		$all_groups = array ( 'all', $type_str );
		$all_types = array ( 'required', 'optional', 'library' );

		// Read field list from TS configuration
		$cfg_fields = array();
		foreach ( $all_groups as $group ) {
			$cfg_fields[$group] = array();
			$cfg_arr =& $this->pi1->conf['editor.']['groups.'][$group.'.'];
			if ( is_array ( $cfg_arr ) ) {
				foreach ( $all_types as $type ) {
					$cfg_fields[$group][$type] = array();
					// Clean string and explode with SPACE as separator
					$ff = str_replace ( ',', ' ', $cfg_arr[$type] );
					$ff = trim ( $ff );
					$ff = preg_replace ( '/\s+/', ' ', $ff );
					$cfg_fields[$group][$type] = explode ( ' ', $ff );
				}
			}
		}

		// Merge field lists
		$pubFields = $this->ra->pubFields;
		unset ( $pubFields[array_search ( 'bibtype',$pubFields)] );
		foreach ( $all_types as $type ) {
			$fields[$type] = array();
			if ( is_array ( $cfg_fields[$type_str][$type] ) )
				$fields[$type] = $cfg_fields[$type_str][$type];
			if ( is_array ( $cfg_fields['all'][$type] ) )
				$fields[$type] = array_merge ( $fields[$type], $cfg_fields['all'][$type] );
			$fields[$type] = array_intersect ( $fields[$type], $pubFields );
			$pubFields = array_diff ( $pubFields, $fields[$type] );
		}

		// Calculate the remaining 'other' fields
		$fields['other'] = $pubFields;
		$fields['typo3'] = array ( 'uid', 'hidden', 'pid' );

		//t3lib_div::debug ( $fields );
		return $fields;
	}


	/**
	 * Get the edit widget for a row field
	 *
	 * @return The field widget
	 */
	function get_widget ( $field, $value, $mode )
	{
		$con = ''; // Content
		$pi1 =& $this->pi1;

		$editMode = ( $mode == $pi1->W_EDIT );

		switch ( $field ) {
			case 'authors':
				$con .= $this->get_authors_widget ( $value, $mode );
				break;
			case 'uid':
				$con .= strval ( $value );
				break;
			case 'pid':
				$con .= $this->get_pid_widget ( $value, $mode );
				break;
			default:
				if ( $editMode ) {
					$con .= $this->get_default_edit_widget ( $field, $value, $mode );
				} else {
					$con .= $this->get_default_static_widget ( $field, $value, $mode );
				}
		}
		return $con;
	}


	function get_default_edit_widget ( $field, $value, $mode )
	{
		$cfg =& $this->TCA[$this->ra->refTable]['columns'][$field]['config'];
		$con = ''; // Content
		$Iclass = ' class="'.$this->pi1->prefixShort.'-editor_input'.'"';
		$pi1 =& $this->pi1;

		// Default widget
		$widgetType = $cfg['type'];
		$nameAttr   = ' name="'.$pi1->prefixId.'[DATA][pub][' . $field . ']"';
		$htmlValue  = $pi1->filter_pub_html ( $value, TRUE );

		switch ( $widgetType )  {
			case 'input' : 
				$con .= '<input type="text"'.$nameAttr.' value="'.$htmlValue.'"';
				if ( $cfg['max'] )
					$con .= ' maxlength="'.$cfg['max'].'"';
				if ( $cfg['size'] )
					$con .= ' size="'.$cfg['size'].'"';
				$con .= $Iclass.'/>';
			break;

			case 'text' : 
				$con .= '<textarea' . $nameAttr;
				$con .= ' rows="'.$cfg['rows'].'"';
				$con .= ' cols="'.$cfg['cols'].'"';
				$con .= $Iclass.'>' . $htmlValue . '</textarea>';
				break;

			case 'select' :
				$con  = '<select'.$nameAttr;
				if ( $field == 'bibtype' ) {
					$con .= ' onchange="click_update_button()"';
				}
				$con .= '>' . "\n";
				for ( $i=0; $i < sizeof($cfg['items']); $i++ )  {
					//$optName = $GLOBALS['LANG']->sL($cfg['items'][$i][0]);
					$optName = $this->get_db_ll ( $cfg['items'][$i][0], $cfg['items'][$i][0] );
					$optVal = strtolower ( $cfg['items'][$i][1] );
					$selAttr = ($optVal == $value) ? ' selected="selected"' : '';
					$con .= '<option value="'.$optVal.'"'.$selAttr.">";
					$con .= $optName.'</option>'."\n"; 
				}
				$con .= '</select>'."\n";
				break;

			case 'check' :
				$checkAttr = ($value == 1) ? ' checked="checked"' : '';
				$con .= '<input type="checkbox"'.$nameAttr.$checkAttr .
				        ' value="1"'.$Iclass.'/>';
				break;

			default :
				$con .= 'Unknown widget: ' . $widgetType;
		}
		return $con;
	}


	function get_default_static_widget ( $field, $value, $mode )
	{
		$cfg =& $this->TCA[$this->ra->refTable]['columns'][$field]['config'];
		$con = ''; // Content
		$Iclass = ' class="'.$this->pi1->prefixShort.'-editor_input'.'"';
		$pi1 =& $this->pi1;

		$silentMode = ( $mode == $pi1->W_SILENT );
		$hiddenMode = ( $mode == $pi1->W_HIDDEN );

		// Default widget
		$widgetType = $cfg['type'];
		$nameAttr   = ' name="'.$pi1->prefixId.'[DATA][pub][' . $field . ']"';
		$htmlValue  = $pi1->filter_pub_html ( $value, TRUE );

		if ( $silentMode || $hiddenMode ) {
			$con .= '<input type="hidden"'.$nameAttr;
			$con .= ' value="'.$htmlValue.'"/>';
		}
		if ( !$hiddenMode ) {
			switch ( $widgetType ) {
				case 'select':
					$name = '';
					foreach ( $cfg['items'] as $it) {
						if ( strtolower ( $it[1] ) == strtolower ( $value ) ) {
							$name = $this->get_db_ll ( $it[0], $it[0] );
							break;
						}
					}
					$con .= $name;
					break;
				case 'check':
					$con .= $this->get_ll (
						( $value == 0 ) ? 'editor_no' : 'editor_yes' );
					break;
				default:
					$con .= $htmlValue;
			}
		}
		return $con;
	}


	/**
	 * Get the authors widget
	 *
	 * @return The authors widget
	 */
	function get_authors_widget ( $value, $mode )
	{
		$con = ''; // Content
		$Iclass = ' class="'.$this->pi1->prefixShort.'-editor_input'.'"';
		$pi1 =& $this->pi1;

		$editMode = ( $mode == $pi1->W_EDIT );
		$silentMode = ( $mode == $pi1->W_SILENT );
		$hiddenMode = ( $mode == $pi1->W_HIDDEN );

		// Author widget
		$authors = is_array ( $value ) ? $value : array();
		$aNum = sizeof ( $authors );
		$edOpts =& $pi1->piVars['editor'];
		$edOpts['numAuthors'] = max ( $edOpts['numAuthors'], 
			sizeof ( $authors ), $pi1->extConf['editor']['numAuthors'], 1 );

		if ( !$hiddenMode ) {
			$con .= '<table class="'.$pi1->prefixShort.'-editor_author">' . "\n";
			$con .= '<tbody>' . "\n";
			$con .= '<tr><td></td>';
			$con .= '<th>'.$this->get_ll ( $this->ra->authorTable.'_forename' ).'</th>';
			$con .= '<th>'.$this->get_ll ( $this->ra->authorTable.'_surname' ).'</th>';
			if ( $editMode ) {
				$con .= '<th></th><th></th>';
			}
			$con .= '</tr>' . "\n";
			for ( $i=0; $i < $edOpts['numAuthors']; $i++ ) {
				if ( $i > ( $aNum - 1 ) && ( $mode != $pi1->W_EDIT ) )
					break;
				
				$fn = $pi1->filter_pub_html ( $authors[$i]['fn'], TRUE );
				$sn = $pi1->filter_pub_Html ( $authors[$i]['sn'], TRUE );
				//t3lib_div::debug ( array('fn' => $fn, 'sn' => $sn) );
				$con .= '<tr>';
				$con .= '<th class="'.$pi1->prefixShort.'-editor_author_num">';
				$con .= strval($i+1);
				$con .= '</th>';
				$con .= '<td>';
				if ( $editMode ) {
					$tmpl =& $GLOBALS['TSFE']->tmpl;
					$this->icon_src['new_record'] = 'src="'.$tmpl->getFileName (
						'EXT:t3skin/icons/gfx/new_record.gif' ).'"';
					$raiseBtn = '<input type="image"'.
						' src="'.$tmpl->getFileName ( 'EXT:t3skin/icons/gfx/button_up.gif' ).'"'.
						' name="'.$pi1->prefixId.'[action][raise_author]"'.
						' value="'.strval($i).'"/>';
					$lowerBtn = '<input type="image"'.
						' src="'.$tmpl->getFileName ( 'EXT:t3skin/icons/gfx/button_down.gif' ).'"'.
						' name="'.$pi1->prefixId.'[action][lower_author]"'.
						' value="'.strval($i).'"/>';

					$con .= '<input type="text" ';
					$con .= 'name="'.$pi1->prefixId.'[DATA][pub][authors]['.$i.'][fn]" ';
					$con .= 'value="'.$fn.'"'.$Iclass.'/>';
					$con .= '</td>';
					$con .= '<td>';
					$con .= '<input type="text" ';
					$con .= 'name="'.$pi1->prefixId.'[DATA][pub][authors]['.$i.'][sn]" ';
					$con .= 'value="'.$sn.'"'.$Iclass.'/>';
					$con .= '</td>';
					$con .= '<td style="padding: 1px;">';
					$con .= ( ($i>=0) && ($i<($aNum-1)) ) ? $lowerBtn : '';
					$con .= '</td><td style="padding: 1px;">';
					$con .= ( ($i>0) && ($i<($aNum)) ) ? $raiseBtn : '';
				} else if ( $silentMode ) {
					$con .= '<input type="hidden" ';
					$con .= 'name="'.$pi1->prefixId.'[DATA][pub][authors]['.$i.'][fn]" ';
					$con .= 'value="'.$fn.'"'.$Iclass.'/>'.$fn;
					$con .= '</td>';
					$con .= '<td>';
					$con .= '<input type="hidden" ';
					$con .= 'name="'.$pi1->prefixId.'[DATA][pub][authors]['.$i.'][sn]" ';
					$con .= 'value="'.$sn.'"'.$Iclass.'/>'.$sn;
				} else {
					$con .= $sn.'</td><td>'.$fn;
				}
				$con .= '</td>';
				$con .= '</tr>' . "\n";
			}
			// Append +/- Buttons
			if ( $editMode ) {
				$con .= '<tr><td colspan="2"></td>';
				$con .= '<td>';
				$con .= '<input type="submit"'
					.  ' name="'.$pi1->prefixId.'[action][more_authors]"'
					.  ' value="+"/>';
				$con .= '<input type="submit"'
					.  ' name="'.$pi1->prefixId.'[action][less_authors]"'
					.  ' value="-"/>' . "\n";
				$con .= '</td></tr>'."\n";
			}
			$con .= '</tbody>';
			$con .= '</table>' . "\n";
		} else {
			for ( $i=0; $i < sizeof ( $authors ); $i++ ) {
				$fn = $pi1->filter_pub_html ( $authors[$i]['fn'], TRUE );
				$sn = $pi1->filter_pub_Html ( $authors[$i]['sn'], TRUE );
				$con .= '<input type="hidden" ';
				$con .= 'name="'.$pi1->prefixId.'[DATA][pub][authors]['.$i.'][fn]" ';
				$con .= 'value="'.$fn.'"'.$Iclass.'/>';
				$con .= '<input type="hidden" ';
				$con .= 'name="'.$pi1->prefixId.'[DATA][pub][authors]['.$i.'][sn]" ';
				$con .= 'value="'.$sn.'"'.$Iclass.'/>';
			}
		}
		return $con;
	}


	/**
	 * Get the pid (storage folder) widget
	 *
	 * @return The pid widget
	 */
	function get_pid_widget ( $value, $mode )
	{
		// Pid
		$pids = $this->pi1->extConf['filter']['pid'];
		$value = intval ( $value );

		// Fetch page titles
		$pages = array();
		$pages = tx_sevenpack_utility::get_page_titles ( $pids ); 
		$pages = array_reverse ( $pages, TRUE ); // Due to how recursive prepends the folders

		switch ( $mode ) {
			case $this->pi1->W_EDIT:
				$con .= tx_sevenpack_utility::html_select_input (
					$pages, $value,
					array ( 'name' => $this->pi1->prefixId . '[DATA][pub][pid]' )
				);
				break;
			case $this->pi1->W_SHOW:
			case $this->pi1->W_SILENT:
			case $this->pi1->W_HIDDEN:
				if ( $mode != $this->pi1->W_SHOW ) {
					$con .= '<input type="hidden"';
					$con .= ' name="'.$this->pi1->prefixId.'[DATA][pub][pid]"';
					$con .= ' value="'.$value.'"';
					$con .= ' />';
				}
				if ( $mode != $this->pi1->W_HIDDEN ) {
					$con .= strval ( $pages[$value] );
				}
				break;
			default:
		}
		return $con;
	}


	/** 
	 * Returns the publication data that was encoded in the
	 * HTTP erquest
	 *
	 * @return An array containing the formatted publication 
	 *         data that was found in the HTTP request
	 */
	function get_http_ref ( $hsc = FALSE ) {
		$pub = array();
		$charset = strtoupper ( $this->pi1->extConf['be_charset'] );
		$fields = $this->ra->pubFields;
		$fields[] = 'uid';
		$fields[] = 'pid';
		$fields[] = 'hidden';
		$fields[] = 'mod_key'; // Get generated on loading from the database
		$data =& $this->pi1->piVars['DATA']['pub'];
		if ( is_array ( $data ) ) {
			foreach ( $fields as $f ) {
				switch ( $f )
				{
					case 'authors':
						if ( is_array ( $data[$f] ) ) {
							$pub['authors'] = array();
							foreach ( $data[$f] as $v ) {
								$fn = trim ( $v['fn'] );
								$sn = trim ( $v['sn'] );
								if ( $hsc ) {
									$fn = htmlspecialchars ( $fn, ENT_QUOTES, $charset );
									$sn = htmlspecialchars ( $sn, ENT_QUOTES, $charset );
								}
								if ( strlen ( $fn ) || strlen ( $sn ) ) {
									$pub['authors'][] = array ( 'fn'=>$fn, 'sn'=>$sn );
								}
							}
						}
						break;
					default:
						if ( array_key_exists ( $f, $data ) ) {
							$pub[$f] = $data[$f];
							if ( $hsc )
								$pub[$f] = htmlspecialchars ( $pub[$f], ENT_QUOTES, $charset );
						}
				}
			}
		}
		//t3lib_div::debug ( $pub );
		return $pub;
	}


	/** 
	 * This switches to the requested dialog
	 *
	 * @return The requested dialog
	 */
	function dialog_view () {
		$con = '';
		$pi1 =& $this->pi1;
		switch ( $pi1->extConf['dialog_mode'] ) {

			case $pi1->DIALOG_SAVE_CONFIRMED : 
				$pub = $this->get_http_ref();
				if ( $this->ra->save_publication ( $pub ) ) {
					$con .= '<div class="'.$pi1->prefixShort.'-warning_box">' . "\n";
					$con .= '<p>'.$this->get_ll ( 'msg_save_fail' ).'</p>';
					$con .= '<p>'.$this->ra->html_error_message().'</p>';
					$con .= '</div>' . "\n";
				} else {
					$con .= '<p>'.$this->get_ll ( 'msg_save_success' ).'</p>';
				}
				break;

			case $pi1->DIALOG_DELETE_CONFIRMED : 
				$pub = $this->get_http_ref();
				if ( $this->ra->delete_publication ( $pi1->piVars['uid'], $pub['mod_key'] ) ) {
					$con .= '<div class="'.$pi1->prefixShort.'-warning_box">' . "\n";
					$con .= '<p>'.$this->get_ll ( 'msg_delete_fail' ).'</p>';
					$con .= '<p>'.$this->ra->html_error_message().'</p>';
					$con .= '</div>' . "\n";
				} else {
					$con .= '<p>'.$this->get_ll ( 'msg_delete_success' ).'</p>';
				}
				break;

			case $pi1->DIALOG_ERASE_CONFIRMED : 
				if ( $this->ra->erase_publication ( $pi1->piVars['uid'] ) ) {
					$con .= '<p>'.$this->get_ll ( 'msg_erase_fail' ).'</p>';
				} else {
					$con .= '<p>'.$this->get_ll ( 'msg_erase_success' ).'</p>';
				}
				break;

			default :
				$con .= 'Unknown dialog mode: ' .
					$pi1->extConf['dialog_mode'];
		}
		return $con;
	}


	/** 
	 * Validates the data in a publication
	 *
	 * @return An array with error messages
	 */
	function validate_data ( $pub, $fields )
	{
		$d_err = array();
		$title = $this->get_ll ( $this->LLPrefix.'title_confirm_save' );

		// Find empty required fields
		$empty = array();
		foreach ( $fields['required'] as $field ) {
			switch ( $field ) {
				case 'authors':
					if ( !is_array ( $pub[$field] ) || ( sizeof ( $pub[$field] ) == 0 ) )
						$empty[] = $field;
					break;
				default:
					if ( strlen ( trim ( $pub[$field] ) ) == 0) 
						$empty[] = $field;
			}
		}
		if ( sizeof ( $empty ) ) {
			$err = array();
			$err['msg'] = $this->get_ll ( $this->LLPrefix.'error_empty_fields');
			$err['list'] = array();
			foreach ( $empty as $field ) {
				switch ( $field ) {
					case 'authors':
						$str = $this->get_ll ( $this->ra->authorTable.'_'.$field );
						break;
					default:
						$str = $this->get_ll ( $this->ra->refTable.'_'.$field );
				}
				$err['list'][] = array ( 'msg' => $str );
			}
			$d_err[] = $err;
		}

		// Cite id doubles
		if ( $this->ra->citeid_exists ( $pub['citeid'], $pub['uid'] ) ) {
			$d_err[] = array ( 
				'msg' => $this->get_ll ( $this->LLPrefix.'error_id_exists') );
		}

		return $d_err;
	}


	/** 
	 * Makes some html out of the return array of
	 * validate_data()
	 *
	 * @return An array with error messages
	 */
	function validation_error_string ( $errors )
	{
		if ( !is_array ( $errors ) || ( sizeof ( $errors ) == 0 ) )
			return '';

		$res = '<ul>' . "\n";
		foreach ( $errors as $err ) {
			$res .= '<li>';
			$res .= $err['msg'] . "\n";
			if ( is_array ( $err['list'] ) ) {
				$res .= $this->validation_error_string ( $err['list'] );
			}
			$res .= '</li>' . "\n";
		}
		$res .= '</ul>' . "\n";

		return $res;
	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_single_view.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_single_view.php"]);
}

?>
