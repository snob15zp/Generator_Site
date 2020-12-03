<?php


namespace App\Http\Controllers;


use App\Http\Resources\ProgramResource;
use App\Models\Folder;
use App\Models\Program;
use App\Models\UserPrivileges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;

class ProgramController extends Controller
{
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
}
