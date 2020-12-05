<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Vinkla\Hashids\Facades\Hashids;

class FolderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => Hashids::encode($this->id),
            'name' => $this->name,
            'expires_in' => $this->expires_in,
            'created_at' => $this->created_at
        ];
    }
}
