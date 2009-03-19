<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');


require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/pi1/class.tx_sevenpack_importer.php') );


class tx_sevenpack_Parser_Exception extends Exception { 
}

class tx_sevenpack_Translator_Exception extends Exception { 
}


/**
 * This parser follows the bibtex format described here
 * http://artis.imag.fr/~Xavier.Decoret/resources/xdkbibtex/bibtex_summary.html
 */
class tx_sevenpack_importer_bibtex extends tx_sevenpack_importer {

	public $bt; // Bibtex translator

	// The parser state
	public $pstate;
	public $pline;

	// Parser states ( S: Search, R: Read )
	public $P_S_Ref        = 1; // Search reference
	public $P_R_Ref_Type   = 2; // Read reference type
	public $P_S_Ref_Beg    = 3; // Search reference begin brace

	public $P_S_Id         = 4; // Search citeid
	public $P_R_Id         = 5; // Read citeid

	public $P_S_Comma      = 6; // Search Comma
	public $P_S_Pair_Name  = 7; // Search Name

	public $P_R_Pair_Name  = 8; // Read name
	public $P_S_Assign     = 9; // Search =
	public $P_S_Pair_Value = 10; // Search value
	public $P_R_Pair_Value = 11; // Search value

	// A value buffer
	public $pair_name;
	public $pair_val;
	public $pair_start;
	public $pair_brace;

	// A raw reference
	public $raw_ref;
	public $raw_refs;

	public $pubKeys;
	public $pubKeyMap;

