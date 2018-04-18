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

use Ipf\Bib\Domain\Model\Author;
use Ipf\Bib\Domain\Model\Reference;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * @see http://ocoins.info/cobgbook.html
 */
class CoinsViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'span';

    public function initializeArguments()
    {
        $this->registerArgument('data', Reference::class, 'The email address to resolve the gravatar for', true);
    }

    /**
     * @return string
     */
    public function render()
    {
        /** @var Reference $reference */
        $reference = $this->arguments['data'];

        $coinsData = [];

        $coinsData[] = 'ctx_ver=Z39.88-2004';
        $coinsData[] = 'rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook';

        if (!empty($reference->getTitle())) {
            $coinsData[] = sprintf('rft.title=%s', $this->formatEntity($reference->getTitle()));
        }

        if (!empty($reference->getISBN())) {
            $coinsData[] = sprintf('rft.isbn=%s', $this->formatEntity($reference->getISBN()));
        }

        if (!empty($reference->getYear())) {
            $coinsData[] = sprintf('rft.date=%s', $this->formatEntity($reference->getYear()));
        }

        if (!empty($reference->getAddress())) {
            $coinsData[] = sprintf('rft.place=%s', $this->formatEntity($reference->getAddress()));
        }

        if (!empty($reference->getPublisher())) {
            $coinsData[] = sprintf('rft.pub=%s', $this->formatEntity($reference->getPublisher()));
        }

        if ($reference->getBibtype()) {
            $coinsData[] = sprintf('rft.genre=%s', $this->formatEntity(LocalizationUtility::translate(sprintf('tx_bib_domain_model_reference_bibtype_I_%d', $reference->getBibtype()), 'bib')));
        }

        if (!empty($reference->getSeries())) {
            $coinsData[] = sprintf('rft.series=%s', $this->formatEntity($reference->getSeries()));
        }

        if (count($reference->getAuthors()) > 0) {
            /** @var Author $author */
            $author = $reference->getAuthors()[0];
            $coinsData[] = 'rft.aulast='.$this->formatEntity($author->getSurName());
            $coinsData[] = 'rft.aufirst='.$this->formatEntity($author->getForeName());
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
}
