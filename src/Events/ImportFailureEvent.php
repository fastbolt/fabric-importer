<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Events;

use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinition;
use DateTimeInterface;
use Exception;

class ImportFailureEvent
{
    /**
     * @var Exception
     */
    private Exception $exception;

    /**
     * @var FabricImporterDefinition|null
     */
    private ?FabricImporterDefinition $definition;

    /**
     * @var DateTimeInterface
     */
    private DateTimeInterface $importStart;

    /**
     * @param FabricImporterDefinition|null $definition
     * @param DateTimeInterface             $importStart
     * @param Exception                     $exception
     */
    public function __construct(
        ?FabricImporterDefinition $definition,
        DateTimeInterface $importStart,
        Exception $exception
    ) {
        $this->definition  = $definition;
        $this->importStart = $importStart;
        $this->exception   = $exception;
    }

    /**
     * @return Exception
     */
    public function getException(): Exception
    {
        return $this->exception;
    }

    /**
     * @return FabricImporterDefinition|null
     */
    public function getDefinition(): ?FabricImporterDefinition
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
