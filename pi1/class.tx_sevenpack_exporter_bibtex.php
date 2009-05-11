<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');


require_once ( $GLOBALS['TSFE']->tmpl->getFileName (
	'EXT:sevenpack/pi1/class.tx_sevenpack_exporter.php') );


class tx_sevenpack_exporter_bibtex extends tx_sevenpack_exporter {

	public $bt; // Bibtex translator

	function initialize ( $pi1 ) {
		parent::initialize( $pi1 );

		$this->file_name = $this->pi1->extKey.'_'.$this->filter_key.'.bib';

		$this->bt = t3lib_div::makeInstance ( 'tx_sevenpack_PRegExp_Translator' );
		$bt =& $this->bt;

		$bt->push ( '/\\\\/', '\\\\textbackslash' );
		$bt->push ( '/&amp;/', '\&' );
		$bt->push ( '/&gt;/', '>' );
		$bt->push ( '/&lt;/', '<' );
		$bt->push ( '/&#0+([123456789]+);/',  '&#\\1;' );

		$bt->push ( '/&quot;/',  "''" );
		$bt->push ( '/&#39;/',   "'" );

		$bt->push ( '/%/',  '\%' );
		$bt->push ( '/\$/', '\$' );
		$bt->push ( '/#/',  '\#' );
		//$bt->push ( '/_/',  '\_' );
		$bt->push ( '/~/',  '\verb=~=' );
		$bt->push ( '/\^/', '\verb=^=' );
		$bt->push ( '/{/', '\{' );
		$bt->push ( '/}/', '\}' );

		$bt->push ( '/<sub>/',   '\(_{' );
		$bt->push ( '/<\/sub>/', '}\)'  );

		$bt->push ( '/<sup>/',   '\(^{' );
		$bt->push ( '/<\/sup>/', '}\)'  );

		$bt->push ( '/<em>/',   '\emph{' );
		$bt->push ( '/<\/em>/', '}'  );

		$bt->push ( '/<strong>/',   '\emph{' );
		$bt->push ( '/<\/strong>/', '}'  );

		// Local characters
		$bt->push ( '/&(.)acute;/', '{\`\\1}'  );
		$bt->push ( '/&(.)tilde;/', '{\~\\1}'  );
		$bt->push ( '/&(.)circ;/',  '{\^\\1}'  );
		$bt->push ( '/&(.)grave;/', '{\\\'\\1}');
		$bt->push ( '/&(.)uml;/',   '{\"\\1}'  );
		$bt->push ( '/&(.)cedil;/', '\c{\\1}'  );
		$bt->push ( '/&szlig;/',    '{\ss}'  );
		$bt->push ( '/&([aeAE]{2})lig;/', '{\\\\\\1}'  );
		$bt->push ( '/&(.)ring;/',   '{\\\\\\1\\1}' );
		$bt->push ( '/&([oO])slash;/',  '{\\\\\\1}' );


		$bt->push ( '/&euro;/',    '{\euro}'  );
		$bt->push ( '/&pound;/',   '{\pounds}'  );

		// Greek characters
		$bt->push ( '/&alpha;/',   '\(\alpha\)' );
		$bt->push ( '/&beta;/',    '\(\beta\)' );
		$bt->push ( '/&gamma;/',   '\(\gamma\)' );
		$bt->push ( '/&delta;/',   '\(\delta\)' );
		$bt->push ( '/&epsilon;/', '\(\epsilon\)' );
		$bt->push ( '/&zeta;/',    '\(\zeta\)' );
		$bt->push ( '/&eta;/',     '\(\eta\)' );
		$bt->push ( '/&theta;/',   '\(\theta\)' );
		$bt->push ( '/&iota;/',    '\(\iota\)' );
		$bt->push ( '/&kappa;/',   '\(\kappa\)' );
		$bt->push ( '/&lambda;/',  '\(\lambda\)' );
		$bt->push ( '/&mu;/',      '\(\mu\)' );
		$bt->push ( '/&nu;/',      '\(\nu\)' );
		$bt->push ( '/&xi;/',      '\(\xi\)' );
		$bt->push ( '/&pi;/',      '\(\pi\)' );
		$bt->push ( '/&rho;/',     '\(\rho\)' );
		$bt->push ( '/&sigma;/',   '\(\sigma\)' );
		$bt->push ( '/&tau;/',     '\(\tau\)' );
		$bt->push ( '/&upsilon;/', '\(\upsilon\)' );
		$bt->push ( '/&phi;/',     '\(\phi\)' );
		$bt->push ( '/&chi;/',     '\(\chi\)' );
		$bt->push ( '/&psi;/',     '\(\psi\)' );
		$bt->push ( '/&omega;/',   '\(\omega\)' );
		$bt->push ( '/&Gamma;/',   '\(\Gamma\)' );
		$bt->push ( '/&Delta;/',   '\(\Delta\)' );
		$bt->push ( '/&Theta;/',   '\(\Theta\)' );
		$bt->push ( '/&Lambda;/',  '\(\Lambda\)' );
		$bt->push ( '/&Xi;/',      '\(\Xi\)' );
		$bt->push ( '/&Pi;/',      '\(\Pi\)' );
		$bt->push ( '/&Sigma;/',   '\(\Sigma\)' );
		$bt->push ( '/&Upsilon;/', '\(\Upsilon\)' );
		$bt->push ( '/&Phi;/',     '\(\Phi\)' );
		$bt->push ( '/&Psi;/',     '\(\Psi\)' );
		$bt->push ( '/&Omega;/',   '\(\Omega\)' );

		// Mathematical characters
		$bt->push ( '/&deg;/',    '\(^{\circ}\)' );
		$bt->push ( '/&radic;/',  '\(\sqrt{}\)' );

		// Relational symbols
		$bt->push ( '/&approx;/', '\(\approx\)' );
		$bt->push ( '/&equiv;/',  '\(\equiv\)' );
		$bt->push ( '/&prop;/',   '\(\propto\)' );
		$bt->push ( '/&le;/',     '\(\le\)' );
		$bt->push ( '/&ne;/',     '\(\neq\)' );
		$bt->push ( '/&geq;/',    '\(\ge\)' );

		// Logical symbols
		$bt->push ( '/&not;/',    '\(\neg\)' );
		$bt->push ( '/&and;/',    '\(\wedge\)' );
		$bt->push ( '/&or;/',     '\(\vee\)' );
		$bt->push ( '/&oplus;/',  '\(\oplus\)' );

		$bt->push ( '/&exist;/',  '\(\exists\)' );
		$bt->push ( '/&forall;/', '\(\forall\)' );

		// Set symbols
		$bt->push ( '/&cap;/',    '\(\cap\)' );
		$bt->push ( '/&cup;/',    '\(\cup\)' );
		$bt->push ( '/&sub;/',    '\(\subset\)' );
		$bt->push ( '/&sup;/',    '\(\supset\)' );
		$bt->push ( '/&empty;/',  '\(\emptyset\)' );
		$bt->push ( '/&isin;/',   '\(\in\)' );
		$bt->push ( '/&notin;/',  '\(\notin\)' );

		// Misc symbols
		$bt->push ( '/&infin;/',  '\(\infty\)' );
		$bt->push ( '/&sim;/',    '\(\sim\)' );
		$bt->push ( '/&rfloor;/', '\(\rfloor\)' );
		$bt->push ( '/&prime;/',  '\(\prime\)' );
		$bt->push ( '/&sim;/',    '\(\sim\)' );
		$bt->push ( '/&times;/',  '\(\times\)' );

	}

