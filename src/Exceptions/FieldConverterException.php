<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Exceptions;

use Exception;

class FieldConverterException extends Exception
{
    /**
     * @param string $definitionName
     * @param string $converterName
     * @param string $message
     */
    public function __construct(string $definitionName, string $converterName, string $message)
    {
        $message = sprintf(
            "Error in converter '$converterName' for import '$definitionName': $message",
        );
        parent::__construct($message);
    }
}
