<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserRole
 * @package App\Models
 */
class UserRole extends Model
{
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_USER = 'ROLE_USER';
    const ROLE_GUEST = 'ROLE_GUEST';

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
