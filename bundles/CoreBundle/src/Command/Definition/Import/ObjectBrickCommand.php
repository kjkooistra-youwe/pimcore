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

namespace Pimcore\Bundle\CoreBundle\Command\Definition\Import;

use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Pimcore\Model\ModelInterface;

/**
 * @internal
 */
class ObjectBrickCommand extends AbstractStructureImportCommand
{
    /**
     * Get type
     *
     */
    protected function getType(): string
    {
        return 'ObjectBrick';
    }

    /**
     * Get definition name from filename (e.g. class_Customer_export.json -> Customer)
     *
     *
     */
    protected function getDefinitionName(string $filename): ?string
    {
        $parts = [];
        if (1 === preg_match('/^objectbrick_(.*)_export\.json$/', $filename, $parts)) {
            return $parts[1];
        }

        return null;
    }

    /**
     * Try to load definition by name
     *
     *
     */
    protected function loadDefinition(string $name): ?ModelInterface
    {
        return Definition::getByKey($name);
    }

    /**
     * Create a new definition
     *
     *
     */
    protected function createDefinition(string $name): Definition
    {
        $definition = new Definition();
        $definition->setKey($name);

        return $definition;
    }

    /**
     * Process import
     *
     *
     */
    protected function import(ModelInterface $definition, string $json): bool
    {
        if (!$definition instanceof Definition) {
            return false;
        }

        return Service::importObjectBrickFromJson($definition, $json);
    }
}
