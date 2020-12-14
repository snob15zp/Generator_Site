<?php


namespace App\Http\Controllers;

use App\Http\Dto\Firmware;
use App\Http\Resources\FirmwareResource;
use App\Http\Resources\ProgramResource;
use App\Models\Folder;
use App\Models\Program;
use App\Models\UserPrivileges;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;
use Vinkla\Hashids\Facades\Hashids;

class FirmwareController extends Controller
{
    private $firmwarePath;

    public function __construct()
    {
        $this->firmwarePath = env('FIRMWARE_PATH');
    }

    public function getAll(Request $request)
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_FIRMWARE)) {
            $this->raiseError(403, "Resource not available");
        }
        $firmwares = $this->load();
        return $this->respondWithResource(JsonResource::collection($firmwares));
    }

    public function download(Request $request, $hash)
    {
        $firmware = $this->findByHash($hash);
        if ($firmware == null) {
            $this->raiseError(404, "File not found");
        }
        return Storage::download($firmware->name);
    }

    public function create(Request $request)
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_FIRMWARE)) {
            $this->raiseError(403, "Resource not available");
        }

        $this->validate($request, [
            'firmware' => 'required|file'
        ]);

        $fileExists = false;
        $uploadedFile = $request->file('firmware');
        try {
            $firmware = $this->parse($uploadedFile->get());
            $fileExists = $this->findByHash($firmware->hash) != null;
            if (!$fileExists) {
                $uploadedFile->storeAs($this->firmwarePath, $firmware->getFileName());
                return $this->respondWithResource(new JsonResource($firmware));
            }
        } catch (\Exception  $e) {
            Log::error($e->getMessage());
            $this->raiseError(500, "Cannot to upload firmware");
        }

        if ($fileExists) {
            $this->raiseError(422, "File already exists");
        }
    }

    public function delete(Request $request, $hash)
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_FIRMWARE)) {
            $this->raiseError(403, "Resource not available");
        }

        try {
            $firmware = $this->findByHash($hash);
            Storage::delete($this->firmwarePath . '/' . $firmware->getFileName());
            return $this->respondWithMessage('Firmware deleted');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->raiseError(500, "Cannot to delete firmware");
        }
    }

    private function findByHash(string $hash): Firmware
    {
        $firmwares = $this->load();
        return collect($firmwares)->first(function ($value, $key) use ($hash) {
            return $value->hash == $hash;
        });
    }

    private function load(): array
    {
        $files = Storage::files($this->firmwarePath);
        $firmware = [];
        $errors = [];
        foreach ($files as $file) {
            try {
                $firmware[] = $this->parse(Storage::get($file));
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                $errors[] = "$file is not parsable";
            }
        }

        return $firmware;
    }

    private function parse(string $xml): Firmware
    {
        $element = new SimpleXMLElement($xml);

        return new Firmware(
            (string) $element->fw->version,
            DateTime::createFromFormat('d/m/Y h:i:s A', (string) $element->fw->date),
            (string) $element->fw->device,
            crc32($xml)
        );
    }
}
