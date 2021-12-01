<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class UserRole
 * @property integer $id
 * @property string $name
 * @property array privileges
 * @package App\Models
 */
class UserRole extends Model
{
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_PROFESSIONAL = 'ROLE_PROFESSIONAL';
    const ROLE_SUPER_PROFESSIONAL = 'ROLE_SUPER_PROFESSIONAL';
    const ROLE_USER = 'ROLE_USER';
    const ROLE_GUEST = 'ROLE_GUEST';

    protected $table = 'user_role';
    public $timestamps = false;

    protected $primaryKey = 'id';

    public static function rolePriority(string $role): int
    {
        switch ($role) {
            case UserRole::ROLE_ADMIN:
                return 0;
            case UserRole::ROLE_PROFESSIONAL:
            case UserRole::ROLE_SUPER_PROFESSIONAL:
                return 1;
            case UserRole::ROLE_USER:
                return 2;
            default:
                return -1;
        }
    }

    public static function compare(string $role1, string $role2): int
    {
        $p1 = self::rolePriority($role1);
        $p2 = self::rolePriority($role2);
        return $p1 < $p2 ? -1 : $p1 > $p2 ? 1 : 0;
    }

    public function privileges(): HasMany
    {
        return $this->hasMany('\App\Models\UserPrivileges');
    }

    public function users(): HasMany
    {
        return $this->hasMany('\App\Models\User');
    }

}
