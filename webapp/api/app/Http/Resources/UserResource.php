<?php

namespace App\Http\Resources;

use App\Models\UserProfile;
use App\Models\UserRole;
use App\Utils\HashUtils;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;

/**
 * @property integer id
 * @property UserRole role
 * @property UserProfile profile
 * @property mixed $owners
 */
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => HashUtils::encode($this->id),
            'profile' => new UserProfileResource($this->profile),
            'role' => $this->role->name,
            'owner' => $this->owners != null ? UserResource::collection($this->owners) : null
        ];
    }
}
