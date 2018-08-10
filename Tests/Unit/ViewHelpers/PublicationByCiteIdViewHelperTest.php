<?php

namespace Ipf\Bib\Tests\Unit\ViewHelpers;

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

use Ipf\Bib\ViewHelpers\PublicationByCiteIdViewHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Description.
 */
class PublicationByCiteIdViewHelperTest extends UnitTestCase
{
    /**
     * @var \Ipf\Bib\ViewHelpers\PublicationByCiteIdViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->templateVariableContainer = new \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer();
        $this->renderingContext->injectTemplateVariableContainer($this->templateVariableContainer);
        $this->viewHelper = $this->getAccessibleMock(PublicationByCiteIdViewHelper::class, ['dummy']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     * @expectedException \Exception
     */
    public function providingAnEmptyCiteIdThrowsAnException()
    {
        $this->viewHelper->setArguments(['citeId' => '']);
        $this->assertEquals($this->viewHelper->__call('render'), $this->getExpectedException());
    }

    /**
     * @test
     */
    public function providingANonExistentCiteIdReturnsAnArrayWithExceptionKey()
    {
        $this->viewHelper->setArguments(['citeId' => 'mueller98']);
        $this->assertArrayHasKey('exception', $this->viewHelper->_call('render'));
    }
}
