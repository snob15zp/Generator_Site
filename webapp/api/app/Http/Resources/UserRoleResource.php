<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserRoleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'name' => $this->name,
            'privileges' => UserPrivilegesResource::collection($this->privileges()->get())
        ];
    }
}
