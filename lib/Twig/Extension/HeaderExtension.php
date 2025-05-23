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

namespace Pimcore\Twig\Extension;

use Pimcore\Twig\Extension\Templating\HeadLink;
use Pimcore\Twig\Extension\Templating\HeadMeta;
use Pimcore\Twig\Extension\Templating\HeadScript;
use Pimcore\Twig\Extension\Templating\HeadStyle;
use Pimcore\Twig\Extension\Templating\HeadTitle;
use Pimcore\Twig\Extension\Templating\InlineScript;
use Pimcore\Twig\Extension\Templating\Placeholder;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
class HeaderExtension extends AbstractExtension
{
    private HeadLink $headLink;

    private HeadMeta $headMeta;

    private HeadScript $headScript;

    private HeadStyle $headStyle;

    private HeadTitle $headTitle;

    private InlineScript $inlineScript;

    private Placeholder $placeholder;

    public function __construct(HeadLink $headLink, HeadMeta $headMeta, HeadScript $headScript, HeadStyle $headStyle, HeadTitle $headTitle, InlineScript $inlineScript, Placeholder $placeholder)
    {
        $this->headLink = $headLink;
        $this->headMeta = $headMeta;
        $this->headScript = $headScript;
        $this->headStyle = $headStyle;
        $this->headTitle = $headTitle;
        $this->inlineScript = $inlineScript;
        $this->placeholder = $placeholder;
    }

    public function getFunctions(): array
    {
        $options = [
            'is_safe' => ['html'],
        ];

        // as runtime extension classes are invokable, we can pass them directly as callable
        return [
            new TwigFunction('pimcore_head_link', $this->headLink, $options),
            new TwigFunction('pimcore_head_meta', $this->headMeta, $options),
            new TwigFunction('pimcore_head_script', $this->headScript, $options),
            new TwigFunction('pimcore_head_style', $this->headStyle, $options),
            new TwigFunction('pimcore_head_title', $this->headTitle, $options),
            new TwigFunction('pimcore_inline_script', $this->inlineScript, $options),
            new TwigFunction('pimcore_placeholder', $this->placeholder, $options),
        ];
    }
}
