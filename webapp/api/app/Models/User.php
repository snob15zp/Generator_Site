<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @property numeric $id
 * @property string $login
 * @property UserRole $role
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, HasFactory;

    protected $table = 'user';

    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = ['login', 'password', 'role', 'one_time_password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'salt'
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo('\App\Models\UserRole')->with('privileges');
    }

    public function profile(): Relation
    {
        return $this->hasOne('\App\Models\UserProfile');
    }

    public function hasRole(string $role): bool
    {
        return $this->role->name === $role;
    }

    public function folders(): Relation
    {
        return $this->hasMany('\App\Models\Folder');
    }

    public function delete(): ?bool
    {
        $this->profile()->delete();
        $this->hasMany('\App\Models\Program', 'owner_user_id')->delete();
        DB::table('user_owner')
            ->where('owner_id', '=', $this->id)
            ->orWhere('user_id', '=', $this->id)
            ->delete();
        return parent::delete();
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
