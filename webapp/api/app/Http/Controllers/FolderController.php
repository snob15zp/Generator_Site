<?php


namespace App\Http\Controllers;


use App\Http\Resources\FolderResource;
use App\Models\Folder;
use App\Models\User;
use App\Models\UserPrivileges;
use App\Models\UserProfile;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;

class FolderController extends Controller
{
    public function getAllByUserProfileId(Request $request, $userProfileId): JsonResponse
    {
        $user = $this->verifyUser($userProfileId);
        if ($request->user()->cannot(UserPrivileges::VIEW_PROGRAMS, $user)) {
            $this->raiseError(403, "Resource not available");
        }
        return $this->respondWithResource(FolderResource::collection($user->folders));
    }

    public function create(Request $request, $userProfileId): JsonResponse
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_PROGRAMS)) {
            $this->raiseError(403, "Resource not available");
        }

        $this->validate($request, [
            'name' => 'required|date_format:d-m-y|after:today'
        ]);

        $user = $this->verifyUser($userProfileId);
        $name = $request->input('name');

        try {
            $folderFileName = Folder::getFolderPath($name, $user->id);
            if (!Storage::exists($folderFileName)) {
                Storage::makeDirectory($folderFileName);
            }
        } catch (\RunTimeException  $e) {
            Log::error($e->getMessage());
            $this->raiseError(500, "Cannot to make folder");
        }

        $folder = $user->folders()->where('name', $name)->first();
        if ($folder != null) {
            $this->raiseError(422, "Folder already exits");
        }

        $expiredDate = DateTime::createFromFormat('d-m-y', $name);
        $expiresIn = $expiredDate->getTimestamp() - (new DateTime())->getTimestamp();

        $folder = $user->folders()->create([
            'name' => $request->input('name'),
            'expires_in' => $expiresIn,
            'active' => true
        ]);
        return $this->respondWithResource(new FolderResource($folder));
    }

    public function delete(Request $request, $id): JsonResponse
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_PROGRAMS)) {
            $this->raiseError(403, "Resource not available");
        }

        $folder = Folder::query()->whereKey(Hashids::decode($id))->first();
        if ($folder == null) {
            $this->raiseError(404, "Folder not found");
        }

        $folderFileName = $folder->path();

        $folder->programs()->delete();
        $folder->delete();

        if (Storage::exists($folderFileName)) {
            Storage::deleteDirectory($folderFileName);
        }
        return $this->respondWithMessage('Folder deleted');
    }

    private function verifyUser($userProfileId)
    {
        $userProfile = UserProfile::query()->whereKey(Hashids::decode($userProfileId))->first();
        if ($userProfile == null) {
            $this->raiseError(404, 'Resource not found');
        }

        return $userProfile->user;
    }
}
