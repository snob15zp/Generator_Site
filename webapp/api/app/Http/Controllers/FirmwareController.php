<?php


namespace App\Http\Controllers;

use App\Http\Dto\Firmware;
use App\Models\UserPrivileges;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use SimpleXMLElement;

class FirmwareController extends Controller
{
    private $firmwarePath;

    public function __construct()
    {
        $this->firmwarePath = env('FIRMWARE_PATH');
    }

    public function getAll(Request $request): JsonResponse
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_FIRMWARE)) {
            $this->raiseError(403, "Resource not available");
        }
        $firmwares = $this->load();
        return $this->respondWithResource(JsonResource::collection($firmwares));
    }

    public function download($hash)
    {
        $firmware = $this->findByHash($hash);
        if ($firmware == null) {
            $this->raiseError(404, "File not found");
        }
        return Storage::download($this->firmwarePath . '/' . $firmware->getFileName());
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
            if (Storage::exists($path)) {
                Storage::deleteDirectory($path);
            }
            Storage::makeDirectory($path);
            $request->file('cpu')->storeAs($path, 'cpu.bf');
            $request->file('fpga')->storeAs($path, 'fpga.bin');

            return $this->respondWithResource(new JsonResource(new Firmware("", new DateTime(), "", "")));
        } catch (\Exception  $e) {
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
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->raiseError(500, "Cannot to delete firmware");
        }

        return $this->respondWithMessage('Firmware deleted');
    }

    private function findByHash(string $hash): ?Firmware
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
        foreach ($files as $file) {
            try {
                $firmware[] = $this->parse(Storage::get($file));
            } catch (\Exception $e) {
                Log::error($e->getMessage());
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
