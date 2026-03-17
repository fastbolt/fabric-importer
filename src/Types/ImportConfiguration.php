<?php

namespace Fastbolt\FabricImporter\Types;

readonly class ImportConfiguration
{
    /**
     * @param string $type
     * @param bool   $isDev
     * @param bool   $isFullSync
     */
    public function __construct(
        private string $type,
        private bool $isDev,
        private bool $isFullSync,
        private bool $isAllTypes
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
    public function isFullSync(): bool
    {
        return $this->isFullSync;
    }

    /**
     * @return bool
     */
    public function isAllTypes(): bool
    {
        return $this->isAllTypes;
    }
}
