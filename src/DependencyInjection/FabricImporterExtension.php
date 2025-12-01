<?php

namespace Fastbolt\FabricImporter\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class FabricImporterExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @param array<string, mixed> $configs
     * @param ContainerBuilder     $container
     *
     * @return void
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // load configuration
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);
        foreach ($config as $key => $value) {
            $container->setParameter('fabric_importer.' . $key, $value);
        }

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yaml');
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return void
     */
    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $configs[0] ?? [];

        /** @var string $databaseUrl */
        $databaseUrl = $config['database_url'] ?? "";
        if ($databaseUrl === "") {
            throw new \Exception('\'database_url\' configuration is required for fabric_importer extension.');
        }

        // DBAL config hinzufügen
        $container->prependExtensionConfig(
            'doctrine',
            [
                'dbal' => [
                    'connections' => [
                        'fabric' => [
                            'driver'  => 'sqlsrv',
                            'url'     => $databaseUrl,
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
                                    'dir' => __DIR__ . '/../Entity',
                                    'prefix'    => 'Fastbolt\\FabricImporter\\Entity',
                                    'alias'     => 'FabricImporter',
                                    'type'      => 'attribute'
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }
}
