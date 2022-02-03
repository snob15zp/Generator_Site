<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @property string name
 * @property numeric $id
 */
class ProgramHistory extends Model
{
    protected $table = 'program_history';

    protected $primaryKey = 'id';
    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = ['name', 'user_owner_id'];

    public function owner(): Relation
    {
        return $this->belongsTo(User::class, 'user_owner_id');
    }
}
