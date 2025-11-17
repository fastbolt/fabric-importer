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
use Fastbolt\FabricImporter\Entity\DwhSync;

/**
 * @extends ServiceEntityRepository<DwhSync>
 */
class DwhSyncRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DwhSync::class);
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
}
