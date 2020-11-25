<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class UserPrivileges extends Model
{
    protected $table = 'user_privileges';
    public $timestamps = false;

    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = ['name'];

    public function userRole() {
        return $this->belongsTo('\App\Model\UserRole');
    }
}
