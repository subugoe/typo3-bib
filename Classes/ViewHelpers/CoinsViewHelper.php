<?php
/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
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

namespace Ipf\Bib\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * @see http://ocoins.info/cobgbook.html
 */
class CoinsViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * assignment from delivered values to the coins identifier.
     *
     * @var array
     */
    protected $fieldAssignment = [
        'title' => 'title',
        'isbn' => 'ISBN',
        'date' => 'year',
        'place' => 'address',
        'pub' => 'publisher',
        'genre' => 'bibtype',
        'series' => 'series',
    ];

    /**
     * @var string
     */
    protected $tagName = 'span';

    /**
     * @param array $data
     *
     * @return string
     */
    public function render($data)
    {
        $coinsData = [];

        $coinsData[] = 'ctx_ver=Z39.88-2004';
        $coinsData[] = 'rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook';

        foreach ($this->fieldAssignment as $coinsTitle => $bibTitle) {
            if ($data[$bibTitle]) {
                $coinsData[] = 'rft.' . $coinsTitle . '=' . $this->formatEntity($data[$bibTitle]);
            }
        }
        if ($data['authors']) {
            $author = $this->formatAuthor($data['authors']);
            $coinsData[] = 'rft.aulast=' . $this->formatEntity($author[0]);
            $coinsData[] = 'rft.aufirst=' . $this->formatEntity($author[1]);
        }

        $this->tag->addAttribute('class', 'Z3988');
        $this->tag->addAttribute('title', implode('&', $coinsData));

        return $this->tag->render();
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function formatEntity($string)
    {
        $string = strip_tags($string);

        return urlencode($string);
    }

    /**
     * @param string $authors
     *
     * @return array
     */
    protected function formatAuthor($authors)
    {
        if (strpos($authors, ';')) {
            $authors = GeneralUtility::trimExplode(';', $authors);
        }
        $author = GeneralUtility::trimExplode(',', $authors);

        return $author;
    }
}
