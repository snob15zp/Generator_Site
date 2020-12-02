<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Vinkla\Hashids\Facades\Hashids;

class UserProfileResource extends JsonResource
{

    public function toArray($request)
    {
        $collection = collect([
            'id' => Hashids::encode($this->id),
            'user_id' => Hashids::encode($this->user->id),
            'name' => $this->name,
            'surname' => $this->surname,
            'email' => $this->email,
            'address' => $this->address,
            'phone_number' => $this->phone_number,
            'date_of_birth' => $this->date_of_birth,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ])->filter(function ($value, $key) {
            return $value !== null;
        });
        return $collection->toArray();
    }
}
