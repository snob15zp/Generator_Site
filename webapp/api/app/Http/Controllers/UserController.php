<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'login' => 'required|string',
            'password' => 'required|string'
        ]);

        $credentials = $request->only(['login', 'password']);
        if (!$token = Auth::attempt($credentials)) {
            $this->raiseError(401, 'Invalid credentials');
        }

        $user = Auth::user();
        return $this->respondWithResource(new UserResource($user, $token));
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();
        return $this->respondWithMessage();
    }

    public function resetPassword(Request $request)
    {

    }

    public function update(Request $request, $id)
    {

    }
}