	function initialize ( $pi1 ) {
		parent::initialize( $pi1 );

		$this->import_type = $pi1->IMP_BIBTEX;

		$this->bt = t3lib_div::makeInstance ( 'tx_sevenpack_PRegExp_Translator' );
		$bt =& $this->bt;

		$bt = t3lib_div::makeInstance ( 'tx_sevenpack_PRegExp_Translator' );

		// Local characters
		$replace = array (
			'`'    => '&\\1acute;',
			'~'    => '&\\1tilde;',
			'\\^'  => '&\\1circ;',
			'\\\'' => '&\\1grave;',
			'"'    => '&\\1uml;',
		);

		foreach( $replace as $key => $val ) {
			$bt->push ( '/\\\\' . $key . '\{(\w)\}' . '/',  $val );
			$bt->push ( '/\{\\\\' . $key . '(\w)\}' . '/',  $val );
			$bt->push ( '/\\\\' . $key . '(\w)([^\w]|$)' . '/',  $val.'\\2' );
		}

		$bt->push ( '/\\\\c\\{([cC])\\}/', '&\\1cedil;' );

		$bt->push ( '/{\\\\ss}/', '&szlig;' );
		$bt->push ( '/\\\\ss([^\w]|$)/', '&szlig;\\1' );

		$bt->push ( '/\{\\\\(ae|AE)\}/', '&\\1lig;' );
		$bt->push ( '/\\\\(ae|AE)([^\w]|$)/', '&\\1lig;\\2' );

		$bt->push ( '/\{\\\\([a-zA-Z])\\1\}/', '&\\1ring;' );
		$bt->push ( '/\\\\([a-zA-Z])\\1([^\w]|$)/', '&\\1ring;\\2' );

		$bt->push ( '/\{\\\\([oO])\}/', '&\\1slash;' );
		$bt->push ( '/\\\\([oO])([^\w]|$)/', '&\\1slash;\\2' );


		$bt->push ( '/\{\\\\euro\}/', '&euro;' );
		$bt->push ( '/\\\\euro([^\w]|$)/', '&euro;\\1' );

		$bt->push ( '/\{\\\\pounds\}/', '&pound;' );
		$bt->push ( '/\\\\pounds([^\w]|$)/', '&pound;\\1' );


		// Greek characters
		$replace = array (
			'alpha'   => '&alpha;',
			'beta'    => '&beta;',
			'gamma'   => '&gamma;',
			'delta'   => '&delta;',
			'epsilon' => '&epsilon;',
			'zeta'    => '&zeta;',
			'theta'   => '&theta;',
			'iota'    => '&iota;',
			'kappa'   => '&kappa;',
			'lambda'  => '&lambda;',
			'mu'      => '&mu;',
			'nu'      => '&nu;',
			'xi'      => '&xi;',
			'pi'      => '&pi;',
			'rho'     => '&rho;',
			'sigma'   => '&sigma;',
			'tau'     => '&tau;',
			'upsilon' => '&upsilon;',
			'phi'     => '&phi;',
			'chi'     => '&chi;',
			'psi'     => '&psi;',
			'omega'   => '&omega;',

			'Gamma'   => '&Gamma;',
			'Delta'   => '&Delta;',
			'Theta'   => '&Theta;',
			'Lambda'  => '&Lambda;',
			'Xi'      => '&Xi;',
			'Pi'      => '&Pi;',
			'Sigma'   => '&Sigma;',
			'Upsilon' => '&Upsilon;',
			'Phi'     => '&Phi;',
			'Psi'     => '&Psi;',
			'Omega'   => '&Omega;',
		);

		foreach( $replace as $key => $val ) {
			$bt->push ( '/\\\\' . $key . '([^\w]|$)/',  $val . '\\1' );
		}

		// Lesser, greater, amp
		$bt->push ( '/\\\\\&/', '&amp;' );
		$bt->push ( '/</', '&lt;' );
		$bt->push ( '/>/', '&gt;' );

		// Environments
		$bt->push ( '/\\\\emph\{([^\{]+)\}/', '<em>\\1</em>' );

		// Mathematics

		// Math expressions
		$bt->push ( '/([^\\\\])\\^\{\\\\circ\}/', '\\1&deg;' );
		$bt->push ( '/\\\\sqrt([^\w]|$)/', '&radic;\\1' );
		$bt->push ( '/([^\\\\])\\^\{([^\{]+)\}/', '\\1<sup>\\2</sup>' );
		$bt->push ( '/([^\\\\])\\_\{([^\{]+)\}/', '\\1<sub>\\2</sub>' );


		$replace = array (
			// Relational symbols
			'approx' => '&approx;',
			'equiv'  => '&equiv;',
			'propto' => '&prop;',
			'le'     => '&le;',
			'neq'    => '&ne;',
			'ge'     => '&geq;',
	
			// Logical symbols
			'neg'    => '&not;',
			'wedge'  => '&and;',
			'vee'    => '&or;',
			'oplus'  => '&oplus;',
	
			'exists' => '&exist;',
			'forall' => '&forall;',
	
			// Set symbols
			'cap'      => '&cap;',
			'cup'      => '&cup;',
			'subset'   => '&sub;',
			'supset'   => '&sup;',
			'emptyset' => '&empty;',
			'in'       => '&isin;',
			'notin'    => '&notin;',
	
			// Misc symbols
			'infty'  => '&infin;',
			'sim'    => '&sim;',
			'rfloor' => '&rfloor;',
			'prime'  => '&prime;',
			'sim'    => '&sim;',
			'times'  => '&times;',
		);

		foreach( $replace as $key => $val ) {
			$bt->push ( '/\\\\' . $key . '([^\w]|$)/',  $val . '\\1' );
		}

		// Environment markers
		$bt->push ( '/\\\\\(/', '' );
		$bt->push ( '/\\\\\)/', '' );

		$bt->push ( '/(^|[^\\\\])\$\$/', '\\1' );
		$bt->push ( '/(^|[^\\\\])\$/', '\\1' );

		// Miscellaneous
		$bt->push ( '/\\\\verb(.)([^\1]?+)\1/', '\2' );

		$bt->push ( '/\\\\%/', '%' );
		$bt->push ( '/\\\\\$/', '$' );
		$bt->push ( '/\\\\#/', '#' );
		//$bt->push ( '/\\\\_/',  '_' );
		$bt->push ( '/\\\\~/',  '~' );
		$bt->push ( '/\\\\\^/', '^' );
		$bt->push ( '/\\\\{/',  '{' );
		$bt->push ( '/\\\\}/',  '}' );

		$bt->push ( '/\'\'/', '&quot;' );

		// Protected parts
		$bt->push ( '/\{([^\{]+)\}/', '<prt>\\1</prt>' );

		$bt->push ( '/\\\\textbackslash/', '\\' );

		// Remove multi spaces
		$bt->push ( '/\s{2,}/', ' ' );
		$bt->push ( '/^\s+/', '' );
		$bt->push ( '/\s+$/', '' );

		// Setup publication fields
		$this->pubKeys = array();
		foreach ( $this->ra->pubFields as $field ) {
			$lfield = strtolower ( $field );
			switch ( $lfield ) {
				case 'bibtype':
				case 'citeid':
					break;
				default:
					$this->pubKeys[] = $lfield;
					if ( $field != $lfield ) {
						$this->pubKeyMap[$lfield] = $field;
					}
			}
		}
		//t3lib_div::debug ( array( 'pubKeys' => $this->pubKeys) );
		//t3lib_div::debug ( array( 'pubKeys' => $this->pubKeyMap) );
	}


