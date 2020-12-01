<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Gate;
use App\Models\Session;
use App\Models\User;
use http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;
use Tymon\JWTAuth\JWTAuth;


class AuthController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'login' => 'required|string',
            'password' => 'required|string'
        ]);

        $credentials = $request->only(['login', 'password']);

        if (!$token = Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $user = Auth::user();
        $session = $user->sessions()->create([
            'token' => $token,
            'expires_in' =>  Auth::factory()->getTTL() * 60,
            'active' => true
        ]);
        return new UserResource($user, $session);
    }
}
