<?php

namespace App\Http\Resources;

use App\Models\UserProfile;
use App\Models\UserRole;
use App\Utils\HashUtils;
use Illuminate\Http\Resources\Json\JsonResource;
use Vinkla\Hashids\Facades\Hashids;

/**
 * @property integer id
 * @property UserRole role
 * @property UserProfile profile
 */
class UserLoginResource extends JsonResource
{
    private $token;

    public function __construct($user, $token)
    {
        parent::__construct($user);
        $this->token = $token;
    }

    public function toArray($request): array
    {
        return collect([
            'id' => HashUtils::encode($this->id),
            'privileges' => UserPrivilegesResource::collection($this->role->privileges),
            'profile' => new UserProfileResource($this->profile),
            'token' => $this->token,
            'role' => $this->role->name
        ])->filter(function ($value, $key) {
            return $value !== null;
        })->all();
    }
}
