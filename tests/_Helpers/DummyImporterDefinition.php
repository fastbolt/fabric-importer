<?php

namespace Fastbolt\FabricImporter\Tests\_Helpers;

use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinition;

class DummyImporterDefinition extends FabricImporterDefinition
{

    function getName(): string
    {
        return 'dummy';
    }

    public function getDoInsertIfNotExist(): bool
    {
        return true;
    }

    public function getSourceTable(): string
    {
        return 'dummy_table';
    }

    public function getDescription(): string
    {
        return 'A dummy instance of the FabricImporterDefinition';
    }

    public function getIdentifierMapping(): array
    {
        return [
            'foo' => 'bar'
        ];
    }
}