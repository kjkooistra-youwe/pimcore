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

namespace Pimcore\Tests\Model\Document;

use Pimcore\Db;
use Pimcore\Model\Document;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class ListingTest
 *
 * @package Pimcore\Tests\Model\Document
 *
 * @group model.document.document
 */
class ListingTest extends ModelTestCase
{
    public function testListCount(): void
    {
        $db = Db::get();

        $count = $db->fetchOne('SELECT count(*) from documents');
        $this->assertEquals(1, $count, 'expected 1 document');

        for ($i = 0; $i < 5; $i++) {
            TestHelper::createEmptyDocumentPage('', true, true);
        }

        $count = $db->fetchOne('SELECT count(*) from documents');
        $this->assertEquals(6, $count, 'expected 6 documents');

        $list = new Document\Listing();
        $totalCount = $list->getTotalCount();
        $this->assertEquals(6, $totalCount, 'expected 6 documents');

        $list = new Document\Listing();
        $list->setLimit(3);
        $list->setOffset(1);
        $count = $list->getCount();
        $this->assertEquals(3, $count, 'expected 3 documents');

        $list = new Document\Listing();
        $list->setLimit(10);
        $list->setOffset(1);
        $count = $list->getCount();
        $this->assertEquals(5, $count, 'expected 5 documents');

        $list = new Document\Listing();
        $list->setLimit(10);
        $list->setOffset(1);
        $list->load();                      // with load
        $count = $list->getCount();
        $this->assertEquals(5, $count, 'expected 5 documents');
        $totalCount = $list->getTotalCount();
        $this->assertEquals(6, $totalCount, 'expected 6 documents');
    }
}
