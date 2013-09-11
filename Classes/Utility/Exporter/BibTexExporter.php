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

use TYPO3\CMS\Core\FormProtection\Exception;

class BibTexExporter extends Exporter {

	/**
	 * @var \Ipf\Bib\Utility\PRegExpTranslator
	 */
	public $bibTexTranslator;

	/**
	 * @param \tx_bib_pi1 $pi1
	 * @return void
	 */
	public function initialize($pi1) {
		parent::initialize($pi1);

		$this->file_name = $this->pi1->extKey . '_' . $this->filter_key . '.bib';

		/** @var \Ipf\Bib\Utility\PRegExpTranslator $bibTexTranslator */
		$bibTexTranslator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\PRegExpTranslator');

		$bibTexTranslator
				->push('/\\\\/', '\\\\textbackslash')
				->push('/&amp;/', '\&')
				->push('/&gt;/', '>')
				->push('/&lt;/', '<')
				->push('/&#0+([123456789]+);/', '&#\\1;');

		$bibTexTranslator
				->push('/&quot;/', "''")
				->push('/&#39;/', "'");

		$bibTexTranslator
				->push('/%/', '\%')
				->push('/\$/', '\$')
				->push('/#/', '\#')
				->push('/~/', '\verb=~=')
				->push('/\^/', '\verb=^=')
				->push('/{/', '\{')
				->push('/}/', '\}');

		$bibTexTranslator
				->push('/<sub>/', '\(_{')
				->push('/<\/sub>/', '}\)');

		$bibTexTranslator
				->push('/<sup>/', '\(^{')
				->push('/<\/sup>/', '}\)');

		$bibTexTranslator
				->push('/<em>/', '\emph{')
				->push('/<\/em>/', '}');

		$bibTexTranslator
				->push('/<strong>/', '\emph{')
				->push('/<\/strong>/', '}');

		// Local characters
		$bibTexTranslator
				->push('/&(.)acute;/', '{\\\'\\1}')
				->push('/&(.)tilde;/', '{\~\\1}')
				->push('/&(.)circ;/', '{\^\\1}')
				->push('/&(.)grave;/', '{\`\\1}')
				->push('/&(.)uml;/', '{\"\\1}')
				->push('/&(.)cedil;/', '\c{\\1}')
				->push('/&szlig;/', '{\ss}')
				->push('/&([aeAE]{2})lig;/', '{\\\\\\1}')
				->push('/&(.)ring;/', '{\\\\\\1\\1}')
				->push('/&([oO])slash;/', '{\\\\\\1}');


		$bibTexTranslator
				->push('/&euro;/', '{\euro}')
				->push('/&pound;/', '{\pounds}');

		// Greek characters
		$bibTexTranslator
				->push('/&alpha;/', '\(\alpha\)')
				->push('/&beta;/', '\(\beta\)')
				->push('/&gamma;/', '\(\gamma\)')
				->push('/&delta;/', '\(\delta\)')
				->push('/&epsilon;/', '\(\epsilon\)')
				->push('/&zeta;/', '\(\zeta\)')
				->push('/&eta;/', '\(\eta\)')
				->push('/&theta;/', '\(\theta\)')
				->push('/&iota;/', '\(\iota\)')
				->push('/&kappa;/', '\(\kappa\)')
				->push('/&lambda;/', '\(\lambda\)')
				->push('/&mu;/', '\(\mu\)')
				->push('/&nu;/', '\(\nu\)')
				->push('/&xi;/', '\(\xi\)')
				->push('/&pi;/', '\(\pi\)')
				->push('/&rho;/', '\(\rho\)')
				->push('/&sigma;/', '\(\sigma\)')
				->push('/&tau;/', '\(\tau\)')
				->push('/&upsilon;/', '\(\upsilon\)')
				->push('/&phi;/', '\(\phi\)')
				->push('/&chi;/', '\(\chi\)')
				->push('/&psi;/', '\(\psi\)')
				->push('/&omega;/', '\(\omega\)')
				->push('/&Gamma;/', '\(\Gamma\)')
				->push('/&Delta;/', '\(\Delta\)')
				->push('/&Theta;/', '\(\Theta\)')
				->push('/&Lambda;/', '\(\Lambda\)')
				->push('/&Xi;/', '\(\Xi\)')
				->push('/&Pi;/', '\(\Pi\)')
				->push('/&Sigma;/', '\(\Sigma\)')
				->push('/&Upsilon;/', '\(\Upsilon\)')
				->push('/&Phi;/', '\(\Phi\)')
				->push('/&Psi;/', '\(\Psi\)')
				->push('/&Omega;/', '\(\Omega\)');

		// Mathematical characters
		$bibTexTranslator
				->push('/&deg;/', '\(^{\circ}\)')
				->push('/&radic;/', '\(\sqrt{}\)');

		// Relational symbols
		$bibTexTranslator
				->push('/&approx;/', '\(\approx\)')
				->push('/&equiv;/', '\(\equiv\)')
				->push('/&prop;/', '\(\propto\)')
				->push('/&le;/', '\(\le\)')
				->push('/&ne;/', '\(\neq\)')
				->push('/&geq;/', '\(\ge\)');

		// Logical symbols
		$bibTexTranslator
				->push('/&not;/', '\(\neg\)')
				->push('/&and;/', '\(\wedge\)')
				->push('/&or;/', '\(\vee\)')
				->push('/&oplus;/', '\(\oplus\)');

		$bibTexTranslator
				->push('/&exist;/', '\(\exists\)')
			->push('/&forall;/', '\(\forall\)');

		// Set symbols
		$bibTexTranslator
				->push('/&cap;/', '\(\cap\)')
				->push('/&cup;/', '\(\cup\)')
				->push('/&sub;/', '\(\subset\)')
				->push('/&sup;/', '\(\supset\)')
				->push('/&empty;/', '\(\emptyset\)')
				->push('/&isin;/', '\(\in\)')
				->push('/&notin;/', '\(\notin\)');

		// Misc symbols
		$bibTexTranslator
				->push('/&infin;/', '\(\infty\)')
				->push('/&sim;/', '\(\sim\)')
				->push('/&rfloor;/', '\(\rfloor\)')
				->push('/&prime;/', '\(\prime\)')
				->push('/&sim;/', '\(\sim\)')
				->push('/&times;/', '\(\times\)');

		$this->bibTexTranslator = $bibTexTranslator;
	}

