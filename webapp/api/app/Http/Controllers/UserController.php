<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\ResetPassword;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use JWTAuth;


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

    public function refresh()
    {
        try {
            return response()->json(['token' => auth()->refresh()]);
        } catch (\Exception $e) {
            return $this->raiseError(401, 'Invalid token');
        }
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
        $hash = base64_decode($request->input('hash'));
        $resetPassword = ResetPassword::query()->where('hash', $hash)->first();
        if ($resetPassword == null) {
            $this->raiseError(404, "Link is expired");
        }

        $user = User::query()->where('login', $resetPassword->login)->first();
        if ($user == null) {
            $this->raiseError(404, "User not found");
        }

        $user->update([
            "password" => Hash::make($request->input('password'))
        ]);

        return $this->respondWithMessage("Password changed");
    }

    public function update(Request $request, $id)
    {

    }
}
