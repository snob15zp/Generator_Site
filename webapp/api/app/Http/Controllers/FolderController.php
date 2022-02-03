<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Resources\FolderResource;
use App\Models\Folder;
use App\Models\FolderProgram;
use App\Models\Program;
use App\Models\User;
use App\Models\UserPrivileges;
use App\Utils\Files;
use App\Utils\HashUtils;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Vinkla\Hashids\Facades\Hashids;

class FolderController extends Controller
{
    public function getAllByUserId(Request $request, $userId): AnonymousResourceCollection
    {
        $user = $this->getUser($userId);
        if ($request->user()->cannot(UserPrivileges::VIEW_PROGRAMS, $user)) {
            throw new ApiException(ErrorStatusCodes::$RESOURCE_NOT_AVAILABLE, 403);
        }
        return FolderResource::collection($user->folders);
    }

    public function get(Request $request, $id, $hash = null): JsonResponse
    {
        if ($hash == null) {
            $user = $request->user();
        } else {
            $user = $this->getUserFromHash($hash);
        }

        $folder = $this->getFolderFromRequest($id);
        $this->canUserDownloadFolder($folder, $user);

        return $this->respondWithResource(new FolderResource($folder));
    }

    /**
     * @throws ValidationException
     */
    public function renew(Request $request, $userId, $folderId): FolderResource
    {
        $folder = $this->createFolder($request, $userId);
        $oldFolder = Folder::query()->whereKey(HashUtils::decode($folderId))->first();
        if ($oldFolder == null) {
            throw new ApiException(ErrorStatusCodes::$RESOURCE_NOT_FOUND, 404);
        }
        $programs = $oldFolder->programs()->get();
        //$oldFolder->delete();

        $folder->programs()->attach($programs);
        return new FolderResource($folder);
    }

    /**
     * @throws ValidationException
     */
    public function create(Request $request, $userId): FolderResource
    {
        $folder = $this->createFolder($request, $userId);
        return new FolderResource($folder);
    }

    /**
     * @throws ValidationException
     */
    private function createFolder(Request $request, $userId): Folder
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_PROGRAMS)) {
            throw new ApiException(ErrorStatusCodes::$RESOURCE_NOT_AVAILABLE, 403);
        }

        $this->validate($request, [
            'name' => 'required|date_format:d-m-y|after:today'
        ]);

        $user = $this->getUser($userId);
        $name = $request->input('name');

        $folder = $user->folders()->where('name', $name)->first();
        if ($folder != null) {
            throw new ApiException(ErrorStatusCodes::$FOLDER_ALREADY_EXISTS, 422);
        }

        $expiredDate = DateTime::createFromFormat('d-m-y', $name);
        $expiresIn = $expiredDate->getTimestamp() - (new DateTime())->getTimestamp();

        return $user->folders()->create([
            'name' => $request->input('name'),
            'expires_in' => $expiresIn,
            'active' => true,
            'is_encrypted' => env('ENCRYPTION_ENABLED', true)
        ]);
    }

    public function delete(Request $request, $id): JsonResponse
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_PROGRAMS)) {
            throw new ApiException(ErrorStatusCodes::$RESOURCE_NOT_AVAILABLE, 403);
        }

        $folder = Folder::query()->whereKey(HashUtils::decode($id))->first();
        if ($folder == null) {
            throw new ApiException(ErrorStatusCodes::$FOLDER_NOT_FOUND, 404);
        }
        try {
            DB::beginTransaction();
            $path = Program::folderPath($folder);
            Storage::deleteDirectory($path);
            $folder->delete();
            DB::commit();
        } catch (Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            throw new ApiException(ErrorStatusCodes::$DELETE_FOLDER_FAILED);
        }
        return $this->respondWithMessage('Folder deleted');
    }

    public function import($id, $hash = null): BinaryFileResponse
    {
        $user = $this->getUserFromHash($hash);
        return $this->createZip($user, $id, false);
    }

    public function download(Request $request, $id): BinaryFileResponse
    {
        $user = $request->user();
        return $this->createZip($user, $id, true);
    }

    public function prepareDownload(Request $request, string $id): string
    {
        $folder = $this->getFolderFromRequest($id);
        $this->canUserDownloadFolder($folder, $request->user());

        $params = $request->user()->id . ':' . Carbon::now()->addMinutes(30)->timestamp;
        return base64_encode(Crypt::encrypt($params));
    }

    private function createZip(User $user, $id, $decryptFiles): BinaryFileResponse
    {
        $folder = $this->getFolderFromRequest($id);
        $this->canUserDownloadFolder($folder, $user);

        try {
            $files = collect($folder->programs)->map(function ($program) {
                return $program->name;
            });
            Log::info($files);

            $zipFile = Files::makeZipWithFiles($folder->name, Storage::path(Program::root()), $files->all(), $decryptFiles);
            return response()->download($zipFile, $folder->name . '.zip', [
                'Content-Length' => filesize($zipFile),
                'Content-Type' => 'application/zip'
            ]);
        } catch (Exception $e) {
            $this->raiseError(500, $e->getMessage());
        }
    }

    private function getUserFromHash($hash): User
    {
        try {
            $decrypted = Crypt::decrypt(base64_decode($hash));
            list($userId, $expiredAt) = explode(":", $decrypted);
            if ($expiredAt < Carbon::now()->timestamp) {
                throw new Exception();
            }
            return User::find($userId);
        } catch (Exception $e) {
            $this->raiseError(422, "Request is not valid");
        }
    }

    private function canUserDownloadFolder(Folder $folder, User $user): bool
    {
        if ($user->cannot(UserPrivileges::VIEW_PROGRAMS, $folder->user)) {
            throw new ApiException(ErrorStatusCodes::$RESOURCE_NOT_AVAILABLE, 403);
        }
        return true;
    }

    private function getFolderFromRequest($id): Folder
    {
        $folder = Folder::query()->whereKey(HashUtils::decode($id))->first();
        if ($folder == null) {
            throw new ApiException(ErrorStatusCodes::$FOLDER_NOT_FOUND, 404);
        }
        return $folder;
    }

    private function getUser($userId): User
    {
        $user = User::query()->whereKey(HashUtils::decode($userId))->first();
        if ($user == null) {
            throw new ApiException(ErrorStatusCodes::$RESOURCE_NOT_FOUND, 404);
        }
        return $user;
    }
}
