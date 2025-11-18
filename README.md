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

Add this to your services.yaml to tag the implementations of the import-definition.
```yaml
services:
    _instanceof:
      Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinitionInterface:
        tags: ['fastbolt.fabric_importer']
```

Add this to your doctrine.yaml.
```yaml
doctrine:
    orm:
        mappings:
            FabricImporter:
                is_bundle: false
                type: attribute
                dir: '%kernel.project_dir%/vendor/fastbolt/fabric-importer/src/Entity'
                prefix: 'Fastbolt\FabricImporter\Entity'
                alias: FabricImporter
```

Run this command to create the dwh_syncs table in your database.
```console
php bin/console fabric-importer:init
```

##Usage
Run this command to import the data
´´´console
php bin/console fabric-importer:import <import name>
´´´

To define an import, extend the FabricImporterDefinition and implement its methods. Here is an example. 
```php
<?php

use DateTime;
use Fastbolt\FabricImporter\Types\FabricJoinedSelect;
use Fastbolt\FabricImporter\Types\FabricTableJoin;
use InvalidArgumentException;
use Fastbolt\FabricImporter\ImporterDefinitions\FabricImporterDefinition;

/**
 * @extends FabricImporterDefinition<Customer>
 */
class CustomerImporterDefinition extends FabricImporterDefinition
{
    private ?User $fboneUser = null;

    /**
     * @var Branch[]
     */
    private array $branches = [];

    public function __construct(
        private readonly BranchRepository $branchRepository,
        private readonly SalesRepFactory $salesRepFactory,
        private readonly CountryFactory $countryFactory,
        private readonly UserRepository $userRepository
    ) {
        parent::__construct();
    }

    public function getName(): string
    {
        return 'customers';
    }

    public function getSourceTable(): string
    {
        return 'lake_bronze.fb.full_customers';
    }

    public function getTargetTable(): string
    {
        return "customers";
    }

    public function getDescription(): string
    {
        return 'Import customers from data warehouse';
    }

    public function getTableJoinsDefinitions(): array
    {
        return [
            new FabricTableJoin(
                         'lake_bronze.fb.branches',
                         'branch',
                         'branch.id = t.branch_ID',
                'LEFT',
                selects: [
                             new FabricJoinedSelect('iso', 'branch_id'),
                         ]
            ),
        ];
    }

    //You normally don't need this method if you provided the identifier-mapping
    public function getIdentifierColumns(): array
    {
        return [
            'shortname',
            'branch',
        ];
    }

    public function getIdentifierMapping(): array
    {
        return [
            'customer_no' => 'shortname',
            'branch_ID'   => 'branch_id', //this is the joined alias, not the ext. field name
        ];
    }

    public function getFieldNameMapping(): array
    {
        return [
            'name1'         => 'name',
            'name2'         => 'name_2',
            'street'        => 'street',
            'zip'           => 'zip',
            'city1'         => 'city',
            'city2'         => 'city_2',
            'country'       => 'country_id',
            'region'        => 'region',
            'language'      => 'language',
            'phone'         => 'phone',
            'mobile'        => 'mobile',
            'email'         => 'email',
            'inco1'         => 'inco_terms',
            'inco2'         => 'inco_terms2',
            'currency'      => 'currency',
            'payment_terms' => 'payment_terms',
            'key_account'   => 'key_account_id',
            'deleted'       => 'deleted',
        ];
    }

    public function getFieldConverters(): array
    {
        return [
            //converted branch is used in key_account, so must be first
            'branch_id' => function (string $branchShort): ?int {
                if ($branchShort === 'GB') {
                    $branchShort = 'UK';
                }

                $branch = $this->getBranch($branchShort);
                return $branch?->getId() ?? null;
            },
            'key_account' => function (string $shortname, array $item): ?int {
                if (!$shortname || str_contains($shortname, '@')) {
                    return null;
                }

                $branchID = $item['branch_id']; //the branch converter was already called here, so we have the

                if (null === ($branchEntity = $this->getBranch($branchID))) {
                    throw new InvalidArgumentException(sprintf('Unknown branch: %s', $branchID));
                }

                return $this->salesRepFactory->getByShortnameAndBranch($shortname, $branchEntity)?->getId();
            },
            'country' => function (?string $iso2): ?int {
                if (!$iso2) {
                    return null;
                }

                return $this->countryFactory->getByIsoCode($iso2)->getId();
            },
        ];
    }

    public function getDefaultValuesForUpdate(): array
    {
        if ($this->fboneUser === null) {
            $this->fboneUser = $this->userRepository->findOneBy(...);
        }

        $defaultCustomer = new Customer();
        $date            = (new DateTime())->format('Y-m-d h:i:s');

        return [
            'type'                   => $defaultCustomer->getType(),
            'changed_by_id'          => $this->fboneUser?->getId(),
            'changed_at'             => $date,
        ];
    }

    public function getDefaultValuesForInsert(): array
    {
        if ($this->fboneUser === null) {
            $this->fboneUser = $this->userRepository->findOneBy(...);
        }

        $date            = (new DateTime())->format('Y-m-d h:i:s');
        $defaultCustomer = new Customer();
        return [
            'type'                   => $defaultCustomer->getType(),
            'changed_by_id'          => $this->fboneUser?->getId(),
            'created_at'             => $date,
            'ranking'                => $defaultCustomer->getRanking(),
            'changed_at'             => $date,
            'minimum_order_value'    => $defaultCustomer->getMinimumOrderValue(),
            'digi_points_level'      => $defaultCustomer->getDigiPointsLevel(),
            'discount'               => $defaultCustomer->getDiscount(),
            'protected'              => (int)$defaultCustomer->isProtected(),
            'is_disabled'            => (int)$defaultCustomer->isDisabled(),
            'hidden'                 => (int)$defaultCustomer->isHidden(),
            'db2_surcharge'          => $defaultCustomer->getDb2Surcharge(),
            'online_surcharge'       => $defaultCustomer->getOnlineSurcharge(),
            'quick_dealer_surcharge' => $defaultCustomer->getQuickDealerSurcharge(),
            'target_revenue'         => $defaultCustomer->getTargetRevenue(),
        ];
    }

    public function getAllowUpdate(): bool
    {
        return true;
    }

    private function getBranch(int|string $branch): ?Branch
    {
        $this->loadBranches();

        if (is_int($branch)) {
            return $this->branches[$branch] ?? null;
        }

        foreach ($this->branches as $b) {
            if ($b->getShortname() === $branch) {
                return $b;
            }
        }

        return null;
    }

    private function loadBranches(): void
    {
        if (empty($this->branches)) {
            $branches = $this->branchRepository->findAll();
            foreach ($branches as $b) {
                $this->branches[$b->getId()] = $b;
            }
        }
    }

    public function getDataBatchSize(): int
    {
        return 500;
    }

    public function getFlushInterval(): int
    {
        return 200;
    }
}
```