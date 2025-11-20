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
     * @param array<string, string|int|float|null>    $item
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

        //parameters are reused in the ON DUPLICATE KEY UPDATE part, where they rely on being equal to the fieldname
        foreach ($columns as $column) {
            $placeholder = ':' . $column;
            $placeholders[] = $placeholder;
            $parameters[$column] = $data[$column];
        }

        $columnsStr = implode(',', $columns);
        $placeholdersStr = implode(',', $placeholders);

        $query = "INSERT INTO $table ($columnsStr) VALUES ($placeholdersStr)";
        $queryObj->setQuery($query);
        $queryObj->setParameters($parameters);

        return $queryObj;
    }

    /**
     * @param FabricImporterDefinitionInterface $definition
     * @param array<string, string|int|float|null>    $item
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
        $query = $insert . ' ON DUPLICATE KEY UPDATE ';
        foreach ($params as $placeholder => $value) {
            if ($placeholder !== null && in_array($placeholder, $definition->getUpdatableFields())) {
                $query .= "$placeholder=:$placeholder,";
            }
        }
        $query = rtrim($query, ',');

        $queryObj->setQuery($query);

        return $queryObj;
    }
}
