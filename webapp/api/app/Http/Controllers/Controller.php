<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected function respondWithToken($token)
    {
        $user = Auth::user();
        $userProfile = $user->profile()->first();
        $userJson = [];
        foreach (['name', 'surname', 'address', 'phone_number', 'email', 'date_of_birth'] as $key) {
            if ($userProfile[$key] != null) {
                $userJson[$key] = $userProfile[$key];
            }
        }
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user' => $userJson
        ], 200);
    }
}
