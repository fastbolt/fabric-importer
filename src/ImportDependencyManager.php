<?php

namespace Fastbolt\FabricImporter;

use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinitionInterface;
use LogicException;

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
     * @return string[]
     */
    public function getNamesInDependencyAwareOrder(): array
    {
        $dependencies = $this->getDependencies();

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

        // Deterministic order for input dependencies, to ease testing
        ksort($result);

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
            throw new LogicException(
                'Recursion detected in importer dependencies. Please check the importer definitions for circular dependencies.'
            );
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
}
