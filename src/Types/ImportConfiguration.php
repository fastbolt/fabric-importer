<?php

namespace Fastbolt\FabricImporter\Types;

readonly class ImportConfiguration
{
    /**
     * @param string $type
     * @param bool   $isDev
     */
    public function __construct(
        private string $type,
        private bool $isDev
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
}
