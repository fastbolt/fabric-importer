<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Repository;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Fastbolt\FabricImporter\Entity\FabricSync;

/**
 * @extends ServiceEntityRepository<FabricSync>
 */
class FabricSyncRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FabricSync::class);
    }

    /**
     * @param string $type
     *
     * @return DateTime|null
     */
    public function findLastImportDate(string $type): ?DateTime
    {
        $sync = $this->findOneBy(['type' => $type], ['loaded_at' => 'DESC']);

        return $sync?->getLoadedAt() ?? null;
    }

    /**
     * @return array<string, DateTime>
     */
    public function findLatestForAllTypes(): array
    {
        /** @var array<string, array{type:string, max_loaded_at:string}> $latest */
        $latest = $this->createQueryBuilder('s')
                       ->select('s.type, MAX(s.loaded_at) as max_loaded_at')
                       ->groupBy('s.type')
                       ->indexBy('s', 's.type')
                       ->getQuery()
                       ->getArrayResult();
        /** @var array<string, DateTime> $latest */
        foreach ($latest as $type => $item) {
            $latest[$type] = new DateTime($item['max_loaded_at']);
        }

        return $latest;
    }

    /**
     * @param int $entryLimit
     *
     * @return void
     */
    public function reduceEntriesToLimit(int $entryLimit): void
    {
        $all    = $this->findBy([], ['loaded_at' => 'ASC']);
        $excess = count($all) - $entryLimit;
        if ($excess <= 0) {
            return;
        }

        $toRemove = array_slice($all, 0, $excess);
        if ($toRemove === []) {
            return;
        }

        foreach ($toRemove as $sync) {
            $this->getEntityManager()->remove($sync);
        }

        $this->getEntityManager()->flush();
    }
}
