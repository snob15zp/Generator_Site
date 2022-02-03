<?php

namespace App\Http\Resources;

use App\Utils\HashUtils;
use Illuminate\Http\Resources\Json\JsonResource;
use Vinkla\Hashids\Facades\Hashids;

/**
 * @property integer id
 * @property string version
 * @property mixed created_at
 * @property mixed active
 * @method fileUrl()
 */
class SoftwareResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => HashUtils::encode($this->id),
            'active' => $this->active,
            'version' => $this->version,
            'file' => $this->file,
            'fileUrl' => $this->fileUrl(),
            'created_at' => $this->created_at
        ];
    }
}
