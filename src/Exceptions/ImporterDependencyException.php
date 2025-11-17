<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Exceptions;

use DateTimeInterface;
use Exception;

class ImporterDependencyException extends Exception
{
    /**
     * @param string                 $importerName
     * @param string                 $dependencyName
     * @param DateTimeInterface|null $lastImportedDate
     */
    public function __construct(string $importerName, string $dependencyName, ?DateTimeInterface $lastImportedDate)
    {
        $lastImportedDate = $lastImportedDate?->format('Y-m-d H:i:s') ?? 'Never imported';
        $message = "The importer-dependency for the importer $importerName has not been executed recently enough: $dependencyName: $lastImportedDate";

        parent::__construct($message, 500);
    }
}
