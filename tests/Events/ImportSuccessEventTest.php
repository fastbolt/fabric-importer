<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Tests\Events;

use DateTime;
use Fastbolt\FabricImporter\Events\ImportSuccessEvent;
use Fastbolt\FabricImporter\Tests\_Helpers\DummyImporterDefinition;
use Fastbolt\FabricImporter\Types\ImportResult;
use PHPUnit\Framework\TestCase;

class ImportSuccessEventTest extends TestCase
{
    public function testGetters(): void
    {
        $definition = new DummyImporterDefinition();
        $date = new DateTime();
        $importResult = new ImportResult($definition);
        $event = new ImportSuccessEvent(
            $definition,
            $date,
            $importResult
        );

        self::assertSame($definition, $event->getDefinition(), 'Event definition not returned correctly.');
        self::assertSame($date, $event->getImportStart(), 'Import start not returned correctly');
        self::assertSame($importResult, $event->getImportResult(), 'Import result not returned correctly');
    }
}
