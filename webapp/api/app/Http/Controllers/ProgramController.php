<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\UploadFilesException;
use App\Http\Resources\ProgramResource;
use App\Http\Resources\ProgramResourceCollection;
use App\Models\Folder;
use App\Models\Program;
use App\Models\ProgramHistory;
use App\Models\UserPrivileges;
use App\Utils\Files;
use App\Utils\HashUtils;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use SebastianBergmann\Diff\Utils\FileUtils;

class ProgramController extends Controller
{
    public function migrate(Request $request)
    {
        collect(Storage::allFiles("programs"))->each(function ($file) {
            if (str_ends_with($file, ".txt")) {
                preg_match_all("/programs\/(\d+)\/([^\/]+)\/(.*)/", $file, $matches);
                $userId = $matches[1][0];
                $folderName = $matches[2][0];
                $programName = $matches[3][0];
                $folder = Folder::query()
                    ->where('user_id', '=', $userId)
                    ->where('name', '=', $folderName)
                    ->first();
                if ($folder != null) {
                    $path = Program::folderPath($folder);
                    if (!Storage::exists($path)) Storage::makeDirectory($path);
                    $to = $path . DIRECTORY_SEPARATOR . $programName;
                    Storage::copy($file, $to);
                    if (!$folder->is_encrypted) {
                        $fullPath = Storage::disk('local')->path($to);
                        $data = Files::encryptData(file_get_contents($fullPath));
                        file_put_contents($fullPath, $data);

                    }
                }
            }
        });
        return "OK";
    }

    public function getAllForFolder(Request $request, $folderId): JsonResponse
    {
        $folder = $this->getFolderById($folderId);
        $user = $request->user();
        if ($user->cannot(UserPrivileges::MANAGE_PROGRAMS) && $user->id != $folder->user->id) {
            throw new ApiException(ErrorStatusCodes::$RESOURCE_NOT_AVAILABLE);
        }
        $this->verifyUserPrivileges($folder->user, UserPrivileges::VIEW_PROGRAMS);

        $programs = Program::fetchAllFromFolder($folder);
        return $this->respondWithResource(ProgramResource::collection($programs));
    }

    public function getAllForUser(Request $request, string $id): ResourceCollection
    {
        $user = $request->user();
        $this->verifyUserPrivileges($user, UserPrivileges::MANAGE_PROGRAMS);

        $owner = $this->getUserById($id);
        $programs = Program::fetchAllForUser($owner);
        return (new ProgramResourceCollection($programs))->owner($owner);
    }


    /**
     * @throws ApiException
     * @throws ValidationException
     * @throws FileNotFoundException
     */
    public function createForUser(Request $request, string $id): ResourceCollection
    {
        $user = $this->getUserById($id);
        return $this->uploadAndCreatePrograms($request, Program::userPath($user));
    }

    /**
     * @throws ApiException
     * @throws ValidationException
     * @throws FileNotFoundException
     */
    public function createForFolder(Request $request, $folderId): ResourceCollection
    {
        $folder = Folder::query()->whereKey(HashUtils::decode($folderId))->first();
        if ($folder == null) {
            throw new ApiException(ErrorStatusCodes::$FOLDER_NOT_FOUND);
        }
        return $this->uploadAndCreatePrograms($request, Program::folderPath($folder));
    }

    /**
     * @throws ValidationException
     */
    public function attachToFolderFromList(Request $request, $folderId): ResourceCollection
    {
        $folder = $this->getFolderById($folderId);
        $programs = $this->attachPrograms($request, Program::folderPath($folder));
        return ProgramResource::collection($programs);
    }

    /**
     * @throws ValidationException
     */
    public function attachToUserFromList(Request $request, $id): ResourceCollection
    {
        $user = $this->getUserById($id);
        $programs = $this->attachPrograms($request, Program::userPath($user));
        return ProgramResource::collection($programs);
    }

    /**
     * @throws ValidationException
     */
    private function attachPrograms(Request $request, $path): array
    {
        $user = $request->user();
        $this->verifyUserPrivileges($user, UserPrivileges::MANAGE_PROGRAMS);
        $this->validate($request, [
            'programs' => 'required|array'
        ]);

        $ids = collect($request->get('programs'));

        $allPrograms = Program::fetchAllForUser($user);
        $existsPrograms = [];
        $programs = [];

        $copyPrograms = $allPrograms->filter(function ($p) use ($ids) {
            return $ids->contains($p->id);
        })->all();

        foreach ($copyPrograms as $program) {
            $fileName = $path . DIRECTORY_SEPARATOR . $program->name;
            if (Storage::exists($fileName)) {
                $existsPrograms[] = $program;
                continue;
            }
            Storage::copy($program->path . DIRECTORY_SEPARATOR . $program->name, $fileName);
            $programs[] = new Program($program->name, $path, new \DateTime());
        }

        if (count($existsPrograms) > 0) {
            throw new UploadFilesException($existsPrograms, []);
        }
        return $programs;
    }