	function export_format_publication ( $pub, $infoArr = array() )
	{
		$str = '';

		$bibtype = ucfirst ( $this->ra->allBibTypes[$pub['bibtype']] );

		$str .= '@';
		$str .= $bibtype . ' { ';
		$str .= trim ( $pub['citeid'] ).",\n";

		$entries = array();
		foreach ( $this->ra->pubFields as $key ) {
			$append = TRUE;
			switch ( $key ) {
				case 'bibtype':
				case 'citeid':
					$append = FALSE;
					break;
				case 'authors':
					$value = $pub['authors'];
					if ( sizeof ( $value ) == 0 )
						$append = FALSE;
					break;
				default:
					$value = trim ( $pub[$key] );
					if ( (strlen($value) == 0) || ( $value == '0' ) )
						$append = FALSE;
			}

			if ( $append ) {
				$astr  = '   ';
				switch ( $key ) {
					case 'authors':
						$astr .= 'author' . ' = {';
						break;
					case 'file_url':
						$astr .= 'url' . ' = {';
						break;
					default:
						$astr .= $key . ' = {';
				}
				$astr .= $this->bibtex_format_field ( $key, $value );
				$astr .= '}';
				$entries[] = $astr;
			}
		}

		$str .= implode ( ",\n", $entries );
		$str .= "\n";
		$str .= '}';
		$str .= "\n\n";

		return $str;
	}


	function bibtex_format_string ( $value ) {

		// Convert characters to html sequences
		$charset = strtoupper ( $this->pi1->extConf['be_charset'] );
		// Replace illegal html ampersands with &amp;
		$value = tx_sevenpack_utility::fix_html_ampersand ( $value );
		// Replaces &amp; with &amp;amp;
		$value = htmlentities ( $value, ENT_QUOTES, $charset );
		// Replaces &amp;amp; with &amp;
		$value = str_replace ( '&amp;', '&', $value );
		$value = $this->bt->translate ( $value );

		// Recognize protected tag
		$tmp = explode ( '<prt>', $value );
		if ( sizeof($tmp) > 1 ) {
			//t3lib_div::debug ('Found prt tag');
			$value = '';
			$first = TRUE;
			foreach ( $tmp as $v ) {
				if ( $first ) {
					$first = FALSE;
					$value .= $v;
				} else {
					$tmp2 = explode('</prt>', $v);
					//$value .= preg_replace('/([ABCDEFGHIJKLMNOPQRSTUVWXYZ])/', '{${1}}', $tmp2[0]);
					$value .= '{'.$tmp2[0].'}';
					for ( $i = 1; $i<sizeof($tmp2); $i++ ) {
						$value .= $tmp2[$i];
					}
				}
			}
		}

		return $value;
	}


	function bibtex_format_field ( $key, $value )
	{
		switch ( $key ) {
			case 'authors':
				$authors = is_array ( $value ) ? $value : explode ( ' and ', $value );
				$value = '';
				$first = TRUE;
				foreach ( $authors as $a ) {
					if ( $first ) {
						$first = FALSE;
					} else {
						$value .= ' and ';
					}
					$fn = $this->bibtex_format_string ( $a['forename'] );
					$sn = $this->bibtex_format_string ( $a['surname'] );
					if ( strlen ( $sn ) && strlen ( $fn ) )
						$value .= $sn.', '.$fn;
					else
						$value .= $sn.$fn;
				}
				break;
			case 'state':
				$value = $this->ra->allStates[$value];
				$value = $this->bibtex_format_string ( $value );
				break;
			default:
				$value = $this->bibtex_format_string ( $value );
		}

		return $value;
	}


	function file_intro ( $infoArr = array() )
	{
		$str = "\n" . $this->info_text ( $infoArr );
		$str = preg_replace ( '/^/m', '% ', $str ) . "\n";
		return $str;
	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_exporter_bibtex.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_exporter_bibtex.php"]);
}

?>
