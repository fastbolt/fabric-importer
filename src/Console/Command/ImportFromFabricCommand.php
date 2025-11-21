<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Console\Command;

use Doctrine\DBAL\Exception;
use Fastbolt\FabricImporter\Exceptions\ImporterDefinitionNotFoundException;
use Fastbolt\FabricImporter\Exceptions\ImporterDependencyException;
use Fastbolt\FabricImporter\Exceptions\NoDataReceivedException;
use Fastbolt\FabricImporter\FabricImporterManager;
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
     * @param FabricImporterManager $importManager
     */
    public function __construct(
        private readonly FabricImporterManager $importManager,
        private readonly FabricSyncRepository $syncRepository
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    public function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::OPTIONAL, 'The import which you want to execute', '')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Development mode')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Request all data, regardless of the date of the last update');
    }

    /**
     * @param array<string, mixed>|null  $errors
     * @param string|null $default
     *
     * @return string
     */
//    private function formatErrors(?array $errors, ?string $default = null): string
//    {
//        $message = "SQL Error - Error information:\n";
//
//        if (!empty($errors)) {
//            foreach ($errors as $error) {
//                $message .= '\nSQLSTATE: ' . $error['SQLSTATE'];
//                $message .= '\nCode: ' . $error['code'];
//                $message .= '\nMessage: ' . $error['message'];
//            }
//        } else {
//            $message .= $default ?? "No information\n";
//        }
//
//        return $message;
//    }

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
        $type  = $input->getArgument('type');
        $isDev = (bool) $input->getOption('dev');
        $isAll = (bool) $input->getOption('all');

        try {
            $bar = new ProgressBar($output);
            $bar->setRedrawFrequency(100);

            $importConfig = new ImportConfiguration(
                $type,
                $isDev,
                $isAll,
                100
            );

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
                           dump($exception->getMessage());
                       }
                    $io->warning($exception);
                }
            );
            $bar->finish();

            $this->syncRepository->reduceEntriesToLimit($importConfig->getEntryLimit());

            $table = $this->getResultTable($results);

            $io->newLine();
            $io->table(
                $table['headers'],
                $table['rows']
            );
        } catch (ImporterDefinitionNotFoundException | ImporterDependencyException $exception) {
            $io->newLine(2);
            $io->error($exception->getMessage());

//            $this->helpRenderer->render($io);

            return Command::FAILURE;
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
                $result->getErrors()
            ];
        }

        return ['headers' => $headers, 'rows' => $rows];
    }
}
