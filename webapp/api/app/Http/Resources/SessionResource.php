<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource {
    public function toArray($request) {
        return [
            'token' => $this->token,
            'expires_in' => $this->expires_in,
            'created_at' => $this->created_at,
        ];
    }
}