	function import_pre_info ( ) {
		$res  = '<p>';
		$res .= 'This will import publication references from a BibTeX file, ';
		$res .= 'though there are some limitations';
		$res .= '</p>' . "\n";
		$res .= '<ul>' . "\n";
		$res .= '<li>';
		$res .= 'The BibTeX format is not strictly defined anywhere.';
		$res .= 'Therefore this parser merely tries to get the best out of it.<br/>';
		$res .= 'If in doubt try it on an empty storage folder first.';
		$res .= '</li>' . "\n";
		$res .= '<li>';
		$res .= 'Non ASCII characters should be encoded in TeX syntax (e.g. \\"u for &uuml;)<br/>';
		$res .= 'Everything else may cause unexpected behaviour.' . "\n";
		$res .= '</li>' . "\n";
		$res .= '<li>';
		$res .= '@String( var = "some text" ) placeholders are not supported';
		$res .= '</li>' . "\n";
		$res .= '</ul>' . "\n";
		return $res;
	}


	function import_state_2 ( ) {
		$stat = array();
		$action = $this->pi1->get_link_url ( array ( 'import_state'=>2 ) );
		$buff_size = 1024;
		//$buff_size = 10;
		$con = '';

		$stat   =& $this->stat;

		$stat['file_name'] = $_FILES['ImportFile']['name'];
		$stat['file_size'] = $_FILES['ImportFile']['size'];
		$stat['succeeded'] = 0;
		$stat['failed']    = 0;
		$stat['errors']    = array();
		$stat['storage']   = $this->storage_pid;
		$stat['warnings']  = array();

		$this->pline = 1;
		$this->pstate = $this->P_S_Ref;
		$this->clear_raw_ref();
		$this->raw_refs = array();

		$handle = fopen ( $_FILES['ImportFile']['tmp_name'], 'r' );
		try {
			while ( !feof ( $handle ) ) {
				$buffer = fread ( $handle, $buff_size );

				$buffer = str_replace ( "\r", ' ', $buffer );
				//$buffer = preg_replace ( '\s+', ' ', $buffer );
				//$buffer = str_replace ( "\n", ' ', $buffer );

				// Split lines
				$buff_arr = explode ( "\n", $buffer );

				$ii = 0;
				$count = count ( $buff_arr );
				foreach ( $buff_arr as $this->buffer ) {
					$ii++;
					$this->parser_switch();
					if ( ( $count > 1 ) && ( $ii < $count ) )
						$this->pline += 1;
				}
			}
		} catch ( tx_sevenpack_Parser_Exception $exc ) {
			$stat['errors'][] = 'Line ' . strval ( $this->pline ) . ': ' . $exc->getMessage();
		}

		fclose ( $handle );

		// Translate and save the raw references
		foreach ( $this->raw_refs as $raw ) {
			try {
				$pub = $this->translate_raw_ref ( $raw );
			} catch ( tx_sevenpack_Translator_Exception $exc ) {
				$stat['failed']++;
				$stat['errors'][] = $exc->getMessage();
			}

			$pub['pid'] = $this->storage_pid;
			//t3lib_div::debug ( $pub );
			$s_ret = false;
			$s_ret = $this->ra->save_publication ( $pub );
			if ( $s_ret ) {
				$stat['failed']++;
				$stat['errors'][] = $this->ra->error_message ( );
			} else {
				$stat['succeeded']++;
			}
		}

		$con .= $this->import_stat_str ( $stat );
		return $con;
	}


	/**
	 * Clears the current raw reference
	 */
	function clear_raw_ref ( ) {
		$this->raw_ref = array ( 
			'type' => '', 
			'citeid' => '', 
			'values'=>array() 
		);
	}


	/**
	 * Pushes the internal name/value pair to the current raw reference
	 */
	function push_raw_pair ( ) {
		$this->raw_ref['values'][$this->pair_name] = $this->pair_value;
		//$this->debug( 'Pushed raw pair: ' . $this->pair_name . ' => ' . $this->pair_value );
	}


