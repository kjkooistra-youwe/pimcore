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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\StaticRoutesBundle\Model\Staticroute;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Hardlink\Wrapper\WrapperInterface;
use Pimcore\Model\Site;
use Pimcore\Tool\Frontend;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets canonical headers for hardlink documents
 *
 * @internal
 */
class HardlinkCanonicalListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    public function __construct(protected DocumentResolver $documentResolver)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (class_exists(Staticroute::class) && null !== Staticroute::getCurrentRoute()) {
            return;
        }

        $document = $this->documentResolver->getDocument($request);
        if (!$document) {
            return;
        }

        if ($document instanceof WrapperInterface) {
            $this->handleHardlink($request, $event->getResponse(), $document);
        }
    }

    protected function handleHardlink(Request $request, Response $response, Document $document): void
    {
        $canonical = null;

        // get the canonical (source) document
        $hardlinkCanonicalSourceDocument = Document::getById($document->getId());

        if (Frontend::isDocumentInCurrentSite($hardlinkCanonicalSourceDocument)) {
            $canonical = $request->getSchemeAndHttpHost() . $hardlinkCanonicalSourceDocument->getFullPath();
        } elseif (Site::isSiteRequest()) {
            $sourceSite = Frontend::getSiteForDocument($hardlinkCanonicalSourceDocument);
            if ($sourceSite) {
                if ($sourceSite->getMainDomain()) {
                    $sourceSiteRelPath = preg_replace('@^' . preg_quote($sourceSite->getRootPath(), '@') . '@', '', $hardlinkCanonicalSourceDocument->getRealFullPath());
                    $canonical = $request->getScheme() . '://' . $sourceSite->getMainDomain() . $sourceSiteRelPath;
                }
            }
        }

        if ($canonical) {
            $response->headers->set('Link', '<' . $canonical . '>; rel="canonical"', false);
        }
    }
}
