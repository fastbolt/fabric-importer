<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\ImporterDefinitions;

use Fastbolt\FabricImporter\Types\FabricTableJoin;

abstract class FabricImporterDefinition implements FabricImporterDefinitionInterface
{
    /**
     * @var FabricTableJoin[]
     */
    private array $tableJoins = [];

    /**
     * Must be called in child classes to make the joins work.
     */
    public function __construct()
    {
        $this->tableJoins = $this->getTableJoinsDefinitions();
    }

    /**
     * @inheritDoc
     */
    abstract public function getName(): string;

    /**
     * @inheritDoc
     */
    public function getTargetTable(): string
    {
        return $this->getName();
    }

    /**
     * @inheritDoc
     */
    public function getFieldConverters(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getDataBatchSize(): int
    {
        return 1000;
    }

    /**
     * @inheritDoc
     */
    public function getFlushInterval(): int
    {
        return $this->getDataBatchSize();
    }

    /**
     * @inheritDoc
     */
    public function getFieldNameMapping(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getIdentifierColumns(): array
    {
        return array_values($this->getIdentifierMapping());
    }

    /**
     * @inheritDoc
     */
    public function isThrowExceptionOnUnknownField(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getTableJoinsDefinitions(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    final public function getTableJoins(): array
    {
        return $this->tableJoins;
    }

    /**
     * @inheritDoc
     */
    final public function getJoinedFields(): array
    {
        /** @var string[] $fields */
        $fields = [];
        foreach ($this->tableJoins as $join) {
            foreach ($join->getSelects() as $jSelect) {
                $fields[] = $jSelect->getTargetField();
            }
        }

        return $fields;
    }

    /**
     * @inheritDoc
     */
    final public function getUpdatableFields(): array
    {
        /** @var string[] $joined */
        $joined = [];
        foreach ($this->getJoinedFields() as $field) {
            if (!in_array($field, $this->getIdentifierColumns())) {
                $joined[] = $field;
            }
        }

        /** @var string[] $fields */
        $fields = [
            ...array_values($this->getFieldNameMapping()),
            ...$joined,
            ...array_keys($this->getDefaultValuesForUpdate())
        ];
        return $fields;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultValuesForUpdate(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getDefaultValuesForInsert(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAllowUpdate(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getImportDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getImportFilters(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function modifyItem(array $item): array
    {
        return $item;
    }
}
