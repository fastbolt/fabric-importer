<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\Entity;

use Fastbolt\FabricImporter\Repository\FabricSyncRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FabricSyncRepository::class)]
#[ORM\Table(name: 'fabric_syncs')]
class FabricSync
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id = 0;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?string $type = null;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return void
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    #[ORM\Column]
    private ?DateTime $loaded_at = null;

    #[ORM\Column]
    private int $execTimeSeconds = 0;

    #[ORM\Column]
    private int $successes = 0;

    #[ORM\Column]
    private int $failures = 0;

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getLoadedAt(): ?DateTime
    {
        return $this->loaded_at;
    }

    /**
     * @param DateTime $loaded_at
     *
     * @return $this
     */
    public function setLoadedAt(DateTime $loaded_at): static
    {
        $this->loaded_at = $loaded_at;

        return $this;
    }

    /**
     * @return int
     */
    public function getExecTimeSeconds(): int
    {
        return $this->execTimeSeconds;
    }

    /**
     * @param int $execTimeSeconds
     *
     * @return void
     */
    public function setExecTimeSeconds(int $execTimeSeconds): void
    {
        $this->execTimeSeconds = $execTimeSeconds;
    }

    /**
     * @return int
     */
    public function getSuccesses(): int
    {
        return $this->successes;
    }

    /**
     * @param int $successes
     *
     * @return FabricSync
     */
    public function setSuccesses(int $successes): self
    {
        $this->successes = $successes;

        return $this;
    }

    /**
     * @return int
     */
    public function getFailures(): int
    {
        return $this->failures;
    }

    /**
     * @param int $failures
     *
     * @return FabricSync
     */
    public function setFailures(int $failures): self
    {
        $this->failures = $failures;

        return $this;
    }
}
