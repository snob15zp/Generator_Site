<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource {
    private $token;

    public function __construct($user, $token) {
        parent::__construct($user);
        $this->token = $token;
    }

    public function toArray($request) {
        return [
            'privileges' => UserPrivilegesResource::collection($this->role->privileges),
            'profile' => new UserProfileResource($this->profile),
            'token' => $this->token
        ];
    }
}
