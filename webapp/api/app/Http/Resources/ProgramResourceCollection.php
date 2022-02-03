<?php

namespace App\Http\Resources;

use App\Models\User;
use DateTime;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProgramResourceCollection extends ResourceCollection
{
    private $owner;

    public function owner(User $owner): ProgramResourceCollection
    {
        $this->owner = $owner;
        return $this;
    }

    public function toArray($request): array
    {
        return $this->collection->map(function (ProgramResource $resource) use ($request) {
            return $resource->owner($this->owner)->toArray($request);
        })->all();
    }
}