	/**
	 * Pushes the current raw reference to the raw reference list
	 */
	function push_raw_ref ( ) {
		$this->raw_refs[] = $this->raw_ref;
		$this->clear_raw_ref();
	}


	/**
	 * Switches the parser state
	 */
	function parser_switch ( ) {
		// Parse buffer chunk
		while ( strlen ( $this->buffer ) > 0 ) {

			switch ( $this->pstate ) {

				case $this->P_S_Ref:
					//$this->debug( 'Searching reference' );
					$pos = strpos ( $this->buffer, '@' );
					if ( $pos === FALSE ) {
						$this->buffer = '';
					} else {
						$this->pstate = $this->P_R_Ref_Type;
						$this->raw_ref['type'] = '';
						$this->buffer = substr ( $this->buffer, $pos+1 );
					}
					break;

				case $this->P_R_Ref_Type:
					//$this->debug( 'Reading reference type' );
					$matches = array();
					$type = '';
					if ( preg_match ( '/^([^,\s{]+)/', $this->buffer, $matches ) > 0 ) {
						$type = $matches[1];
						$this->raw_ref['type'] .= $type;
						$this->buffer = substr ( $this->buffer, strlen ( $type ) );
					} else {
						if ( strlen ( $this->raw_ref['type'] ) == 0 ) {
							throw new tx_sevenpack_Parser_Exception ( 'Empty bibliography type' );
						}
						//$this->debug( 'Found reference type: ' . $this->raw_ref['type'] );
						$this->pstate = $this->P_S_Ref_Beg;
					}
					break;

				case $this->P_S_Ref_Beg:
					$this->buffer = preg_replace ( '/^\s*/', '',  $this->buffer );
					if ( strlen ( $this->buffer ) > 0 ) {
						if ( substr ( $this->buffer, 0, 1 ) == "{" ) {
							//$this->debug( 'Found reference begin' );
							$this->buffer = substr ( $this->buffer, 1 );
							$this->pstate = $this->P_S_Id;
						} else {
							throw new tx_sevenpack_Parser_Exception ( 'Expected an {' );
						}
					}
					break;

				case $this->P_S_Id:
					$this->buffer = preg_replace ( '/^\s*/', '',  $this->buffer );
					if ( strlen ( $this->buffer ) > 0 ) {
						if ( preg_match ( '/^[^,\s]+/', $this->buffer ) > 0 ) {
							//$this->debug( 'Found citeid begin' );
							$this->pstate = $this->P_R_Id;
						} else {
							throw new tx_sevenpack_Parser_Exception ( 'Invalid citeid beginning' );
						}
					}
					break;

				case $this->P_R_Id:
					$matches = array();
					$id = '';
					if ( preg_match ( '/^([^,\s]+)/', $this->buffer, $matches ) > 0 ) {
						$id = $matches[1];
						$this->raw_ref['citeid'] .= $id;
						$this->buffer = substr ( $this->buffer, strlen ( $id ) );
					} else {
						if ( strlen ( $this->raw_ref['citeid'] ) == 0 ) {
							throw new tx_sevenpack_Parser_Exception ( 'Empty citeid' );
						}
						//$this->debug( 'Found citeid: ' . $this->raw_ref['citeid'] );
						$this->pstate = $this->P_S_Comma;
					}
					break;

				case $this->P_S_Comma:
					$this->buffer = preg_replace ( '/^\s*/', '',  $this->buffer );
					if ( strlen ( $this->buffer ) > 0 ) {
						$char = substr ( $this->buffer, 0, 1 );
						if ( $char == "," ) {
							//$this->debug( 'Found Comma' );
							$this->buffer = substr ( $this->buffer, 1 );
							$this->pstate = $this->P_S_Pair_Name;
						} else if ( $char == "}" ) {
							//$this->debug( 'Found reference end' );
							$this->buffer = substr ( $this->buffer, 1 );
							$this->push_raw_ref();
							$this->pstate = $this->P_S_Ref;
						} else {
							throw new tx_sevenpack_Parser_Exception ( 'Expected , or } but found: ' . $char );
						}
					}
					break;

				case $this->P_S_Pair_Name:
					$this->buffer = preg_replace ( '/^\s*/', '',  $this->buffer );
					if ( strlen ( $this->buffer ) > 0 ) {
						$char = substr ( $this->buffer, 0, 1 );
						if ( preg_match ( '/^[a-zA-Z_0-9]/', $char ) > 0 ) {
							//$this->debug( 'Found pair name begin' );
							$this->pair_name = '';
							$this->pstate = $this->P_R_Pair_Name;
						} else if ( $char == "}" ) {
							//$this->debug( 'Found reference end' );
							$this->buffer = substr ( $this->buffer, 1 );
							$this->push_raw_ref();
							$this->pstate = $this->P_S_Ref;
						} else {
							throw new tx_sevenpack_Parser_Exception ( 'Found illegal pair name characer: ' . $char );
						}
					}
					break;

				case $this->P_R_Pair_Name:
					$matches = array();
					$str = '';
					if ( preg_match ( '/^([a-zA-Z_0-9]+)/', $this->buffer, $matches ) > 0 ) {
						$str = $matches[1];
						$this->pair_name .= $str;
						$this->buffer = substr ( $this->buffer, strlen ( $str ) );
					} else {
						if ( strlen ( $this->pair_name ) == 0 ) {
							throw new tx_sevenpack_Parser_Exception ( 'Empty value name' );
						}
						//$this->debug( 'Found pair name: ' . $this->pair_name );
						$this->pstate = $this->P_S_Assign;
					}
					break;

				case $this->P_S_Assign:
					$this->buffer = preg_replace ( '/^\s*/', '',  $this->buffer );
					if ( strlen ( $this->buffer ) > 0 ) {
						$char = substr ( $this->buffer, 0, 1 );
						if ( $char == "=" ) {
							//$this->debug( 'Found Assignment' );
							$this->buffer = substr ( $this->buffer, 1 );
							$this->pstate = $this->P_S_Pair_Value;
						} else  {
							throw new tx_sevenpack_Parser_Exception ( 'Expected = but found ' . $char );
						}
					}

				case $this->P_S_Pair_Value:
					$this->buffer = preg_replace ( '/^\s*/', '',  $this->buffer );
					if ( strlen ( $this->buffer ) > 0 ) {
						$char = substr ( $this->buffer, 0, 1 );
						if ( preg_match ( '/^[^}=]/', $char ) > 0 ) {
							//$this->debug( 'Found pair value begin: ' . $char );
							if ( ($char == "{") || ($char == "'") || ($char == "\"") ) {
								$this->pair_start = $char;
								$this->pair_value = '';
							} else {
								$this->pair_start = '';
								$this->pair_value = $char;
							}
							$this->pair_brace = 0;
							$this->buffer = substr ( $this->buffer, 1 );
							$this->pstate = $this->P_R_Pair_Value;
						} else {
							throw new tx_sevenpack_Parser_Exception ( 'Found illegal pair value begin characer: ' . $char );
						}
					}
					break;

				case $this->P_R_Pair_Value:
					//$this->debug( 'Reading pair value' );
					$go_on = true;
					$ii = 0;
					$last = 0;
					$prev_char = "";

					while ( $go_on ) {
						if ( $ii > 0)
							$prev_char = $char;
						$char = $this->buffer{ $ii };
						$last = $ii;

						switch ( $char ) {
							case "\"":
							case "'":
								if ( ($prev_char != "\\") && ($this->pair_start == $char) ) {
									if ( $this->pair_braces != 0 ) {
										throw new tx_sevenpack_Parser_Exception ( 'Unbalanced brace count' );
									}
									$go_on = false;
								} else {
									$this->pair_value .= $char;
								}
								break;
							case "{":
								$this->pair_value .= $char;
								if ( $prev_char != "\\" ) {
									$this->pair_brace++;
								}
								break;
							case "}":
								if ( $prev_char == "\\" ) {
									$this->pair_value .= $char;
								} else {
									$this->pair_brace--;
									if ( $this->pair_brace >= 0 ) {
										$this->pair_value .= $char;
									} else {
										if ( $this->pair_start == "{" ) {
											$go_on = false;
										} else {
											throw new tx_sevenpack_Parser_Exception ( 'Unbalanced brace count' );
										}
									}
								}
								break;
							case " ":
							case "\t":
							case ",":
								if ( $this->pair_start == "" ) {
									$last--;
									$go_on = false;
								} else {
									$this->pair_value .= $char;
								}
								break;
							default:
								$this->pair_value .= $char;
						}

						// Increment character position counter
						if ( !$go_on ) {
							//$this->debug( 'Found pair value end: ' . $char );
							$this->push_raw_pair();
							$this->pstate = $this->P_S_Comma;
						} else {
							if ( $ii < strlen( $this->buffer ) ) {
								$ii++;
							} else {
								$go_on = false;
							}
						}
					}
					$this->buffer = substr ( $this->buffer, $last+1 );
					//throw new tx_sevenpack_Parser_Exception ( 'Stopping here');
					break;

				default:
					throw new tx_sevenpack_Parser_Exception ( 'Illegal BibTeX parser state: ' . strval ( $this->pstate ) );
					break;
			}
		}
	}


