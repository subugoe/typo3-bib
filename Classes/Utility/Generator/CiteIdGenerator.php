<?php
namespace Ipf\Bib\Utility\Generator;

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

use Ipf\Bib\Utility\Utility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CiteIdGenerator
 * @package Ipf\Bib\Utility\Generator
 */
class CiteIdGenerator
{

    /**
     * @var \tx_bib_pi1
     */
    public $pi1;

    /**
     * @var \Ipf\Bib\Utility\ReferenceReader
     */
    public $referenceReader;

    /**
     * @var String
     */
    public $charset;


    /**
     * @param \tx_bib_pi1 $pi1
     */
    public function initialize($pi1)
    {
        $this->referenceReader =& $pi1->referenceReader;
        $this->charset = $pi1->extConf['charset']['upper'];
    }

    /**
     * Generates a cite id for the publication in piVars['DATA']
     *
     * @param array $row
     * @return string The generated id
     */
    public function generateId($row)
    {
        $id = $this->generateBasicId($row);
        $tmpId = $id;

        $uid = -1;
        if (array_key_exists('uid', $row) && ($row['uid'] >= 0)) {
            $uid = intval($row['uid']);
        }

        $num = 1;
        while ($this->referenceReader->citeIdExists($tmpId, $uid)) {
            $num++;
            $tmpId = $id . '_' . $num;
        }

        return $tmpId;
    }


    /**
     * @param array $row
     * @return string
     */
    protected function generateBasicId($row)
    {
        $authors = $row['authors'];
        $editors = Utility::explodeAuthorString($row['editor']);

        $persons = [$authors, $editors];

        $id = '';
        foreach ($persons as $list) {
            if (strlen($id) == 0) {
                if (sizeof($list) > 0) {
                    $pp =& $list[0];
                    $a_str = '';
                    if (strlen($pp['surname']) > 0) {
                        $a_str = $pp['surname'];
                    } else {
                        if (strlen($pp['forename'])) {
                            $a_str = $pp['forename'];
                        }
                    }
                    if (strlen($a_str) > 0) {
                        $id = $this->simplifiedString($a_str);
                    }
                }
            }
            $listSize = sizeof($list);
            for ($i = 1; $i < $listSize; $i++) {
                $pp =& $list[$i];
                $a_str = '';
                if (strlen($pp['surname']) > 0) {
                    $a_str = $pp['surname'];
                } else {
                    if (strlen($pp['forename'])) {
                        $a_str = $pp['forename'];
                    }
                }
                if (strlen($a_str) > 0) {
                    $id .= mb_substr($this->simplifiedString($a_str), 0, 1, $this->charset);
                }
            }
        }

        if (strlen($id) == 0) {
            $id = GeneralUtility::shortMD5(serialize($row));
        }
        if ($row['year'] > 0) {
            $id .= $row['year'];
        }

        return $this->simplifiedString($id);
    }


    /**
     * Replaces all special characters and HTML sequences in a string to
     * characters that are allowed in a citation id
     *
     * @param string $id
     * @return string The simplified string
     */
    protected function simplifiedString($id)
    {
        // Replace some special characters with ASCII characters
        $id = htmlentities($id, ENT_QUOTES, $this->charset);
        $id = str_replace('&amp;', '&', $id);
        $id = preg_replace('/&(\w)\w{1,7};/', '$1', $id);

        // Replace remaining special characters with ASCII characters
        $tmpId = '';
        $idLength = mb_strlen($id, $this->charset);
        for ($i = 0; $i < $idLength; $i++) {
            $c = mb_substr($id, $i, 1, $this->charset);
            if (ctype_alnum($c) || ($c == '_')) {
                $tmpId .= $c;
            }
        }
        return $tmpId;
    }

}
