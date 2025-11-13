<?php

namespace Fastbolt\FabricImporter\Types;

readonly class ImportConfiguration
{
    public function __construct(
        private string $type,
        private bool $isDev
    ) {
    }

    public function getType(): string {
        return $this->type;
    }

    public function isDevMode(): bool
    {
        return $this->isDev;
    }
}
