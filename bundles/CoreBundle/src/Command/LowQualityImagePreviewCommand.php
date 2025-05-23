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

namespace Pimcore\Bundle\CoreBundle\Command;

use Exception;
use Pimcore;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db\Helper;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:image:low-quality-preview',
    description: 'Regenerates low-quality image previews for all image assets',
    aliases: ['pimcore:image:svg-preview']
)]
class LowQualityImagePreviewCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'id',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'only create thumbnails of images with this (IDs)'
            )
            ->addOption(
                'parent',
                'p',
                InputOption::VALUE_OPTIONAL,
                'only create thumbnails of images in this folder (ID)'
            )
            ->addOption(
                'pathPattern',
                null,
                InputOption::VALUE_OPTIONAL,
                'Filter images against the given regex pattern (path + filename), example:  ^/Sample.*urban.jpg$'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'generate preview regardless if it already exists or not'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conditionVariables = [];

        // get only images
        $conditions = ["type = 'image'"];

        if ($input->getOption('parent')) {
            $parent = Asset::getById((int) $input->getOption('parent'));
            if ($parent instanceof Asset\Folder) {
                $conditions[] = "path LIKE '" . Helper::escapeLike($parent->getRealFullPath()) . "/%'";
            } else {
                $this->writeError($input->getOption('parent') . ' is not a valid asset folder ID!');
                exit;
            }
        }

        if ($ids = $input->getOption('id')) {
            $conditions[] = sprintf('id in (%s)', implode(',', $ids));
        }

        if ($regex = $input->getOption('pathPattern')) {
            $conditions[] = 'CONCAT(`path`, filename) REGEXP ?';
            $conditionVariables[] = $regex;
        }

        $force = $input->getOption('force');

        $list = new Asset\Listing();
        $list->setCondition(implode(' AND ', $conditions), $conditionVariables);
        $total = $list->getTotalCount();
        $perLoop = 10;
        $progressBar = new ProgressBar($this->output, $total);

        for ($i = 0; $i < (ceil($total / $perLoop)); $i++) {
            $list->setLimit($perLoop);
            $list->setOffset($i * $perLoop);
            /** @var Asset\Image[] $images */
            $images = $list->load();
            foreach ($images as $image) {
                $progressBar->advance();
                if ($force || !$image->getLowQualityPreviewDataUri()) {
                    try {
                        $this->output->writeln('generating low-quality preview for image: ' . $image->getRealFullPath() . ' | ' . $image->getId());
                        $image->generateLowQualityPreview();
                    } catch (Exception $e) {
                        $this->output->writeln('<error>'.$e->getMessage().'</error>');
                    }
                }
            }
            Pimcore::collectGarbage();
        }

        $progressBar->finish();

        return 0;
    }
}
