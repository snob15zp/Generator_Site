<?php


namespace App\Http\Controllers;

use App\Http\Dto\CpuFirmware;
use App\Http\Dto\Firmware;
use App\Http\Dto\FpgaFirmware;
use App\Models\UserPrivileges;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FirmwareController extends Controller
{
    private $firmwarePath;

    public function __construct()
    {
        $this->firmwarePath = env('FIRMWARE_PATH');
    }

    public function getAll(): JsonResponse
    {
        return $this->respondWithResource(JsonResource::collection($this->load()));
    }

    public function download($version): BinaryFileResponse
    {
        $firmware = $this->findByVersion($version);
        if ($firmware == null) {
            $this->raiseError(404, "Version not found");
        }
        try {
            $zipFile = $this->makeZipWithFiles($firmware);
            return response()->download($zipFile, $firmware->version . '.zip', array(
                //'Content-Length: ' . filesize($zipFile),
                'Content-Type: application/zip'
            ));
        } catch (Exception $e) {
            $this->raiseError(500, $e->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function create(Request $request): JsonResponse
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_FIRMWARE)) {
            $this->raiseError(403, "Resource not available");
        }

        $this->validate($request, [
            'version' => 'required|regex:/^\d+\.\d+\.\d+$/',
            'cpu' => 'required|file',
            'fpga' => 'required|file'
        ]);

        try {
            $path = $this->firmwarePath . '/' . $request->input('version');

            $firmware = new Firmware();
            $firmware->version = $request->input('version');
            $firmware->createdAt = new DateTime();
            $firmware->cpu = $this->parse($request->file('cpu')->getContent());
            $firmware->fpga = new FpgaFirmware();

            if (Storage::exists($path)) {
                Storage::deleteDirectory($path);
            }
            Storage::makeDirectory($path);

            $request->file('cpu')->storeAs($path, $firmware->cpu->getFileName());
            $request->file('fpga')->storeAs($path, $firmware->fpga->getFileName());

            return $this->respondWithResource(new JsonResource($firmware));
        } catch (Exception  $e) {
            Log::error($e->getMessage());
            $this->raiseError(500, "Cannot to upload firmware");
        }
    }

    /**
     * @param Request $request
     * @param $hash
     * @return JsonResponse
     */
    public function delete(Request $request, $hash): JsonResponse
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_FIRMWARE)) {
            $this->raiseError(403, "Resource not available");
        }

        try {
            $firmware = $this->findByHash($hash);
            Storage::delete($this->firmwarePath . '/' . $firmware->getFileName());
        } catch (Exception $e) {
            Log::error($e->getMessage());
            $this->raiseError(500, "Cannot to delete firmware");
        }

        return $this->respondWithMessage('Firmware deleted');
    }

    private function findByVersion(string $version): ?Firmware
    {
        $firmwares = $this->load();
        return collect($firmwares)->first(function ($value) use ($version) {
            return $value->version == $version;
        });
    }

    private function load(): array
    {
        $directories = Storage::directories($this->firmwarePath);
        $firmwares = [];
        foreach ($directories as $dir) {
            try {
                $cpuFile = $dir . '/' . CpuFirmware::FILE_NAME;
                $fpgaFile = $dir . '/' . FpgaFirmware::FILE_NAME;
                if (!Storage::exists($cpuFile) || !Storage::exists($fpgaFile)) {
                    continue;
                }

                $firmware = new Firmware();
                $firmware->version = basename($dir);
                $firmware->createdAt = new DateTime('@' . Storage::lastModified($dir));
                $firmware->cpu = $this->parse(Storage::get($cpuFile));
                $firmware->fpga = new FpgaFirmware();

                $firmwares[] = $firmware;

            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
        }

        return $firmwares;
    }

    /**
     * @param Firmware $firmware
     * @return string
     * @throws Exception
     */
    private function makeZipWithFiles(Firmware $firmware): string
    {
        $zip = new \ZipArchive();
        $tempFile = tmpfile();
        $tempFileUri = stream_get_meta_data($tempFile)['uri'];
        if ($zip->open($tempFileUri, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            // Add File in ZipArchive
            foreach ($firmware->getFiles() as $name => $file) {
                if (!$zip->addFile($file, $name)) {
                    throw new Exception('Could not add file to ZIP: ' . $file);
                }
            }
            // Close ZipArchive
            $zip->close();
        } else {
            throw new Exception('Could not open ZIP file.');
        }
        return $tempFileUri;
    }

    private function parse(string $xml): CpuFirmware
    {
        $element = new SimpleXMLElement($xml);

        return new CpuFirmware(
            (string)$element->fw->version,
            DateTime::createFromFormat('d/m/Y h:i:s A', (string)$element->fw->date),
            (string)$element->fw->device
        );
    }
}
