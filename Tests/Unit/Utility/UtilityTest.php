<?php

namespace Ipf\Bib\Tests\Unit\Utility;

use Ipf\Bib\Utility\Utility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\BaseTestCase;

class UtilityTest extends BaseTestCase
{
    /**
     * @var Utility
     */
    private $utility;

    protected function setUp()
    {
        parent::setUp();
        $this->utility = GeneralUtility::makeInstance(Utility::class);
    }

    /**
     * @test
     */
    public function implodeAndLastDeliverLastElement()
    {
        $element = ['sheytan', 'savatage', 'mountain king'];
        $this->assertSame('sheytan, savatage and mountain king', Utility::implode_and_last($element, ', ', ' and '));
    }

    /**
     * @test
     */
    public function cropMiddleCropsTheMiddle()
    {
        $string = 'sheytan, savatage and mountain king';
        $this->assertSame('sheytan, s…ntain king', Utility::crop_middle($string, 20));

        $string = 'sheytan, sövata’¥×ge and mountain king';
        $this->assertSame('sheytan, s…ntain king', Utility::crop_middle($string, 20));
    }

    /**
     * @test
     */
    public function unnecessaryTagsAreFiltered()
    {
        // em is an allowed tag, prt is going to be filtered
        $string = '<lorem>&</lorem> <ipsum sheytan="22"><em>Yo</em></ipsum><prt>ernoster</prt>';
        $this->assertSame('&lt;lorem&gt;&amp;&lt;/lorem&gt; &lt;ipsum sheytan="22"&gt;<em>Yo</em>&lt;/ipsum&gt;ernoster', Utility::filter_pub_html_display($string));
    }
}
