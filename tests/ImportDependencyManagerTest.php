<?php

namespace Fastbolt\FabricImporter\Tests;

use Fastbolt\FabricImporter\Exceptions\CircularDependencyException;
use Fastbolt\FabricImporter\ImportDependencyManager;
use Fastbolt\FabricImporter\Tests\_Helpers\GenericDummyImporterDefinition;
use PHPUnit\Framework\TestCase;

class ImportDependencyManagerTest extends TestCase
{
    public function testGetNamesInDependencyAwareOrderCompleteDefinition(): void
    {
        $definitions = [
            new GenericDummyImporterDefinition('customers', []),
            new GenericDummyImporterDefinition('orders', ['customers']),
            new GenericDummyImporterDefinition('material_groups', []),
            new GenericDummyImporterDefinition('order_items', ['orders', 'materials']),
            new GenericDummyImporterDefinition('materials', ['material_groups']),
            new GenericDummyImporterDefinition('discounts', []),
        ];

        $dependencyManager = new ImportDependencyManager($definitions);
        $dependencies      = $dependencyManager->getNamesInDependencyAwareOrder();

        self::assertSame(
            ['customers', 'discounts', 'material_groups', 'materials', 'orders', 'order_items'],
            $dependencies
        );
    }

    public function testGetNamesInDependencyAwareOrderSingleType(): void
    {
        $definitions = [
            new GenericDummyImporterDefinition('customers', []),
            new GenericDummyImporterDefinition('orders', ['customers', 'discounts']),
            new GenericDummyImporterDefinition('material_groups', []),
            new GenericDummyImporterDefinition('order_items', ['orders', 'materials']),
            new GenericDummyImporterDefinition('materials', ['material_groups']),
            new GenericDummyImporterDefinition('discounts', []),
        ];

        $dependencyManager = new ImportDependencyManager($definitions);
        $dependencies      = $dependencyManager->getNamesInDependencyAwareOrder('orders');
        self::assertSame(
            ['customers', 'discounts', 'orders'],
            $dependencies
        );

        $dependencies = $dependencyManager->getNamesInDependencyAwareOrder('order_items');
        self::assertSame(
            ['customers', 'discounts', 'material_groups', 'materials', 'orders', 'order_items'],
            $dependencies
        );
    }

    public function testGetNamesInDependencyAwareOrderCompleteDefinitionRecursionError(): void
    {
        $this->expectException(CircularDependencyException::class);

        $definitions = [
            new GenericDummyImporterDefinition('customers', ['orders']),
            new GenericDummyImporterDefinition('orders', ['customers']),
        ];

        $dependencyManager = new ImportDependencyManager($definitions);
        $dependencyManager->getNamesInDependencyAwareOrder();
    }
}
