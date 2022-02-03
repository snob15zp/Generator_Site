<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    protected $fillable = ['login', 'password', 'role_id', 'one_time_password'];

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
        return $this->belongsTo(UserRole::class)->with('privileges');
    }

    public function profile(): Relation
    {
        return $this->hasOne(UserProfile::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->role->name === $role;
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, UserOwner::class, 'user_id', 'owner_id');
    }

    public function folders(): Relation
    {
        return $this->hasMany(Folder::class);
    }

    public function programs(): Relation
    {
        return $this->hasMany(Program::class, 'user_owner_id');
    }

    public function delete(): ?bool
    {
        UserOwner::query()
            ->where('owner_id', '=', $this->id)
            ->orWhere('user_id', '=', $this->id)
            ->delete();

        $this->folders()->get()->each(function ($folder) {
            $folder->delete();
        });
        $this->programs()->get()->each(function ($program) {
            $program->delete();
        });

        $this->profile()->delete();
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
