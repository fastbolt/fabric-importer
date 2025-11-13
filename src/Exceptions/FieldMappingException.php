<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Exceptions;

use Exception;

class FieldMappingException extends Exception
{
    /**
     * @param string $definitionName
     * @param string $sourceFiled
     * @param string $targetField
     */
    public function __construct(string $definitionName, string $sourceFiled, string $targetField)
    {
        $message = sprintf(
            'Field mapping contains errors for %s, source field: %s, target field: %s',
            $definitionName,
            $sourceFiled,
            $targetField
        );
        parent::__construct($message);
    }
}
