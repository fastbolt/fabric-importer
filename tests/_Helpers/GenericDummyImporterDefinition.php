<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Tests\_Helpers;

use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinition;

class GenericDummyImporterDefinition extends FabricImporterDefinition
{
    public function __construct(
        private readonly string $name,
        private readonly array $dependencies = [],
    ) {
        parent::__construct();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getImportDependencies(): array
    {
        return $this->dependencies;
    }

    public function getSourceTable(): string
    {
        return $this->name . '_table';
    }

    public function getTargetTable(): string
    {
        return $this->name . 'table_target';
    }

    public function getDescription(): string
    {
        return sprintf('A %s instance of the FabricImporterDefinition', $this->name);
    }

    public function getIdentifierMapping(): array
    {
        return [
            'foo_a' => 'foo_b',
        ];
    }

    public function getFieldNameMapping(): array
    {
        return [
            'field1_a' => 'field1_b',
            'field2_a' => 'field2_b',
        ];
    }

    public function getFlushInterval(): int
    {
        return 0;
    }
}
