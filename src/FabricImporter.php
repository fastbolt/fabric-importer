<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Fastbolt\FabricImporter\Exceptions\DataModifierException;
use Fastbolt\FabricImporter\Exceptions\FieldConverterException;
use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinitionInterface;
use Fastbolt\FabricImporter\Providers\SaveQueryProvider;
use Fastbolt\FabricImporter\Types\ImportConfiguration;
use Fastbolt\FabricImporter\Types\ImportResult;
use Throwable;

/**
 * Possible issues:
 *  - The targetField in the FabricJoinSelect entity is also used as alias in the select, which could(?) cause problems
 */

readonly class FabricImporter
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param SaveQueryProvider      $queryProvider
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SaveQueryProvider $queryProvider
    ) {
    }

    /**
     * @param FabricImporterDefinitionInterface $definition
     * @param array<int, array<string, mixed>>  $data
     * @param ImportConfiguration               $importConfig
     * @param ImportResult                      $importResult
     * @param callable                          $statusCallback
     * @param callable                          $errorCallback
     * @param callable                          $warningCallback
     *
     * @return ImportResult
     */
    public function import(
        FabricImporterDefinitionInterface $definition,
        array $data,
        ImportConfiguration $importConfig,
        ImportResult $importResult,
        callable $statusCallback,
        callable $errorCallback,
        callable $warningCallback
    ): ImportResult {
        $rollbackSuccess = $importResult->getSuccess();
        $flushInterval = $definition->getFlushInterval();

        try {
            //data formatting
            if ($importConfig->isDevMode()) {
                foreach ($data as &$i) {
                    $removedFields = $this->reduceItemToImportedFields($definition, $i);
                }
            }

            $this->escapeData($data);
            $this->applyModifierFunction($definition, $data);
            $this->applyFieldConverters($definition, $data, $importResult, $warningCallback);
            $this->applyDefaultValues($definition, $data);

            $conn = $this->entityManager->getConnection();
            $conn->beginTransaction();
            $counter = 0;
            foreach ($data as $item) {
                $counter++;
                $queryObj = $this->queryProvider->getInsertUpdateQuery($definition, $item);
                $stmt    = $conn->prepare($queryObj->getQuery());
                $stmt->executeQuery($queryObj->getParameters());

                if ($counter >= $flushInterval) {
                    $conn->commit();
                    $conn->beginTransaction();
                    $counter = 0;
                }

                $importResult->increasesuccess();
                $statusCallback();
            }

            $conn->commit();
        } catch (Throwable $exception) {
            $importResult
                ->setSuccess($rollbackSuccess)
                ->increaseErrors(count($data));
            $errorCallback($exception);
        }

        return $importResult;
    }

    /**
     * @param FabricImporterDefinitionInterface $definition
     * @param array<int, array<string, mixed>>  $data
     *
     * @return void
     * @throws DataModifierException
     */
    private function applyModifierFunction(
        FabricImporterDefinitionInterface $definition,
        array &$data
    ): void {
        foreach ($data as &$item) {
            try {
                $item = $definition->modifyItem($item);
            } catch (Throwable $e) {
                throw new DataModifierException($definition->getName(), $e->getMessage());
            }
        }
    }

    /**
     * @param FabricImporterDefinitionInterface $definition
     * @param array<int, array<string,mixed>> $items
     * @param ImportResult                      $importResult
     * @param callable                          $warningCallback
     *
     * @return void
     * @throws FieldConverterException
     */
    private function applyFieldConverters(
        FabricImporterDefinitionInterface $definition,
        array &$items,
        ImportResult $importResult,
        callable $warningCallback
    ): void {
        $converters = $definition->getFieldConverters();

        try {
            foreach ($converters as $extField => $conv) {
                foreach ($items as $i => &$item) {
                    if (!array_key_exists($extField, $item)) {
                        throw new Exception("Converter found for field '$extField', but this field does not exist in the received data. Use the fields of the incoming data for converter names.");
                    }

                    try {
                        $item[$extField] = $conv($item[$extField], $item);
                    } catch (Throwable $e) {
                        $importResult->increaseErrors();
                        $warningCallback(
                            new FieldConverterException(
                                $definition->getName(),
                                $extField,
                                $e->getMessage()
                            )
                        );
                        unset($items[$i]);
                    }
                }
            }
        } catch (Throwable $e) {
            throw new FieldConverterException($definition->getName(), $extField, $e->getMessage());
        }
    }

    /**
     * @param FabricImporterDefinitionInterface $definition
     * @param array<int, array<string,mixed>>    $data
     *
     * @return void
     */
    private function applyDefaultValues(
        FabricImporterDefinitionInterface $definition,
        array &$data
    ): void {
        foreach ($definition->getDefaultValuesForUpdate() as $key => $default) {
            foreach ($data as &$item) {
                if (!array_key_exists($key, $item)) {
                    $item[$key] = $default;
                }
            }
        }
    }

    /**
     * Removes the fields from imported items that are not needed for the import
     *
     * @param FabricImporterDefinitionInterface $definition
     * @param array<string, mixed>              $item
     *
     * @return array<int, string>
     */
    private function reduceItemToImportedFields(
        FabricImporterDefinitionInterface $definition,
        array &$item
    ): array {
        $removedFields = [];
        $joinFields = $definition->getJoinedFields();

        foreach ($item as $extField => $field) {
            if (array_key_exists($extField, $definition->getFieldNameMapping())) {
                continue;
            }

            if (array_key_exists($extField, $definition->getIdentifierMapping())) {
                continue;
            }

            if (in_array($extField, $joinFields)) {
                continue;
            }

            $removedFields[] = $extField;
            unset($item[$extField]);
        }

        return $removedFields;
    }

    /**
     * Escapes all strings in $data for sql safety
     *
     * @param array<int, array<string, mixed>> $data
     *
     * @return void
     */
    private function escapeData(array &$data): void
    {
        foreach ($data as &$item) {
            foreach ($item as $key => $value) {
                if (is_string($value)) {
                    $item[$key] = addslashes($value);
                }
            }
        }
    }
}
