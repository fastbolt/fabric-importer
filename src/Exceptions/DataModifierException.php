<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Exceptions;

use Exception;

class DataModifierException extends Exception
{
    /**
     * @param string $definitionName
     * @param string $message
     */
    public function __construct(string $definitionName, string $message)
    {
        $message = "Error in modifier method for import '$definitionName': $message";
        parent::__construct($message);
    }
}
