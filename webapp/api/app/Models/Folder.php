<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string name
 * @property bool is_encrypted
 * @property numeric $id
 */
class Folder extends Model
{
    protected $table = 'folder';

    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = ['name', 'expires_in', 'active', 'is_encrypted'];

    public function user(): BelongsTo
    {
        return $this->belongsTo('\App\Models\User');
    }
}
