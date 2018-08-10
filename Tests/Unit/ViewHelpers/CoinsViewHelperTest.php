<?php

namespace Ipf\Bib\Tests\Unit\ViewHelpers;

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

use Ipf\Bib\Domain\Model\Author;
use Ipf\Bib\Domain\Model\Reference;
use Ipf\Bib\ViewHelpers\CoinsViewHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test for coins ViewHelper.
 */
class CoinsViewHelperTest extends UnitTestCase
{
    /**
     * @var \Ipf\Bib\ViewHelpers\CoinsViewHelper
     */
    protected $fixture;

    /**
     * @return array
     */
    public function publicationProvider()
    {
        return [
            [
                (new Reference())
                ->setHidden(false)
                ->setTstamp(1418897565)
                ->setCrdate(1418897565)
                ->setBibtype(2)
                ->setTitle('Die Mönchsklöster der Benediktiner in Mecklenburg-Vorpommern, Sachsen-Anhalt, Thüringen und Sachsen')
                ->setYear(2012)
                ->setVolume('10')
                ->setPublisher('EOS-Verlag')
                ->setAddress('St. Ottilien')
                ->setSeries('Germania Benedictina')
                ->setISBN('9783830675716')
                ->setAuthors([(new Author())->setForeName('Christof')->setSurName('Römer')]),
                '<span class="Z3988" title="ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook&amp;rft.title=Die+M%C3%B6nchskl%C3%B6ster+der+Benediktiner+in+Mecklenburg-Vorpommern%2C+Sachsen-Anhalt%2C+Th%C3%BCringen+und+Sachsen&amp;rft.isbn=9783830675716&amp;rft.date=2012&amp;rft.place=St.+Ottilien&amp;rft.pub=EOS-Verlag&amp;rft.genre=bib&amp;rft.series=Germania+Benedictina&amp;rft.aulast=R%C3%B6mer&amp;rft.aufirst=Christof" />',
            ],
        ];
    }

    public function setUp()
    {
        parent::setUp();
        $this->fixture = $this->getAccessibleMock(CoinsViewHelper::class, ['localize']);
        $this->fixture->expects($this->any())->method('localize')->willReturn('bib');
    }

    /**
     * @test
     * @dataProvider publicationProvider
     */
    public function referenceMatchesCoins($data, $result)
    {
        $this->fixture->setArguments(['data' => $data]);
        $this->assertSame($result, $this->fixture->render());
    }
}
