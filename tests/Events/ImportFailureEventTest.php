<?php

namespace Fastbolt\FabricImporter\Tests\Events;

use DateTime;
use Exception;
use Fastbolt\FabricImporter\Events\ImportFailureEvent;
use Fastbolt\FabricImporter\Tests\_Helpers\DummyImporterDefinition;
use PHPUnit\Framework\TestCase;

/**
 * @covers("\Fastbolt\FabricImporter\Events\ImportFailureEvent")
 */
class ImportFailureEventTest extends TestCase
{
    public function testGetters(): void
    {
        $definition = new DummyImporterDefinition();
        $date = new DateTime();
        $ex = new Exception("Test");
        $event = new ImportFailureEvent($definition, $date, $ex);

        self::assertSame($definition, $event->getDefinition(), 'Event definition not returned correctly.');
        self::assertSame($date, $event->getImportStart(), 'Import start not returned correctly');
        self::assertEquals('Test', $event->getException()->getMessage(),  'Event message not correct');
    }
}
