<?php


namespace App\Http\Controllers;


use App\Http\Resources\ProgramResource;
use App\Models\Folder;
use App\Models\User;
use App\Models\UserPrivileges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;

class ProgramController extends Controller
{
    public function create(Request $request, $folderId)
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_PROGRAMS)) {
            $this->raiseError(403, "Resource not available");
        }

        $this->validate($request, [
            'program' => 'required|file'
        ]);

        $folder = Folder::query()->whereKey(Hashids::decode($folderId))->first();
        if ($folder == null) {
            $this->raiseError(404, "Folder not found");
        }

        $path = $request->file('program')->store($folder->path());
        $program = $folder->programs()->create([
            'name' => basename($path),
            'hash' => hash_file('sha256', $path),
            'active' => true
        ]);
        return $this->respondWithResource(new ProgramResource($program));
    }

    public function delete(Request $request, $id)
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_PROGRAMS)) {
            $this->raiseError(403, "Resource not available");
        }

        $folder = Folder::query()->whereKey(Hashids::decode($id))->first();
        if ($folder == null) {
            $this->raiseError(404, "Folder not found");
        }

        $folder->programs()->delete();
        $folder->delete();

        $folderFileName = $this->getFolderFileName($folder->name, $folder->user->id);
        if (Storage::exists($folderFileName)) {
            Storage::deleteDirectory($folderFileName);
        }
        return $this->respondWithMessage('Folder deleted');
    }

    private function getFolderFileName($name, $userId)
    {
        return env('PROGRAMS_PATH') . '/' . $userId . '/' . $name;
    }

    private function verifyUser($userId)
    {
        $user = User::query()->whereKey(Hashids::decode($userId))->first();
        if ($user == null) {
            $this->raiseError(404, 'Resource not found');
        }

        return $user;
    }
}
