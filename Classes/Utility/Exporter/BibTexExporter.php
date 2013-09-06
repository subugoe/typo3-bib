<?php
namespace Ipf\Bib\Utility\Exporter;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
 *      Goettingen State Library
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * ************************************************************* */

class BibTexExporter extends Exporter {

	/**
	 * @var \Ipf\Bib\Utility\PRegExpTranslator
	 */
	public $bibTexTranslator;

	public function initialize($pi1) {
		parent::initialize($pi1);

		$this->file_name = $this->pi1->extKey . '_' . $this->filter_key . '.bib';

		$this->bibTexTranslator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\PRegExpTranslator');
		$bibTexTranslator =& $this->bibTexTranslator;

		$bibTexTranslator->push('/\\\\/', '\\\\textbackslash');
		$bibTexTranslator->push('/&amp;/', '\&');
		$bibTexTranslator->push('/&gt;/', '>');
		$bibTexTranslator->push('/&lt;/', '<');
		$bibTexTranslator->push('/&#0+([123456789]+);/', '&#\\1;');

		$bibTexTranslator->push('/&quot;/', "''");
		$bibTexTranslator->push('/&#39;/', "'");

		$bibTexTranslator->push('/%/', '\%');
		$bibTexTranslator->push('/\$/', '\$');
		$bibTexTranslator->push('/#/', '\#');
		//$bt->push ( '/_/',  '\_' );
		$bibTexTranslator->push('/~/', '\verb=~=');
		$bibTexTranslator->push('/\^/', '\verb=^=');
		$bibTexTranslator->push('/{/', '\{');
		$bibTexTranslator->push('/}/', '\}');

		$bibTexTranslator->push('/<sub>/', '\(_{');
		$bibTexTranslator->push('/<\/sub>/', '}\)');

		$bibTexTranslator->push('/<sup>/', '\(^{');
		$bibTexTranslator->push('/<\/sup>/', '}\)');

		$bibTexTranslator->push('/<em>/', '\emph{');
		$bibTexTranslator->push('/<\/em>/', '}');

		$bibTexTranslator->push('/<strong>/', '\emph{');
		$bibTexTranslator->push('/<\/strong>/', '}');

		// Local characters
		$bibTexTranslator->push('/&(.)acute;/', '{\\\'\\1}');
		$bibTexTranslator->push('/&(.)tilde;/', '{\~\\1}');
		$bibTexTranslator->push('/&(.)circ;/', '{\^\\1}');
		$bibTexTranslator->push('/&(.)grave;/', '{\`\\1}');
		$bibTexTranslator->push('/&(.)uml;/', '{\"\\1}');
		$bibTexTranslator->push('/&(.)cedil;/', '\c{\\1}');
		$bibTexTranslator->push('/&szlig;/', '{\ss}');
		$bibTexTranslator->push('/&([aeAE]{2})lig;/', '{\\\\\\1}');
		$bibTexTranslator->push('/&(.)ring;/', '{\\\\\\1\\1}');
		$bibTexTranslator->push('/&([oO])slash;/', '{\\\\\\1}');


		$bibTexTranslator->push('/&euro;/', '{\euro}');
		$bibTexTranslator->push('/&pound;/', '{\pounds}');

		// Greek characters
		$bibTexTranslator->push('/&alpha;/', '\(\alpha\)');
		$bibTexTranslator->push('/&beta;/', '\(\beta\)');
		$bibTexTranslator->push('/&gamma;/', '\(\gamma\)');
		$bibTexTranslator->push('/&delta;/', '\(\delta\)');
		$bibTexTranslator->push('/&epsilon;/', '\(\epsilon\)');
		$bibTexTranslator->push('/&zeta;/', '\(\zeta\)');
		$bibTexTranslator->push('/&eta;/', '\(\eta\)');
		$bibTexTranslator->push('/&theta;/', '\(\theta\)');
		$bibTexTranslator->push('/&iota;/', '\(\iota\)');
		$bibTexTranslator->push('/&kappa;/', '\(\kappa\)');
		$bibTexTranslator->push('/&lambda;/', '\(\lambda\)');
		$bibTexTranslator->push('/&mu;/', '\(\mu\)');
		$bibTexTranslator->push('/&nu;/', '\(\nu\)');
		$bibTexTranslator->push('/&xi;/', '\(\xi\)');
		$bibTexTranslator->push('/&pi;/', '\(\pi\)');
		$bibTexTranslator->push('/&rho;/', '\(\rho\)');
		$bibTexTranslator->push('/&sigma;/', '\(\sigma\)');
		$bibTexTranslator->push('/&tau;/', '\(\tau\)');
		$bibTexTranslator->push('/&upsilon;/', '\(\upsilon\)');
		$bibTexTranslator->push('/&phi;/', '\(\phi\)');
		$bibTexTranslator->push('/&chi;/', '\(\chi\)');
		$bibTexTranslator->push('/&psi;/', '\(\psi\)');
		$bibTexTranslator->push('/&omega;/', '\(\omega\)');
		$bibTexTranslator->push('/&Gamma;/', '\(\Gamma\)');
		$bibTexTranslator->push('/&Delta;/', '\(\Delta\)');
		$bibTexTranslator->push('/&Theta;/', '\(\Theta\)');
		$bibTexTranslator->push('/&Lambda;/', '\(\Lambda\)');
		$bibTexTranslator->push('/&Xi;/', '\(\Xi\)');
		$bibTexTranslator->push('/&Pi;/', '\(\Pi\)');
		$bibTexTranslator->push('/&Sigma;/', '\(\Sigma\)');
		$bibTexTranslator->push('/&Upsilon;/', '\(\Upsilon\)');
		$bibTexTranslator->push('/&Phi;/', '\(\Phi\)');
		$bibTexTranslator->push('/&Psi;/', '\(\Psi\)');
		$bibTexTranslator->push('/&Omega;/', '\(\Omega\)');

		// Mathematical characters
		$bibTexTranslator->push('/&deg;/', '\(^{\circ}\)');
		$bibTexTranslator->push('/&radic;/', '\(\sqrt{}\)');

		// Relational symbols
		$bibTexTranslator->push('/&approx;/', '\(\approx\)');
		$bibTexTranslator->push('/&equiv;/', '\(\equiv\)');
		$bibTexTranslator->push('/&prop;/', '\(\propto\)');
		$bibTexTranslator->push('/&le;/', '\(\le\)');
		$bibTexTranslator->push('/&ne;/', '\(\neq\)');
		$bibTexTranslator->push('/&geq;/', '\(\ge\)');

		// Logical symbols
		$bibTexTranslator->push('/&not;/', '\(\neg\)');
		$bibTexTranslator->push('/&and;/', '\(\wedge\)');
		$bibTexTranslator->push('/&or;/', '\(\vee\)');
		$bibTexTranslator->push('/&oplus;/', '\(\oplus\)');

		$bibTexTranslator->push('/&exist;/', '\(\exists\)');
		$bibTexTranslator->push('/&forall;/', '\(\forall\)');

		// Set symbols
		$bibTexTranslator->push('/&cap;/', '\(\cap\)');
		$bibTexTranslator->push('/&cup;/', '\(\cup\)');
		$bibTexTranslator->push('/&sub;/', '\(\subset\)');
		$bibTexTranslator->push('/&sup;/', '\(\supset\)');
		$bibTexTranslator->push('/&empty;/', '\(\emptyset\)');
		$bibTexTranslator->push('/&isin;/', '\(\in\)');
		$bibTexTranslator->push('/&notin;/', '\(\notin\)');

		// Misc symbols
		$bibTexTranslator->push('/&infin;/', '\(\infty\)');
		$bibTexTranslator->push('/&sim;/', '\(\sim\)');
		$bibTexTranslator->push('/&rfloor;/', '\(\rfloor\)');
		$bibTexTranslator->push('/&prime;/', '\(\prime\)');
		$bibTexTranslator->push('/&sim;/', '\(\sim\)');
		$bibTexTranslator->push('/&times;/', '\(\times\)');

	}

