<?php

namespace Fastbolt\FabricImporter\Types;

readonly class FabricTableJoin
{
    /**
     * @param string                            $table
     * @param string                            $tableAlias
     * @param 'LEFT'|'RIGHT'|'INNER'|'OUTER'|'' $joinMode
     * @param string                            $joinCondition Part of the join Statement, like: ... JOIN ON <joinCondition>
     * @param FabricJoinedSelect[]              $selects Fields that will be selected from the joined table
     */
    public function __construct(
        private string $table,
        private string $tableAlias,
        private string $joinCondition,
        private string $joinMode = '',
        private array $selects = []
    ) {
    }

    public function getJoinStatement(): string
    {
        return "$this->joinMode JOIN $this->table $this->tableAlias 
            ON $this->joinCondition";
    }

    /**
     * Returns a fields for the selected fields for the joined table
     *
     * @return FabricJoinedSelect[]
     */
    public function getSelects(): array
    {
        return $this->selects;
    }

    public function getTableAlias(): string
    {
        return $this->tableAlias;
    }
}