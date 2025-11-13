<?php

namespace Fastbolt\FabricImporter\Exceptions;

use DateTimeInterface;
use Exception;

class ImporterDependencyException extends Exception
{
    public function __construct(string $importerName, string $dependencyName, DateTimeInterface $lastImportedDate)
    {
        $lastImportedDate = $lastImportedDate->format('Y-m-d H:i:s');
        $message = "The importer-dependency for the importer $importerName has not been executed recently enough: $dependencyName: $lastImportedDate";

        parent::__construct($message, 500);
    }
}