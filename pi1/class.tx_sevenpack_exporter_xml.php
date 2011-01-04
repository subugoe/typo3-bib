<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');


require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/pi1/class.tx_sevenpack_exporter.php') );


class tx_sevenpack_exporter_xml extends tx_sevenpack_exporter {

	// Pattern replacements
	public $pat;
	public $rep; 

	function initialize ( $pi1 ) {
		parent::initialize( $pi1 );

		$this->pat = array();
		$this->rep = array();

		$pat =& $this->pat;
		$rep =& $this->rep;

		$pat[] = '/&/'; $rep[] = '&amp;';
		$pat[] = '/</'; $rep[] = '&lt;';
		$pat[] = '/>/'; $rep[] = '&gt;';

		$this->file_name = $this->pi1->extKey.'_'.$this->filter_key.'.xml';
	}


	function export_format_publication ( $pub, $infoArr = array() )
	{
		$str = '';

		$pi1 =& $this->pi1;

		$charset = $pi1->extConf['charset']['lower'];
		//t3lib_div::debug ( $charset );
		if ( $charset != 'utf-8' ) {
			$pub = $this->ref_read->change_pub_charset ( $pub, $charset, 'utf-8' );
		}

		$str .= '<reference>' . "\n";

		$entries = array();
		foreach ( $this->ref_read->pubFields as $key ) {
			$value = '';
			$append = TRUE;

			switch ( $key ) {
				case 'authors':
					$value = $pub['authors'];
					if ( sizeof ( $value ) == 0 )
						$append = FALSE;
					break;
				default:
					$value = trim ( $pub[$key] );
					if ( ( strlen ( $value ) == 0 ) || ( $value == '0' ) )
						$append = FALSE;
			}

			if ( $append ) {
				$str .= $this->xml_format_field ( $key, $value );
			}
		}

		$str .= '</reference>' . "\n";

		return $str;
	}


	function xml_format_string ( $value )
	{
		$value = preg_replace ( $this->pat, $this->rep, $value );
		return $value;
	}


	function xml_format_field ( $key, $value )
	{
		$str = '';
		switch ( $key ) {
			case 'authors':
				$authors = is_array ( $value ) ? $value : explode ( ' and ', $value );
				$value = '';
				$aXML = array ( );
				foreach ( $authors as $a ) {
					$a_str = '';
					$fn = $this->xml_format_string ( $a['forename'] );
					$sn = $this->xml_format_string ( $a['surname'] );
					if ( strlen($fn) )
						$a_str .= '<fn>'.$fn.'</fn>';
					if ( strlen($sn) )
						$a_str .= '<sn>'.$sn.'</sn>';
					if ( strlen($a_str) )
						$aXML[] = $a_str;
				}
				if ( sizeof($aXML) ) {
					$value .= "\n";
					foreach ( $aXML as $a ) {
						$value .= '<person>'.$a.'</person>'."\n";
					}
				}
				break;
			case 'bibtype':
				$value = $this->ref_read->allBibTypes[$value];
				$value = $this->xml_format_string ( $value );
				break;
			case 'state':
				$value = $this->ref_read->allStates[$value];
				$value = $this->xml_format_string ( $value );
				break;
			default:
				$value = $this->xml_format_string ( $value );
		}
		$str .= '<'.$key.'>'.$value.'</'.$key.'>'."\n";

		return $str;
	}


	function file_intro ( $infoArr = array() )
	{
		$str  = '';
		$str .= '<?xml version="1.0" encoding="utf-8"?>'."\n";
		$str .= '<sevenpack>'."\n";
		$str .= '<comment>'."\n";
		$str .= $this->xml_format_string ( $this->info_text ( $infoArr ) );
		$str .= '</comment>'."\n";
		return $str;
	}


	function file_outtro ( $infoArr = array() )
	{
		$str = '';
		$str .= '</sevenpack>'."\n";
		return $str;
	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_exporter_xml.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_exporter_xml.php"]);
}

?>