<?php

namespace App\Utils;

use App\Models\Firmware;
use Illuminate\Support\Facades\Log;


class Files
{
    /**
     * @param Firmware $firmware
     * @return string
     * @throws \Exception
     */
    public static function makeZipWithFiles(string $name, string $path, array $files): string
    {
        $zip = new \ZipArchive();
        $tempFileUri = $path . '/' . $name . '.zip';
        if (file_exists($tempFileUri)) unlink($tempFileUri);

        Log::info('Create zip archive: ' . $tempFileUri);
        if ($zip->open($tempFileUri, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            // Add File in ZipArchive
            foreach ($files as $file) {
                $fileName = $path . '/' . $file;
                if (!file_exists($fileName)) continue;

                Log::info("Add $fileName to $tempFileUri archive");
                if (!$zip->addFile($fileName, $file)) {
                    throw new \Exception('Could not add file to ZIP: ' . $file);
                }
            }
            // Close ZipArchive
            $zip->close();
        } else {
            throw new \Exception('Could not open ZIP file.');
        }
        return $tempFileUri;
    }
}
