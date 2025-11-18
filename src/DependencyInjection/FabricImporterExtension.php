<?php

namespace Fastbolt\FabricImporter\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class FabricImporterExtension extends Extension
{
    /**
     * @param array           $configs
     * @param ContainerBuilder $container
     *
     * @return void
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yaml');
        $loader->load('doctrine.yaml');
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return void
     */
    public function prepend(ContainerBuilder $container): void
    {
        // DBAL config hinzufügen
        $container->prependExtensionConfig(
            'doctrine',
            [
                'dbal' => [
                    'connections' => [
                        'fabric' => [
                            'driver'  => 'sqlsrv',
                            'url'     => '%env(resolve:DATABASE_FABRIC_URL)%',
                            'options' => [
                                'CharacterSet' => 'UTF-8',
                            ],
                        ],
                    ],
                ],
                'orm'  => [
                    'entity_managers' => [
                        'default' => [
                            'mappings' => [
                                'FabricImporter' => [
                                    'is_bundle' => false,
                                    'type'      => 'attribute',
                                    'dir'       => __DIR__ . '/../Entity',
                                    'prefix'    => 'Fastbolt\\FabricImporter\\Entity',
                                    'alias'     => 'FabricImporter',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }
}
