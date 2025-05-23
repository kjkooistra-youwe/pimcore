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

namespace Pimcore\Document\Adapter;

use Exception;
use Gotenberg\Gotenberg as GotenbergAPI;
use Gotenberg\Stream;
use Pimcore\Config;
use Pimcore\Helper\GotenbergHelper;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Tool\Storage;

/**
 * @internal
 */
class Gotenberg extends Ghostscript
{
    use GetTextConversionHelperTrait;

    public function isAvailable(): bool
    {
        try {
            $lo = self::checkGotenberg();
            if ($lo && parent::isAvailable()) { // GhostScript is necessary for pdf count, pdf to text conversion
                return true;
            }
        } catch (Exception $e) {
            Logger::notice($e->getMessage());
        }

        return false;
    }

    public function isFileTypeSupported(string $fileType): bool
    {
        // it's also possible to pass a path or filename
        if (preg_match("/\.?(pdf|doc|docx|odt|xls|xlsx|ods|ppt|pptx|odp)$/i", $fileType)) {
            return true;
        }

        return false;
    }

    /**
     *
     * @throws Exception
     */
    public static function checkGotenberg(): bool
    {
        return GotenbergHelper::isAvailable();
    }

    public function load(Asset\Document $asset): static
    {
        // avoid timeouts
        $maxExecTime = (int) ini_get('max_execution_time');
        if ($maxExecTime > 1 && $maxExecTime < 250) {
            set_time_limit(250);
        }

        if (!$this->isFileTypeSupported($asset->getFilename())) {
            $message = "Couldn't load document " . $asset->getRealFullPath() . ' only Microsoft/Libre/Open-Office/PDF documents are currently supported';
            Logger::error($message);

            throw new Exception($message);
        }

        $this->asset = $asset;

        // first we have to create a pdf out of the document (if it isn't already one), so that we can pass it to ghostscript
        // unfortunately there isn't any other way at the moment
        if (!preg_match("/\.?pdf$/i", $asset->getFilename())) {
            if (!parent::isFileTypeSupported($asset->getFilename())) {
                $this->getPdf();
            }
        }

        return $this;
    }

    public function getPdf(?Asset\Document $asset = null)
    {
        if (!$asset && $this->asset) {
            $asset = $this->asset;
        }

        try {
            // if the document is already an PDF, delegate the call directly to parent::getPdf() (Ghostscript)
            if (parent::isFileTypeSupported($asset->getFilename())) {
                return parent::getPdf($asset);
            }
        } catch (Exception $e) {
            // nothing to do, delegate to gotenberg
        }

        $storage = Storage::get('asset_cache');

        $storagePath = $this->getTemporaryPdfStorageFilePath($asset);

        if (!$storage->fileExists($storagePath)) {
            $localAssetTmpPath = $asset->getLocalFile();

            try {
                $request = GotenbergAPI::libreOffice(Config::getSystemConfiguration('gotenberg')['base_url'])
                    ->convert(
                        Stream::path($localAssetTmpPath)
                    );

                $response = GotenbergAPI::send($request);
                $fileContent = $response->getBody()->getContents();
                $storage->write($storagePath, $fileContent);

            } catch (Exception $e) {
                $message = "Couldn't convert document to PDF: " . $asset->getRealFullPath() . ' with Gotenberg: ';
                Logger::error($message. $e->getMessage());

                throw $e;
            }
        }

        return $storage->readStream($storagePath);
    }
}
