<?php

namespace App\Http\Controllers;

use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected function raiseError($status, $message)
    {
        abort($status, $message);
    }

    protected function respondWithMessage($message = null)
    {
        return response()->json([
            'status' => 'OK',
            'message' => $message
        ]);
    }

    protected function respondWithResource(JsonResource $resource)
    {
        return response()->json($resource);
    }
}