    public function getProgramsHistory(Request $request, $id): ResourceCollection
    {
        $this->verifyUserPrivileges($request->user(), UserPrivileges::UPLOAD_PROGRAMS);

        $user = $this->getUserById($id);
        $history = ProgramHistory::query()->where('user_owner_id', $user->id)->get();
        return ProgramResource::collection($history);
    }

    /**
     * @throws ApiException
     * @throws ValidationException
     */
    private function uploadAndCreatePrograms(Request $request, string $path): ResourceCollection
    {
        $user = $request->user();
        $this->verifyUserPrivileges($user, UserPrivileges::UPLOAD_PROGRAMS);

        $this->validate($request, [
            'programs' => 'required|array',
            'programs.*' => 'required|file',
            'encrypted' => 'nullable|boolean'
        ]);

        $encrypted = $request->input('encrypted');
        if ($encrypted == null) $encrypted = false;

        $programFiles = $request->file('programs');
        $allPrograms = Program::fetchAllByPath($path);

        $programs = [];
        $programNames = collect();
        $existsPrograms = [];
        $failedPrograms = [];

        foreach ($programFiles as $requestFile) {
            try {
                $name = $requestFile->getClientOriginalName();
                $program = new Program($name, $path, new \DateTime());
                if ($allPrograms->contains('name', $program->name)) {
                    //$existsPrograms[] = $program;
                    //continue;
                    $program->delete();
                }

                $content = $requestFile->get();
                if (!$encrypted) {
                    $content = Files::encryptData($content);
                }
                $program->save($content);
                $programNames->add($program->name);
                $programs[] = $program;
            } catch (Exception  $e) {
                Log::error($e->getMessage());
                $failedPrograms[] = $program;
            }
        }

        $programHistory = ProgramHistory::query()
            ->where('user_owner_id', $user->id)
            ->whereIn('name', $programNames->all())
            ->get()
            ->map(function ($p) {
                return $p->name;
            });

        $programNames->diff($programHistory)->each(function ($name) use ($user) {
            ProgramHistory::create([
                'name' => $name,
                'user_owner_id' => $user->id
            ]);
        });

        if (count($failedPrograms) > 0 || count($existsPrograms) > 0) {
            throw new UploadFilesException($existsPrograms, $failedPrograms);
        }

        return ProgramResource::collection($programs);
    }

    /**
     * @throws ValidationException
     */
    public function deleteForUser(Request $request, $id): JsonResponse
    {
        $this->verifyUserPrivileges($request->user(), UserPrivileges::UPLOAD_PROGRAMS);

        $owner = $this->getUserById($id);
        $this->deleteAllByPath($request, Program::userPath($owner));
        return $this->respondWithMessage("OK");
    }

    /**
     * @throws ValidationException
     */
    public function deleteFromFolder(Request $request, $folderId): JsonResponse
    {
        $this->verifyUserPrivileges($request->user(), UserPrivileges::MANAGE_PROGRAMS);

        $folder = $this->getFolderById($folderId);
        $this->deleteAllByPath($request, Program::folderPath($folder));
        return $this->respondWithMessage("OK");
    }

    /**
     * @throws ValidationException
     */
    private function deleteAllByPath(Request $request, string $path)
    {
        $this->validate($request, [
            'ids' => 'required|array|min:1'
        ]);
        $ids = collect($request->input('ids'));
        Program::fetchAllByPath($path)->each(function ($program) use ($ids) {
            if ($ids->contains($program->id)) {
                Log::info("Delete program {$program->fileName()}");
                Storage::delete($program->fileName());
            }
        });
    }

    private function getFolderById($encodedFolderId): Folder
    {
        $folder = Folder::query()->whereKey(HashUtils::decode($encodedFolderId))->first();
        if ($folder == null) {
            throw new ApiException(ErrorStatusCodes::$FOLDER_NOT_FOUND);
        }

        return $folder;
    }
}
