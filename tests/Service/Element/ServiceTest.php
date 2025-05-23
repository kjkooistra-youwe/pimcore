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

namespace Pimcore\Tests\Service\Element;

use Pimcore\Model\DataObject;
use Pimcore\Model\Element\Service;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;

class ServiceTest extends TestCase
{
    public function testCloneMe(): void
    {
        // create object with property
        $object = TestHelper::createEmptyObject('', false);
        $object->setProperty('propertyA', 'input', 'valueA');
        $object->save();

        // copy object in the same folder
        $clonedObject = Service::cloneMe($object);
        $this->assertNull($clonedObject->getId());
        $this->assertNull($clonedObject->getParent());
        $this->assertNull($clonedObject->getParentId());
        $target = DataObject::getById(1);
        $clonedObject->setKey(Service::getSafeCopyName($clonedObject->getKey(), $target));
        $clonedObject->setParentId($target->getId());
        $clonedObject->save();

        // reload the new object from the db
        $clonedObject = DataObject::getById($clonedObject->getId(), ['force' => true]);

        $this->assertEquals($object->getKey() . '_copy', $clonedObject->getKey());
        $this->assertEquals('valueA', $clonedObject->getProperty('propertyA'));
    }
}
