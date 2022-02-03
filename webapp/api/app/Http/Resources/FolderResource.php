<?php

namespace App\Http\Resources;

use App\Utils\HashUtils;
use Illuminate\Http\Resources\Json\JsonResource;
use Vinkla\Hashids\Facades\Hashids;

/**
 * @property integer id
 * @property string name
 * @property mixed expires_in
 * @property mixed created_at
 * @property bool is_encrypted
 * @property bool $active
 */
class FolderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => HashUtils::encode($this->id),
            'name' => $this->name,
            'expires_in' => $this->expires_in,
            'created_at' => $this->created_at,
            'is_encrypted' => $this->is_encrypted != 0,
            'active'=>$this->active
        ];
    }
}
