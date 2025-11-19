<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Providers;

use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinitionInterface;
use Fastbolt\FabricImporter\Types\Query;
use OutOfRangeException;

/**
 * Builds SQL Insert/Update queries
 */
class SaveQueryProvider
{
    /**
     * @param FabricImporterDefinitionInterface $definition
     * @param array<string, int|string|null>         $item
     *
     * @return string
     */
    public function getUpdateQuery(FabricImporterDefinitionInterface $definition, array $item): string
    {
        $table = $definition->getTargetTable();
        $set   = $this->getSetter($definition, $item);
        $where = $this->getWhere($definition, $item);

        return "UPDATE $table SET $set $where;";
    }

    /**
     * @param FabricImporterDefinitionInterface $definition
     * @param array<string, int|string|null>         $item
     *
     * @return string
     */
    private function getSetter(FabricImporterDefinitionInterface $definition, array $item): string
    {
        $fieldMapping = $definition->getFieldNameMapping();
        $joinedFields = $definition->getJoinedFields();

        $setter = '';
        foreach ($item as $prop => $value) {
            $value = $this->formatValueByType($value);

            if (
                in_array($prop, $joinedFields)
                || array_key_exists($prop, $definition->getDefaultValuesForUpdate())
            ) {
                $setter .= "$prop = $value, ";
                continue;
            }

            //skipping identifiers (we dont want to update those) and fields, which are not in field-mapping
            $field = $fieldMapping[$prop] ?? null;
            if (!$field) {
                continue;
            }

            //standard field
            $setter .= "$field = $value, ";
        }
        //cut last comma
        $setter = substr($setter, 0, strlen($setter) - 2);

        return $setter;
    }

    /**
     * @param FabricImporterDefinitionInterface $definition
     * @param array<string, int|string|null> $item
     *
     * @return string
     */
    private function getWhere(FabricImporterDefinitionInterface $definition, array $item): string
    {
        $whereQuery = '';
        $isFirst    = true;
        foreach ($definition->getIdentifierMapping() as $extName => $fieldName) {
            $where = $isFirst ? 'WHERE' : ' AND';

            if (!array_key_exists($extName, $item)) {
                $className = $definition::class;
                throw new OutOfRangeException("Identifier '$extName' not found in imported data for $className");
            }

            $whereQuery .= "$where $fieldName = \"" . $item[$extName] . "\"";

            $isFirst = false;
        }

        return $whereQuery;
    }

    /**
     * @param FabricImporterDefinitionInterface $definition
     * @param array<string, int|string|null>         $item
     *
     * @return Query
     */
    public function getInsertQuery(FabricImporterDefinitionInterface $definition, array $item): Query
    {
        $queryObj = new Query();

        $table             = $definition->getTargetTable();
        $fieldMapping      = $definition->getFieldNameMapping();
        $identifierMapping = $definition->getIdentifierMapping();

        foreach ($fieldMapping as $extName => $fieldName) {
            if (!array_key_exists($extName, $item)) {
                $className = $definition::class;
                throw new OutOfRangeException("Identifier '$extName' not found in imported data for $className");
            }
        }

        $data = [];
        foreach ($item as $extField => $value) {
            // If a join creates a field with the same name as an identifier or normal field,
            // the join value will overwrite the identifier value as it appears later in the item array
            $field = $fieldMapping[$extField] ?? $identifierMapping[$extField] ?? null;
            if ($field) {
                $data[$field] = $value;
            }
        }

        foreach ($definition->getJoinedFields() as $joinedField) {
            $data[$joinedField] = $item[$joinedField];
        }

        foreach ($definition->getDefaultValuesForInsert() as $key => $value) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = $value;
            }
        }

        //Joins are already included in the item, so we don't need to add them separately here

        foreach ($data as &$val) {
            $val = $this->formatValueByType($val);
        }

        $columns = array_keys($data);
        $values  = array_values($data);

        $columns = implode(', ', $columns);
        $values  = implode(', ', $values);

        $queryObj->setParameters([
            'table' => $table,
            'columns' => $columns,
            'values' => $values
                                 ]);

        $query = "INSERT INTO :table (:columns) VALUES(:values)";
        $queryObj->setQuery($query);

        return $queryObj;
    }

    /**
     * @param int|string|null $value
     *
     * @return int|string
     */
    private function formatValueByType(int|string|null $value): int|string
    {
        if ($value === null) {
            return '(NULL)';
        } elseif (is_string($value)) {
            return "\"$value\"";
        }

        return $value;
    }
}
