<?php

namespace App\Http\Resources;

use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Vinkla\Hashids\HashidsManager;

class UserProfileResource extends JsonResource {

    public function toArray($request) {
        $t = (\extension_loaded('gmp')) ? "gmp" : "none_gmp";
        $v = (\extension_loaded('bcmath')) ? "bcmath" : "none_bcmatch";

        $collection =  collect([
            'id' => $t . $v//Hashids::encode($this->id)
        ]);
        return $collection->toArray();
    }
}
