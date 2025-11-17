<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Tests\Providers;

use DateTime;
use Fastbolt\FabricImporter\Providers\ImportQueryProvider;
use Fastbolt\FabricImporter\Tests\_Helpers\DummyImporterDefinition;
use Fastbolt\FabricImporter\Tests\_Helpers\QueryCleaner;
use PHPUnit\Framework\TestCase;

class ImportQueryProviderTest extends TestCase
{
    use QueryCleaner;

    public function testBuildQuery()
    {
        $date = new DateTime("2024-01-01");
        $offset = 100;
        $definition = new DummyImporterDefinition();

        $provider = new ImportQueryProvider();
        ['parameters' => $params, 'query' => $query]
            = $provider->buildQuery($definition, $offset, $date);

        $expectedQuery = "
            SELECT 
                t.foo_a AS foo_a,
                t.field1_a AS field1_a,
                t.field2_a AS field2_a,
                h.eggs AS eggTarget
            FROM dummy_table t
            RIGHT JOIN ham h ON testCondition
            WHERE t.dwh_loaded_at > :lastImportDate  
            ORDER BY foo_a ASC 
            OFFSET 100 ROWS
            FETCH NEXT 1000 ROWS ONLY
            ";

        $expectedQuery = $this->cleanString($expectedQuery);
        $query         = $this->cleanString($query);
        $this::assertEquals($expectedQuery, $query, 'Query not like expected.');
        $this::assertEquals(['lastImportDate' => $date], $params, 'Params not as expected.');
    }
}
