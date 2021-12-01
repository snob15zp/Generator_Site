<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\ResetPassword;
use App\Models\User;
use App\Models\UserPrivileges;
use App\Models\UserProfile;
use App\Models\UserRole;
use App\Notifications\ForgetPasswordNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Vinkla\Hashids\Facades\Hashids;


class UserController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $this->validate($request, [
            'login' => 'required|string',
            'password' => 'required|string'
        ]);

        $credentials = $request->only(['login', 'password']);
        if (!$token = Auth::attempt($credentials)) {
            $this->raiseError(ErrorStatusCodes::$INVALID_CREDENTIALS, 'Invalid credentials');
        }

        $user = Auth::user();
        return $this->respondWithResource(new UserResource($user, $token));
    }

    public function refresh(): JsonResponse
    {
        try {
            return response()->json(['token' => auth()->refresh()]);
        } catch (\Exception $e) {
            $this->raiseError(ErrorStatusCodes::$INVALID_TOKEN, 'Invalid token');
        }
    }

    /**
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth()->logout();
        return $this->respondWithMessage();
    }

    public function forgetPassword(Request $request): JsonResponse
    {
        $user = User::query()->where('login', $request->input('login'))->first();
        if ($user == null) {
            return $this->respondWithMessage("User not found");
        }

        $resetPassword = ResetPassword::create([
            'login' => $user->login,
            'hash' => Hash::make(Str::random(12)),
            'expired_at' => (new \DateTime())->modify('+7 day')
        ]);

        Notification::route('mail', $user->login)->notify(new ForgetPasswordNotification($user, $resetPassword));
        return $this->respondWithMessage("OK");
    }

    public function resetPassword(Request $request): JsonResponse
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

    /**
     * @param Request $request
     * @return ResourceCollection
     * @throws ValidationException
     */
    public function getAll(Request $request): ResourceCollection
    {
        $user = $request->user();
        if ($user->cannot(UserPrivileges::VIEW_USERS)) {
            $this->raiseError(403, 'Resource not available');
        }

        $this->validate($request, [
            'roles' => 'nullable|array',
            'owner_id' => 'nullable|string'
        ]);

        $ownerId = $request->input('owner_id');
        $roles = collect($request->input('roles'))->filter(function ($role) use ($user) {
            return UserRole::compare($role, $user->role->name) > 0;
        });

        $query = User::with(['profile', 'role']);
        $select = collect(['user.*']);

        if ($user->can(UserPrivileges::MANAGE_USERS)) {
            if ($ownerId != null) {
                $select->add('user_owner.owner_id');
                $query->join('user_owner', 'user_owner.user_id', '=', 'user.id')
                    ->where('user_owner.owner_id', '=', Hashids::decode($ownerId)[0]);
            }

            if ($roles->count() > 0) {
                $select->add('user_role.name');
                $query->join('user_role', "user_role.id", "=", "user.role_id")
                    ->whereIn('user_role.name', $roles);
            }
        } elseif ($user->can(UserPrivileges::MANAGE_OWN_USERS)) {
            $select->add('user_owner.owner_id');
            $query->join('user_owner', "user_owner.user_id", "=", "user.id")
                ->where("user_owner.owner_id", "=", $user->id);
        } else {
            $this->raiseError(403, 'Resource not available');
        }
        return UserResource::collection($query->select($select->all())->get());
    }

    public function delete(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->cannot(UserPrivileges::MANAGE_OWN_USERS) && $user->cannot(UserPrivileges::MANAGE_USERS)) {
            $this->raiseError(403, 'Resource not available');
        }

        $this->validate($request, [
            'ids' => 'required|array|min:1'
        ]);

        $ids = collect($request->input('ids'))->map(function ($id) {
            return Hashids::decode($id)[0];
        });

        $query = User::query()->whereIn('id', $ids);
        $select = ['user.*'];
        if ($user->can(UserPrivileges::MANAGE_OWN_USERS)) {
            $select[] = "user_owner.owner_id";
            $query->join('user_owner', "user_owner.user_id", "=", "user.id")
                ->where("user_owner.owner_id", "=", $user->id);
        }

        try {
            DB::beginTransaction();
            $query->select($select)->get()->each(function ($user) {
               $user->delete();
            });
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            $this->raiseError(500, "Cannot to delete users");
        }
        return $this->respondWithMessage();
    }

    public function update(Request $request, $id)
    {
    }
}
