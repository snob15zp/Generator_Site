<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property numeric id
 * @property numeric program_id
 * @property numeric folder_id
 */
class FolderProgram extends Model
{
    protected $table = 'folder_program';

    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = ['program_id', 'folder_id'];
}
