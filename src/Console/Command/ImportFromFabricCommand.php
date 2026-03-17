<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Console\Command;

use Doctrine\DBAL\Exception;
use Fastbolt\FabricImporter\Exceptions\CircularDependencyException;
use Fastbolt\FabricImporter\Exceptions\ImporterDefinitionNotFoundException;
use Fastbolt\FabricImporter\Exceptions\ImporterDependencyException;
use Fastbolt\FabricImporter\Exceptions\NoDataReceivedException;
use Fastbolt\FabricImporter\FabricImporterManager;
use Fastbolt\FabricImporter\ImportDependencyManager;
use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinitionInterface;
use Fastbolt\FabricImporter\Repository\FabricSyncRepository;
use Fastbolt\FabricImporter\Types\ImportConfiguration;
use Fastbolt\FabricImporter\Types\ImportResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'fabric-importer:import',
    description: 'Imports table data from fabric.'
)]
class ImportFromFabricCommand extends Command
{
    /**
     * @param FabricImporterManager   $importManager
     * @param FabricSyncRepository    $syncRepository
     * @param ImportDependencyManager $dependencyManager
     * @param int                     $entryLimit
     */
    public function __construct(
        private readonly FabricImporterManager $importManager,
        private readonly FabricSyncRepository $syncRepository,
        private readonly ImportDependencyManager $dependencyManager,
        private int $entryLimit
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    public function configure(): void
    {
        $this
            ->addArgument(
                'type',
                InputArgument::OPTIONAL,
                'The import which you want to execute.',
                ''
            )
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Development mode')
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Request all data, regardless of the date of the last update'
            )
            ->addOption('all-types', 'a', InputOption::VALUE_NONE, 'Execute all import types, one after another.');
    }

    /**
     * @param OutputInterface     $output
     * @param ImportConfiguration $importConfig
     * @param SymfonyStyle        $io
     * @param bool                $isDev
     *
     * @return void
     * @throws Exception
     * @throws ImporterDependencyException
     */
    private function executeSingle(
        OutputInterface $output,
        ImportConfiguration $importConfig,
        SymfonyStyle $io,
        bool $isDev
    ): void {
        $bar = new ProgressBar($output);
        $bar->setRedrawFrequency(100);

        $results = $this->importManager->import(
            $importConfig,
            static function (int $steps = 1) use ($bar) {
                $bar->advance($steps);

                return true;
            },
            static function (Throwable $exception) use ($io, $isDev) {
                if ($isDev) {
                    dump($exception->getMessage());
                }
                if ($exception instanceof ImporterDefinitionNotFoundException) {
                    throw $exception;
                }

                if ($exception instanceof NoDataReceivedException) {
                    $io->warning($exception->getMessage());

                    return true;
                }

                $io->error($exception->getMessage());

                return false;
            },
            static function (Throwable $exception) use ($io, $isDev) {
                if ($isDev) {
                    dump($exception);
                }
                $io->warning($exception->getMessage());
            }
        );
        $bar->finish();

        $this->syncRepository->reduceEntriesToLimit($this->entryLimit);

        $table = $this->getResultTable($results);

        $io->newLine();
        $io->table(
            $table['headers'],
            $table['rows']
        );
        $io->newLine();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Throwable
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $type */
        if (empty($type = $input->getArgument('type'))) {
            return $this->showAvailableImportDefinitions($io);
        }
        $isDev      = (bool)$input->getOption('dev');
        $isFullSync = (bool)$input->getOption('full');
        $isAllTypes = (bool)$input->getOption('all');

        try {
            $types = $isAllTypes
                ? $this->dependencyManager->getNamesInDependencyAwareOrder()
                : [$type];
        } catch (CircularDependencyException $circularDependencyException) {
            $io->newLine(2);
            $io->error($circularDependencyException->getMessage());

            return Command::FAILURE;
        }

        if (count($types) > 1) {
            $io->info(sprintf('Importing all types in the following order: %s', implode(', ', $types)));
        }

        foreach ($types as $type) {
            $io->title(sprintf('Executing import for type %s', $type));

            $importConfig = new ImportConfiguration(
                $type,
                $isDev,
                $isFullSync,
                $isAllTypes
            );

            try {
                $this->executeSingle($output, $importConfig, $io, $isDev);
            } catch (ImporterDefinitionNotFoundException | ImporterDependencyException $exception) {
                $io->newLine(2);
                $io->error($exception->getMessage());
                $io->newLine();
                $this->showAvailableImportDefinitions($io);

                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param ImportResult[] $results
     *
     * @return array{
     *     headers: array<int, string>,
     *     rows: array<int<0, max>,
     * array{string, int, int}>
     * }
     */
    private function getResultTable(array $results): array
    {
        $headers = ['Importer', 'Successful', 'Failed'];

        $rows = [];
        foreach ($results as $result) {
            $rows[] = [
                $result->getDefinition()->getName(),
                $result->getSuccess(),
                $result->getErrors(),
            ];
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @param SymfonyStyle $io
     *
     * @return int
     */
    private function showAvailableImportDefinitions(SymfonyStyle $io): int
    {
        $lastRuns = $this->syncRepository->findLatestForAllTypes();
        $io->newLine();
        $io->error('Please add `type` argument to specify which import you want to execute. Available importers:');
        $io->table(
            ['Name', 'Description', 'Last ran', 'Dependencies'],
            array_map(
                static function (FabricImporterDefinitionInterface $def) use ($lastRuns) {
                    $lastRun = $lastRuns[$def->getName()] ?? null;

                    return [
                        $def->getName(),
                        $def->getDescription(),
                        $lastRun ? $lastRun?->format('Y-m-d H:i:s') : null,
                        implode(',', $def->getImportDependencies()),
                    ];
                },
                iterator_to_array($this->importManager->getImporterDefinitions())
            )
        );
        $io->newLine();

        return Command::FAILURE;
    }
}
