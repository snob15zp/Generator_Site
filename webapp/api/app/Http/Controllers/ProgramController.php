<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Resources\ProgramResource;
use App\Models\Folder;
use App\Models\Program;
use App\Models\User;
use App\Models\UserPrivileges;
use App\Models\UserRole;
use App\Utils\Files;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Vinkla\Hashids\Facades\Hashids;
use const App\Exceptions\ERROR_DUPLICATE_PROGRAM;
use const App\Exceptions\ERROR_SQL_EXCEPTION;

class ProgramController extends Controller
{
    public function getAll(Request $request, $folderId): JsonResponse
    {
        if ($folderId != null) {
            $folder = Folder::query()->whereKey(Hashids::decode($folderId))->first();
            if ($request->user()->cannot(UserPrivileges::VIEW_PROGRAMS, $folder->user)) {
                $this->raiseError(403, "Resource not available");
            }
            $programs = $this->synchronizeWithDisk($folder, $folder->programs);
        } else {
            $user = $request->user();
            if ($user->cannot(UserPrivileges::MANAGE_PROGRAMS))
                $programs = [];
        }
        return $this->respondWithResource(ProgramResource::collection($programs));
    }

    public function getAllForUser(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Program::query();
        if (!$user->hasRole(UserRole::ROLE_ADMIN)) {
            $query
                ->select(['program.*', 'folder.user_id', 'folder.active'])
                ->join('folder_program', 'folder_program.program_id', '=', 'program.id')
                ->join('folder', 'folder.id', '=', 'folder_program.folder_id')
                ->where('folder.user_id', '=', $user->id)
                ->where('folder.active', '=', true);
        }
        return $this->respondWithResource(ProgramResource::collection($query->get()));
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

    /**
     * @throws ApiException
     * @throws ValidationException
     */
    public function create(Request $request): JsonResponse
    {
        return $this->createWithFolder($request, null);
    }

    /**
     * @throws ApiException
     * @throws ValidationException
     */
    public function createWithFolder(Request $request, $folderId): JsonResponse
    {
        $this->verifyUserPrivileges($request);

        $this->validate($request, [
            'programs' => 'required|array',
            'programs.*' => 'required|file',
            'encrypted' => 'nullable|boolean'
        ]);

        if ($folderId != null) {
            $folder = Folder::query()->whereKey(Hashids::decode($folderId))->first();
            if ($folder == null) {
                $this->raiseError(404, "Folder not found");
            }
        } else {
            $folder = null;
        }

        $user = $request->user();
        $encrypted = $request->input('encrypted');
        if ($encrypted == null) $encrypted = false;

        $programs = collect();
        try {
            DB::beginTransaction();
            $programFiles = $request->file('programs');
            $programs = collect($programFiles)->map(function ($requestFile) use ($encrypted, $user, $folder) {
                $name = $requestFile->getClientOriginalName();

                $content = $requestFile->get();
                if (!$encrypted) {
                    $content = Files::encryptData($content);
                }

                $program = new Program([
                    'owner_user_id' => $user->id,
                    'name' => $name,
                    'hash' => crc32($content),
                    'active' => true
                ]);
                $program->save();
                Storage::put($program->fileName(), $content);

                if ($folder != null) {
                    $program->folders()->attach($folder->id);
                }
                return $program;
            });
            DB::commit();
            return $this->respondWithResource(ProgramResource::collection($programs));
        } catch (\Exception  $e) {
            DB::rollBack();
            $programs->each(function (Program $program) {
                $fileName = $program->fileName();
                if (Storage::exists($fileName)) {
                    Storage::delete($fileName);
                }
            });
            Log::error($e->getMessage());
            if ($e instanceof QueryException) {
                switch ($e->getCode()) {
                    case 23000:
                        throw new ApiException(ERROR_DUPLICATE_PROGRAM);
                    default:
                        throw new ApiException(ERROR_SQL_EXCEPTION);
                }
            } else {
                $this->raiseError(500, "Cannot to make folder");
            }
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
            return Storage::exists(Program::path() . '/' . $program->name);
        });

        $notExistsProgramIds = collect($programs)->filter(function ($program) use ($folder) {
            return !Storage::exists(Program::path() . '/' . $program->name);
        })->map(function ($program) {
            return $program->id;
        });
        Program::query()->whereIn('id', $notExistsProgramIds)->delete();

        return $existsPrograms;
    }

    private function verifyUserPrivileges(Request $request)
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_PROGRAMS)) {
            $this->raiseError(403, "Resource not available");
        }
    }
}
