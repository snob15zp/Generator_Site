<?php

namespace App\Http\Resources;

use App\Models\UserRole;
use App\Utils\HashUtils;
use Illuminate\Http\Resources\Json\JsonResource;
use Vinkla\Hashids\Facades\Hashids;

/**
 * @property mixed $id
 * @property mixed $profile
 * @property UserRole $role
 */
class SimpleUserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => HashUtils::encode($this->id),
            'role' => $this->role->name,
            'profile' => new SimpleUserProfileResource($this->profile)
        ];
    }
}
