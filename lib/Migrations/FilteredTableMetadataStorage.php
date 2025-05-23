<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Migrations;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorage;
use Doctrine\Migrations\Version\ExecutionResult;

/**
 * @internal
 */
final class FilteredTableMetadataStorage implements MetadataStorage
{
    private TableMetadataStorage $storage;

    private ?string $prefix = null;

    /**
     * @return $this
     */
    public function __invoke(DependencyFactory $dependencyFactory): static
    {
        $storage = new TableMetadataStorage(
            $dependencyFactory->getConnection(),
            $dependencyFactory->getVersionComparator(),
            $dependencyFactory->getConfiguration()->getMetadataStorageConfiguration(),
            $dependencyFactory->getMigrationRepository()
        );

        $this->setStorage($storage);

        return $this;
    }

    public function setStorage(TableMetadataStorage $storage): void
    {
        $this->storage = $storage;
    }

    public function setPrefix(?string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function ensureInitialized(): void
    {
        $this->storage->ensureInitialized();
    }

    public function getExecutedMigrations(): ExecutedMigrationsList
    {
        $migrations = $this->storage->getExecutedMigrations();
        if (!$this->prefix) {
            return $migrations;
        }

        $filteredMigrations = [];
        foreach ($migrations->getItems() as $migration) {
            if (str_starts_with((string)$migration->getVersion(), $this->prefix)) {
                $filteredMigrations[] = $migration;
            }
        }

        return new ExecutedMigrationsList($filteredMigrations);
    }

    public function complete(ExecutionResult $migration): void
    {
        $this->storage->complete($migration);
    }

    public function reset(): void
    {
        $this->storage->reset();
    }
}
