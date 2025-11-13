<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Types;


use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinition;

class ImportResult
{
    private FabricImporterDefinition $definition;

    /**
     * @var int
     */
    private int $success = 0;

    /**
     * @var int
     */
    private int $errors = 0;

    public function __construct(FabricImporterDefinition $definition)
    {
        $this->definition = $definition;
    }

    public function getDefinition(): FabricImporterDefinition
    {
        return $this->definition;
    }

    /**
     * @return $this
     */
    public function increaseSuccess(int $number = 1): self
    {
        $this->success = $this->success + $number;

        return $this;
    }

    /**
     * @return $this
     */
    public function increaseErrors(): self
    {
        $this->errors++;

        return $this;
    }

    /**
     * @return int
     */
    public function getErrors(): int
    {
        return $this->errors;
    }

    /**
     * @return int
     */
    public function getSuccess(): int
    {
        return $this->success;
    }
}
