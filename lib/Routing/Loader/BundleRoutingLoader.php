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

namespace Pimcore\Routing\Loader;

use Pimcore\Config\BundleConfigLocator;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal
 */
class BundleRoutingLoader extends Loader
{
    private BundleConfigLocator $locator;

    public function __construct(BundleConfigLocator $locator)
    {
        $this->locator = $locator;
    }

    public function load(mixed $resource, ?string $type = null): mixed
    {
        $collection = new RouteCollection();
        $files = $this->locator->locate('routing');

        if (empty($files)) {
            return $collection;
        }

        foreach ($files as $file) {
            $routes = $this->import($file);
            $collection->addCollection($routes);
        }

        return $collection;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'pimcore_bundle' === $type;
    }
}
