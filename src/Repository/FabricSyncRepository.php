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
        $query = $this->createQueryBuilder('s')
                      ->select('s')
                      ->where('s.loadedAt = (SELECT MAX(sub.loadedAt) FROM ' . FabricSync::class . ' sub WHERE sub.type = s.type)')
                      ->groupBy('s.type')
                      ->getQuery();

        /** @var FabricSync[] $res */
        $res = $query->getResult();

        return $res;
    }

    /**
     * @param int $entryLimit
     *
     * @return void
     */
    public function reduceEntriesToLimit(int $entryLimit): void
    {
        /** @var FabricSync[] $all */
        $all = $this->createQueryBuilder('s')
                    ->orderBy('s.loaded_at', 'ASC')
                    ->getQuery()
                    ->getResult();

        $excess = count($all) -  $entryLimit;

        while ($excess > 0) {
            $sync = array_shift($all);
            if ($sync === null) {
                continue;
            }
            $this->getEntityManager()->remove($sync);
            $excess--;
        }

        $this->getEntityManager()->flush();
    }
}
