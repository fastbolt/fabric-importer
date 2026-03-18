<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter;

use Closure;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Fastbolt\FabricImporter\Entity\FabricSync;
use Fastbolt\FabricImporter\Exceptions\ImporterDefinitionNotFoundException;
use Fastbolt\FabricImporter\Exceptions\ImporterDependencyException;
use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinitionInterface;
use Fastbolt\FabricImporter\Providers\ImportQueryProvider;
use Fastbolt\FabricImporter\Repository\FabricSyncRepository;
use Fastbolt\FabricImporter\Types\ImportConfiguration;
use Fastbolt\FabricImporter\Types\ImportResult;
use InvalidArgumentException;

readonly class FabricImporterManager
{
    /**
     * @param ManagerRegistry                             $managerRegistry
     * @param FabricImporter                              $importer
     * @param FabricSyncRepository                        $syncRepository
     * @param EntityManagerInterface                      $em
     * @param ImportQueryProvider                         $queryProvider
     * @param iterable<FabricImporterDefinitionInterface> $definitions
     */
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private FabricImporter $importer,
        private FabricSyncRepository $syncRepository,
        private EntityManagerInterface $em,
        private ImportQueryProvider $queryProvider,
        private string $dependencyMaxAge,
        private string $sourceTimeZone,
        private iterable $definitions = [],
    ) {
    }

    /**
     * @param ImportConfiguration $importConfig
     * @param Closure             $statusCallback
     * @param Closure             $errorCallback
     * @param Closure             $warningCallback
     *
     * @return ImportResult
     *
     * @throws ImporterDependencyException
     * @throws \Doctrine\DBAL\Exception
     */
    public function import(
        ImportConfiguration $importConfig,
        Closure $statusCallback,
        Closure $errorCallback,
        Closure $warningCallback
    ): ImportResult {
        $type = $importConfig->getType();
        if (!$type) {
            throw new InvalidArgumentException("Name of the import is required");
        }

        $found      = false;
        $definition = null;
        foreach ($this->definitions as $definition) {
            if ($type === $definition->getName()) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new ImporterDefinitionNotFoundException($type);
        }

        /** @var FabricImporterDefinitionInterface $definition */
        $this->checkForDependedImports($definition);

        $offset     = 0;
        $isFirstTry = true;
        $syncDate   = new DateTime();

        $lastImportDate = $this->syncRepository->findLastImportDate($definition->getName());
        if ($importConfig->isFullSync() === true) {
            $lastImportDate = null;
        }
        if (null !== $lastImportDate && !empty($this->sourceTimeZone)) {
            $lastImportDate->setTimezone(new DateTimeZone($this->sourceTimeZone));
        }

        $connection   = $this->managerRegistry->getConnection('fabric');
        $importResult = new ImportResult($definition);
        while (true) {
            ['query' => $query, 'parameters' => $parameters]
                = $this->queryProvider->buildQuery($definition, $offset, $lastImportDate);

            /** @var Connection $connection */
            /** @var string $query */
            /** @var array<string, mixed> $parameters */
            $importedData = $connection
                ->executeQuery($query, $parameters)
                ->fetchAllAssociative();

            if (!$importedData) {
                if ($isFirstTry) {
                    $warningCallback(
                        new Exception(
                            sprintf(
                                "Received data is empty for import of '%s', last import date was '%s'",
                                $type,
                                $lastImportDate ? $lastImportDate->format('Y-m-d H:i:s') : 'never'
                            )
                        )
                    );
                }
                break;
            }
            $isFirstTry = false;

            $this->importer->import(
                $definition,
                $importedData,
                $importConfig,
                $importResult,
                $statusCallback,
                $errorCallback,
                $warningCallback
            );
            $offset = $definition->getDataBatchSize() + $offset;
        }

        $this->saveSyncEntry($type, $syncDate, $importResult, $importConfig);

        return $importResult;
    }

    /**
     * @param string              $type
     * @param DateTime            $startDate
     * @param ImportResult        $importResult
     * @param ImportConfiguration $importConfig
     *
     * @return void
     */
    private function saveSyncEntry(
        string $type,
        DateTime $startDate,
        ImportResult $importResult,
        ImportConfiguration $importConfig
    ): void {
        if ($importConfig->isDevMode() === true) {
            return;
        }

        $timePassed = (time() - $startDate->getTimestamp());
        $syncEntry  = new FabricSync();
        $syncEntry
            ->setType($type)
            ->setLoadedAt($startDate)
            ->setSuccesses($importResult->getSuccess())
            ->setFailures($importResult->getErrors())
            ->setExecTimeSeconds($timePassed);
        $this->em->persist($syncEntry);
        $this->em->flush();
    }

    /**
     * @param FabricImporterDefinitionInterface $definition
     *
     * @return void
     * @throws ImporterDependencyException
     */
    private function checkForDependedImports(FabricImporterDefinitionInterface $definition): void
    {
        $dependencies = $definition->getImportDependencies();
        $syncs        = $this->syncRepository->findLatestForAllTypes();
        $threshold    = new DateTime('-' . $this->dependencyMaxAge);

        foreach ($dependencies as $dep) {
            if (null === ($lastSync = $syncs[$dep] ?? null)) {
                throw new ImporterDependencyException(
                    $definition->getName(),
                    $dep,
                    null
                );
            }

            if ($lastSync < $threshold) {
                throw new ImporterDependencyException(
                    $definition->getName(),
                    $dep,
                    $lastSync
                );
            }
        }
    }

    /**
     * @return iterable<FabricImporterDefinitionInterface>
     */
    public function getImporterDefinitions(): iterable
    {
        return $this->definitions;
    }
}
