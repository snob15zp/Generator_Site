<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Folder folder
 * @property string name
 */
class Program extends Model
{
    protected $table = 'program';

    protected $primaryKey = 'id';
    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = ['name', 'hash', 'active', 'owner_user_id'];

    public function folders(): BelongsToMany
    {
        return $this->belongsToMany(Folder::class, 'folder_program');
    }

    public function fileName(): string
    {
        return Program::path() . '/' . $this->name;
    }

    public static function path()
    {
        return env('PROGRAM_PATH');
    }
}
