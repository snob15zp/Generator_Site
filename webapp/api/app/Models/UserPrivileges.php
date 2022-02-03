<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class UserPrivileges extends Model
{
    const ATTACH_PROGRAMS = 'attach-programs';
    const UPLOAD_PROGRAMS = 'upload-programs';
    const DOWNLOAD_PROGRAMS = 'download-programs';
    const IMPORT_PROGRAMS = 'import-programs';
    const MANAGE_PROGRAMS = 'manage-programs';
    const MANAGE_FIRMWARE = 'manage-firmware';
    const MANAGE_SOFTWARE = 'manage-software';
    const MANAGE_USERS = 'manage-users';
    const MANAGE_OWN_USERS = 'manage-own-users';
    const VIEW_USERS = 'view-users';
    const VIEW_PROFILE = 'view-profile';
    const VIEW_PROGRAMS = 'view-programs';

    static function names(): Collection
    {
        return collect([
            UserPrivileges::ATTACH_PROGRAMS,
            UserPrivileges::UPLOAD_PROGRAMS,
            UserPrivileges::MANAGE_PROGRAMS,
            UserPrivileges::IMPORT_PROGRAMS,
            UserPrivileges::DOWNLOAD_PROGRAMS,
            UserPrivileges::MANAGE_FIRMWARE,
            UserPrivileges::MANAGE_SOFTWARE,
            UserPrivileges::MANAGE_USERS,
            UserPrivileges::MANAGE_OWN_USERS,
            UserPrivileges::VIEW_USERS,
            UserPrivileges::VIEW_PROFILE,
            UserPrivileges::VIEW_PROGRAMS
        ]);
    }

    protected $table = 'user_privileges';
    public $timestamps = false;

    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = ['name'];

    public function userRole()
    {
        return $this->belongsTo('\App\Model\UserRole');
    }
}
