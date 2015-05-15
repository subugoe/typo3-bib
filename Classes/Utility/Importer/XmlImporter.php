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

/**
 * Class XmlImporter
 * @package Ipf\Bib\Utility\Importer
 */
class XmlImporter extends Importer {

	/**
	 * @param \tx_bib_pi1 $pi1
	 * @return void
	 */
	public function initialize($pi1) {
		parent::initialize($pi1);
		$this->import_type = Importer::IMP_XML;
	}

	/**
	 * @return string
	 */
	protected function displayInformationBeforeImport() {
		$val = $this->pi1->get_ll('import_xml_title', 'import_xml_title', TRUE);
		$content = '<p>' . $val . '</p>';

		return $content;
	}

	/**
	 * @return string
	 */
	protected function importStateTwo() {
		$content = '';

		$stat =& $this->statistics;
		$stat['file_name'] = $_FILES['ImportFile']['name'];
		$stat['file_size'] = $_FILES['ImportFile']['size'];

		$stat['succeeded'] = 0;
		$stat['failed'] = 0;
		$stat['storage'] = $this->storage_pid;

		$fstr = file_get_contents($_FILES['ImportFile']['tmp_name']);

		$parsed = $this->parseXmlPublications($fstr);
		if (is_string($parsed)) {
			$stat['failed'] += 1;
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
	 * @return array|string
	 */
	protected function parseXmlPublications($xmlPublications) {
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		$parse_ret = xml_parse_into_struct($parser, $xmlPublications, $tags);
		xml_parser_free($parser);

		if ($parse_ret == 0) {
			return 'File is not valid XML';
		}

		$referenceFields = [];
		foreach ($this->referenceReader->getReferenceFields() as $field) {
			$referenceFields[] = strtolower($field);
		}

		$publications = [];
		$startlevel = 0;
		$in_bib = FALSE;
		$in_ref = FALSE;
		$in_authors = FALSE;
		$in_person = FALSE;

		foreach ($tags as $cTag) {
			$lowerCaseTag = strtolower($cTag['tag']);
			$upperCaseTag = strtoupper($cTag['tag']);
			$value = $this->importUnicodeString($cTag['value']);

			if (!$in_bib) {
				if (($cTag['tag'] == 'bib') && ($cTag['type'] == 'open')) {
					$in_bib = TRUE;
				}
			} else {
				if (!$in_ref) {
					if (($cTag['tag'] == 'reference') && ($cTag['type'] == 'open')) {
						// News reference
						$in_ref = TRUE;
						$publication = [];
					} else
						if (($cTag['tag'] == 'bib') && ($cTag['type'] == 'close')) {
							// Leave bib
							$in_bib = FALSE;
						}
				} else {
					// In reference
					if (!$in_authors) {
						if (($cTag['tag'] == 'authors') && ($cTag['type'] == 'open')) {
							// Enter authors
							$in_authors = TRUE;
							$publication['authors'] = [];
						} else
							if (in_array($lowerCaseTag, $referenceFields)) {
								if ($cTag['type'] == 'complete') {
									switch ($lowerCaseTag) {
										case 'bibtype':
											foreach ($this->referenceReader->allBibTypes as $ii => $bib) {
												if (strtolower($value) == $bib) {
													$value = $ii;
													break;
												}
											}
											break;
										case 'state':
											foreach ($this->referenceReader->allStates as $ii => $state) {
												if (strtolower($value) == $state) {
													$value = $ii;
													break;
												}
											}
											break;
										default:
									}
									// Apply value
									if (in_array($lowerCaseTag, $this->referenceReader->getReferenceFields())) {
										$publication[$lowerCaseTag] = $value;
									} else {
										if (in_array($upperCaseTag, $this->referenceReader->getReferenceFields())) {
											$publication[$upperCaseTag] = $value;
										} else {
											$publication[$cTag['tag']] = $value;
										}
									}
								} else {
									// Unknown field
									$this->statistics['warnings'][] = 'Ignored field: ' . $cTag['tag'];
								}
							} else
								if (($cTag['tag'] == 'reference') && ($cTag['type'] == 'close')) {
									// Leave reference
									$in_ref = FALSE;
									$publications[] = $publication;
								} else {
									// Unknown field
									$this->statistics['warnings'][] = 'Ignored field: ' . $cTag['tag'];
								}
					} else {
						// In authors
						if (!$in_person) {
							if (($cTag['tag'] == 'person') && ($cTag['type'] == 'open')) {
								// Enter person
								$in_person = TRUE;
								$author = [];
							} else
								if (($cTag['tag'] == 'authors') && ($cTag['type'] == 'close')) {
									// Leave authors
									$in_authors = FALSE;
								}
						} else {
							// In person
							$sn_fields = ['surname', 'sn'];
							$fn_fields = ['forename', 'fn'];
							if (in_array($cTag['tag'], $sn_fields) && ($cTag['type'] == 'complete')) {
								$author['surname'] = $value;
							} else {
								if (in_array($cTag['tag'], $fn_fields) && ($cTag['type'] == 'complete')) {
									$author['forename'] = $value;
								} else {
									if (($cTag['tag'] == 'person') && ($cTag['type'] == 'close')) {
										// Leave person
										$in_person = FALSE;
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
