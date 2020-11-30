<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource {
    public function toArray($request) {
        return [
            'id' => $this->id,
            'role' => new UserRoleResource($this->role()->first()),
            'profile' => new UserProfileResource($this->profile()->first())
        ];
    }
}
