<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/pi1/class.tx_sevenpack_citeid_generator.php' ) );

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/res/class.tx_sevenpack_reference_writer.php' ) );

require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/res/class.tx_sevenpack_db_utility.php' ) );


class tx_sevenpack_editor_view {

	public $pi1; // Plugin 1
	public $conf; // configuration array
	public $ref_read;  // Reference reader
	public $ref_write;  // Reference writer
	public $db_utility;  // Databse utility
	public $LLPrefix = 'editor_';
	public $idGenerator = FALSE;

	public $is_new = FALSE;
	public $is_first_edit = FALSE;
	
	// Widget modes
	public $W_SHOW   = 0; // Show and pass value
	public $W_EDIT   = 1; // Edit and pass value
	public $W_SILENT = 2; // Don't show but pass value
	public $W_HIDDEN = 3; // Don't show and don't pass value


	/** 
	 * Initializes this class
	 *
	 * @return Not defined
	 */
	function initialize ( $pi1 ) {
		$this->pi1 =& $pi1;
		$this->conf =& $pi1->conf['editor.'];
		$this->ref_read =& $pi1->ref_read;
		$this->ref_read->clear_cache = $this->pi1->extConf['editor']['clear_page_cache'];
		// Load editor language data
		$this->pi1->extend_ll ( 'EXT:'.$this->pi1->extKey.'/pi1/locallang_editor.xml' );


		// setup db_utility
		$this->db_utility = t3lib_div::makeInstance ( 'tx_sevenpack_db_utility' );
		$this->db_utility->initialize ( $pi1->ref_read );
		$this->db_utility->charset = $pi1->extConf['charset']['upper'];
		$this->db_utility->read_full_text_conf ( $this->conf['full_text.'] );


		// Create an instance of the citeid generator
		if ( isset ( $this->conf['citeid_generator_file'] ) ) {
			$ext_file = $GLOBALS['TSFE']->tmpl->getFileName ( $this->conf['citeid_generator_file'] );
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
	 * The editor shows a single publication entry
	 * and allows to edit, delete or save it.
	 *
	 * @return A publication editor
	 */
	function editor_view () {
		//t3lib_div::debug ($GLOBALS["HTTP_POST_VARS"]);

		// --- check whether the BE user is authorized
		if ( !$this->pi1->extConf['edit_mode'] )  {
			$con .= 'ERROR: You are not authorized to edit the publication database.';
			return $con;
		}

		$pi1 =& $this->pi1;
		$editor_mode = $pi1->extConf['editor_mode'];
		$preId =& $pi1->prefix_pi1;
		$preSh =& $pi1->prefixShort;
		$edConf =& $this->conf;
		$edExtConf =& $pi1->extConf['editor'];

		$pub_http = $this->get_ref_http ();
		$pub_db = array();
		$pub = array(); // The publication data
		$con = ''; // Content
		$preCon = ''; // Pre content
		$uid = -1;
		$dataValid = TRUE;
		$btn_class = $preSh.'-editor_button';
		$btn_del_class = $preSh.'-delete_button';

		// Determine widget mode
		switch ( $editor_mode ) {
			case $pi1->EDIT_SHOW :
				$w_mode = $this->W_SHOW;
				break;
			case $pi1->EDIT_EDIT :
			case $pi1->EDIT_NEW :
				$w_mode = $this->W_EDIT;
				break;
			case $pi1->EDIT_CONFIRM_SAVE :
			case $pi1->EDIT_CONFIRM_DELETE :
			case $pi1->EDIT_CONFIRM_ERASE :
				$w_mode = $this->W_SHOW;
				break;
			default :
				$w_mode = $this->W_SHOW;
		}

		// include $TCA
		t3lib_div::loadTCA ( $this->ref_read->refTable );

		// determine entry uid
		if ( array_key_exists( 'uid', $pi1->piVars ) ) {
			if (  is_numeric ( $pi1->piVars['uid'] ) ) {
				$uid = intval ( $pi1->piVars['uid'] );
			}
		}

		$this->is_new = TRUE;
		if ( $uid >= 0 ) {
			$this->is_new = FALSE;
			$pub_http['uid'] = $uid;
		}

		$this->is_first_edit = TRUE;		
		if ( is_array ( $pi1->piVars['DATA']['pub'] ) ) {
			$this->is_first_edit = FALSE;
		}

		$title = $this->LLPrefix;
		switch ( $editor_mode ) {
			case $pi1->EDIT_SHOW :
				$title .= 'title_view';
				break;
			case $pi1->EDIT_EDIT :
				$title .= 'title_edit';
				break;
			case $pi1->EDIT_NEW :
				$title .= 'title_new';
				break;
			case $pi1->EDIT_CONFIRM_DELETE :
				$title .= 'title_confirm_delete';
				break;
			case $pi1->EDIT_CONFIRM_ERASE :
				$title .= 'title_confirm_erase';
				break;
			default:
				$title .= 'title_edit';
				break;
		}
		$title = $this->get_ll ( $title );

		// Load default data
		if ( $this->is_first_edit ) {
			if ( $this->is_new ) {
				// Load defaults for a new publication 
				$pub = $this->get_ref_default();
			} else {
				if ( $uid < 0 ) {
					return $pi1->error_msg ( 'No publication id given' );
				}

				// Load publication data from database				
				$pub_db = $this->ref_read->fetch_db_pub ( $uid );
				if ( $pub_db ) {
					$pub = array_merge ( $pub, $pub_db );
				} else {
					return $pi1->error_msg ( 'No publication with uid: ' . $uid );
				}
			}
		}

		// Merge in data from HTTP request
		$pub = array_merge ( $pub, $pub_http );

		// Generate cite id if requested
		$genID = FALSE;
		$genIDRequest = FALSE;

		// Evaluate actions
		if ( is_array ( $pi1->piVars['action'] ) ) {
			$actions =& $pi1->piVars['action'];
			//t3lib_div::debug ( $actions );

			// Generate cite id
			if ( array_key_exists ( 'generate_id', $actions ) ) {
				$genIDRequest = TRUE;
			}

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

		// Generate cite id on demand
		if ( $this->is_new ) {
			// Generate cite id for new entries
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
			// Generate cite id for already existing (old) entries
			$auto_id = $edExtConf['citeid_gen_old'];
			if ( ( $genIDRequest && ( $auto_id == $pi1->AUTOID_HALF ) )
				|| ( strlen ( $pub['citeid'] ) == 0 ) )
			{
				$genID = TRUE;
			}
		}
		if ( $genID ) {
			$pub['citeid'] = $this->idGenerator->generateId ( $pub );
		}

		// Determine the number of authors
		$pi1->piVars['editor']['numAuthors'] = max (
			$pi1->piVars['editor']['numAuthors'], $edConf['numAuthors'], 
			sizeof ( $pub['authors'] ), 1 );

		// Edit button
		$btn_edit = '';
		if ( $editor_mode == $pi1->EDIT_CONFIRM_SAVE ) {
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
		if ( $w_mode == $this->W_EDIT ) {
			$url = $GLOBALS['TSFE']->tmpl->getFileName (
				'EXT:sevenpack/doc/syntax.xhtml' );
			$btn_help = '<span class="'.$btn_class.'">'.
				'<a href="'.$url.'" target="_blank">'.
				$this->get_ll ( $this->LLPrefix.'btn_syntax_help').'</a></span>';
		}

		$fields = $this->get_edit_fields ( $pub['bibtype'] );

		// Data validation
		if ( $editor_mode == $pi1->EDIT_CONFIRM_SAVE ) {
			$d_err = $this->validate_data ( $pub );
			$title = $this->get_ll ( $this->LLPrefix.'title_confirm_save' );

			if ( sizeof ( $d_err ) > 0 ) {
				$dataValid = FALSE;
				$cfg =& $edConf['warn_box.'];
				$txt = $this->get_ll ( $this->LLPrefix.'error_title');
				$box = $pi1->cObj->stdWrap ( $txt, $cfg['title.'] ) . "\n";
				$box .= $this->validation_error_string ( $d_err );
				$box .= $btn_edit;
				$box = $pi1->cObj->stdWrap ( $box, $cfg['all_wrap.'] ) . "\n";
				$preCon .= $box;
			}
		}

		// Cancel button
		$btn_cancel = '<span class="'.$btn_class.'">' . $pi1->get_link ( 
			$this->get_ll ( $this->LLPrefix.'btn_cancel') ) . '</span>';

		// Generate Citeid button
		$btn_gen_id = '';
		if ( $w_mode == $this->W_EDIT ) {
			$btn_gen_id =	'<input type="submit" ' . 
				'name="'.$preId.'[action][generate_id]" ' . 
				'value="'.$this->get_ll($this->LLPrefix.'btn_generate_id').
				'" class="'.$btn_class.'"/>';
		}

		// Update button
		$btn_update = '';
		$btn_update_name = $preId.'[action][update_form]';
		$btn_update_value = $this->get_ll($this->LLPrefix.'btn_update_form');
		if ( $w_mode == $this->W_EDIT ) {
			$btn_update =	'<input type="submit"' .
				' name="'.$btn_update_name.'"' .
				' value="'.$btn_update_value.'"' .
				' class="'.$btn_class.'"/>';
		}

		// Save button
		$btn_save = '';
		//if ( $dataValid ) {
			if ( $w_mode == $this->W_EDIT )
				$btn_save = '[action][confirm_save]';
			if ( $editor_mode == $pi1->EDIT_CONFIRM_SAVE )
				$btn_save = '[action][save]';
			if ( strlen ( $btn_save ) > 0 ) {
				$btn_save = '<input type="submit" name="'.$preId.$btn_save.'" '.
					'value="'.$this->get_ll($this->LLPrefix.'btn_save').
					'" class="'.$btn_class.'"/>';
			}
		//}

		// Delete button
		$btn_delete = '';
		if ( !$this->is_new ) {
			if ( ($editor_mode != $pi1->EDIT_SHOW) &&
			     ($editor_mode != $pi1->EDIT_CONFIRM_SAVE) )
				$btn_delete = '[action][confirm_delete]';
			if ( $editor_mode == $pi1->EDIT_CONFIRM_DELETE )
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
		$con .= ' action="'.$pi1->get_edit_link_url().'" method="post"';
		$con .= '>' . "\n";
		$con .= $preCon;

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
		$con .= '<div class="'.$preSh.'-editor_button_box">'. "\n";
		$con .= '<span class="'.$preSh.'-box_right">';
		$con .= $btn_delete;
		$con .= '</span>'. "\n";
		$con .= '<span class="'.$preSh.'-box_left">';
		$con .= $btn_save . $btn_edit . $btn_cancel . $btn_help;
		$con .= '</span>'. "\n";
		$con .= '</div>'. "\n";

		$fieldGroups = array ( 
			'required', 
			'optional', 
			'other', 
			'library', 
			'typo3' );
		array_unshift ( $fields['required'], 'bibtype' );

		$bib_str = $this->ref_read->allBibTypes[$pub['bibtype']];

		foreach ( $fieldGroups as $fg ) {
			$class_str = ' class="'.$preSh.'-editor_'.$fg.'"';

			$rows_vis = "";
			$rows_silent = "";
			$rows_hidden = "";
			foreach ( $fields[$fg] as $ff ) {

				// Field label
				$label = $this->field_label ( $ff, $bib_str );

				// Adjust the widget mode on demand
				$wm = $this->get_widget_mode ( $ff, $w_mode );

				// Field value widget
				$widget = '';
				switch ( $ff ) {
					case 'citeid':
						if ( $edExtConf['citeid_gen_new'] == $pi1->AUTOID_FULL ) {
							$widget .= $this->get_widget ( $ff, $pub[$ff], $wm );
						} else {
							$widget .= $this->get_widget ( $ff, $pub[$ff], $wm );
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
						$widget .= $this->get_widget ( 'year',  $pub['year'],  $wm );
						$widget .= ' - ';
						$widget .= $this->get_widget ( 'month', $pub['month'], $wm );
						$widget .= ' - ';
						$widget .= $this->get_widget ( 'day',   $pub['day'],   $wm );
						break;
					case 'month':
					case 'day':
						break;
					default:
						$widget .= $this->get_widget ( $ff, $pub[$ff], $wm );
				}
				if ( $ff == 'bibtype' ) {
					$widget .= $btn_update;
				}

				if ( strlen ( $widget ) > 0 ) {
					if ( $wm == $this->W_SILENT ) {
						$rows_silent .= $widget . "\n";
					} else if ( $wm == $this->W_HIDDEN ) {
						$rows_hidden .= $widget . "\n";
					} else {
						$label  = $pi1->cObj->stdWrap ( $label, $edConf['field_labels.'] );
						$widget = $pi1->cObj->stdWrap ( $widget, $edConf['field_widgets.'] );
						$rows_vis .= '<tr>' . "\n";
						$rows_vis .= '<th' . $class_str . '>' . $label  . '</th>' . "\n";
						$rows_vis .= '<td' . $class_str . '>' . $widget . '</td>' . "\n";
						$rows_vis .= '</tr>' . "\n";
					}
				}

			}

			# Append header and table it there're rows
			if ( strlen ( $rows_vis ) > 0 ) {
				$con .= '<h3>';
				$con .= $this->get_ll ( $this->LLPrefix.'fields_'.$fg );
				$con .= '</h3>';

				$con .= '<table class="'.$preSh.'-editor_fields">' . "\n";
				$con .= '<tbody>' . "\n";

				$con .= $rows_vis . "\n";

				$con .= '</tbody>' . "\n";
				$con .= '</table>' . "\n";
			}

			if ( strlen ( $rows_silent ) > 0 ) {
				$con .= "\n";
				$con .= $rows_silent . "\n";
			}

			if ( strlen ( $rows_hidden ) > 0 ) {
				$con .= "\n";
				$con .= $rows_hidden . "\n";
			}
		}
		
		// Invisible 'uid' and 'mod_key' field
		if ( !$this->is_new ) {
			if ( isset ( $pub['mod_key'] ) ) {
				$con .= tx_sevenpack_utility::html_hidden_input (
					$preId.'[DATA][pub][mod_key]', 
					htmlspecialchars ( $pub['mod_key'], ENT_QUOTES ) );
				$con .= "\n";
			}
		}

		// Footer Buttons
		$con .= '<div class="'.$preSh.'-editor_button_box">' . "\n";
		$con .= '<span class="'.$preSh.'-box_right">';
		$con .= $btn_delete;
		$con .= '</span>' . "\n";
		$con .= '<span class="'.$preSh.'-box_left">';
		$con .= $btn_save . $btn_edit . $btn_cancel;
		$con .= '</span>' . "\n";
		$con .= '</div>' . "\n";

		$con .= '</div>' . "\n";
		$con .= '</form>' . "\n";

		return $con;
	}


	/** 
	 * Depending on the bibliography type this function returns
	 * The label for a field
	 * @param field The field
	 * @param bib_str The bibtype identifier string
	 */
	function field_label ( $field, $bib_str ) {
		$label = $this->ref_read->refTable . '_' . $field;

		switch ( $field ) {
			case 'authors':
				$label = $this->ref_read->authorTable . '_' . $field;
				break;
			case 'year':
				$label = 'olabel_year_month_day';
				break;
			case 'month':
			case 'day':
				$label = '';
				break;
		}

		$over = array (
			$this->conf['olabel.']['all.'][$field],
			$this->conf['olabel.'][$bib_str . '.'][$field]
		);

		foreach ( $over as $lvar ) {
			if ( is_string ( $lvar ) ) $label = $lvar;
		}

		$label = trim ( $label );
		if ( strlen ( $label ) > 0 ) {
			$label = $this->get_ll ( $label, $label, TRUE );
		}
		return $label;
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
		$bib_str = $bibType;
		if ( is_numeric ( $bib_str ) ) {
			$bib_str = $this->ref_read->allBibTypes[$bibType];
		}

		$all_groups = array ( 'all', $bib_str );
		$all_types = array ( 'required', 'optional', 'library' );

		// Read field list from TS configuration
		$cfg_fields = array();
		foreach ( $all_groups as $group ) {
			$cfg_fields[$group] = array();
			$cfg_arr =& $this->conf['groups.'][$group.'.'];
			if ( is_array ( $cfg_arr ) ) {
				foreach ( $all_types as $type ) {
					$cfg_fields[$group][$type] = array();
					$ff = tx_sevenpack_utility::multi_explode_trim (
						array ( ',', '|' ), $cfg_arr[$type], TRUE );
					//t3lib_div::debug ( $ff );
					$cfg_fields[$group][$type] = $ff;
				}
			}
		}

		// Merge field lists
		$pubFields = $this->ref_read->pubFields;
		unset ( $pubFields[array_search ( 'bibtype',$pubFields)] );
		foreach ( $all_types as $type ) {
			$fields[$type] = array();
			$cur =& $fields[$type];
			if ( is_array ( $cfg_fields[$bib_str][$type] ) )
				$cur = $cfg_fields[$bib_str][$type];
			if ( is_array ( $cfg_fields['all'][$type] ) ) {
				foreach ( $cfg_fields['all'][$type] as $field ) {
					$cur[] = $field;
				}
			}
			$cur = array_unique ( $cur );
			//t3lib_div::debug ( array( 'After' => $cur ) );
			$cur = array_intersect ( $cur, $pubFields );
			$pubFields = array_diff ( $pubFields, $cur );
		}

		// Calculate the remaining 'other' fields
		$fields['other'] = $pubFields;
		$fields['typo3'] = array ( 'uid', 'hidden', 'pid' );

		//t3lib_div::debug ( $fields );
		return $fields;
	}
	
	
	/**
	 * Get the widget mode for an edit widget
	 *
	 * @return The widget mode
	 */
	function get_widget_mode ( $field, $mode )
	{
		$edConf =& $this->conf;

		$wm = $mode;

		if ( ( $wm == $this->W_EDIT ) && $edConf['no_edit.'][$field] ) {
			$wm = $this->W_SHOW;
		}
		if ( $edConf['no_show.'][$field] ) {
			$wm = $this->W_HIDDEN;
		}

		if ( $field == 'uid' ) {
			if ( $wm == $this->W_EDIT ) {
				$wm = $this->W_SHOW;
			} else if ( $wm == $this->W_HIDDEN ) {
				$wm = $this->W_SILENT;
			}
		}

		return $wm;
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

		switch ( $field ) {
			case 'authors':
				$con .= $this->get_authors_widget ( $value, $mode );
				break;
			case 'pid':
				$con .= $this->get_pid_widget ( $value, $mode );
				break;
			default:
				if ( $mode == $this->W_EDIT ) {
					$con .= $this->get_default_edit_widget ( $field, $value, $mode );
				} else {
					$con .= $this->get_default_static_widget ( $field, $value, $mode );
				}
		}
		return $con;
	}


	function get_default_edit_widget ( $field, $value, $mode )
	{
		$cfg =& $GLOBALS['TCA'][$this->ref_read->refTable]['columns'][$field]['config'];
		$pi1 =& $this->pi1;
		$cclass = $pi1->prefixShort.'-editor_input';
		$Iclass = ' class="'.$cclass.'"';
		$con = ''; // Content

		$isize = 60;
		$all_size = array (  
			$this->conf['input_size.']['default'],
			$this->conf['input_size.'][$field]
		);
		foreach ( $all_size as $ivar) {
			if ( is_numeric ( $ivar ) ) $isize = intval ( $ivar );
		}

		// Default widget
		$widgetType = $cfg['type'];
		$fieldAttr  = $pi1->prefix_pi1 . '[DATA][pub][' . $field . ']';
		$nameAttr   = ' name="' . $fieldAttr . '"';
		$htmlValue  = $pi1->filter_pub_html ( $value, TRUE );

		$attrs = array();
		$attrs['class'] = $cclass;

		switch ( $widgetType )  {
			case 'input' : 
				if ( $cfg['max'] )
					$attrs['maxlength'] = $cfg['max'];
				$size = intval ( $cfg['size'] );
				if ( $size > 40 ) 
					$size = $isize;
				$attrs['size'] = strval ( $size );

				$con .= tx_sevenpack_utility::html_text_input (
					$fieldAttr, $htmlValue, $attrs );

				break;

			case 'text' :
				$con .= '<textarea' . $nameAttr;
				$con .= ' rows="'.$cfg['rows'].'"';
				$con .= ' cols="' . strval ( $isize ) . '"';
				$con .= $Iclass . '>';
				$con .= $htmlValue;
				$con .= '</textarea>';
				$con .= "\n";

				break;

			case 'select' :
				$attrs['name'] = $fieldAttr;
				if ( $field == 'bibtype' ) {
					$attrs['onchange'] = 'click_update_button()';
				}

				$pairs = array();
				for ( $ii=0; $ii < sizeof($cfg['items']); $ii++ )  {
					$p_name = $this->get_db_ll ( $cfg['items'][$ii][0], $cfg['items'][$ii][0] );
					$p_val = strtolower ( $cfg['items'][$ii][1] );
					$pairs[$p_val] = $p_name;
				}

				$con .= tx_sevenpack_utility::html_select_input (
					$pairs, $value, $attrs );

				break;

			case 'check' :
				$con .= tx_sevenpack_utility::html_check_input (
					$fieldAttr, '1', ( $value == 1 ), $attrs );

				break;

			default :
				$con .= 'Unknown edit widget: ' . $widgetType;
		}

		return $con;
	}


	function get_default_static_widget ( $field, $value, $mode )
	{
		$cfg =& $GLOBALS['TCA'][$this->ref_read->refTable]['columns'][$field]['config'];
		$pi1 =& $this->pi1;
		$Iclass = ' class="'.$pi1->prefixShort.'-editor_input'.'"';

		// Default widget
		$widgetType = $cfg['type'];
		$fieldAttr  = $pi1->prefix_pi1 . '[DATA][pub][' . $field . ']';
		$htmlValue  = $pi1->filter_pub_html ( $value, TRUE );

		$con = '';
		if ( $mode == $this->W_SHOW ) {
			$con .= tx_sevenpack_utility::html_hidden_input ( 
				$fieldAttr, $htmlValue );

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

		} else if ( $mode == $this->W_SILENT ) {

			$con .= tx_sevenpack_utility::html_hidden_input ( 
				$fieldAttr, $htmlValue );

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
		$cclass = $this->pi1->prefixShort.'-editor_input';
		$pi1 =& $this->pi1;

		$isize = 25;
		$ivar = $this->conf['input_size.']['author'];
		if ( is_numeric ( $ivar ) ) $isize = intval ( $ivar );

		$key_action = $pi1->prefix_pi1.'[action]';
		$key_data = $pi1->prefix_pi1.'[DATA][pub][authors]';

		// Author widget
		$authors = is_array ( $value ) ? $value : array();
		$aNum = sizeof ( $authors );
		$edOpts =& $pi1->piVars['editor'];
		$edOpts['numAuthors'] = max ( $edOpts['numAuthors'], 
			sizeof ( $authors ), $pi1->extConf['editor']['numAuthors'], 1 );

		if ( ( $mode == $this->W_SHOW ) || ( $mode == $this->W_EDIT ) ) {
			$au_con = array();
			for ( $i=0; $i < $edOpts['numAuthors']; $i++ ) {
				if ( $i > ( $aNum - 1 ) && ( $mode != $this->W_EDIT ) )
					break;

				$row_con = array();
				
				$fn = $pi1->filter_pub_html ( $authors[$i]['forename'], TRUE );
				$sn = $pi1->filter_pub_Html ( $authors[$i]['surname'], TRUE );
				
				$row_con[0] = strval ( $i+1 );
				if ( $mode == $this->W_SHOW ) {
					$row_con[1] = tx_sevenpack_utility::html_hidden_input (
						$key_data.'['.$i.'][forename]', $fn );
					$row_con[1] .= $fn;

					$row_con[2] =  tx_sevenpack_utility::html_hidden_input (
						$key_data.'['.$i.'][surname]',  $sn );
					$row_con[2] .= $sn;

				} else if ( $mode == $this->W_EDIT ) {

					$lowerBtn = tx_sevenpack_utility::html_image_input ( 
						$key_action.'[lower_author]', 
						strval ( $i ), $pi1->icon_src['down'] );
					$raiseBtn = tx_sevenpack_utility::html_image_input ( 
						$key_action.'[raise_author]', 
						strval ( $i ), $pi1->icon_src['up'] );

					$row_con[1] = tx_sevenpack_utility::html_text_input ( 
						$key_data.'['.$i.'][forename]', $fn,
						array ( 'size' => $isize, 'maxlength' => 255, 'class' => $cclass ) );

					$row_con[2] .= tx_sevenpack_utility::html_text_input ( 
						$key_data.'['.$i.'][surname]', $sn,
						array ( 'size' => $isize, 'maxlength' => 255, 'class' => $cclass ) );

					$row_con[3] = ( $i < ($aNum-1) ) ? $lowerBtn : '';
					$row_con[4] = ( ($i>0) && ($i<($aNum)) ) ? $raiseBtn : '';
				}
				
				$au_con[] = $row_con;
			}

			$con .= '<table class="'.$pi1->prefixShort.'-editor_author">' . "\n";
			$con .= '<tbody>' . "\n";

			// Head rows
			$con .= '<tr><th></th>';
			$con .= '<th>';
			$con .= $this->get_ll ( $this->ref_read->authorTable.'_forename' );
			$con .= '</th>';
			$con .= '<th>';
			$con .= $this->get_ll ( $this->ref_read->authorTable.'_surname' );
			$con .= '</th>';
			if ( $mode == $this->W_EDIT ) {
				$con .= '<th></th><th></th>';
			}
			$con .= '</tr>' . "\n";

			// Author data rows
			foreach ( $au_con as $row_con ) {
				$con .= '<tr>';
				$con .= '<th class="'.$pi1->prefixShort.'-editor_author_num">';
				$con .= $row_con[0];
				$con .= '</th><td>';
				$con .= $row_con[1];
				$con .= '</td><td>';
				$con .= $row_con[2];
				if ( sizeof ( $row_con ) > 3 ) {
					$con .= '</td><td style="padding: 1px;">';
					$con .= $row_con[3];
					$con .= '</td><td style="padding: 1px;">';
					$con .= $row_con[4];
				}
				$con .= '</td>';
				$con .= '</tr>' . "\n";
			}

			$con .= '</tbody>';
			$con .= '</table>' . "\n";

			// Bottom buttons
			if ( $mode == $this->W_EDIT ) {
				$con .= '<div style="padding-top: 0.5ex; padding-bottom: 0.5ex;">' . "\n";
				$con .= tx_sevenpack_utility::html_submit_input (
					$key_action.'[more_authors]', '+' );
				$con .= ' ';
				$con .= tx_sevenpack_utility::html_submit_input (
					$key_action.'[less_authors]', '-' );
				$con .= '</div>' . "\n";
			}

		} else if ( $mode == $this->W_SILENT ) {

			for ( $i=0; $i < sizeof ( $authors ); $i++ ) {
				$fn = $pi1->filter_pub_html ( $authors[$i]['forename'], TRUE );
				$sn = $pi1->filter_pub_Html ( $authors[$i]['surname'], TRUE );
				$con .= tx_sevenpack_utility::html_hidden_input (
					$key_data.'['.$i.'][forename]', $fn );
				$con .= tx_sevenpack_utility::html_hidden_input (
					$key_data.'['.$i.'][surname]', $sn );
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
		$pi1 =& $this->pi1;
		$pids = $pi1->extConf['pid_list'];
		$value = intval ( $value );
		$fieldAttr = $pi1->prefix_pi1 . '[DATA][pub][pid]';

		$attrs = array();
		$attrs['class'] = $pi1->prefixShort.'-editor_input';

		// Fetch page titles
		$pages = tx_sevenpack_utility::get_page_titles ( $pids ); 

		if ( $mode == $this->W_SHOW ) {
			$con .= tx_sevenpack_utility::html_hidden_input (
				$fieldAttr, $value );
			$con .= strval ( $pages[$value] );

		} else if ( $mode == $this->W_EDIT ) {
			$attrs['name'] = $fieldAttr;
			$con .= tx_sevenpack_utility::html_select_input (
				$pages, $value, $attrs );

		} else if ( $mode == $this->W_SILENT ) {
			$con .= tx_sevenpack_utility::html_hidden_input (
				$fieldAttr, $value );

		}

		return $con;
	}


	/** 
	 * Returns the default storage uid
	 *
	 * @return The parent id pid
	 */
	function get_default_pid ( ) {
		$edConf =& $this->conf;
		$pid = 0;
		if ( is_numeric ( $edConf['default_pid'] ) ) {
			$pid = intval ( $edConf['default_pid'] );
		}
		if ( !in_array ( $pid, $this->ref_read->pid_list ) ) {
			$pid = intval ( $this->ref_read->pid_list[0] );
		}
		return $pid;
	}

	
	/** 
	 * Returns the default publication data 
	 *
	 * @return An array containing the default publication data
	 */
	function get_ref_default ( ) {
		$edConf =& $this->conf;
		$pub = array();
		
		if ( is_array ( $edConf['field_default.'] ) ) {
			foreach ( $this->ref_read->refFields as $field ) {
				if ( array_key_exists ( $field, $edConf['field_default.'] ) )
					$pub[$field] = strval ( $edConf['field_default.'][$field] );
			}
		}

		if ( $pub['bibtype'] == 0 ) {
			$pub['bibtype'] = array_search ( 'article', $this->ref_read->allBibTypes );
		}

		if ( $pub['year'] == 0 ) {
			if ( is_numeric ( $pi1->extConf['year'] ) )
				$pub['year'] = intval ( $pi1->extConf['year'] );
			else
				$pub['year'] = intval ( date ( 'Y' ) );
		}
			
		if ( !in_array ( $pub['pid'], $this->ref_read->pid_list ) ) {
			$pub['pid'] = $this->get_default_pid();
		}

		return $pub;
	}


	/** 
	 * Returns the publication data that was encoded in the
	 * HTTP erquest
	 *
	 * @return An array containing the formatted publication 
	 *         data that was found in the HTTP request
	 */
	function get_ref_http ( $hsc = FALSE ) {
		$pub = array();
		$charset = $this->pi1->extConf['charset']['upper'];
		$fields = $this->ref_read->pubFields;
		$fields[] = 'uid';
		$fields[] = 'pid';
		$fields[] = 'hidden';
		$fields[] = 'mod_key'; // Gets generated on loading from the database
		$data =& $this->pi1->piVars['DATA']['pub'];
		if ( is_array ( $data ) ) {
			foreach ( $fields as $ff ) {
				switch ( $ff ) {

					case 'authors':
						if ( is_array ( $data[$ff] ) ) {
							$pub['authors'] = array();
							foreach ( $data[$ff] as $v ) {
								$fn = trim ( $v['forename'] );
								$sn = trim ( $v['surname'] );
								if ( $hsc ) {
									$fn = htmlspecialchars ( $fn, ENT_QUOTES, $charset );
									$sn = htmlspecialchars ( $sn, ENT_QUOTES, $charset );
								}
								if ( strlen ( $fn ) || strlen ( $sn ) ) {
									$pub['authors'][] = array ( 'forename' => $fn, 'surname' => $sn );
								}
							}
						}

						break;

					default:

						if ( array_key_exists ( $ff, $data ) ) {
							$pub[$ff] = $data[$ff];
							if ( $hsc ) {
								$pub[$ff] = htmlspecialchars ( $pub[$ff], ENT_QUOTES, $charset );
							}
						}
				}
			}
		}
		//t3lib_div::debug ( $pub );
		return $pub;
	}


	/** 
	 * Performs actions after Database write access (save/delete)
	 *
	 * @return The requested dialog
	 */
	function post_db_write ( ) {
		$events = array();
		$errors = array();
		if ( $this->conf['delete_no_ref_authors'] ) {
			$count = $this->db_utility->delete_no_ref_authors();
			if ( $count > 0 ) {
				$msg = $this->get_ll ( 'msg_deleted_authors' );
				$msg = str_replace ( '%d', strval ( $count ), $msg );
				$events[] = $msg;
			}
		}
		if ( $this->conf['full_text.']['update'] ) {
			$stat = $this->db_utility->update_full_text_all();

			$count = sizeof ( $stat['updated'] );
			if ( $count > 0 ) {
				$msg = $this->get_ll ( 'msg_updated_full_text' );
				$msg = str_replace ( '%d', strval ( $count ), $msg );
				$events[] = $msg;
			}

			if ( sizeof ( $stat['errors'] ) > 0 ) {
				foreach ( $stat['errors'] as $err ) {
					$msg = $err[1]['msg'];
					$errors[] = $msg;
				}
			}

			if ( $stat['limit_num'] ) {
				$msg = $this->get_ll ( 'msg_warn_ftc_limit' ) . ' - ';
				$msg .= $this->get_ll ( 'msg_warn_ftc_limit_num' );
				$errors[] = $msg;
			}

			if ( $stat['limit_time'] ) {
				$msg = $this->get_ll ( 'msg_warn_ftc_limit' ) . ' - ';
				$msg .= $this->get_ll ( 'msg_warn_ftc_limit_time' );
				$errors[] = $msg;
			}

		}
		return array ( $events, $errors );
	}


	/** 
	 * Creates a html text from a post db write event
	 *
	 * @return The html message string
	 */
	function post_db_write_message ( $messages ) {
		$con = '';
		if ( count ( $messages[0] ) > 0 ) {
			$con .= '<h4>' . $this->get_ll ( 'msg_title_events' ) . '</h4>' . "\n";
			$con .= $this->post_db_write_message_items ( $messages[0] );
		}
		if ( count ( $messages[1] ) > 0 ) {
			$con .= '<h4>' . $this->get_ll ( 'msg_title_errors' ) . '</h4>' . "\n";
			$con .= $this->post_db_write_message_items ( $messages[1] );
		}
		return $con;
	}


	/** 
	 * Creates a html text from a post db write event
	 *
	 * @return The html message string
	 */
	function post_db_write_message_items ( $messages ) {
		$con = '';
		$messages = tx_sevenpack_utility::string_counter ( $messages );
		$con .= '<ul>' . "\n";
		foreach ( $messages as $msg => $count ) {
			$msg = htmlspecialchars ( $msg, ENT_QUOTES, $this->pi1->extConf['charset']['upper'] );
			$con .= '<li>';
 			$con .= $msg;
			if ( $count > 1 ) {
				$app = str_replace ( '%d', strval ( $count ), $this->get_ll ( 'msg_times' ) );
				$con .= '(' . $app . ')';
			}
 			$con .= '</li>' . "\n";
		}
		$con .= '</ul>' . "\n";
		return $con;
	}


	/** 
	 * This switches to the requested dialog
	 *
	 * @return The requested dialog
	 */
	function dialog_view () {
		$con = '';
		$pi1 =& $this->pi1;

		$this->ref_write = t3lib_div::makeInstance ( 'tx_sevenpack_reference_writer' );
		$this->ref_write->initialize( $this->ref_read );

		switch ( $pi1->extConf['dialog_mode'] ) {

			case $pi1->DIALOG_SAVE_CONFIRMED : 
				$pub = $this->get_ref_http();
				foreach ( $this->ref_read->refFields as $ff ) {
					if ( $pub[$ff] ) {
						if ( $this->conf['no_edit.'][$ff] || 
							$this->conf['no_show.'][$ff] ) 
						{
							unset ( $pub[$ff] );
						}
					}
				}
				if ( $this->ref_write->save_publication ( $pub ) ) {
					$con .= '<div class="'.$pi1->prefixShort.'-warning_box">' . "\n";
					$con .= '<p>'.$this->get_ll ( 'msg_save_fail' ).'</p>';
					$con .= '<p>'.$this->ref_write->html_error_message().'</p>';
					$con .= '</div>' . "\n";
				} else {
					$con .= '<p>'.$this->get_ll ( 'msg_save_success' ).'</p>';
					$messages = $this->post_db_write();
					$con .= $this->post_db_write_message ( $messages );
				}
				break;

			case $pi1->DIALOG_DELETE_CONFIRMED : 
				$pub = $this->get_ref_http();
				if ( $this->ref_write->delete_publication ( $pi1->piVars['uid'], $pub['mod_key'] ) ) {
					$con .= '<div class="'.$pi1->prefixShort.'-warning_box">' . "\n";
					$con .= '<p>'.$this->get_ll ( 'msg_delete_fail' ).'</p>';
					$con .= '<p>'.$this->ref_write->html_error_message().'</p>';
					$con .= '</div>' . "\n";
				} else {
					$con .= '<p>'.$this->get_ll ( 'msg_delete_success' ).'</p>';
					$messages = $this->post_db_write();
					$con .= $this->post_db_write_message ( $messages );
				}
				break;

			case $pi1->DIALOG_ERASE_CONFIRMED : 
				if ( $this->ref_write->erase_publication ( $pi1->piVars['uid'] ) ) {
					$con .= '<p>'.$this->get_ll ( 'msg_erase_fail' ).'</p>';
				} else {
					$con .= '<p>'.$this->get_ll ( 'msg_erase_success' ).'</p>';
					$messages = $this->post_db_write();
					$con .= $this->post_db_write_message ( $messages );
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
	function validate_data ( $pub )
	{
		$d_err = array();
		$title = $this->get_ll ( $this->LLPrefix.'title_confirm_save' );

		$bib_str = $this->ref_read->allBibTypes[$pub['bibtype']];

		$fields = $this->get_edit_fields ( $bib_str, TRUE );

		$cond = array();
		$parts = tx_sevenpack_utility::explode_trim ( ',', $this->conf['groups.'][$bib_str.'.']['required'] );
		foreach ( $parts as $part ) {
			if ( !( strpos ( $part, '|' ) === FALSE ) ) {
				$cond[] = tx_sevenpack_utility::explode_trim ( '|', $part );
			}
		}
		//t3lib_div::debug ( $cond );

		$warn =& $this->conf['warnings.'];
		//t3lib_div::debug ( $warn );

		//
		// Find empty required fields
		//
		$type = 'empty_fields';
		if ( $warn[$type] ) {
			$empty = array();
			// Find empty fields
			foreach ( $fields['required'] as $field ) {
				switch ( $field ) {
					case 'authors':
						if ( !is_array ( $pub[$field] ) || ( sizeof ( $pub[$field] ) == 0 ) )
							$empty[] = $field;
						break;
					default:
						if ( strlen ( trim ( $pub[$field] ) ) == 0 ) 
							$empty[] = $field;
				}	
			}

			// Check conditions
			$clear = array();
			foreach ( $empty as $em ) {
				$ok = FALSE;
				foreach ( $cond as $con ) {
					if ( in_array ( $em, $con ) ) {
						foreach ( $con as $ff ) {
							if ( !in_array ( $ff, $empty ) ) {
								$ok = TRUE;
								break;
							}
						}
						if ( $ok ) break;
					}
				}
				if ( $ok ) $clear[] = $em;
			}

			//t3lib_div::debug ( $empty );
			$empty = array_diff ( $empty, $clear );
			//t3lib_div::debug ( $empty );

			if ( sizeof ( $empty ) ) {
				$err = array ( 'type' => $type );
				$err['msg'] = $this->get_ll ( $this->LLPrefix.'error_empty_fields');
				$err['list'] = array();
				$bib_str = $this->ref_read->allBibTypes[$pub['bibtype']];
				foreach ( $empty as $field ) {
					switch ( $field ) {
						case 'authors':
							$str = $this->field_label ( $field, $bib_str );
							break;
						default:
							$str = $this->field_label ( $field, $bib_str );
					}
					$err['list'][] = array ( 'msg' => $str );
				}
				$d_err[] = $err;
			}
		}

		// Local file does not exist
		$type = 'file_nexist';
		if ( $warn[$type] ) {
			$file = $pub['file_url'];
			if ( tx_sevenpack_utility::check_file_nexist ( $file ) ) {
				$msg = $this->get_ll ( 'editor_error_file_nexist' );
				$msg = str_replace ( '%f', $file, $msg );
				$d_err[] = array ( 'type' => $type, 'msg' => $msg );
			}
		}

		// Cite id doubles
		$type = 'double_citeid';
		if ( $warn[$type] && !$this->conf['no_edit.']['citeid'] && 
			!$this->conf['no_show.']['citeid'] ) 
		{
			if ( $this->ref_read->citeid_exists ( $pub['citeid'], $pub['uid'] ) ) {
				$err = array ( 'type' => $type );
				$err['msg'] = $this->get_ll ( $this->LLPrefix.'error_id_exists');
				$d_err[] = $err; 
			}
		}

		return $d_err;
	}


	/** 
	 * Makes some html out of the return array of
	 * validate_data()
	 *
	 * @return An array with error messages
	 */
	function validation_error_string ( $errors, $level = 0 )
	{
		if ( !is_array ( $errors ) || ( sizeof ( $errors ) == 0 ) )
			return '';

		//t3lib_div::debug ( array ( 's_errors' => $errors ) );
		$charset = $this->pi1->extConf['charset']['upper'];

		$res = '<ul>';
		foreach ( $errors as $err ) {
			$tmp = '<li>';
			$msg = htmlspecialchars ( $err['msg'], ENT_QUOTES, $charset );
			$tmp .= $this->pi1->cObj->stdWrap ( $msg, 
				$this->conf['warn_box.']['msg.'] ) . "\n";

			$lst =& $err['list'];
			if ( is_array ( $lst ) && ( sizeof ( $lst ) > 0 ) ) {
				$tmp .= '<ul>';
				$tmp .= $this->validation_error_string ( $lst, $level + 1 );
				$tmp .= '</ul>'.  "\n";
			}

			$tmp .= '</li>';
			$res .= $tmp;
		}
		$res .= '</ul>';

		return $res;
	}


}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_editor_view.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_editor_view.php"]);
}

?>
