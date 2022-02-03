<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * @property Folder folder
 * @property string name
 * @property numeric $id
 */
class Program
{
    public $id;
    public $name;
    public $createdAt;
    public $path;

    public function __construct(string $name, string $path, $createdAt)
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR);
        $this->name = $name;
        $this->createdAt = $createdAt;

        $this->id = crc32($this->path . DIRECTORY_SEPARATOR . $this->name);
    }

    public static function root(): string
    {
        return env('PROGRAM_PATH');
    }

    public static function userPath(User $user): string
    {
        if ($user->hasRole(UserRole::ROLE_ADMIN)) {
            return self::root() . DIRECTORY_SEPARATOR . 'main';
        } else {
            return self::root() . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $user->id;
        }
    }

    public static function folderPath(Folder $folder): string
    {
        return self::root() . DIRECTORY_SEPARATOR . 'folders' . DIRECTORY_SEPARATOR . $folder->id;
    }

    public static function mainFolderPath(): string
    {
        return self::root() . DIRECTORY_SEPARATOR . 'main';
    }


    public static function mainList(): Collection
    {
        return self::fetchAllByPath(self::mainFolderPath());
    }

    public static function fetchAllFromFolder(Folder $folder): Collection
    {
        $path = self::folderPath($folder);
        if ($path == null) return collect([]);

        return self::fetchAllByPath($path);
    }

    public static function fetchAllForUser(User $owner): Collection
    {
        $path = self::userPath($owner);
        if ($path == null) return collect([]);

        return self::fetchAllByPath($path);
    }

    public static function fetchAllByPath($path): Collection
    {
        return collect(Storage::files($path))->map(function ($fileName) use ($path) {
            $lastModified = new \DateTime();
            $lastModified->setTimestamp(Storage::lastModified($fileName));

            return new Program(
                basename($fileName),
                $path,
                $lastModified
            );
        });
    }

    public function save($content)
    {
        if (!Storage::exists($this->path)) Storage::makeDirectory($this->path);
        Storage::put($this->fileName(), $content);
    }

    public function fileName(): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->name;
    }
}

//class Program extends Model
//{
//    protected $table = 'program';
//
//    protected $primaryKey = 'id';
//    public $incrementing = true;
//
//    public $timestamps = true;
//
//    protected $fillable = ['name', 'user_owner_id'];
//
//    public function folders(): Relation
//    {
//        return $this->belongsToMany(Folder::class, 'folder_program');
//    }
//
//    public function owner(): Relation
//    {
//        return $this->belongsTo(User::class, 'user_owner_id');
//    }
//
//    public function fileName(): string
//    {
//        return Program::path() . '/' . $this->name;
//    }
//
//    public static function path()
//    {
//        return env('PROGRAM_PATH');
//    }
//
//    public function delete(): ?bool
//    {
//        $fileName = $this->fileName();
//        if (Storage::exists($fileName)) {
//            Storage::delete($fileName);
//        }
//        FolderProgram::query()->where('program_id', '=', $this->id)->delete();
//        return parent::delete();
//    }
//}
