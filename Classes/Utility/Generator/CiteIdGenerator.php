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

use Ipf\Bib\Domain\Model\Reference;
use Ipf\Bib\Utility\ReferenceReader;
use Ipf\Bib\Utility\Utility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Class CiteIdGenerator.
 */
class CiteIdGenerator
{
    /**
     * @var \Ipf\Bib\Utility\ReferenceReader
     */
    public $referenceReader;

    public function __construct(array $configuration)
    {
        $this->referenceReader = GeneralUtility::makeInstance(ReferenceReader::class, $configuration);
    }

    /**
     * Generates a cite id for the publication].
     */
    public function generateId(Reference $row): string
    {
        $id = $this->generateBasicId($row);
        $tmpId = $id;

        $uid = -1;
        if ($row->getUid() >= 0) {
            $uid = $row->getUid();
        }

        $num = 1;
        while ($this->referenceReader->citeIdExists($tmpId, $uid)) {
            ++$num;
            $tmpId = $id.'_'.$num;
        }
        $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        $signalSlotDispatcher->dispatch(
            __CLASS__,
            'beforeCiteIdGeneration',
            [
                'bib',
                $this,
            ]
        );

        return $tmpId;
    }

    /**
     * @param Reference $row
     *
     * @return string
     */
    private function generateBasicId(Reference $row): string
    {
        $authors = $row->getAuthors();
        $editors = Utility::explodeAuthorString($row->getEditor());

        $persons = [$authors, $editors];

        $id = '';
        foreach ($persons as $list) {
            if (0 === strlen($id)) {
                if (count($list) > 0) {
                    $pp = &$list[0];
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
            $listSize = count($list);
            for ($i = 1; $i < $listSize; ++$i) {
                $pp = &$list[$i];
                $a_str = '';
                if (strlen($pp['surname']) > 0) {
                    $a_str = $pp['surname'];
                } else {
                    if (strlen($pp['forename'])) {
                        $a_str = $pp['forename'];
                    }
                }
                if (strlen($a_str) > 0) {
                    $id .= mb_substr($this->simplifiedString($a_str), 0, 1);
                }
            }
        }

        if (0 === strlen($id)) {
            $id = GeneralUtility::shortMD5(serialize($row));
        }
        if ($row->getYear() > 0) {
            $id .= $row->getYear();
        }

        return $this->simplifiedString($id);
    }

    /**
     * Replaces all special characters and HTML sequences in a string to
     * characters that are allowed in a citation id.
     *
     * @param string $id
     *
     * @return string The simplified string
     */
    protected function simplifiedString(string $id): string
    {
        // Replace some special characters with ASCII characters
        $id = htmlentities($id, ENT_QUOTES);
        $id = str_replace('&amp;', '&', $id);
        $id = preg_replace('/&(\w)\w{1,7};/', '$1', $id);

        // Replace remaining special characters with ASCII characters
        $tmpId = '';
        $idLength = mb_strlen($id);
        for ($i = 0; $i < $idLength; ++$i) {
            $c = mb_substr($id, $i, 1);
            if (ctype_alnum($c) || ('_' === $c)) {
                $tmpId .= $c;
            }
        }

        return $tmpId;
    }
}
