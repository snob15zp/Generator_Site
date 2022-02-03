<?php

namespace App\Http\Resources;

use App\Utils\HashUtils;
use Illuminate\Http\Resources\Json\JsonResource;
use Vinkla\Hashids\Facades\Hashids;

/**
 * @property mixed $id
 * @property mixed $name
 * @property mixed $surname
 */
class SimpleUserProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => HashUtils::encode($this->id),
            'name' => $this->name,
            'surname' => $this->surname,
        ];
    }
}
