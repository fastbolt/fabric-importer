<?php

namespace Fastbolt\FabricImporter\Types;

class Query
{
    private string $query = "";

    /**
     * @var array<string, string|int|float|null>
     */
    private array $parameters = [];

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @param string $query
     *
     * @return Query
     */
    public function setQuery(string $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return array<string, string|int|float|null>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array<string, string|int|float|null> $parameters
     *
     * @return Query
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @param array<string, string|int|float|null> $parameters
     *
     * @return void
     */
    public function addParameters(array $parameters): void
    {
        $this->parameters = [...$this->parameters, ...$parameters];
    }
}
