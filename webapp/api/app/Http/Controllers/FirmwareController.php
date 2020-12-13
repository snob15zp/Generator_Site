<?php


namespace App\Http\Controllers;


use App\Http\Resources\ProgramResource;
use App\Models\Folder;
use App\Models\Program;
use App\Models\UserPrivileges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;

class FirmwareController extends Controller
{
    public function getAll(Request $request)
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_FIRMWARE)) {
            $this->raiseError(403, "Resource not available");
        }
        $files = Storage::files(env('FIRMWARE_PATH'));
        return $files;
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
}