	/**
	 * @param array $pub
	 * @param array $infoArr
	 * @return string
	 */
	public function export_format_publication($pub, $infoArr = array()) {
		$str = '';

		$bibtype = ucfirst($this->referenceReader->allBibTypes[$pub['bibtype']]);

		$str .= '@';
		$str .= $bibtype . ' { ';
		$str .= trim($pub['citeid']) . ",\n";

		$entries = array();
		foreach ($this->referenceReader->pubFields as $key) {
			$append = TRUE;
			switch ($key) {
				case 'bibtype':
				case 'citeid':
					$append = FALSE;
					break;
				case 'authors':
					$value = $pub['authors'];
					if (sizeof($value) == 0)
						$append = FALSE;
					break;
				default:
					$value = trim($pub[$key]);
					if ((strlen($value) == 0) || ($value == '0'))
						$append = FALSE;
			}

			if ($append) {
				$astr = '   ';
				switch ($key) {
					case 'authors':
						$astr .= 'author' . ' = {';
						break;
					case 'file_url':
						$astr .= 'url' . ' = {';
						break;
					default:
						$astr .= $key . ' = {';
				}
				$astr .= $this->bibtex_format_field($key, $value);
				$astr .= '}';
				$entries[] = $astr;
			}
		}

		$str .= implode(",\n", $entries);
		$str .= "\n";
		$str .= '}';
		$str .= "\n\n";

		return $str;
	}

