<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $table = 'program';

    protected $primaryKey = 'id';
    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = ['name', 'hash', 'active'];

    public function folder()
    {
        return $this->belongsTo('\App\Models\Folder');
    }
}
