<?php

namespace Fastbolt\FabricImporter;

use Fastbolt\FabricImporter\Exceptions\ImporterDefinitionNotFoundException;
use Fastbolt\FabricImporter\Exceptions\ImporterDependencyException;
use Fastbolt\FabricImporter\Exceptions\NoDataReceivedException;
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
    description: 'WIP tests import from Fabric'
)]
class ImportFromDWHCommand extends Command
{
    public function __construct(
        private readonly FabricImporterManager $importManager
    )
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::OPTIONAL, 'Type to import', '')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Development mode');
    }

    private function formatErrors(?array $errors, ?string $default = null): string
    {
        $message = "SQL Error - Error information:\n";

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $message .= '\nSQLSTATE: '. $error['SQLSTATE'];
                $message .= '\nCode: '. $error['code'];
                $message .= '\nMessage: '. $error['message'];
            }
        } else {
            $message .= $default ?? "No information\n";
        }

        return $message;
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $type = $input->getArgument('type');
        $isDev = $input->getOption('dev');

        try {
            $bar = new ProgressBar($output);
            $bar->setRedrawFrequency(100);

            $importConfig = new ImportConfiguration(
                $type,
                $isDev
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

            [$headers, $rows] = $this->getResultTable($results);

            $io->newLine();
            $io->table(
                $headers,
                $rows
            );
        } catch (
            ImporterDefinitionNotFoundException|ImporterDependencyException $exception
        ) {
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
     * @return array
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

        return [$headers, $rows];
    }
}
