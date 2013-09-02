<?php
namespace Ipf\Bib\Utility\Importer;

use \Ipf\Bib\Exception\ParserException;
use \Ipf\Bib\Exception\TranslatorException;

/**
 * This parser follows the bibtex format described here
 * http://artis.imag.fr/~Xavier.Decoret/resources/xdkbibtex/bibtex_summary.html
 */
class BibTexImporter extends Importer {

	/**
	 * Bibtex translator
	 *
	 * @var \Ipf\Bib\Utility\PRegExpTranslator
	 */
	public $pRegExpTranslator;

	// The parser state
	public $parserState;
	public $pline;

	// Parser states
	const PARSER_SEARCH_REFERENCE = 1;
	const PARSER_READ_REFERENCE_TYPE = 2;
	const PARSER_SEARCH_REFERENCE_BEGIN = 3;

	const PARSER_SEARCH_CITE_ID = 4;
	const PARSER_READ_CITE_ID = 5;

	const PARSER_SEARCH_COMMA = 6;
	const PARSER_SEARCH_PAIR_NAME = 7;

	const PARSER_READ_PAIR_NAME = 8;

	const PARSER_SEARCH_ASSIGN = 9;
	const PARSER_SEARCH_PAIR_VALUE = 10;
	const PARSER_READ_PAIR_VALUE = 11;

	// A value buffer
	public $pair_name;
	public $pair_val;
	public $pair_start;
	public $pair_brace;

	// A raw reference
	public $raw_ref;
	public $raw_refs;

	/**
	 * @var array
	 */
	public $pubKeys =  array();
	public $pubKeyMap;

