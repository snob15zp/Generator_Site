<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserRole
 * @package App\Models
 */
class UserRole extends Model
{
    protected $table = 'user_role';
    public $timestamps = false;

    protected $primaryKey = 'id';

    public function privileges()
    {
        return $this->hasMany('\App\Models\UserPrivileges');
    }

    public function users()
    {
        return $this->hasMany('\App\Models\User');
    }

}
