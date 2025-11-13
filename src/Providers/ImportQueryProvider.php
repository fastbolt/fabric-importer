<?php

namespace Fastbolt\FabricImporter\Providers;

use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinitionInterface;
use DateTimeInterface;

class ImportQueryProvider
{
    public function buildQuery(
        FabricImporterDefinitionInterface $definition,
        int $offset,
        ?DateTimeInterface $lastImportDate
    ): array {
        $parameters = [];

        $table          = $definition->getSourceTable();
        $mainTableAlias = 't';

        $fields = $this->getFields($definition, $mainTableAlias);

        $joins    = $definition->getTableJoins();
        $joinPart = '';
        foreach ($joins as $join) {
            $joinPart .= ' ' . $join->getJoinStatement();
        }

        $query = "SELECT $fields 
                  FROM $table $mainTableAlias 
                  $joinPart ";


        $isFirstWhere   = true;
        if ($lastImportDate !== null) {
            $query                        .= "WHERE $mainTableAlias.dwh_loaded_at > :lastImportDate";
            $parameters['lastImportDate'] = $lastImportDate;
            $isFirstWhere                 = false;
        }

        foreach ($definition->getImportFilters() as $condition) {
            if ($isFirstWhere) {
                $query        .= ' WHERE ' . $condition;
                $isFirstWhere = false;
            } else {
                $query .= ' AND ' . $condition;
            }
        }

        //need to order to be able to offset/limit
        $query .= ' ORDER BY';
        foreach ($definition->getIdentifierMapping() as $id => $x) {
            $query .= " $id ASC,";
        }
        $query = rtrim($query, ',');

        $limit = $definition->getDataBatchSize();
        $query .= " OFFSET $offset ROWS";
        $query .= " FETCH NEXT $limit ROWS ONLY";

        return [
            'query'      => $query,
            'parameters' => $parameters
        ];
    }

    /**
     * Builds the string that determines which fields to select
     *
     * @param FabricImporterDefinitionInterface $definition
     * @param string                            $mainTableAlias
     *
     * @return string
     */
    private function getFields(FabricImporterDefinitionInterface $definition, string $mainTableAlias): string
    {
        //main table fields
        $fields = [
            ...array_keys($definition->getIdentifierMapping()),
            ...array_keys($definition->getFieldNameMapping())
        ];
        foreach ($fields as &$field) {
            $field = "$mainTableAlias.$field AS $field";
        }

        //joined fields
        foreach ($definition->getTableJoins() as $join) {
            $joinTableAlias = $join->getTableAlias();
            foreach ($join->getSelects() as $select) {
                $joinField = $select->getField();
                $fieldAlias = $select->getTargetField();
                $fields[] = "$joinTableAlias.$joinField AS $fieldAlias";
            }
        }

        return implode(', ', $fields);
    }
}
