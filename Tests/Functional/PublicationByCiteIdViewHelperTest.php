<?php

namespace Ipf\Bib\Tests\Functional;

use Ipf\Bib\ViewHelpers\PublicationByCiteIdViewHelper;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PublicationByCiteIdViewHelperTest extends FunctionalTestCase
{
    /**
     * @var PublicationByCiteIdViewHelper
     */
    private $viewHelper;

    public function setUp()
    {
        parent::setUp();
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(PublicationByCiteIdViewHelper::class, ['dummy']);

        $this->importDataSet(__DIR__.'/../Fixtures/tx_bib_domain_model_author.xml');
    }

    /**
     * @test
     * @expectedException \Ipf\Bib\Exception\DataException
     */
    public function providingANonExistentCiteIdThrowsAnException()
    {
        $this->viewHelper->setArguments(['citeId' => 'mueller98', 'storagePid' => 442]);
        $this->assertSame($this->getExpectedException(), $this->viewHelper->render());
    }
}
