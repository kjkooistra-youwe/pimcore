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

namespace Pimcore\Bundle\XliffBundle\Controller;

use Exception;
use Pimcore\Bundle\XliffBundle\ExportService\Exporter\ExporterInterface;
use Pimcore\Bundle\XliffBundle\ExportService\ExportServiceInterface;
use Pimcore\Bundle\XliffBundle\ImportDataExtractor\ImportDataExtractorInterface;
use Pimcore\Bundle\XliffBundle\ImporterService\ImporterServiceInterface;
use Pimcore\Bundle\XliffBundle\TranslationItemCollection\TranslationItemCollection;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Pimcore\Logger;
use Pimcore\Model\Element;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/translation')]
class XliffTranslationController extends UserAwareController
{
    use JsonHelperTrait;

    /**
     * @throws Exception
     */
    #[Route('/xliff-export', name: 'pimcore_bundle_xliff_translation_xliffexport', methods: ['POST'])]
    public function xliffExportAction(Request $request, ExportServiceInterface $exportService): JsonResponse
    {
        $this->checkPermission('xliff_import_export');

        $id = $request->request->getString('id');
        $data = $this->decodeJson($request->request->getString('data'));
        $source = $request->request->getString('source');
        $target = $request->request->getString('target');

        $translationItems = new TranslationItemCollection();

        foreach ($data as $el) {
            $element = Element\Service::getElementById($el['type'], $el['id']);
            $translationItems->addPimcoreElement($element);
        }

        $exportService->exportTranslationItems($translationItems, $source, [$target], $id);

        return $this->jsonResponse([
            'success' => true,
        ]);
    }

    #[Route('/xliff-export-download', name: 'pimcore_bundle_xliff_translation_exportdownload', methods: ['GET'])]
    public function xliffExportDownloadAction(Request $request, ExporterInterface $translationExporter, ExportServiceInterface $exportService): BinaryFileResponse
    {
        $this->checkPermission('xliff_import_export');

        $id = $request->query->getString('id');
        $exportFile = $exportService->getTranslationExporter()->getExportFilePath($id);

        $response = new BinaryFileResponse($exportFile);
        $response->headers->set('Content-Type', $translationExporter->getContentType());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($exportFile));
        $response->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/xliff-import-upload', name: 'pimcore_bundle_xliff_translation_xliffimportupload', methods: ['POST'])]
    public function xliffImportUploadAction(Request $request, ImportDataExtractorInterface $importDataExtractor): JsonResponse
    {
        $this->checkPermission('xliff_import_export');

        $jobs = [];
        $id = uniqid();
        $importFile = $importDataExtractor->getImportFilePath($id);
        copy($_FILES['file']['tmp_name'], $importFile);

        $steps = $importDataExtractor->countSteps($id);

        for ($i = 0; $i < $steps; $i++) {
            $jobs[] = [[
                'url' => $this->generateUrl('pimcore_bundle_xliff_translation_xliffimportelement'),
                'method' => 'POST',
                'params' => [
                    'id' => $id,
                    'step' => $i,
                ],
            ]];
        }

        $response = $this->jsonResponse([
            'success' => true,
            'jobs' => $jobs,
            'id' => $id,
        ]);
        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @throws Exception
     */
    #[Route('/xliff-import-element', name: 'pimcore_bundle_xliff_translation_xliffimportelement', methods: ['POST'])]
    public function xliffImportElementAction(Request $request, ImportDataExtractorInterface $importDataExtractor, ImporterServiceInterface $importerService): JsonResponse
    {
        $this->checkPermission('xliff_import_export');

        $id = $request->request->getString('id');
        $step = $request->request->getInt('step');

        try {
            $attributeSet = $importDataExtractor->extractElement($id, $step);
            if ($attributeSet) {
                $importerService->import($attributeSet);
            } else {
                Logger::warning(sprintf('Could not resolve element %s', $id));
            }
        } catch (Exception $e) {
            Logger::err($e->getMessage());

            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->jsonResponse([
            'success' => true,
        ]);
    }
}