	/**
	 * Translates a raw reference to a useable reference structure
	 */
	function translate_raw_ref ( $raw ) {
		$pub = array();

		// Bibtype
		$raw_val = strtolower ( $raw['type'] );
		if ( in_array ( $raw_val, $this->ra->allBibTypes ) ) {
			$pub['bibtype'] = array_search ( $raw_val, $this->ra->allBibTypes );
		} else {
			throw new tx_sevenpack_Translator_Exception ( 'Unknown bibtype: ' . strval ( $raw_val ) );
		}

		// Citeid
		$pub['citeid'] = $raw['citeid'];

		// Iterate through all raw values
		foreach ( $raw['values'] as $r_key => $r_val ) {
			//t3lib_div::debug ( array( 'pre_trans' => $r_val) );
			$r_val = $this->translate_raw_string ( $r_val );
			//t3lib_div::debug ( array( 'post_trans' => $r_val) );
			$r_key = strtolower ( $r_key );
			switch ( $r_key ) {
				case 'author':
					$pub['authors'] = $this->translate_raw_authors ( $r_val );
					break;
				case 'state':
					foreach ( $this->ra->allStates as $ii => $state ) {
						if ( strtolower ( $r_val ) == $state ) {
							$r_val = $ii;
							break;
						}
					}
					$pub['state'] = $r_val;
					break;
				case 'url':
				case 'file_url':
					$pub['file_url'] = $r_val;
					break;
				default:
					if ( in_array ( $r_key, $this->pubKeys )  ) {
						if ( array_key_exists ( $r_key, $this->pubKeyMap ) )
							$r_key = $this->pubKeyMap[$r_key];
						$pub[$r_key] = $r_val;
					} else {
						$this->stat['warnings'][] = 'Ignored field: ' . $r_key;
					}
			}
		}

		return $pub;
	}


