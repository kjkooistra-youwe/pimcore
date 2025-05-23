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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211221152344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        //disable foreign key checks
        $this->addSql('SET foreign_key_checks = 0');

        $this->addSql('ALTER TABLE `assets_metadata` CHANGE `cid` `cid` int(11) unsigned NOT NULL;');

        if (!$schema->getTable('assets_metadata')->hasForeignKey('fk_assets_metadata_assets')) {
            $this->addSql(
                'ALTER TABLE `assets_metadata`
                ADD CONSTRAINT `fk_assets_metadata_assets`
                FOREIGN KEY (`cid`)
                REFERENCES `assets` (`id`)
                ON UPDATE NO ACTION
                ON DELETE CASCADE;'
            );
        }

        //enable foreign key checks
        $this->addSql('SET foreign_key_checks = 1');
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('assets_metadata')->hasForeignKey('fk_assets_metadata_assets')) {
            $this->addSql('ALTER TABLE `assets_metadata` DROP FOREIGN KEY `fk_assets_metadata_assets`;');
        }

        $this->addSql('ALTER TABLE `assets_metadata` CHANGE `cid` `cid` int(11) NOT NULL;');
    }
}
