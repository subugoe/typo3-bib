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

    private $tables = [
        'tx_bib_domain_model_reference',
        'tx_bib_domain_model_author',
        'tx_bib_domain_model_authorships',
    ];

    protected $testExtensionsToLoad = [
        'typo3conf/ext/bib',
    ];

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(PublicationByCiteIdViewHelper::class, ['dummy']);

        $fixtureRootPath = __DIR__.'/../../.Build/Fixtures/';

        foreach ($this->tables as $table) {
            $this->importDataSet($fixtureRootPath.$table.'.xml');
        }
    }

    /**
     * @test
     * @expectedException \Ipf\Bib\Exception\DataException
     */
    public function providingANonExistentCiteIdThrowsAnException()
    {
        $this->viewHelper->setArguments(['citeId' => 'mueller98', 'storagePid' => 584]);
        $this->assertSame($this->getExpectedException(), $this->viewHelper->render());
    }
}
