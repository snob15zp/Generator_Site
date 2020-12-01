<?php

namespace App\Http\Resources;

use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Vinkla\Hashids\HashidsManager;

class UserProfileResource extends JsonResource
{

    public function toArray($request)
    {
        $collection =  collect([
            'id' => Hashids::encode($this->id),
            'name' => $this->name,
            'suranme' => $this->suranme,
            'email' => $this->email,
            'address' => $this->address,
            'phone_number' => $this->phone_number,
            'date_of_birth' => $this->date_of_birth
        ])->filter(function ($value, $key) {
            return $value !== null;
        });
        return $collection->toArray();
    }
}
