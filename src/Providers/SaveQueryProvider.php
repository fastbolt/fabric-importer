<?php

namespace Fastbolt\FabricImporter\Providers;

use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinitionInterface;
use OutOfRangeException;

/**
 * Builds SQL Insert/Update queries
 */
class SaveQueryProvider
{
    public function getUpdateQuery($definition, $item): string {
        $table = $definition->getTargetTable();
        $set   = $this->getSetter($definition, $item);
        $where = $this->getWhere($definition, $item);

        return "UPDATE $table SET $set $where;";
    }

    private function getSetter(FabricImporterDefinitionInterface $definition, $item): string
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
     * @param array<string, string>             $item
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

    public function getInsertQuery(FabricImporterDefinitionInterface $definition, array $item): string
    {
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

        return "INSERT INTO $table ($columns) VALUES($values)";
    }

    private function formatValueByType(mixed $value): mixed
    {
        if ($value === null) {
            return '(NULL)';
        } elseif (is_string($value)) {
            return "\"$value\"";
        }

        return $value;
    }
}
