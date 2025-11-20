<?php

namespace Fastbolt\FabricImporter\Types;

readonly class ImportConfiguration
{
    /**
     * @param string $type
     * @param bool   $isDev
     * @param bool   $isAll
     */
    public function __construct(
        private string $type,
        private bool $isDev,
        private bool $isAll
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
}