	/**
	 * @param array $publication
	 * @param array $infoArr
	 * @return string
	 */
	public function formatPublicationForExport($publication, $infoArr = array()) {

		$bibliographyType = ucfirst($this->referenceReader->allBibTypes[$publication['bibtype']]);

		$content = '@';
		$content .= $bibliographyType . ' { ';
		$content .= trim($publication['citeid']) . ",\n";

		$entries = array();
		foreach ($this->referenceReader->getPublicationFields() as $publicationField) {
			$append = TRUE;
			switch ($publicationField) {
				case 'bibtype':
				case 'citeid':
					$append = FALSE;
					$publication['citeid'] = $this->formatCiteKey($publication['citeid']);
					break;
				case 'authors':
					$value = $publication['authors'];
					if (sizeof($value) == 0) {
						$append = FALSE;
					}
					break;
				default:
					$value = trim($publication[$publicationField]);
					if ((strlen($value) == 0) || ($value == '0')) {
						$append = FALSE;
					}
			}

			if ($append) {
				$astr = '   ';
				switch ($publicationField) {
					case 'authors':
						$astr .= 'author' . ' = {';
						break;
					case 'file_url':
						$astr .= 'url' . ' = {';
						break;
					default:
						$astr .= $publicationField . ' = {';
				}
				$astr .= $this->bibTexFormatField($publicationField, $value);
				$astr .= '}';
				$entries[] = $astr;
			}
		}

		$content .= implode(",\n", $entries);
		$content .= "\n";
		$content .= '}';
		$content .= "\n\n";

		return $content;
	}

	/**
	 * Replaces characters not matching [A-Za-z0-9_-] from cite keys
	 *
	 * @param string $publicationCiteId
	 * @return string
	 */
	protected function formatCiteKey($publicationCiteId) {

		$matchPattern = '/^[A-Za-z0-9_-]+$/';
		$matcher = preg_match($matchPattern, $publicationCiteId);

		if ($matcher === 0) {
			$replacePattern = '/[^a-zA-Z0-9_-]/';
			$publicationCiteId = preg_replace($replacePattern, '_', $publicationCiteId);
		}

		return $publicationCiteId;
	}

	/**
	 * @param string $content
	 * @return mixed|string
	 */
	protected function bibTexFormatString($content) {

		// Convert characters to html sequences
		$charset = $this->pi1->extConf['charset']['upper'];
		// Replace illegal html ampersands with &amp;
		$content = \Ipf\Bib\Utility\Utility::fix_html_ampersand($content);
		// Replaces &amp; with &amp;amp;
		$content = htmlentities($content, ENT_QUOTES, $charset);
		// Replaces &amp;amp; with &amp;
		$content = str_replace('&amp;', '&', $content);
		$content = $this->bibTexTranslator->translate($content);

		// Recognize protected tag
		$tmp = explode('<prt>', $content);
		if (sizeof($tmp) > 1) {

			$content = '';
			$first = TRUE;
			foreach ($tmp as $v) {
				if ($first) {
					$first = FALSE;
					$content .= $v;
				} else {
					$tmp2 = explode('</prt>', $v);

					$content .= '{' . $tmp2[0] . '}';
					for ($i = 1; $i < sizeof($tmp2); $i++) {
						$content .= $tmp2[$i];
					}
				}
			}
		}

		return $content;
	}

	/**
	 * @param string $key
	 * @param $value
	 * @return mixed|string
	 */
	protected function bibTexFormatField($key, $value) {
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
					$fn = $this->bibTexFormatString($a['forename']);
					$sn = $this->bibTexFormatString($a['surname']);
					if (strlen($sn) && strlen($fn))
						$value .= $sn . ', ' . $fn;
					else
						$value .= $sn . $fn;
				}
				break;
			case 'state':
				$value = $this->referenceReader->allStates[$value];
				$value = $this->bibTexFormatString($value);
				break;
			default:
				$value = $this->bibTexFormatString($value);
		}

		return $value;
	}

	/**
	 * @param array $infoArr
	 * @return string
	 */
	protected function fileIntro($infoArr = array()) {
		$str = "\n" . $this->getGeneralInformationText($infoArr);
		$str = preg_replace('/^/m', '% ', $str) . "\n";
		return $str;
	}

	/**
	 * @param array $infoArr
	 * @return string
	 */
	protected function fileOutro($infoArr = array()){
		return '';
	}
}

?>