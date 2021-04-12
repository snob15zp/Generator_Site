<?php


namespace App\Http\Controllers;


use App\Http\Resources\ProgramResource;
use App\Models\Folder;
use App\Models\Program;
use App\Models\UserPrivileges;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;

class ProgramController extends Controller
{
    private const IS_ENCRYPTION_ENABLED = false;
    private const KEY = "\x3a\xf5\x4c\x68\xaa\x0a\x65\xf2\xb2\x2f\xd5\x33\x05\xb9\xad\x96";
    private const IV = "\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0\x0";
    private const CYPHER = "AES-128-CBC";

    public function getAll(Request $request, $folderId): JsonResponse
    {
        $folder = Folder::query()->whereKey(Hashids::decode($folderId))->first();
        if ($request->user()->cannot(UserPrivileges::VIEW_PROGRAMS, $folder->user)) {
            $this->raiseError(403, "Resource not available");
        }

        $programs = $this->synchronizeWithDisk($folder, $folder->programs);
        return $this->respondWithResource(ProgramResource::collection($programs));
    }

    public function download(Request $request, $id)
    {
        $program = Program::query()->whereKey(Hashids::decode($id))->first();
        if ($program == null) {
            $this->raiseError(404, 'Program not found');
        }

        if ($request->user()->cannot(UserPrivileges::VIEW_PROGRAMS, $program->folder->user)) {
            $this->raiseError(403, "Resource not available");
        }

        return Storage::download($program->fileName());
    }

    public function create(Request $request, $folderId): JsonResponse
    {
        $this->verifyUserPrivileges($request);

        $this->validate($request, [
            'programs' => 'required|array',
            'programs.*' => 'required|file'
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
            $programFiles = $request->file('programs');
            $programs = [];

            collect($programFiles)->each(function ($requestFile) use ($folder) {
                $name = $requestFile->getClientOriginalName();

                $content = $requestFile->get();
                if (ProgramController::IS_ENCRYPTION_ENABLED) {
                    $content = openssl_encrypt($content, ProgramController::CYPHER, ProgramController::KEY, 0, ProgramController::IV);
                }
                Storage::put($folder->path() . "/$name", $content);
                $programs[] = $folder->programs()->create([
                    'name' => $name,
                    'hash' => crc32($content),
                    'is_encrypted' => ProgramController::IS_ENCRYPTION_ENABLED,
                    'active' => true
                ]);
            });
            DB::commit();
            return $this->respondWithResource(ProgramResource::collection($programs));
        } catch (\Exception  $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            $this->raiseError(500, "Cannot to make folder");
        }
    }

    public function delete(Request $request, $id): JsonResponse
    {
        $this->verifyUserPrivileges($request);

        $program = Program::query()->whereKey(Hashids::decode($id))->first();
        if ($program == null) {
            $this->raiseError(404, "Folder not found");
        }

        $this->deleteProgram($program);

        return $this->respondWithMessage('Program deleted');
    }

    public function deleteAll(Request $request): JsonResponse
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_PROGRAMS)) {
            $this->raiseError(403, 'Operation is restricted');
        }

        $this->validate($request, [
            'ids' => 'required|array|min:1'
        ]);

        $ids = collect($request->input('ids'))->map(function ($id) {
            return Hashids::decode($id)[0];
        });
        Program::query()->findMany($ids)->each(function ($program) {
            $this->deleteProgram($program);
        });

        return $this->respondWithMessage();
    }

    private function deleteProgram(Program $program)
    {
        $fileName = $program->fileName();
        if (Storage::exists($fileName)) {
            Storage::delete($fileName);
        }

        $program->delete();
    }

    private function synchronizeWithDisk(Folder $folder, Collection $programs): Collection
    {
        $existsPrograms = collect($programs)->filter(function ($program) use ($folder) {
            return Storage::exists($folder->path() . '/' . $program->name);
        });

        $notExistsProgramIds = collect($programs)->filter(function ($program) use ($folder) {
            return !Storage::exists($folder->path() . '/' . $program->name);
        })->map(function ($program) {
            return $program->id;
        });
        Program::whereIn('id', $notExistsProgramIds)->delete();

        return $existsPrograms;
    }

    private function verifyUserPrivileges(Request $request)
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_PROGRAMS)) {
            $this->raiseError(403, "Resource not available");
        }
    }
}
