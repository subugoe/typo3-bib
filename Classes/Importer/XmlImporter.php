<?php

namespace Ipf\Bib\Importer;

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

use Ipf\Bib\Utility\ReferenceReader;

/**
 * Class XmlImporter.
 */
class XmlImporter extends Importer
{
    public function initialize()
    {
        parent::initialize();
        $this->import_type = Importer::IMP_XML;
    }

    /**
     * @return string
     */
    protected function importStateTwo()
    {
        $content = '';

        $stat = &$this->statistics;
        $stat['file_name'] = $_FILES['ImportFile']['name'];
        $stat['file_size'] = $_FILES['ImportFile']['size'];

        $stat['succeeded'] = 0;
        $stat['failed'] = 0;
        $stat['storage'] = $this->storage_pid;

        $fstr = file_get_contents($_FILES['ImportFile']['tmp_name']);

        $parsed = $this->parseXmlPublications($fstr);
        if (is_string($parsed)) {
            ++$stat['failed'];
            $stat['errors'][] = $parsed;
        } else {
            foreach ($parsed as $pub) {
                $this->savePublication($pub);
            }
        }

        return $content;
    }

    /**
     * @param string $xmlPublications
     *
     * @return array|string
     */
    protected function parseXmlPublications($xmlPublications)
    {
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        $parse_ret = xml_parse_into_struct($parser, $xmlPublications, $tags);
        xml_parser_free($parser);

        if (0 === $parse_ret) {
            return 'File is not valid XML';
        }

        $referenceFields = [];
        foreach (ReferenceReader::$referenceFields as $field) {
            $referenceFields[] = strtolower($field);
        }

        $publications = [];
        $startlevel = 0;
        $in_bib = false;
        $in_ref = false;
        $in_authors = false;
        $in_person = false;

        foreach ($tags as $cTag) {
            $lowerCaseTag = strtolower($cTag['tag']);
            $upperCaseTag = strtoupper($cTag['tag']);
            $value = $cTag['value'];

            if (!$in_bib) {
                if (('bib' == $cTag['tag']) && ('open' == $cTag['type'])) {
                    $in_bib = true;
                }
            } else {
                if (!$in_ref) {
                    if (('reference' == $cTag['tag']) && ('open' == $cTag['type'])) {
                        // News reference
                        $in_ref = true;
                        $publication = [];
                    } else {
                        if (('bib' == $cTag['tag']) && ('close' == $cTag['type'])) {
                            // Leave bib
                            $in_bib = false;
                        }
                    }
                } else {
                    // In reference
                    if (!$in_authors) {
                        if (('authors' == $cTag['tag']) && ('open' == $cTag['type'])) {
                            // Enter authors
                            $in_authors = true;
                            $publication['authors'] = [];
                        } else {
                            if (in_array($lowerCaseTag, $referenceFields)) {
                                if ('complete' == $cTag['type']) {
                                    switch ($lowerCaseTag) {
                                        case 'bibtype':
                                            foreach (ReferenceReader::$allBibTypes as $ii => $bib) {
                                                if (strtolower($value) == $bib) {
                                                    $value = $ii;
                                                    break;
                                                }
                                            }
                                            break;
                                        case 'state':
                                            foreach (ReferenceReader::$allStates as $ii => $state) {
                                                if (strtolower($value) == $state) {
                                                    $value = $ii;
                                                    break;
                                                }
                                            }
                                            break;
                                        default:
                                    }
                                    // Apply value
                                    if (in_array($lowerCaseTag, ReferenceReader::$referenceFields)) {
                                        $publication[$lowerCaseTag] = $value;
                                    } else {
                                        if (in_array($upperCaseTag, ReferenceReader::$referenceFields)) {
                                            $publication[$upperCaseTag] = $value;
                                        } else {
                                            $publication[$cTag['tag']] = $value;
                                        }
                                    }
                                } else {
                                    // Unknown field
                                    $this->statistics['warnings'][] = 'Ignored field: '.$cTag['tag'];
                                }
                            } else {
                                if (('reference' == $cTag['tag']) && ('close' == $cTag['type'])) {
                                    // Leave reference
                                    $in_ref = false;
                                    $publications[] = $publication;
                                } else {
                                    // Unknown field
                                    $this->statistics['warnings'][] = 'Ignored field: '.$cTag['tag'];
                                }
                            }
                        }
                    } else {
                        // In authors
                        if (!$in_person) {
                            if (('person' == $cTag['tag']) && ('open' == $cTag['type'])) {
                                // Enter person
                                $in_person = true;
                                $author = [];
                            } else {
                                if (('authors' == $cTag['tag']) && ('close' == $cTag['type'])) {
                                    // Leave authors
                                    $in_authors = false;
                                }
                            }
                        } else {
                            // In person
                            $sn_fields = ['surname', 'sn'];
                            $fn_fields = ['forename', 'fn'];
                            if (in_array($cTag['tag'], $sn_fields) && ('complete' == $cTag['type'])) {
                                $author['surname'] = $value;
                            } else {
                                if (in_array($cTag['tag'], $fn_fields) && ('complete' == $cTag['type'])) {
                                    $author['forename'] = $value;
                                } else {
                                    if (('person' == $cTag['tag']) && ('close' == $cTag['type'])) {
                                        // Leave person
                                        $in_person = false;
                                        $publication['authors'][] = $author;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $publications;
    }
}