	/**
	 * @param \tx_bib_pi1 $pi1
	 */
	public function initialize($pi1) {

		parent::initialize($pi1);

		$this->import_type = $pi1::IMP_BIBTEX;

		$pRegExpTranslator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Ipf\\Bib\\Utility\\PRegExpTranslator');

		// Local characters
		$replace = array(
			'`' => '&\\1grave;',
			'~' => '&\\1tilde;',
			'\\^' => '&\\1circ;',
			'\\\'' => '&\\1acute;',
			'"' => '&\\1uml;',
		);

		foreach ($replace as $key => $val) {
			$pRegExpTranslator
					->push('/\\\\' . $key . '\{(\w)\}' . '/', $val)
					->push('/\{\\\\' . $key . '(\w)\}' . '/', $val)
					->push('/\\\\' . $key . '(\w)' . '/', $val);
		}

		$pRegExpTranslator
				->push('/\\\\c\\{([cC])\\}/', '&\\1cedil;')
				->push('/{\\\\ss}/', '&szlig;')
				->push('/\\\\ss([^\w]|$)/', '&szlig;\\1')
				->push('/\{\\\\(ae|AE)\}/', '&\\1lig;')
				->push('/\\\\(ae|AE)([^\w]|$)/', '&\\1lig;\\2')
				->push('/\{\\\\([a-zA-Z])\\1\}/', '&\\1ring;')
				->push('/\\\\([a-zA-Z])\\1([^\w]|$)/', '&\\1ring;\\2')
				->push('/\{\\\\([oO])\}/', '&\\1slash;')
				->push('/\\\\([oO])([^\w]|$)/', '&\\1slash;\\2')
				->push('/\{\\\\euro\}/', '&euro;')
				->push('/\\\\euro([^\w]|$)/', '&euro;\\1')
				->push('/\{\\\\pounds\}/', '&pound;')
				->push('/\\\\pounds([^\w]|$)/', '&pound;\\1');


		// Greek characters
		$replace = array(
			'alpha' => '&alpha;',
			'beta' => '&beta;',
			'gamma' => '&gamma;',
			'delta' => '&delta;',
			'epsilon' => '&epsilon;',
			'zeta' => '&zeta;',
			'theta' => '&theta;',
			'iota' => '&iota;',
			'kappa' => '&kappa;',
			'lambda' => '&lambda;',
			'mu' => '&mu;',
			'nu' => '&nu;',
			'xi' => '&xi;',
			'pi' => '&pi;',
			'rho' => '&rho;',
			'sigma' => '&sigma;',
			'tau' => '&tau;',
			'upsilon' => '&upsilon;',
			'phi' => '&phi;',
			'chi' => '&chi;',
			'psi' => '&psi;',
			'omega' => '&omega;',

			'Gamma' => '&Gamma;',
			'Delta' => '&Delta;',
			'Theta' => '&Theta;',
			'Lambda' => '&Lambda;',
			'Xi' => '&Xi;',
			'Pi' => '&Pi;',
			'Sigma' => '&Sigma;',
			'Upsilon' => '&Upsilon;',
			'Phi' => '&Phi;',
			'Psi' => '&Psi;',
			'Omega' => '&Omega;',
		);

		foreach ($replace as $key => $val) {
			$pRegExpTranslator->push('/\\\\' . $key . '([^\w]|$)/', $val . '\\1');
		}

		// Lesser, greater, amp
		$pRegExpTranslator
				->push('/\\\\\&/', '&amp;')
				->push('/</', '&lt;')
				->push('/>/', '&gt;');

		// Environments
		$pRegExpTranslator->push('/\\\\emph\{([^\{]+)\}/', '<em>\\1</em>');

		// Mathematics

		// Math expressions
		$pRegExpTranslator
				->push('/([^\\\\])\\^\{\\\\circ\}/', '\\1&deg;')
				->push('/\\\\sqrt([^\w]|$)/', '&radic;\\1')
				->push('/([^\\\\])\\^\{([^\{]+)\}/', '\\1<sup>\\2</sup>')
				->push('/([^\\\\])\\_\{([^\{]+)\}/', '\\1<sub>\\2</sub>');


		$replace = array(
			// Relational symbols
			'approx' => '&approx;',
			'equiv' => '&equiv;',
			'propto' => '&prop;',
			'le' => '&le;',
			'neq' => '&ne;',
			'ge' => '&geq;',

			// Logical symbols
			'neg' => '&not;',
			'wedge' => '&and;',
			'vee' => '&or;',
			'oplus' => '&oplus;',

			'exists' => '&exist;',
			'forall' => '&forall;',

			// Set symbols
			'cap' => '&cap;',
			'cup' => '&cup;',
			'subset' => '&sub;',
			'supset' => '&sup;',
			'emptyset' => '&empty;',
			'in' => '&isin;',
			'notin' => '&notin;',

			// Misc symbols
			'infty' => '&infin;',
			'sim' => '&sim;',
			'rfloor' => '&rfloor;',
			'prime' => '&prime;',
			'sim' => '&sim;',
			'times' => '&times;',
		);

		foreach ($replace as $key => $val) {
			$pRegExpTranslator->push('/\\\\' . $key . '([^\w]|$)/', $val . '\\1');
		}

		// Environment markers
		$pRegExpTranslator
				->push('/\\\\\(/', '')
				->push('/\\\\\)/', '')
				->push('/(^|[^\\\\])\$\$/', '\\1')
				->push('/(^|[^\\\\])\$/', '\\1');

		// Miscellaneous
		$pRegExpTranslator
				->push('/\\\\verb(.)([^\1]?+)\1/', '\2')
				->push('/\\\\%/', '%')
				->push('/\\\\\$/', '$')
				->push('/\\\\#/', '#')
				->push('/\\\\~/', '~')
				->push('/\\\\\^/', '^')
				->push('/\\\\{/', '{')
				->push('/\\\\}/', '}')
				->push('/\'\'/', '&quot;');

		// Protected parts
		$pRegExpTranslator
				->push('/\{([^\{]+)\}/', '<prt>\\1</prt>')
				->push('/\\\\textbackslash/', '\\');

		// Remove multi spaces
		$pRegExpTranslator
				->push('/\s{2,}/', ' ')
				->push('/^\s+/', '')
				->push('/\s+$/', '');

		// Setup publication fields
		foreach ($this->referenceReader->pubFields as $field) {
			$lfield = strtolower($field);
			switch ($lfield) {
				case 'bibtype':
				case 'citeid':
					break;
				default:
					$this->pubKeys[] = $lfield;
					if ($field != $lfield) {
						$this->pubKeyMap[$lfield] = $field;
					}
			}
		}
		$this->bt = $pRegExpTranslator;
	}

	/**
	 * @return string $content
	 */
	protected function import_pre_info() {
		$content = '';

		$val = $this->pi1->get_ll('import_bibtex_title', 'import_bibtex_title', TRUE);
		$content .= '<p>' . $val . '</p>' . "\n";

		$content .= '<ul>' . "\n";
		$content .= '<li>';
		$content .= 'The BibTeX format is not strictly defined anywhere. ';
		$content .= 'Therefore this parser merely tries to get the best out of it. <br/>';
		$content .= 'If in doubt try it on an empty storage folder first.';
		$content .= '</li>' . "\n";
		$content .= '<li>';
		$content .= 'Non ASCII characters should be encoded in TeX syntax (e.g. \\"u for &uuml;)<br/>';
		$content .= 'Everything else may cause unexpected behaviour.' . "\n";
		$content .= '</li>' . "\n";
		$content .= '<li>';
		$content .= '@String( var = "some text" ) placeholders are not supported';
		$content .= '</li>' . "\n";
		$content .= '</ul>' . "\n";
		return $content;
	}


