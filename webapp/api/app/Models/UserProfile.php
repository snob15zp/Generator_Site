<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $table = 'user_profile';

    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = ['name', 'surname', 'address', 'phone_number', 'email', 'date_of_birth'];


    public function user()
    {
        return $this->belongsTo('\App\Models\User');
    }


}