	/**
	 * Translates some latex commands to sevenpack style
	 * The input string should be ASCII
	 */
	function translate_raw_string ( $raw ) {
		$res = $this->bt->translate ( $raw );
		$res = $this->code_to_utf8( $res );
		$res = $this->import_utf8_string( $res );
		return $res;
	}


	/**
	 * Translates a raw author string to an author array
	 */
	function translate_raw_authors ( $authors ) {
		$res = array();
		$arr = preg_split ( '/[\s]and[\s]/i', $authors );
		foreach ( $arr as $a_str ) {
			$author = array();
			$a_str = trim ( $a_str );
			if ( strpos ( $a_str, ',' ) === FALSE ) {
				// No comma in author string
				$a_split = preg_split ( '/\s+/', $a_str );
				foreach ( $a_split as &$a_part ) {
					$a_part = trim ( $a_part );
				}
				$author['fn'] = trim ( $a_split[0] );
				unset ( $a_split[0] );
				$author['sn'] = trim ( implode ( ' ', $a_split ) );
			} else {
				// Comma in author string
				$a_split = explode ( ',', $a_str );
				foreach ( $a_split as &$a_part ) {
					$a_part = trim ( $a_part );
				}
				$author['sn'] = trim ( $a_split[0] );
				unset ( $a_split[0] );
				$author['fn'] = trim ( implode ( ', ', $a_split ) );
			}
			$res[] = $author;
		}
		return $res;
	}


	/**
	 * Used to display debug messages
	 */
	function debug ( $str ) {
		t3lib_div::debug( 
			array( 'debug' => '(' . strval ( $this->pline ) . ') ' . strval ( $str ) )
		);
	}


}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_importer_bibtex.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_importer_bibtex.php"]);
}


?>
