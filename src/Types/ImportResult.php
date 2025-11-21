<?php

namespace Fastbolt\FabricImporter\Types;

use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinitionInterface;

class ImportResult
{
    private FabricImporterDefinitionInterface $definition;

    /**
     * @var int
     */
    private int $success = 0;

    /**
     * @var int
     */
    private int $errors = 0;

    /**
     * @param FabricImporterDefinitionInterface $definition
     */
    public function __construct(FabricImporterDefinitionInterface $definition)
    {
        $this->definition = $definition;
    }

    /**
     * @return FabricImporterDefinitionInterface
     */
    public function getDefinition(): FabricImporterDefinitionInterface
    {
        return $this->definition;
    }

    /**
     * @return $this
     */
    public function increaseSuccess(int $number = 1): self
    {
        $this->success += $number;

        return $this;
    }

    /**
     * @param int $number
     *
     * @return $this
     */
    public function setSuccess(int $number): self
    {
        $this->success = $number;

        return $this;
    }

    /**
     * @return $this
     */
    public function increaseErrors(int $number = 1): self
    {
        $this->errors += $number;

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
