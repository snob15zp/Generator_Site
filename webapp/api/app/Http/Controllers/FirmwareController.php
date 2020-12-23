<?php


namespace App\Http\Controllers;

use App\Http\Resources\FirmwareResource;
use App\Models\Firmware;
use App\Models\FirmwareFiles;
use App\Models\UserPrivileges;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Vinkla\Hashids\Facades\Hashids;

class FirmwareController extends Controller
{
    private $firmwarePath;

    public function __construct()
    {
        $this->firmwarePath = env('FIRMWARE_PATH');
    }

    public function getAll(Request $request): JsonResponse
    {
        if ($request->user() == null || $request->user()->cannot(UserPrivileges::MANAGE_FIRMWARE)) {
            $firmwares = Firmware::where('active', true)->get();
        } else {
            $firmwares = Firmware::all();
        }
        return $this->respondWithResource(FirmwareResource::collection($firmwares));
    }

    public function update(Request $request, $id): JsonResponse
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_FIRMWARE)) {
            $this->raiseError(403, "Resource not available");
        }

        $firmware = Firmware::where('id', Hashids::decode($id))->first();
        if ($firmware == null) {
            $this->raiseError(404, "Firmware not found");
        }

        $this->validate($request, [
            'version' => 'nullable|regex:/^\d+\.\d+\.\d+$/',
            'active' => 'nullable|boolean',
        ]);

        $fields = collect($request->only(['version', 'active']))->filter(function ($item) {
            return $item !== null;
        })->toArray();

        $firmware->update($fields);
        return $this->respondWithResource(new FirmwareResource($firmware));
    }

    public function download($version): BinaryFileResponse
    {
        $firmware = $this->findByVersion($version);
        if ($firmware == null) {
            $this->raiseError(404, "Version not found");
        }
        $zipFile = null;
        try {
            $zipFile = $this->makeZipWithFiles($firmware);
            Log::info(file_exists($zipFile));
            return response()->download($zipFile, 'firmware_v' . str_replace('.', '-', $firmware->version) . '.zip', [
                'Content-Length' => filesize($zipFile),
                'Content-Type' => 'application/zip'
            ])->deleteFileAfterSend();
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

        $version = $request->input('version');
        $firmware = Firmware::where('version', $version)->first();
        if ($firmware != null) {
            $this->raiseError(422, "Version $version is already uploaded");
        }

        $files = [
            $request->file('cpu'),
            $request->file('fpga')
        ];
        $directory = Firmware::getPath($version);

        $this->storeOnDisk($directory, $files);

        try {
            DB::beginTransaction();
            $firmware = Firmware::create(['version' => $request->input('version'), 'active' => true]);
            $firmware->files()->saveMany(collect($files)->map(function ($file) use ($directory) {
                $name = $file->getClientOriginalName();
                return new FirmwareFiles([
                    'file_name' => $name,
                    'hash' => hash_file("sha256", storage_path('app/' . $directory . '/' . $name))
                ]);
            }));
            DB::commit();
            return $this->respondWithResource(new FirmwareResource($firmware));
        } catch (Exception  $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            $this->raiseError(500, "Cannot to create firmware");
        }
    }

    /**
     * @param Request $request
     * @param $version
     * @return JsonResponse
     */
    public function delete(Request $request, $version): JsonResponse
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_FIRMWARE)) {
            $this->raiseError(403, "Resource not available");
        }

        try {
            $firmware = $this->findByVersion($version);
            Storage::deleteDirectory($firmware->getPath());
        } catch (Exception $e) {
            Log::error($e->getMessage());
            $this->raiseError(500, "Cannot to delete firmware");
        }

        return $this->respondWithMessage('Firmware deleted');
    }

    /**
     * @param string $directory
     * @param UploadedFile[] $files
     */
    private function storeOnDisk(string $directory, array $files)
    {
        try {
            if (Storage::exists($directory)) {
                Storage::deleteDirectory($directory);
            }
            Storage::makeDirectory($directory);

            foreach ($files as $file) {
                $file->storeAs($directory, $file->getClientOriginalName());
            }
        } catch (Exception  $e) {
            Log::error($e->getMessage());
            $this->raiseError(500, "Cannot to store firmware");
        }
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
                $firmware->cpu = new FirmwareFile();
                $firmware->fpga = new FirmwareFile();

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
        $tempFileUri = $firmware->getFullPath() . '/' . $firmware->version . '.zip';
        Log::info($tempFileUri);
        if ($zip->open($tempFileUri, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            // Add File in ZipArchive
            foreach (Storage::files($firmware->getPath()) as $name => $file) {
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

//    private function parse(string $xml, string $fileName): CpuFirmware
//    {
//        $element = new SimpleXMLElement($xml);
//
//        return new CpuFirmware(
//            $fileName,
//            (string)$element->fw->version,
//            DateTime::createFromFormat('d/m/Y h:i:s A', (string)$element->fw->date),
//            (string)$element->fw->device
//        );
//    }
}
