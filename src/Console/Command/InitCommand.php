<?php

namespace Fastbolt\FabricImporter\Console\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'fabric-importer:init',
    description: 'Set up for the use of the importer command.'
)]
class InitCommand extends Command
{
    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $conn = $this->entityManager->getConnection();
        $this->createSyncTable($conn);

        return 0;
    }

    /**
     * @param Connection $conn
     *
     * @return void
     * @throws Exception
     */
    private function createSyncTable(Connection $conn): void
    {
        $query = "CREATE TABLE `fabric_syncs` (
          `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
          `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
          `loaded_at` datetime NOT NULL,
          `exec_time_seconds` int NOT NULL,
          `successes` int NOT NULL,
          `failures` int NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $conn->prepare($query)->executeStatement();
    }
}
