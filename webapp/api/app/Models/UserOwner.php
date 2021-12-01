<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property numeric id
 * @property numeric owner_id
 * @property numeric user_id
 */
class UserOwner extends Model
{
    protected $table = 'user_owner';

    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = ['owner_id', 'user_id'];
}