	/**
	 * @param $value
	 * @return mixed|string
	 */
	protected function bibtex_format_string($value) {

		// Convert characters to html sequences
		$charset = $this->pi1->extConf['charset']['upper'];
		// Replace illegal html ampersands with &amp;
		$value = \Ipf\Bib\Utility\Utility::fix_html_ampersand($value);
		// Replaces &amp; with &amp;amp;
		$value = htmlentities($value, ENT_QUOTES, $charset);
		// Replaces &amp;amp; with &amp;
		$value = str_replace('&amp;', '&', $value);
		$value = $this->bibTexTranslator->translate($value);

		// Recognize protected tag
		$tmp = explode('<prt>', $value);
		if (sizeof($tmp) > 1) {

			$value = '';
			$first = TRUE;
			foreach ($tmp as $v) {
				if ($first) {
					$first = FALSE;
					$value .= $v;
				} else {
					$tmp2 = explode('</prt>', $v);

					$value .= '{' . $tmp2[0] . '}';
					for ($i = 1; $i < sizeof($tmp2); $i++) {
						$value .= $tmp2[$i];
					}
				}
			}
		}

		return $value;
	}

	/**
	 * @param string $key
	 * @param $value
	 * @return mixed|string
	 */
	protected function bibtex_format_field($key, $value) {
		switch ($key) {
			case 'authors':
				$authors = is_array($value) ? $value : explode(' and ', $value);
				$value = '';
				$first = TRUE;
				foreach ($authors as $a) {
					if ($first) {
						$first = FALSE;
					} else {
						$value .= ' and ';
					}
					$fn = $this->bibtex_format_string($a['forename']);
					$sn = $this->bibtex_format_string($a['surname']);
					if (strlen($sn) && strlen($fn))
						$value .= $sn . ', ' . $fn;
					else
						$value .= $sn . $fn;
				}
				break;
			case 'state':
				$value = $this->referenceReader->allStates[$value];
				$value = $this->bibtex_format_string($value);
				break;
			default:
				$value = $this->bibtex_format_string($value);
		}

		return $value;
	}


	protected function file_intro($infoArr = array()) {
		$str = "\n" . $this->info_text($infoArr);
		$str = preg_replace('/^/m', '% ', $str) . "\n";
		return $str;
	}

}

?>