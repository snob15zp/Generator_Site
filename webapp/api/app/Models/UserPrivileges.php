<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class UserPrivileges extends Model
{
    const UPLOAD_PROGRAMS = 'upload-programs';
    const CREATE_USER = 'create-user';
    const MANAGE_PROGRAMS = 'manage-programs';
    const MANAGE_FIRMWARE = 'manage-firmware';
    const VIEW_USERS = 'view-users';
    const VIEW_PROFILE = 'view-profile';
    const VIEW_PROGRAMS = 'view-programs';

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
