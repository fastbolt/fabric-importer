<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Events;

use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinition;
use Fastbolt\FabricImporter\Types\ImportResult;
use DateTimeInterface;

class ImportSuccessEvent
{
    /**
     * @var ImportResult
     */
    private ImportResult $importResult;

    /**
     * @var FabricImporterDefinition
     */
    private FabricImporterDefinition $definition;

    /**
     * @var DateTimeInterface
     */
    private DateTimeInterface $importStart;

    /**
     * @param FabricImporterDefinition $definition
     * @param DateTimeInterface        $importStart
     * @param ImportResult             $importResult
     */
    public function __construct(
        FabricImporterDefinition $definition,
        DateTimeInterface $importStart,
        ImportResult $importResult
    ) {
        $this->definition   = $definition;
        $this->importStart  = $importStart;
        $this->importResult = $importResult;
    }

    /**
     * @return ImportResult
     */
    public function getImportResult(): ImportResult
    {
        return $this->importResult;
    }

    /**
     * @return FabricImporterDefinition
     */
    public function getDefinition(): FabricImporterDefinition
    {
        return $this->definition;
    }

    /**
     * @return DateTimeInterface
     */
    public function getImportStart(): DateTimeInterface
    {
        return $this->importStart;
    }
}
