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

namespace Pimcore\Model\Element\Traits;

use Pimcore\Model\Element;
use Pimcore\Model\Version;

/**
 * @internal
 */
trait VersionDaoTrait
{
    /**
     * Get latest available version, using $includingPublished to also consider the published one
     */
    public function getLatestVersion(?int $userId = null, bool $includingPublished = false): ?Version
    {
        $operator = $includingPublished ? '>=' : '>';
        $versionId = $this->db->fetchOne('SELECT id FROM versions WHERE cid = :cid AND ctype = :ctype AND (`date` ' . $operator . ' :mdate OR versionCount ' . $operator . ' :versionCount) AND ((autoSave = 1 AND userId = :userId) OR autoSave = 0) ORDER BY `versionCount` DESC LIMIT 1', [
            'cid' => $this->model->getId(),
            'ctype' => Element\Service::getElementType($this->model),
            'userId' => $userId,
            'mdate' => $this->model->getModificationDate(),
            'versionCount' => $this->model->getVersionCount(),
        ]);

        if ($versionId) {
            return Version::getById($versionId);
        }

        return null;
    }

    /**
     * Get available versions fot the object and return an array of them
     *
     * @return Version[]
     */
    public function getVersions(): array
    {
        $list = new Version\Listing();
        $list->setCondition('cid = :cid AND ctype = :ctype', [
            'cid' => $this->model->getId(),
            'ctype' => Element\Service::getElementType($this->model),
        ])->setOrderKey('id')->setOrder('ASC');

        return $list->load();
    }
}