	protected function import_state_2() {
		$stat =& $this->statistics;
		$action = $this->pi1->get_link_url(array('import_state' => 2));
		$buff_size = 1024;
		//$buff_size = 10;
		$con = '';

		$stat =& $this->statistics;

		$stat['file_name'] = $_FILES['ImportFile']['name'];
		$stat['file_size'] = $_FILES['ImportFile']['size'];
		$stat['succeeded'] = 0;
		$stat['failed'] = 0;
		$stat['errors'] = array();
		$stat['storage'] = $this->storage_pid;
		$stat['warnings'] = array();

		$this->pline = 1;
		$this->parserState = self::PARSER_SEARCH_REFERENCE;
		$this->clear_raw_ref();
		$this->raw_refs = array();

		$handle = fopen($_FILES['ImportFile']['tmp_name'], 'r');
		try {
			while (!feof($handle)) {
				$buffer = fread($handle, $buff_size);

				$buffer = str_replace("\r", ' ', $buffer);

				// Split lines
				$buff_arr = explode("\n", $buffer);

				$ii = 0;
				$count = count($buff_arr);
				foreach ($buff_arr as $this->buffer) {
					$ii++;
					$this->parser_switch();
					if (($count > 1) && ($ii < $count))
						$this->pline += 1;
				}
			}
		} catch (ParserException $parserException) {
			$stat['errors'][] = 'Line ' . strval($this->pline) . ': ' . $parserException->getMessage();
		}

		fclose($handle);

		// Translate and save the raw references
		foreach ($this->raw_refs as $raw) {
			$save_ok = false;
			try {
				$pub = $this->translate_raw_ref($raw);
				$save_ok = true;
			} catch (TranslatorException $translatorException) {
				$stat['failed']++;
				$stat['errors'][] = $translatorException->getMessage();
				$save_ok = false;
			}

			if ($save_ok) {
				$this->save_publication($pub);
			}
		}

		return $con;
	}


	/**
	 * Clears the current raw reference
	 */
	function clear_raw_ref() {
		$this->raw_ref = array(
			'type' => '',
			'citeid' => '',
			'values' => array()
		);
	}


	/**
	 * Pushes the internal name/value pair to the current raw reference
	 */
	function push_raw_pair() {
		$this->raw_ref['values'][$this->pair_name] = $this->pair_value;
	}


	/**
	 * Pushes the current raw reference to the raw reference list
	 */
	function push_raw_ref() {
		$this->raw_refs[] = $this->raw_ref;
		$this->clear_raw_ref();
	}


