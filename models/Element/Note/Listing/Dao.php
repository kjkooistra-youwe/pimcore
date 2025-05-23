<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Element\Note\Listing;

use Exception;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Element\Note\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of static routes for the specified parameters, returns an array of Element\Note elements
     *
     * @return Model\Element\Note[]
     */
    public function load(): array
    {
        $notesData = $this->db->fetchFirstColumn(
            'SELECT id FROM notes' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(),
            $this->model->getConditionVariables(),
            $this->model->getConditionVariableTypes()
        );

        $notes = [];
        foreach ($notesData as $noteData) {
            if ($note = Model\Element\Note::getById($noteData)) {
                $notes[] = $note;
            }
        }

        $this->model->setNotes($notes);

        return $notes;
    }

    /**
     * @return int[]
     */
    public function loadIdList(): array
    {
        $notesIds = $this->db->fetchFirstColumn(
            'SELECT id FROM notes' . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(),
            $this->model->getConditionVariables(),
            $this->model->getConditionVariableTypes()
        );

        return array_map('intval', $notesIds);
    }

    public function getTotalCount(): int
    {
        try {
            return (int)$this->db->fetchOne(
                'SELECT COUNT(*) FROM notes ' . $this->getCondition(),
                $this->model->getConditionVariables(),
                $this->model->getConditionVariableTypes()
            );
        } catch (Exception $e) {
            return 0;
        }
    }
}
