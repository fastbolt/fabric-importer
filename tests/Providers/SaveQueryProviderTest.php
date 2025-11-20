<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Tests\Providers;

use Fastbolt\FabricImporter\Providers\SaveQueryProvider;
use Fastbolt\FabricImporter\Tests\_Helpers\DummyImporterDefinition;
use Fastbolt\FabricImporter\Tests\_Helpers\QueryCleaner;
use PHPUnit\Framework\TestCase;

class SaveQueryProviderTest extends TestCase
{
    use QueryCleaner;

//    public function testGetUpdateQuery(): void
//    {
//        $definition = new DummyImporterDefinition();
//
//        $item = [
//            'foo_a'    => 'foo',
//            'field1_a' => 'val1',
//            'field2_a' => 'val2'
//        ];
//
//        $provider = new SaveQueryProvider();
//        $result = $provider->getUpdateQuery($definition, $item);
//
//        $expectedQuery = 'UPDATE dummy_table_target SET field1_b = "val1", field2_b = "val2" WHERE foo_b = "foo";';
//
//        $this->assertEquals($expectedQuery, $result, 'Update query not returned as expected.');
//    }

    public function testGetInsertQuery(): void
    {
        $definition = new DummyImporterDefinition();

        $item = [
            'foo_a'     => 'foo',
            'field1_a'  => 'val1',
            'field2_a'  => 'val2',
            'eggTarget' => 'egg'
        ];

        $provider = new SaveQueryProvider();
        $result = $provider->getInsertQuery($definition, $item);

        $expectedQuery = 'INSERT INTO dummy_table_target (foo_b, field1_b, field2_b, eggTarget) VALUES("foo", "val1", "val2", "egg")';

        self::assertEquals($expectedQuery, $result, 'Insert query not generated as expected.');
    }
}
