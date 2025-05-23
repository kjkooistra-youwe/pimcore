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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

interface OptionsProviderInterface
{
    public const TYPE_CONFIGURE = 'configure';

    public const TYPE_SELECT_OPTIONS = 'select_options';

    public const TYPE_CLASS = 'class';

    public const TYPES = [
        self::TYPE_CONFIGURE,
        self::TYPE_SELECT_OPTIONS,
        self::TYPE_CLASS,
    ];

    public function getOptionsProviderType(): ?string;

    public function setOptionsProviderType(?string $optionsProviderType): void;

    public function getOptionsProviderClass(): ?string;

    public function setOptionsProviderClass(?string $optionsProviderClass): void;

    public function getOptionsProviderData(): ?string;

    public function setOptionsProviderData(?string $optionsProviderData): void;

    public function useConfiguredOptions(): bool;
}
