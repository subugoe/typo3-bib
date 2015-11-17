<?php

namespace Ipf\Bib\Utility\Importer;

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

use Ipf\Bib\Exception\ParserException;
use Ipf\Bib\Exception\TranslatorException;
use Ipf\Bib\Utility\PRegExpTranslator;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * This parser follows the bibtex format described here
 * http://artis.imag.fr/~Xavier.Decoret/resources/xdkbibtex/bibtex_summary.html.
 */
class BibTexImporter extends Importer
{
    /**
     * Bibtex translator.
     *
     * @var \Ipf\Bib\Utility\PRegExpTranslator
     */
    protected $pRegExpTranslator;

    /**
     * @var int
     */
    protected $parserState;

    /**
     * @var string
     */
    protected $pline;

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

    /**
     * A value buffer.
     *
     * @var string
     */
    protected $pair_name;

    /**
     * @var string
     */
    protected $pair_value;

    /**
     * @var string
     */
    protected $pair_start;

    /**
     * @var int
     */
    protected $pair_brace;

    /**
     * A raw reference.
     *
     * @var array
     */
    protected $raw_ref;

    /**
     * @var array
     */
    protected $raw_refs;

    /**
     * @var string
     */
    protected $buffer;

    /**
     * @var array
     */
    protected $pubKeys = [];
    protected $pubKeyMap;

    /**
     * @param \tx_bib_pi1 $pi1
     */
    public function initialize($pi1)
    {
        parent::initialize($pi1);

        $this->import_type = Importer::IMP_BIBTEX;

        /** @var \Ipf\Bib\Utility\PRegExpTranslator $pRegExpTranslator */
        $pRegExpTranslator = GeneralUtility::makeInstance(PRegExpTranslator::class);

        // Local characters
        $replace = [
            '`' => '&\\1grave;',
            '~' => '&\\1tilde;',
            '\\^' => '&\\1circ;',
            '\\\'' => '&\\1acute;',
            '"' => '&\\1uml;',
        ];

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
        $replace = [
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
        ];

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

        // Math expressions
        $pRegExpTranslator
            ->push('/([^\\\\])\\^\{\\\\circ\}/', '\\1&deg;')
            ->push('/\\\\sqrt([^\w]|$)/', '&radic;\\1')
            ->push('/([^\\\\])\\^\{([^\{]+)\}/', '\\1<sup>\\2</sup>')
            ->push('/([^\\\\])\\_\{([^\{]+)\}/', '\\1<sub>\\2</sub>');

        $replace = [
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
            'times' => '&times;',
        ];

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
        foreach ($this->referenceReader->getPublicationFields() as $field) {
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
        $this->pRegExpTranslator = $pRegExpTranslator;
    }

    /**
     * @return string $content
     */
    protected function displayInformationBeforeImport()
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(ExtensionManagementUtility::extPath('bib') . 'Resources/Private/Templates/Importer/BibTexInformation.html');

        return $view->render();
    }

