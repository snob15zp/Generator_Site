<?php

namespace App\Http\Resources;

use App\Models\User;
use DateTime;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property integer id
 * @property string name
 * @property DateTime $createdAt
 */
class ProgramResource extends JsonResource
{
    private $owner;

    public function owner(User $owner): ProgramResource
    {
        $this->owner = $owner;
        return $this;
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->createdAt->format("c"),
            'owner' => $this->owner != null ? new SimpleUserResource($this->owner) : null
        ];
    }
}
