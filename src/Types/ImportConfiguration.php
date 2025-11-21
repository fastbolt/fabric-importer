<?php

namespace Fastbolt\FabricImporter\Types;

readonly class ImportConfiguration
{
    /**
     * @param string $type
     * @param bool   $isDev
     * @param bool   $isAll
     * @param int    $entryLimit    The number of rows of import-results kept in the database.
     */
    public function __construct(
        private string $type,
        private bool $isDev,
        private bool $isAll,
        private int $entryLimit
    ) {
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isDevMode(): bool
    {
        return $this->isDev;
    }

    /**
     * @return bool
     */
    public function isAllMode(): bool
    {
        return $this->isAll;
    }

    /**
     * @return int
     */
    public function getEntryLimit(): int
    {
        return $this->entryLimit;
    }
}
