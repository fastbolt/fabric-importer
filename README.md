# fabric-importer
A package to import data from Microsoft Fabric.

## Prerequisites

The library is tested with PHP 8.2 and 8.3 and relies on doctrine.


## Installation

The library can be installed via composer:

```
composer require fastbolt/fabric-importer
```

## Configuration

If not configured automatically, the bundle needs to be enabled in your project's `bundles.php` file:

```php
<?php

return [
    Fastbolt\FabricImporter\FabricImporterBundle::class => ['all' => true],
];
```