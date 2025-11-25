<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter;

use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class FabricImporterBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     *
     * @return void
     */
    public function build(ContainerBuilder $container): void
    {
        $container
            ->registerForAutoconfiguration(FabricImporterDefinitionInterface::class)
            ->addTag('fastbolt.fabric_importer');
    }
}
