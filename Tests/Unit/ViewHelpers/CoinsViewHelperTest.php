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
use Ipf\Bib\ViewHelpers\CoinsViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer;
use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for coins ViewHelper.
 */
class CoinsViewHelperTest extends ViewHelperBaseTestcase
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
                'data' => [
                    'hidden' => '0',
                    'tstamp' => '1418897565',
                    'sorting' => '0',
                    'crdate' => '1418897565',
                    'cruser_id' => '14',
                    'bibtype' => 'Buch',
                    'title' => '<a href="/test/bib/?no_cache=1&amp;tx_bib_pi1%5Bshow_uid%5D=3456
								      #c3456">Die Mönchsklöster der Benediktiner in Mecklenburg-Vorpomme
								      rn, Sachsen-Anhalt, Thüringen und Sachsen</a>',
                    'year' => '2012',
                    'volume' => '10',
                    'publisher' => 'EOS-Verlag',
                    'address' => 'St. Ottilien',
                    'series' => 'Germania Benedictina',
                    'ISBN' => '9783830675716',
                    'authors' => ' Römer, Christof',
                ],
            ],
        ];
    }

    public function setUp()
    {
        parent::setUp();
        $this->templateVariableContainer = new TemplateVariableContainer();
        $this->renderingContext->injectTemplateVariableContainer($this->templateVariableContainer);
        $this->fixture = $this->getAccessibleMock(CoinsViewHelper::class, ['dummy']);
        $this->injectDependenciesIntoViewHelper($this->fixture);
    }

    /**
     * @test
     * @dataProvider publicationProvider
     */
    public function tagFromTypeSpanIsGenerated($data)
    {
        $this->fixture->setArguments(['data' => $data]);
        $mockTagBuilder = $this->getMock(TagBuilder::class, ['setTagName', 'addAttribute', 'setContent']);
        $mockTagBuilder->expects($this->once())->method('setTagName')->with('span');
        $this->fixture->_set('tag', $mockTagBuilder);
        $this->fixture->initialize();
        $this->fixture->expects($this->any())->method('render')->will($this->returnValue('span tag'));

        $this->fixture->render($data);
    }
}
