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
        $firmwares = $this->load();
        $firmware = collect($firmwares)->first(function($value, $key) use ($hash) {
            return $value->hash == $hash;
        });
        if($firmware == null) {
            $this->raiseError(404, "File not found");
        }
        return Storage::download($firmware->name);
    }

    public function create(Request $request, $folderId)
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_FIRMWARE)) {
            $this->raiseError(403, "Resource not available");
        }

        $this->validate($request, [
            'firmware' => 'required|file'
        ]);

        $folder = Folder::query()->whereKey(Hashids::decode($folderId))->first();
        if ($folder == null) {
            $this->raiseError(404, "Folder not found");
        }
        try {
            $folderFileName = $folder->path();
            if (!Storage::exists($folderFileName)) {
                Storage::makeDirectory($folderFileName);
            }
            DB::beginTransaction();
            $path = $request->file('program')->store($folder->path());
            $program = $folder->programs()->create([
                'name' => basename($path),
                'hash' => hash_file('sha256', Storage::disk('local')->path($path)),
                'active' => true
            ]);
            DB::commit();
            return $this->respondWithResource(new ProgramResource($program));
        } catch (\Exception  $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            $this->raiseError(500, "Cannot to make folder");
        }
    }

    public function delete(Request $request, $id)
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_PROGRAMS)) {
            $this->raiseError(403, "Resource not available");
        }

        $program = Program::query()->whereKey(Hashids::decode($id))->first();
        if ($program == null) {
            $this->raiseError(404, "Folder not found");
        }

        $fileName = $program->fileName();
        if (Storage::exists($fileName)) {
            Storage::delete($fileName);
        }

        $program->delete();
        return $this->respondWithMessage('Program deleted');
    }

    private function load(): array {
        $files = Storage::files(env('FIRMWARE_PATH'));
        $firmware = [];
        $errors = [];
        foreach ($files as $file) {
            try {
                $firmware[] = $this->parse($file);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                $errors[] = "$file is not parsable";
            }
        }

        return $firmware;
    }

    private function parse(string $file): Firmware
    {
        $xml = Storage::get($file);
        $element = new SimpleXMLElement($xml);

        return new Firmware(
            $file,
            (string) $element->fw->version,
            DateTime::createFromFormat('d/m/Y h:i:s A', (string) $element->fw->date),
            (string) $element->fw->device,
            crc32($xml)
        );
    }
}
