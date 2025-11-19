<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter;

use Closure;
use DateTime;
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
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class FabricImporterManager
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
        #[AutowireIterator('fastbolt.fabric_importer')]
        private iterable $definitions = []
    ) {
    }

    /**
     * @param ImportConfiguration $importConfig
     * @param Closure             $statusCallback
     * @param Closure             $errorCallback
     * @param Closure             $warningCallback
     *
     * @return ImportResult[]
     * @throws ImporterDependencyException
     * @throws \Doctrine\DBAL\Exception
     */
    public function import(
        ImportConfiguration $importConfig,
        Closure $statusCallback,
        Closure $errorCallback,
        Closure $warningCallback
    ): array {
        $type = $importConfig->getType();
        if (!$type) {
            throw new Exception("Name of the import is required, a complete import is currently not supported.");
        }

        $found = false;
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

        $offset         = 0;
        $isFirstTry     = true;
        $syncDate       = new DateTime();
        $lastImportDate = $this->syncRepository->findLastImportDate($definition->getName());
        $connection     = $this->managerRegistry->getConnection('fabric');
        $importResult   = new ImportResult($definition);
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
                    $errorCallback(new Exception("Received data is empty for import of '$type'"));
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

        if ($importConfig->isDevMode() === false) {
            $this->saveSyncEntry($type, $syncDate, $importResult);
        }

        return [$importResult];
    }

    /**
     * @param string       $type
     * @param DateTime     $startDate
     * @param ImportResult $importResult
     *
     * @return void
     */
    private function saveSyncEntry(string $type, DateTime $startDate, ImportResult $importResult): void
    {
        $syncEntry = $this->syncRepository->find($type);
        if (!$syncEntry) {
            $syncEntry = new FabricSync();
            $syncEntry->setType($type);
        }

        $timePassed = (time() - $startDate->getTimestamp());
        $syncEntry
            ->setLoadedAt($startDate)
            ->setSuccesses($importResult->getSuccess())
            ->setFailures($importResult->getErrors())
            ->setExecTimeSeconds($timePassed)
        ;
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
        $syncs        = $this->syncRepository->findAll();
        foreach ($dependencies as $dep) {
            foreach ($syncs as $sync) {
                if ($sync->getType() !== $dep) {
                    continue;
                }

                $threshold = new DateTime('-1 hour');
                if ($sync->getLoadedAt() && $sync->getLoadedAt() > $threshold) {
                    continue 2;
                }

                throw new ImporterDependencyException(
                    $definition->getName(),
                    $sync->getType(),
                    $sync->getLoadedAt()
                );
            }
        }
    }
}
