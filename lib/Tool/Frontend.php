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

namespace Pimcore\Tool;

use Pimcore;
use Pimcore\Bundle\CoreBundle\EventListener\Frontend\FullPageCacheListener;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Document;
use Pimcore\Model\Site;

final class Frontend
{
    public static function isDocumentInSite(?Site $site, Document $document): bool
    {
        $siteRootDocument = $site?->getRootDocument();

        if (
            $siteRootDocument &&
            !str_starts_with($document->getRealFullPath() . '/', $siteRootDocument->getRealFullPath() . '/')
        ) {
            return false;
        }

        return true;
    }

    public static function isDocumentInCurrentSite(Document $document): bool
    {
        if (Site::isSiteRequest()) {
            $site = Site::getCurrentSite();

            return self::isDocumentInSite($site, $document);
        }

        return true;
    }

    public static function getSiteForDocument(Document $document): ?Site
    {
        $siteIdOfDocument = self::getSiteIdForDocument($document);

        if (!$siteIdOfDocument) {
            return null;
        }

        return Site::getById($siteIdOfDocument);
    }

    public static function getSiteIdForDocument(Document $document): ?int
    {
        $siteMapping = self::getSiteMapping();

        foreach ($siteMapping as $sitePath => $id) {
            if (str_starts_with($document->getRealFullPath() . '/', $sitePath . '/')) {
                return $id;
            }
        }

        return null;
    }

    private static function getSiteMapping(): array
    {
        $cacheKey = 'sites_path_mapping';

        if (RuntimeCache::isRegistered($cacheKey)) {
            return RuntimeCache::get($cacheKey);
        }

        $siteMapping = Pimcore\Cache::load($cacheKey);

        if (!$siteMapping) {
            $siteMapping = [];
            $sites = new Site\Listing();
            $sites->setOrderKey(
                '(SELECT LENGTH(CONCAT(`path`, `key`)) FROM documents WHERE documents.id = sites.rootId) DESC',
                false
            );
            $sites = $sites->load();
            foreach ($sites as $site) {
                $siteMapping[$site->getRootPath()] = $site->getId();
            }
            Pimcore\Cache::save($siteMapping, $cacheKey, ['system', 'resource'], null, 997);
        }
        RuntimeCache::set($cacheKey, $siteMapping);

        return $siteMapping;
    }

    /**
     * @return false|array{enabled: true, lifetime: int|null}
     */
    public static function isOutputCacheEnabled(): bool|array
    {
        $cacheService = Pimcore::getContainer()->get(FullPageCacheListener::class);

        if ($cacheService->isEnabled()) {
            return [
                'enabled' => true,
                'lifetime' => $cacheService->getLifetime(),
            ];
        }

        return false;
    }
}
