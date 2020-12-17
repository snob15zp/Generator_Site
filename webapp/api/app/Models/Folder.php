<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    protected $table = 'folder';

    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = ['name', 'expires_in', 'active'];

    public function user()
    {
        return $this->belongsTo('\App\Models\User');
    }

    public function programs()
    {
        return $this->hasMany('\App\Models\Program');
    }

    public function path()
    {
        return Folder::getFolderPath($this->name, $this->user->id);
    }


    public static function getFolderPath($name, $userId)
    {
        return env('PROGRAM_PATH') . '/' . $userId . '/' . $name;
    }
}
