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
//    /**
//     * @param FabricImporterDefinitionInterface $definition
//     * @param array<string, int|string|null>         $item
//     *
//     * @return Query
//     */
//    public function getUpdateQuery(FabricImporterDefinitionInterface $definition, array $item): Query
//    {
//        $queryObj = new Query();
//        $queryObj->setParameters([
//            'table' => $definition->getTargetTable()
//        ]);
//
//        $set   = $this->getSetter($definition, $item, $queryObj);
//        $where = $this->getWhere($definition, $item, $queryObj);
//
//        $query = "UPDATE :table SET $set $where;";
//        $queryObj->setQuery($query);
//
//
//        return $queryObj;
//    }

    /**
     * @param FabricImporterDefinitionInterface $definition
     * @param array<string, int|string|null>         $item
     *
     * @return string
     */
    private function getSetter(FabricImporterDefinitionInterface $definition, array $item, Query $query): string
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
                $placeholder = ':' . $prop;
                $setter .= "$prop = $placeholder, ";
                $query->addParameters([$placeholder => $value]);
                continue;
            }

            //skipping identifiers (we don't want to update those) and fields, which are not in field-mapping
            $field = $fieldMapping[$prop] ?? null;
            if (!$field) {
                continue;
            }

            //standard field, prefix placeholder because it might be that we use the same in WHERE
            $placeholder = ':s_' . $field;
            $setter .= "$field = $placeholder, ";
            $query->addParameters([$placeholder => $value]);
        }

        //cut last comma
        return substr($setter, 0, strlen($setter) - 2);
    }

    /**
     * @param FabricImporterDefinitionInterface $definition
     * @param array<string, int|string|null>    $item
     * @param Query                             $query
     *
     * @return string
     */
    private function getWhere(FabricImporterDefinitionInterface $definition, array $item, Query $query): string
    {
        $whereQuery = '';
        $isFirst    = true;
        foreach ($definition->getIdentifierMapping() as $extName => $fieldName) {
            $where = $isFirst ? 'WHERE' : ' AND';

            if (!array_key_exists($extName, $item)) {
                $className = $definition::class;
                throw new OutOfRangeException("Identifier '$extName' not found in imported data for $className");
            }

            //prefix placeholder because it might be that we use the same in SET
            $placeholder = ':w_' . $fieldName;
            $whereQuery .= "$where $fieldName = $placeholder";
            $query->addParameters([$placeholder => $item[$extName]]);

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

        $columns = array_keys($data);
        $placeholders = [];
        $parameters = [];

        //placeholders are reused in the ON DUPLICATE KEY UPDATE part, where they rely on being equal to the fieldname
        foreach ($columns as $column) {
            $placeholder = ':' . $column;
            $placeholders[] = $placeholder;
            $parameters[$placeholder] = $data[$column];
        }

        $columnsStr = implode(', ', $columns);
        $placeholdersStr = implode(', ', $placeholders);

        $query = "INSERT INTO $table ($columnsStr) VALUES ($placeholdersStr)";
        $queryObj->setQuery($query);
        $queryObj->setParameters($parameters);

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

    /**
     * @param FabricImporterDefinitionInterface $definition
     * @param array<string, string|int|null>                             $item
     *
     * @return Query
     */
    public function getInsertUpdateQuery(FabricImporterDefinitionInterface $definition, array $item): Query
    {
        $queryObj = $this->getInsertQuery($definition, $item);
        if ($definition->getAllowUpdate() === false) {
            return $queryObj;
        }

        $insert = $queryObj->getQuery();
        $params = $queryObj->getParameters();

        //placeholders = field names, so no new mapping needed
        $query = $insert . " ON DUPLICATE KEY UPDATE ";
        foreach ($params as $placeholder) {
            if ($placeholder !== null && array_key_exists($placeholder, $definition->getFieldNameMapping())) {
                $query .= "$placeholder = :$placeholder,";
            }
        }
        $query = rtrim($query, ',');

        $queryObj->setQuery($query);

        return $queryObj;
    }
}