	/**
	 * Switches the parser state
	 */
	function parser_switch() {
		// Parse buffer chunk
		while (strlen($this->buffer) > 0) {

			switch ($this->parserState) {

				case self::PARSER_SEARCH_REFERENCE:

					$pos = strpos($this->buffer, '@');
					if ($pos === FALSE) {
						$this->buffer = '';
					} else {
						$this->parserState = self::PARSER_READ_REFERENCE_TYPE;
						$this->raw_ref['type'] = '';
						$this->buffer = substr($this->buffer, $pos + 1);
					}
					break;

				case self::PARSER_READ_REFERENCE_TYPE:

					$matches = array();
					$type = '';
					if (preg_match('/^([^,\s{]+)/', $this->buffer, $matches) > 0) {
						$type = $matches[1];
						$this->raw_ref['type'] .= $type;
						$this->buffer = substr($this->buffer, strlen($type));
					} else {
						if (strlen($this->raw_ref['type']) == 0) {
							throw new ParserException ('Empty bibliography type');
						}
						$this->parserState = self::PARSER_SEARCH_REFERENCE_BEGIN;
					}
					break;

				case self::PARSER_SEARCH_REFERENCE_BEGIN:
					$this->buffer = preg_replace('/^\s*/', '', $this->buffer);
					if (strlen($this->buffer) > 0) {
						if (substr($this->buffer, 0, 1) == "{") {

							$this->buffer = substr($this->buffer, 1);
							$this->parserState = self::PARSER_SEARCH_CITE_ID;
						} else {
							throw new ParserException('Expected an {');
						}
					}
					break;

				case self::PARSER_SEARCH_CITE_ID:
					$this->buffer = preg_replace('/^\s*/', '', $this->buffer);
					if (strlen($this->buffer) > 0) {
						if (preg_match('/^[^,\s]+/', $this->buffer) > 0) {

							$this->parserState = self::PARSER_READ_CITE_ID;
						} else {
							throw new ParserException ('Invalid citeid beginning');
						}
					}
					break;

				case self::PARSER_READ_CITE_ID:
					$matches = array();
					$id = '';
					if (preg_match('/^([^,\s]+)/', $this->buffer, $matches) > 0) {
						$id = $matches[1];
						$this->raw_ref['citeid'] .= $id;
						$this->buffer = substr($this->buffer, strlen($id));
					} else {
						if (strlen($this->raw_ref['citeid']) == 0) {
							throw new ParserException ('Empty citeid');
						}

						$this->parserState = self::PARSER_SEARCH_COMMA;
					}
					break;

				case self::PARSER_SEARCH_COMMA:
					$this->buffer = preg_replace('/^\s*/', '', $this->buffer);
					if (strlen($this->buffer) > 0) {
						$char = substr($this->buffer, 0, 1);
						if ($char == ",") {

							$this->buffer = substr($this->buffer, 1);
							$this->parserState = self::PARSER_SEARCH_PAIR_NAME;
						} else if ($char == "}") {

							$this->buffer = substr($this->buffer, 1);
							$this->push_raw_ref();
							$this->parserState = self::PARSER_SEARCH_REFERENCE;
						} else {
							throw new ParserException ('Expected , or } but found: ' . $char);
						}
					}
					break;

				case self::PARSER_SEARCH_PAIR_NAME:
					$this->buffer = preg_replace('/^\s*/', '', $this->buffer);
					if (strlen($this->buffer) > 0) {
						$char = substr($this->buffer, 0, 1);
						if (preg_match('/^[a-zA-Z_0-9]/', $char) > 0) {

							$this->pair_name = '';
							$this->parserState = self::PARSER_READ_PAIR_NAME;
						} else if ($char == "}") {

							$this->buffer = substr($this->buffer, 1);
							$this->push_raw_ref();
							$this->parserState = self::PARSER_SEARCH_REFERENCE;
						} else {
							throw new ParserException ('Found illegal pair name characer: ' . $char);
						}
					}
					break;

				case self::PARSER_READ_PAIR_NAME:
					$matches = array();
					$str = '';
					if (preg_match('/^([a-zA-Z_0-9]+)/', $this->buffer, $matches) > 0) {
						$str = $matches[1];
						$this->pair_name .= $str;
						$this->buffer = substr($this->buffer, strlen($str));
					} else {
						if (strlen($this->pair_name) == 0) {
							throw new ParserException ('Empty value name');
						}

						$this->parserState = self::PARSER_SEARCH_ASSIGN;
					}
					break;

				case self::PARSER_SEARCH_ASSIGN:
					$this->buffer = preg_replace('/^\s*/', '', $this->buffer);
					if (strlen($this->buffer) > 0) {
						$char = substr($this->buffer, 0, 1);
						if ($char == "=") {

							$this->buffer = substr($this->buffer, 1);
							$this->parserState = self::PARSER_SEARCH_PAIR_VALUE;
						} else {
							throw new ParserException ('Expected = but found ' . $char);
						}
					}

				case self::PARSER_SEARCH_PAIR_VALUE:
					$this->buffer = preg_replace('/^\s*/', '', $this->buffer);
					if (strlen($this->buffer) > 0) {
						$char = substr($this->buffer, 0, 1);
						if (preg_match('/^[^}=]/', $char) > 0) {

							if (($char == "{") || ($char == "'") || ($char == "\"")) {
								$this->pair_start = $char;
								$this->pair_value = '';
							} else {
								$this->pair_start = '';
								$this->pair_value = $char;
							}
							$this->pair_brace = 0;
							$this->buffer = substr($this->buffer, 1);
							$this->parserState = self::PARSER_READ_PAIR_VALUE;
						} else {
							throw new ParserException ('Found illegal pair value begin characer: ' . $char);
						}
					}
					break;

				case self::PARSER_READ_PAIR_VALUE:

					$go_on = true;
					$ii = 0;
					$last = 0;
					$prev_char = "";

					while ($go_on) {
						if ($ii > 0)
							$prev_char = $char;
						$char = $this->buffer{$ii};
						$last = $ii;

						switch ($char) {
							case "\"":
							case "'":
								if (($prev_char != "\\") && ($this->pair_start == $char)) {
									if ($this->pair_braces != 0) {
										throw new ParserException('Unbalanced brace count');
									}
									$go_on = false;
								} else {
									$this->pair_value .= $char;
								}
								break;
							case "{":
								$this->pair_value .= $char;
								if ($prev_char != "\\") {
									$this->pair_brace++;
								}
								break;
							case "}":
								if ($prev_char == "\\") {
									$this->pair_value .= $char;
								} else {
									$this->pair_brace--;
									if ($this->pair_brace >= 0) {
										$this->pair_value .= $char;
									} else {
										if ($this->pair_start == "{") {
											$go_on = false;
										} else {
											throw new ParserException('Unbalanced brace count');
										}
									}
								}
								break;
							case " ":
							case "\t":
							case ",":
								if ($this->pair_start == "") {
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
						if (!$go_on) {

							$this->push_raw_pair();
							$this->parserState = self::PARSER_SEARCH_COMMA;
						} else {
							if ($ii < strlen($this->buffer)) {
								$ii++;
							} else {
								$go_on = false;
							}
						}
					}
					$this->buffer = substr($this->buffer, $last + 1);

					break;

				default:
					throw new ParserException ('Illegal BibTeX parser state: ' . strval($this->parserState));
					break;
			}
		}
	}


	/**
	 * Translates a raw reference to a useable reference structure
	 */
	function translate_raw_ref($raw) {
		$pub = array();

		// Bibtype
		$raw_val = strtolower($raw['type']);
		if (in_array($raw_val, $this->referenceReader->allBibTypes)) {
			$pub['bibtype'] = array_search($raw_val, $this->referenceReader->allBibTypes);
		} else {
			throw new TranslatorException ('Unknown bibtype: ' . strval($raw_val));
		}

		// Citeid
		$pub['citeid'] = $raw['citeid'];

		// Iterate through all raw values
		foreach ($raw['values'] as $r_key => $r_val) {

			$r_val = $this->translate_raw_string($r_val);

			$r_key = strtolower($r_key);
			switch ($r_key) {
				case 'author':
					$pub['authors'] = $this->translate_raw_authors($r_val);
					break;
				case 'state':
					foreach ($this->referenceReader->allStates as $ii => $state) {
						if (strtolower($r_val) == $state) {
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
					if (in_array($r_key, $this->pubKeys)) {
						if (array_key_exists($r_key, $this->pubKeyMap))
							$r_key = $this->pubKeyMap[$r_key];
						$pub[$r_key] = $r_val;
					} else {
						$this->statistics['warnings'][] = 'Ignored field: ' . $r_key;
					}
			}
		}

		return $pub;
	}


	/**
	 * Translates some latex commands to bib style
	 * The input string should be ASCII
	 */
	function translate_raw_string($raw) {
		$res = $this->bt->translate($raw);
		$res = $this->code_to_utf8($res);
		$res = $this->import_utf8_string($res);
		return $res;
	}


	/**
	 * Translates a raw author string to an author array
	 */
	function translate_raw_authors($authors) {
		$res = array();
		$arr = preg_split('/[\s]and[\s]/i', $authors);
		foreach ($arr as $a_str) {
			$author = array();
			$a_str = trim($a_str);
			if (strpos($a_str, ',') === FALSE) {
				// No comma in author string
				$a_split = preg_split('/\s+/', $a_str);
				foreach ($a_split as &$a_part) {
					$a_part = trim($a_part);
				}
				$author['forename'] = trim($a_split[0]);
				unset ($a_split[0]);
				$author['surname'] = trim(implode(' ', $a_split));
			} else {
				// Comma in author string
				$a_split = explode(',', $a_str);
				foreach ($a_split as &$a_part) {
					$a_part = trim($a_part);
				}
				$author['surname'] = trim($a_split[0]);
				unset ($a_split[0]);
				$author['forename'] = trim(implode(', ', $a_split));
			}
			$res[] = $author;
		}
		return $res;
	}


	/**
	 * Used to display debug messages
	 */
	function debug($str) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::debug(
			array('debug' => '(' . strval($this->pline) . ') ' . strval($str))
		);
	}

}

?>