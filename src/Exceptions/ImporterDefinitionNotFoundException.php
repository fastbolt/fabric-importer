<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Exceptions;

use InvalidArgumentException;

class ImporterDefinitionNotFoundException extends InvalidArgumentException
{
    private const MESSAGE = 'Importer %s not found.';

    /**
     * @var string
     */
    private string $name;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;

        parent::__construct(sprintf(self::MESSAGE, $name));
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
