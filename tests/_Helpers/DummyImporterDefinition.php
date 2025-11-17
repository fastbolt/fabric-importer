<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Tests\_Helpers;

use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinition;
use Fastbolt\FabricImporter\Types\FabricJoinedSelect;
use Fastbolt\FabricImporter\Types\FabricTableJoin;

class DummyImporterDefinition extends FabricImporterDefinition
{
    public function getName(): string
    {
        return 'dummy';
    }

    public function getTableJoinsDefinitions(): array
    {
        return [
            new FabricTableJoin(
                'ham',
                'h',
                'testCondition',
                'RIGHT',
                [
                    new FabricJoinedSelect(
                        'eggs',
                        'eggTarget'
                    )
                ]
            )
        ];
    }

    public function getSourceTable(): string
    {
        return 'dummy_table';
    }

    public function getTargetTable(): string
    {
        return 'dummy_table_target';
    }

    public function getDescription(): string
    {
        return 'A dummy instance of the FabricImporterDefinition';
    }

    public function getIdentifierMapping(): array
    {
        return [
            'foo_a' => 'foo_b'
        ];
    }

    public function getFieldNameMapping(): array
    {
        return [
            'field1_a' => 'field1_b',
            'field2_a' => 'field2_b'
        ];
    }

    public function getFlushInterval(): int
    {
        return 0;
    }
}
