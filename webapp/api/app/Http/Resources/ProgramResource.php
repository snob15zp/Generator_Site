<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Vinkla\Hashids\Facades\Hashids;

/**
 * @property integer id
 * @property string name
 * @property string hash
 * @property mixed created_at
 * @property boolean is_encrypted
 */
class ProgramResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => Hashids::encode($this->id),
            'name' => $this->name,
            'hash' => $this->hash,
            'is_encrypted' => $this->is_encrypted,
            'created_at' => $this->created_at,
        ];
    }
}
