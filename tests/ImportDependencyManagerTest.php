<?php

namespace Fastbolt\FabricImporter\Tests;

use Fastbolt\FabricImporter\ImportDependencyManager;
use Fastbolt\FabricImporter\Tests\_Helpers\GenericDummyImporterDefinition;
use LogicException;
use PHPUnit\Framework\TestCase;

class ImportDependencyManagerTest extends TestCase
{
    public function testGetNamesInDependencyAwareOrderCompleteDefinition()
    {
        $definitions = [
            new GenericDummyImporterDefinition('customers', []),
            new GenericDummyImporterDefinition('orders', ['customers']),
            new GenericDummyImporterDefinition('material_groups', []),
            new GenericDummyImporterDefinition('order_items', ['orders', 'materials']),
            new GenericDummyImporterDefinition('materials', ['material_groups']),
        ];

        $dependencyManager = new ImportDependencyManager($definitions);
        $dependencies      = $dependencyManager->getNamesInDependencyAwareOrder();

        self::assertSame(
            ['customers', 'material_groups', 'materials', 'orders', 'order_items'],
            $dependencies
        );
    }

    public function testGetNamesInDependencyAwareOrderCompleteDefinitionRecursionError()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Recursion detected in importer dependencies. Please check the importer definitions for circular dependencies.'
        );

        $definitions = [
            new GenericDummyImporterDefinition('customers', ['orders']),
            new GenericDummyImporterDefinition('orders', ['customers']),
        ];

        $dependencyManager = new ImportDependencyManager($definitions);
        $dependencyManager->getNamesInDependencyAwareOrder();
    }
}
