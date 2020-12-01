<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource {
    private $session;

    public function __construct($user, $session) {
        parent::__construct($user);
        $this->session = $session;
    }

    public function toArray($request) {
        return [
            'privileges' => UserPrivilegesResource::collection($this->role->privileges),
            'profile' => new UserProfileResource($this->profile),
            'session' => new SessionResource($this->session)
        ];
    }
}
