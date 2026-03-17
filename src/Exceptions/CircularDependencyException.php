<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Exceptions;

use Exception;

class CircularDependencyException extends Exception
{
    public function __construct()
    {
        $message = "Circular dependency detected. This can occur when importer A depends on importer B, which in turn depends on importer A. Please check the dependencies of your importers and ensure there are no circular references.";

        parent::__construct($message, 500);
    }
}
