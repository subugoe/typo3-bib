<?php

namespace Ipf\Bib\Tests\Functional;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 *
 */
class ListViewTest extends FunctionalTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_bib_domain_model_author.xml');
    }

}