    /**
     * @return string
     */
    protected function importStateTwo()
    {
        $action = $this->pi1->get_link_url(['import_state' => 2]);
        $buff_size = 1024;

        $this->statistics['file_name'] = $_FILES['ImportFile']['name'];
        $this->statistics['file_size'] = $_FILES['ImportFile']['size'];
        $this->statistics['succeeded'] = 0;
        $this->statistics['failed'] = 0;
        $this->statistics['errors'] = [];
        $this->statistics['storage'] = $this->storage_pid;
        $this->statistics['warnings'] = [];

        $this->pline = 1;
        $this->parserState = self::PARSER_SEARCH_REFERENCE;
        $this->clearCurrentRawReference();
        $this->raw_refs = [];

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
                    ++$ii;
                    $this->switchParserState();
                    if (($count > 1) && ($ii < $count)) {
                        $this->pline += 1;
                    }
                }
            }
        } catch (ParserException $parserException) {
            $this->statistics['errors'][] = 'Line ' . strval($this->pline) . ': ' . $parserException->getMessage();
        }

        fclose($handle);

        // Translate and save the raw references
        foreach ($this->raw_refs as $raw) {
            try {
                $publication = $this->convertRawReferenceToReference($raw);
                $this->savePublication($publication);
            } catch (TranslatorException $translatorException) {
                ++$this->statistics['failed'];
                $this->statistics['errors'][] = $translatorException->getMessage();
            }
        }

        return '';
    }

    /**
     * Clears the current raw reference.
     */
    protected function clearCurrentRawReference()
    {
        $this->raw_ref = [
            'type' => '',
            'citeid' => '',
            'values' => [],
        ];
    }

    /**
     * Pushes the internal name/value pair to the current raw reference.
     */
    protected function pushInternalNameValuePairToCurrentRawReference()
    {
        $this->raw_ref['values'][$this->pair_name] = $this->pair_value;
    }

    /**
     * Pushes the current raw reference to the raw reference list.
     */
    protected function pushCurrentRawReferenceToList()
    {
        $this->raw_refs[] = $this->raw_ref;
        $this->clearCurrentRawReference();
    }

    /**
     * Switches the parser state.
     *
     * @throws ParserException
     * @throws TranslatorException
     */
    protected function switchParserState()
    {
        // Parse buffer chunk
        while (strlen($this->getBuffer()) > 0) {
            switch ($this->parserState) {

                case self::PARSER_SEARCH_REFERENCE:
                    $pos = strpos($this->getBuffer(), '@');
                    if ($pos === false) {
                        $this->setBuffer('');
                    } else {
                        $this->parserState = self::PARSER_READ_REFERENCE_TYPE;
                        $this->raw_ref['type'] = '';
                        $this->setBuffer(substr($this->getBuffer(), $pos + 1));
                    }
                    break;
                case self::PARSER_READ_REFERENCE_TYPE:
                    $matches = [];
                    $type = '';
                    if (preg_match('/^([^,\s{]+)/', $this->getBuffer(), $matches) > 0) {
                        $type = $matches[1];
                        $this->raw_ref['type'] .= $type;
                        $this->setBuffer(substr($this->getBuffer(), strlen($type)));
                    } else {
                        if (strlen($this->raw_ref['type']) == 0) {
                            throw new ParserException('Empty bibliography type', 1378736591);
                        }
                        $this->parserState = self::PARSER_SEARCH_REFERENCE_BEGIN;
                    }
                    break;
                case self::PARSER_SEARCH_REFERENCE_BEGIN:
                    $this->setBuffer(preg_replace('/^\s*/', '', $this->getBuffer()));
                    if (strlen($this->getBuffer()) > 0) {
                        if (substr($this->getBuffer(), 0, 1) == '{') {
                            $this->setBuffer(substr($this->getBuffer(), 1));
                            $this->parserState = self::PARSER_SEARCH_CITE_ID;
                        } else {
                            throw new ParserException('Expected "{"', 1378736585);
                        }
                    }
                    break;
                case self::PARSER_SEARCH_CITE_ID:
                    $this->setBuffer(preg_replace('/^\s*/', '', $this->getBuffer()));
                    if (strlen($this->getBuffer()) > 0) {
                        if (preg_match('/^[^,\s]+/', $this->getBuffer()) > 0) {
                            $this->parserState = self::PARSER_READ_CITE_ID;
                        } else {
                            throw new ParserException('Invalid cite Id start', 1378736577);
                        }
                    }
                    break;
                case self::PARSER_READ_CITE_ID:
                    $matches = [];
                    if (preg_match('/^([^,\s]+)/', $this->getBuffer(), $matches) > 0) {
                        $id = $matches[1];
                        $this->raw_ref['citeid'] .= $id;
                        $this->setBuffer(substr($this->getBuffer(), strlen($id)));
                    } else {
                        if (strlen($this->raw_ref['citeid']) === 0) {
                            throw new ParserException('Empty cite Id', 1378736569);
                        }

                        $this->parserState = self::PARSER_SEARCH_COMMA;
                    }
                    break;
                case self::PARSER_SEARCH_COMMA:
                    $this->setBuffer(preg_replace('/^\s*/', '', $this->getBuffer()));
                    if (strlen($this->getBuffer()) > 0) {
                        $char = substr($this->getBuffer(), 0, 1);
                        if ($char === ',') {
                            $this->setBuffer(substr($this->getBuffer(), 1));
                            $this->parserState = self::PARSER_SEARCH_PAIR_NAME;
                        } else {
                            if ($char === '}') {
                                $this->setBuffer(substr($this->getBuffer(), 1));
                                $this->pushCurrentRawReferenceToList();
                                $this->parserState = self::PARSER_SEARCH_REFERENCE;
                            } else {
                                throw new ParserException('Expected "," or "}" but found: "' . $char . '"', 1378736559);
                            }
                        }
                    }
                    break;
                case self::PARSER_SEARCH_PAIR_NAME:
                    $this->setBuffer(preg_replace('/^\s*/', '', $this->getBuffer()));
                    if (strlen($this->getBuffer()) > 0) {
                        $char = substr($this->getBuffer(), 0, 1);
                        if (preg_match('/^[a-zA-Z_0-9]/', $char) > 0) {
                            $this->pair_name = '';
                            $this->parserState = self::PARSER_READ_PAIR_NAME;
                        } else {
                            if ($char === '}') {
                                $this->setBuffer(substr($this->getBuffer(), 1));
                                $this->pushCurrentRawReferenceToList();
                                $this->parserState = self::PARSER_SEARCH_REFERENCE;
                            } else {
                                throw new ParserException('Found illegal pair name character: ' . $char, 1378736549);
                            }
                        }
                    }
                    break;
                case self::PARSER_READ_PAIR_NAME:
                    $matches = [];
                    if (preg_match('/^([a-zA-Z_0-9]+)/', $this->getBuffer(), $matches) > 0) {
                        $str = $matches[1];
                        $this->pair_name .= $str;
                        $this->setBuffer(substr($this->getBuffer(), strlen($str)));
                    } else {
                        if (strlen($this->pair_name) === 0) {
                            throw new ParserException('Empty value name', 1378736541);
                        }
                        $this->parserState = self::PARSER_SEARCH_ASSIGN;
                    }
                    break;
                case self::PARSER_SEARCH_ASSIGN:
                    $this->setBuffer(preg_replace('/^\s*/', '', $this->getBuffer()));
                    if (strlen($this->getBuffer()) > 0) {
                        $char = substr($this->getBuffer(), 0, 1);
                        if ($char === '=') {
                            $this->setBuffer(substr($this->getBuffer(), 1));
                            $this->parserState = self::PARSER_SEARCH_PAIR_VALUE;
                        } else {
                            throw new ParserException('Expected "=" but found "' . $char . '"', 1378736530);
                        }
                    }
                    break;
                case self::PARSER_SEARCH_PAIR_VALUE:
                    $this->setBuffer(preg_replace('/^\s*/', '', $this->getBuffer()));
                    if (strlen($this->getBuffer()) > 0) {
                        $char = substr($this->getBuffer(), 0, 1);
                        if (preg_match('/^[^}=]/', $char) > 0) {
                            if (($char == '{') || ($char == "'") || ($char == '"')) {
                                $this->pair_start = $char;
                                $this->pair_value = '';
                            } else {
                                $this->pair_start = '';
                                $this->pair_value = $char;
                            }
                            $this->pair_brace = 0;
                            $this->setBuffer(substr($this->getBuffer(), 1));
                            $this->parserState = self::PARSER_READ_PAIR_VALUE;
                        } else {
                            throw new ParserException('Found illegal pair value begin character: ' . $char, 1378736499);
                        }
                    }
                    break;
                case self::PARSER_READ_PAIR_VALUE:
                    $go_on = true;
                    $ii = 0;
                    $last = 0;
                    $prev_char = '';

                    while ($go_on) {
                        if ($ii > 0) {
                            $prev_char = $char;
                        }
                        $char = $this->buffer{$ii};
                        $last = $ii;

                        switch ($char) {
                            case '"':
                            case "'":
                                if (($prev_char != '\\') && ($this->pair_start == $char)) {
                                    if ($this->pair_brace != 0) {
                                        throw new ParserException('Unbalanced brace count', 1378736624);
                                    }
                                    $go_on = false;
                                } else {
                                    $this->pair_value .= $char;
                                }
                                break;
                            case '{':
                                $this->pair_value .= $char;
                                if ($prev_char != '\\') {
                                    ++$this->pair_brace;
                                }
                                break;
                            case '}':
                                if ($prev_char == '\\') {
                                    $this->pair_value .= $char;
                                } else {
                                    --$this->pair_brace;
                                    if ($this->pair_brace >= 0) {
                                        $this->pair_value .= $char;
                                    } else {
                                        if ($this->pair_start == '{') {
                                            $go_on = false;
                                        } else {
                                            throw new ParserException('Unbalanced brace count', 1378736661);
                                        }
                                    }
                                }
                                break;
                            case ' ':
                            case "\t":
                            case ',':
                                if ($this->pair_start == '') {
                                    --$last;
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
                            $this->pushInternalNameValuePairToCurrentRawReference();
                            $this->parserState = self::PARSER_SEARCH_COMMA;
                        } else {
                            if ($ii < strlen($this->buffer)) {
                                ++$ii;
                            } else {
                                $go_on = false;
                            }
                        }
                    }
                    $this->setBuffer(substr($this->getBuffer(), $last + 1));

                    break;
                default:
                    throw new ParserException('Illegal BibTeX parser state: "' . strval($this->parserState) . '"',
                        1378736678);
                    break;
            }
        }
    }

    /**
     * Translates a raw reference to a usable reference structure.
     *
     * @throws TranslatorException
     *
     * @param array $raw
     *
     * @return array
     */
    protected function convertRawReferenceToReference($raw)
    {
        $publication = [];

        // Bibtype
        $raw_val = strtolower($raw['type']);
        if (in_array($raw_val, $this->referenceReader->allBibTypes)) {
            $publication['bibtype'] = array_search($raw_val, $this->referenceReader->allBibTypes);
        } else {
            throw new TranslatorException('Unknown bibtype: "' . strval($raw_val) . '"', 1378736700);
        }

        // Citeid
        $publication['citeid'] = $raw['citeid'];

        // Iterate through all raw values
        foreach ($raw['values'] as $r_key => $r_val) {
            $r_val = $this->convertLatexCommandsToBibStyle($r_val);

            $r_key = strtolower($r_key);
            switch ($r_key) {
                case 'author':
                    $publication['authors'] = $this->convertRawAuthorToAuthor($r_val);
                    break;
                case 'state':
                    foreach ($this->referenceReader->allStates as $ii => $state) {
                        if (strtolower($r_val) == $state) {
                            $r_val = $ii;
                            break;
                        }
                    }
                    $publication['state'] = $r_val;
                    break;
                case 'url':
                    $publication['web_url'] = $r_val;
                    break;
                case 'urldate':
                    $publication['web_url_date'] = $r_val;
                    break;
                case 'annote':
                    $publication['annotation'] = $r_val;
                    break;
                case 'file_url':
                    $publication['file_url'] = $r_val;
                    break;
                default:
                    if (in_array($r_key, $this->pubKeys)) {
                        if (array_key_exists($r_key, $this->pubKeyMap)) {
                            $r_key = $this->pubKeyMap[$r_key];
                        }
                        $publication[$r_key] = $r_val;
                    } else {
                        $this->statistics['warnings'][] = 'Ignored field: ' . $r_key;
                    }
            }
        }

        return $publication;
    }

    /**
     * Translates some latex commands to bib style
     * The input string should be ASCII.
     *
     * @param string $raw
     *
     * @return string
     */
    protected function convertLatexCommandsToBibStyle($raw)
    {
        $res = $this->pRegExpTranslator->translate($raw);
        $res = $this->codeToUnicode($res);
        $res = $this->importUnicodeString($res);

        return $res;
    }

    /**
     * Translates a raw author string to an author array.
     *
     * @param $authors
     *
     * @return array
     */
    protected function convertRawAuthorToAuthor($authors)
    {
        $res = [];
        $arr = preg_split('/[\s]and[\s]/i', $authors);
        foreach ($arr as $a_str) {
            $author = [];
            $a_str = trim($a_str);
            if (strpos($a_str, ',') === false) {
                // No comma in author string
                $a_split = preg_split('/\s+/', $a_str);
                foreach ($a_split as &$a_part) {
                    $a_part = trim($a_part);
                }
                $author['forename'] = trim($a_split[0]);
                unset($a_split[0]);
                $author['surname'] = trim(implode(' ', $a_split));
            } else {
                // Comma in author string
                $a_split = explode(',', $a_str);
                foreach ($a_split as &$a_part) {
                    $a_part = trim($a_part);
                }
                $author['surname'] = trim($a_split[0]);
                unset($a_split[0]);
                $author['forename'] = trim(implode(', ', $a_split));
            }
            $res[] = $author;
        }

        return $res;
    }

    /**
     * @param string $buffer
     */
    public function setBuffer($buffer)
    {
        $this->buffer = $buffer;
    }

    /**
     * @return string
     */
    public function getBuffer()
    {
        return $this->buffer;
    }
}
