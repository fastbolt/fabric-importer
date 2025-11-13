<?php

namespace Fastbolt\FabricImporter\ImporterDefinitions;

use Fastbolt\FabricImporter\Types\FabricTableJoin;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('fastbolt.fabric_importer')]
interface FabricImporterDefinitionInterface
{
    /**
     * Name displayed in console / used to execute command.
     * Should not contain white spaces and special characters.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * The database and table (database.table) where the data is imported from
     *
     * @return string
     */
    public function getSourceTable(): string;

    /**
     * The name of the table the data will be saved to
     *
     * @return string
     */
    public function getTargetTable(): string;

    /**
     * Description displayed in "Available import types" overview.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Returns list of mappings for differing field names between source and target.
     * Key is the external name, value is the current project's name. Must not include identifiers.
     * Example:
     *      ['name1' => 'lastname']
     *
     * @return array<string, string>
     */
    public function getFieldNameMapping(): array;

    /**
     * List of columns used as doctrine identifier, for loading existing items from the database.
     * You will only need to overwrite this, if you have a foreign key as identifier.
     *
     * @return array<int, string>
     */
    public function getIdentifierColumns(): array;

    /**
     * Must return all identifiers as extName => localName.
     * Returns list of mappings for differing identifier names between source and target.
     *
     * Example:
     *      ['extPropName' => 'ourPropName']
     *
     * @return array<string, string>
     */
    public function getIdentifierMapping(): array;

    /**
     * Get list of converters (callables) per field. Callable will recieve the current field value and the item and must
     * return the correct value / type for the field.
     *
     * @return array<string, callable>
     */
    public function getFieldConverters(): array;

    /**
     * Rows requested from the Server at once (limit).
     *
     * @return int
     */
    public function getDataBatchSize(): int;

    /**
     * Interval - Every n'th row will flush the entity manager. Returns the data-batch-size value by default.
     *
     * @return int
     */
    public function getFlushInterval(): int;

    /**
     * Option to disable throwing an exception on unknown fields.
     *
     * @return bool
     */
    public function isThrowExceptionOnUnknownField(): bool;

    /**
     * The join statement applied to the request to the dwh. Overwrite in child classes.
     *
     * @return FabricTableJoin[]
     */
    public function getTableJoinsDefinitions(): array;

    /**
     * Internal method to get the instantiated join objects. Do not overwrite.
     *
     * @return FabricTableJoin[]
     */
    function getTableJoins(): array;

    /**
     * Returns the aliased name of all fields added by the join
     *
     * @return string[]
     */
    function getJoinedFields(): array;

    /**
     * Returns a fieldName => value array of default values, that will be added to the UPDATE statement.
     *
     * @return array
     */
    function getDefaultValuesForUpdate(): array;

    /**
     * Returns a fieldName => value array of default values, that will be added to the INSERT statement.
     *
     * @return array
     */
    function getDefaultValuesForInsert(): array;

    /**
     * If false, only Inserts will be attempted.
     */
    function getAllowUpdate(): bool;

    /**
     * TODO we could try to make the importers with dependencies only import items that have
     *  changed before the tables this entity depends on has changed so we never get orphaned data
     *
     * Returns the names of all importers that must run before this one because it is dependent on data from those imports.
     * Importers must have run in the last hour.
     *
     * @return array<int, string>
     */
    function getImportDependencies(): array;

    /**
     * Returns additional filters applied to the query, like "WHERE <filter_content>"
     *
     * @return array<int, string>
     */
    public function getImportFilters(): array;

    /**
     * A method which is applied to the whole received item before anything else. Must return the whole item.
     *
     * @param array $item
     *
     * @return array
     */
    public function modifyItem(array $item): array;
}