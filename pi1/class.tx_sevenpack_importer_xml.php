<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');


require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/pi1/class.tx_sevenpack_importer.php') );


class tx_sevenpack_importer_xml extends tx_sevenpack_importer {

	function initialize ( $pi1 ) {
		parent::initialize( $pi1 );
		$this->import_type = $pi1->IMP_XML;
	}

	function import_pre_info ( ) {
		$res = '';

		$val = $this->pi1->get_ll ( 'import_xml_title', 'import_xml_title', TRUE );
		$res .= '<p>' . $val . '</p>' . "\n";

		return $res;
	}


	function import_state_2 ( ) {
		$stat =& $this->stat;
		$action = $this->pi1->get_link_url ( array ( 'import_state'=>2 ) );
		$con = '';

		$stat =& $this->stat;
		$stat['file_name'] = $_FILES['ImportFile']['name'];
		$stat['file_size'] = $_FILES['ImportFile']['size'];

		$stat['succeeded'] = 0;
		$stat['failed']    = 0;
		$stat['storage']   = $this->storage_pid;

		$fstr = file_get_contents ( $_FILES['ImportFile']['tmp_name'] );

		$parsed = 'Unknown error';
		$parsed = $this->parse_xml_pubs ( $fstr );
		if ( is_string ( $parsed ) ) {
			$stat['failed'] += 1;
			$stat['errors'][] = $parsed;
		} else {
			//t3lib_div::debug ( $parsed );

			foreach ( $parsed as $pub ) {
				$this->save_publication ( $pub );
			}
		}
		return $con;
	}


	function parse_xml_pubs ( $str ) {
		$parser = xml_parser_create ( );
		xml_parser_set_option ( $parser, XML_OPTION_CASE_FOLDING, 0 );
		xml_parser_set_option ( $parser, XML_OPTION_SKIP_WHITE, 1 );
		$parse_ret = xml_parse_into_struct ( $parser, $str, $tags );
		xml_parser_free ( $parser );

		if ( $parse_ret == 0 ) {
			return 'File is not valid XML';
		}

		//t3lib_div::debug ( $tags );

		$refFields = array();
		foreach ( $this->ref_read->refFields as $field ) {
			$refFields[] = strtolower ( $field );
		}
		//t3lib_div::debug ( $refFields );

		$pubs = array ( );
		$startlevel = 0;
		$in_sevenpack = FALSE;
		$in_ref       = FALSE;
		$in_authors   = FALSE;
		$in_person    = FALSE;
		foreach ( $tags as $cTag ) {
			$tag     =& $cTag['tag'];
			$tag_low = strtolower ( $tag );
			$tag_up = strtoupper ( $tag );
			$type  =& $cTag['type'];
			$level =& $cTag['level'];
			$value =  $this->import_utf8_string ( $cTag['value'] );
			if ( !$in_sevenpack ) {
				if ( ( $tag == 'sevenpack' ) &&
				     ( $type == 'open' ) ) {
					$in_sevenpack = TRUE;
				}
			} else {

				if ( !$in_ref ) {
					if ( ( $tag == 'reference' ) && ( $type == 'open' ) ) {
						// News reference
						$in_ref = TRUE;
						$pub = array ( );
					} else
					if ( ( $tag == 'sevenpack' ) && ( $type == 'close' ) ) {
						// Leave sevenpack
						$in_sevenpack = FALSE;
					}
				} else {
					// In reference
					if ( !$in_authors ) {
						if ( ( $tag == 'authors' ) && ( $type == 'open' ) ) {
							// Enter authors
							$in_authors = TRUE;
							$pub['authors'] = array ( );
						} else
						if ( in_array ( $tag_low, $refFields ) ) {
							if ( $type == 'complete' ) {
								switch ( $tag_low ) {
									case 'bibtype':
										foreach ( $this->ref_read->allBibTypes as $ii => $bib ) {
											if ( strtolower ( $value ) == $bib ) {
												$value = $ii;
												break;
											}
										}
										break;
									case 'state':
										foreach ( $this->ref_read->allStates as $ii => $state ) {
											if ( strtolower ( $value ) == $state ) {
												$value = $ii;
												break;
											}
										}
										break;
									default:
								}
								// Apply value
								if ( in_array ( $tag_low, $this->ref_read->refFields ) ) {
									$pub[$tag_low] = $value;
								} else {
									if ( in_array ( $tag_up, $this->ref_read->refFields ) ) {
										$pub[$tag_up] = $value;
									} else {
										$pub[$tag] = $value;	
									}
								}
							} else {
								// Unknown field
								$this->stat['warnings'][] = 'Ignored field: ' . $tag;
							}
						} else
						if ( ( $tag == 'reference' ) && ( $type == 'close' ) ) {
							// Leave reference
							$in_ref = FALSE;
							$pubs[] = $pub;
						} else {
							// Unknown field
							$this->stat['warnings'][] = 'Ignored field: ' . $tag;
						}
					} else {
						// In authors
						if ( !$in_person ) {
							if ( ( $tag == 'person' ) && ( $type == 'open' ) ) {
								// Enter person
								$in_person = TRUE;
								$author = array ( );
							} else
							if ( ( $tag == 'authors' ) && ( $type == 'close' ) ) {
								// Leave authors
								$in_authors = FALSE;
							}
						} else {
							// In person
							$sn_fields = array ( 'surname', 'sn' );
							$fn_fields = array ( 'forename', 'fn' );
							if ( in_array ( $tag, $sn_fields ) && ( $type == 'complete' ) ) {
								$author['surname'] = $value;
							} else
							if ( in_array ( $tag, $fn_fields ) && ( $type == 'complete' ) ) {
								$author['forename'] = $value;
							} else
							if ( ( $tag == 'person' ) && ( $type == 'close' ) ) {
								// Leave person
								$in_person = FALSE;
								$pub['authors'][] = $author;
							}
						}
					}
				}

			}
		}

		return $pubs;
	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_importer_xml.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_importer_xml.php"]);
}


?>
