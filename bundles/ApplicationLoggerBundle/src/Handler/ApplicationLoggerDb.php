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

namespace Pimcore\Bundle\ApplicationLoggerBundle\Handler;

use DateTimeZone;
use Doctrine\DBAL\Connection;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Pimcore\Db;

class ApplicationLoggerDb extends AbstractProcessingHandler
{
    const TABLE_NAME = 'application_logs';

    const TABLE_ARCHIVE_PREFIX = 'application_logs_archive';

    private Connection $db;

    public function __construct(Connection $db, int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        $this->db = $db;
        parent::__construct($level, $bubble);
    }

    public function write(LogRecord $record): void
    {
        $data = [
            'pid' => getmypid(),
            'priority' => $record->level->toPsrLogLevel(),
            'message' => $record->message,
            'timestamp' => $record->datetime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'component' => $record->context['component'] ?? $record->channel,
            'fileobject' => $record->context['fileObject'] ?? null,
            'relatedobject' => $record->context['relatedObject'] ?? null,
            'relatedobjecttype' => $record->context['relatedObjectType'] ?? null,
            'source' => $record->context['source'] ?? null,
        ];

        $this->db->insert(self::TABLE_NAME, $data);
    }

    /**
     * @return string[]
     */
    public static function getComponents(): array
    {
        $db = Db::get();

        $components = $db->fetchFirstColumn('SELECT component FROM ' . self::TABLE_NAME . ' WHERE NOT ISNULL(component) GROUP BY component;');

        return $components;
    }
}
