<?php

namespace Fastbolt\FabricImporter;

use Fastbolt\FabricImporter\Exceptions\CircularDependencyException;
use Fastbolt\FabricImporter\Exceptions\ImporterDefinitionNotFoundException;
use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinitionInterface;

readonly class ImportDependencyManager
{
    /**
     * @param iterable<FabricImporterDefinitionInterface> $definitions
     */
    public function __construct(
        private iterable $definitions = [],
    ) {
    }

    /**
     * @param string|null $type Type to check dependencies for. If null, all importers are resolved.
     *
     * @return string[]
     *
     * @throws CircularDependencyException
     */
    public function getNamesInDependencyAwareOrder(?string $type = null): array
    {
        $dependencies = $type
            ? $this->getDependenciesForType($type)
            : $this->getDependencies();

        // Deterministic order for input dependencies, to ease testing
        ksort($dependencies);

        return $this->resolveDependencies($dependencies);
    }

    /**
     * @return array<string, string[]> Array with definition names as keys and an array of the names of the definitions
     *                       they depend on as values
     */
    private function getDependencies(): array
    {
        $result = [];
        foreach ($this->definitions as $definition) {
            $result[$definition->getName()] = $definition->getImportDependencies();
        }

        return $result;
    }

    /**
     * @param array<string, string[]> $dependenciesToCheck
     * @param string[]                $namesOrdered
     *
     * @return string[]
     */
    private function resolveDependencies(array &$dependenciesToCheck, array &$namesOrdered = []): array
    {
        $removed = [];
        foreach ($dependenciesToCheck as $name => $itemDependencies) {
            if (!empty($itemDependencies)) {
                continue;
            }

            $namesOrdered[] = $name;
            $removed[]      = $name;
            unset($dependenciesToCheck[$name]);
        }

        // Erst Test implementieren - das sollte rekursive Abhängigkeiten verhindern
        if (empty($removed)) {
            throw new CircularDependencyException();
        }

        foreach ($dependenciesToCheck as $name => $itemDependencies) {
            foreach ($removed as $removedName) {
                if (false === ($key = array_search($removedName, $itemDependencies))) {
                    continue;
                }
                unset($dependenciesToCheck[$name][$key]);
            }
        }

        if (count($dependenciesToCheck)) {
            $this->resolveDependencies($dependenciesToCheck, $namesOrdered);
        }

        return $namesOrdered;
    }

    /**
     * @param string $type Type to get dependencies for
     *
     * @return array<string, string[]> Array with definition names as keys and an array of the names of the definitions
     *                       they depend on as values
     */
    private function getDependenciesForType(string $type, array &$dependencies = []): array
    {
        $definitionToCheck = $this->getDefinition($type);
        if (null === $definitionToCheck) {
            throw new ImporterDefinitionNotFoundException($type);
        }

        $dependencies[$type] = $dependenciesToCheck = $definitionToCheck->getImportDependencies();
        foreach ($dependenciesToCheck as $dependency) {
            if (isset($dependencies[$dependency])) {
                continue;
            }

            $this->getDependenciesForType($dependency, $dependencies);
        }

        return $dependencies;
    }

    /**
     * @param string $type
     *
     * @return FabricImporterDefinitionInterface|null
     */
    private function getDefinition(string $type): ?FabricImporterDefinitionInterface
    {
        /** @var array<FabricImporterDefinitionInterface> $definitionsFiltered */
        $definitionsFiltered = array_filter(
            iterator_to_array($this->definitions),
            static fn(FabricImporterDefinitionInterface $definition): bool => $definition->getName() === $type
        );

        return array_pop($definitionsFiltered);
    }
}
