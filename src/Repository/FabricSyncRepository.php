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
        $sync = $this->findOneBy(['type' => $type]);

        return $sync?->getLoadedAt() ?? null;
    }

    /**
     * @return FabricSync[]
     */
    public function findLatestForAllTypes(): array
    {
        /** @var FabricSync[] $syncs */
        $syncs = $this->createQueryBuilder('s')
                      ->getQuery()
                      ->getResult();

        /** @var FabricSync[] $latest */
        $latest = [];
        foreach ($syncs as $sync) {
            if (array_key_exists($sync->getType() ?? "", $latest)) {
                if ($sync->getLoadedAt() > $latest[$sync->getType()]->getLoadedAt()) {
                    continue;
                }
            }
            $latest[$sync->getType()] = $sync;
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
        $all = $this->findBy([], ['loaded_at' => 'ASC']);
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
