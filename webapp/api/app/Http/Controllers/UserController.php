<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Resources\UserLoginResource;
use App\Http\Resources\UserResource;
use App\Models\ResetPassword;
use App\Models\User;
use App\Models\UserOwner;
use App\Models\UserPrivileges;
use App\Models\UserProfile;
use App\Models\UserRole;
use App\Notifications\ForgetPasswordNotification;
use App\Notifications\UserCreateNotification;
use App\Utils\HashUtils;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
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


class UserController extends Controller
{
    /**
     * @param Request $request
     * @return UserLoginResource
     * @throws ValidationException
     * @throws ApiException
     */
    public function login(Request $request): UserLoginResource
    {
        $this->validate($request, [
            'login' => 'required|string',
            'password' => 'required|string'
        ]);

        $credentials = $request->only(['login', 'password']);
        $token = Auth::attempt($credentials);
        if (!$token) {
            throw new ApiException(ErrorStatusCodes::$INVALID_CREDENTIALS, 403);
        }

        $user = Auth::user();
        return new UserLoginResource($user, $token);
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
     * @throws ValidationException
     */
    public function getAll(Request $request): ResourceCollection
    {
        if ($request->user()->cannot(UserPrivileges::VIEW_USERS)) {
            throw new ApiException(ErrorStatusCodes::$RESOURCE_NOT_AVAILABLE, 403);
        }
        $this->validate($request, [
            'page' => 'nullable|numeric',
            'perPage' => 'nullable|numeric',
            'query' => 'nullable|min:3|max:255',
            'sortBy' => 'nullable|array',
            'sortDir' => 'required_with:sortBy|array',
            'roles' => 'nullable|array',
            'owner_id' => 'nullable|string',
            'unlinked' => 'nullable'
        ]);

        $query = User::with(['profile', 'role'])
            ->join('user_profile', 'user_profile.user_id', '=', 'user.id');

        $select = collect(['user.id', 'user.role_id', 'user.created_at', 'user.updated_at',
            'user_profile.email', 'user_profile.name',
            'user_profile.surname', 'user_profile.address', 'user_profile.phone_number', 'user_profile.date_of_birth'
        ]);

        $query = $this->querySort($request, $query);
        $query = $this->querySearch($request, $query);

        $user = $request->user();
        $ownerId = $request->get('owner_id');
        $roles = collect($request->input('roles'))->filter(function ($role) use ($user) {
            return UserRole::compare($role, $user->role->name) >= 0;
        });
        if ($user->can(UserPrivileges::MANAGE_USERS)) {
            if ($ownerId != null) {
                if ($request->has('unlinked')) {
                    $query->whereRaw('user.id not in (select user_id from user_owner where owner_id = ?)', HashUtils::decode($ownerId));
                } elseif ($ownerId != -1) {
                    $select->add('user_owner.owner_id');
                    $query->join('user_owner', 'user_owner.user_id', '=', 'user.id');
                    $query->where('user_owner.owner_id', '=', HashUtils::decode($ownerId));
                } else {
                    // Get users without owner
                    $query->whereRaw('user.id not in (select user_id from user_owner)');
                }
            }
        } elseif ($user->can(UserPrivileges::MANAGE_OWN_USERS)) {
            $select->add('user_owner.owner_id');
            $query->join('user_owner', "user_owner.user_id", "=", "user_profile.user_id")
                ->where("user_owner.owner_id", "=", $user->id);
        } else {
            $this->raiseError(403, 'Resource not available');
        }

        $query->join('user_role', "user_role.id", "=", "user.role_id")
            ->whereIn('user_role.name', $roles);

        $perPage = $request->input('perPage', 10);
        if ($perPage == -1) {
            $perPage = $query->count();
        }
        return UserResource::collection($query->select($select->all())->paginate($perPage));
    }

    /**
     * @throws ValidationException
     */
    public function addChildren(Request $request, $id): JsonResponse
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_USERS)) {
            throw new ApiException(ErrorStatusCodes::$RESOURCE_NOT_AVAILABLE);
        }
        $this->validate($request, [
            'ids' => 'array'
        ]);
        $owner = User::query()->whereKey(HashUtils::decode($id))->first();
        if ($owner == null) {
            throw new ApiException(ErrorStatusCodes::$USER_NOT_FOUND);
        } elseif ($owner->cannot(UserPrivileges::MANAGE_OWN_USERS)) {
            throw new ApiException(ErrorStatusCodes::$RESOURCE_NOT_AVAILABLE);
        }
        $ids = HashUtils::decodeHashes($request->input('ids'));
        User::query()->whereKey($ids)->get()->map(function ($user) use ($owner) {
            $userOwner = new UserOwner([
                "user_id" => $user->id,
                "owner_id" => $owner->id
            ]);
            $userOwner->save();
        });
        return $this->respondWithMessage("OK");
    }

    private function querySearch(Request $request, Builder $query): Builder
    {
        $search = trim($request->input('query', ''));
        if ($search !== '') {
            $where = 'MATCH(user_profile.name, surname, address, phone_number, email) AGAINST(? IN BOOLEAN MODE)';
            $query->whereRaw($where, ["*$search*"]);
        }
        return $query;
    }

    private function querySort(Request $request, Builder $query): Builder
    {
        $sortBy = $request->input('sortBy', ['name']);
        $sortDir = $request->input('sortDir', ['asc']);
        collect($sortBy)
            ->map(function ($item, $key) use ($sortDir) {
                $dir = array_key_exists($key, $sortDir) ? $sortDir[$key] : 'asc';
                return ["column" => $item, "dir" => $dir];
            })->filter(function ($item) {
                return in_array($item["column"], ['created_at', 'updated_at', 'email', 'name', 'surname', 'address', 'phone_number', 'date_of_birth']);
            })->each(function ($item) use ($query) {
                $query->orderBy('user_profile.' . $item['column'], $item['dir']);
            });

        return $query;
    }

    /**
     * @param Request $request
     * @param string $id
     * @return UserResource
     */
    public function get(Request $request, string $id): UserResource
    {
        $user = $request->user();
        if ($user->cannot(UserPrivileges::VIEW_USERS)) {
            throw new ApiException(ErrorStatusCodes::$RESOURCE_NOT_AVAILABLE, 403);
        }

        $userId = HashUtils::decode($id);
        Log::info("Get user $userId");
        $query = User::with(['profile', 'role', 'owners'])->whereKey($userId);
        $select = ['user.*'];

        if ($user->can(UserPrivileges::MANAGE_OWN_USERS) && $user->id != $userId) {
            $query->join('user_owner', "user_owner.user_id", "=", "user.id")
                ->where("user_owner.owner_id", "=", $user->id);
        }
        $manageUser = $query->select($select)->first();
        if ($manageUser == null) {
            throw new ApiException(ErrorStatusCodes::$USER_NOT_FOUND, 404);
        }
        return new UserResource($manageUser);
    }

    /**
     * @throws ValidationException
     */
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
            return HashUtils::decode($id);
        });

        $query = User::query()->whereIn('user.id', $ids);
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

    /**
     * @throws ValidationException, HttpException
     */
    public function create(Request $request, string $ownerId = null): UserResource
    {
        $currentUser = $request->user();
        $owner = null;
        if ($currentUser->can(UserPrivileges::MANAGE_OWN_USERS)) {
            if ($ownerId != null && HashUtils::decode($ownerId) != $currentUser->id) {
                $this->raiseError(403, 'Access denied');
            }
            $owner = $currentUser;
        } elseif ($currentUser->can(UserPrivileges::MANAGE_USERS)) {
            $owner = ($ownerId != null) ? User::query()->whereKey(HashUtils::decode($ownerId))->first() : null;
        } else {
            $this->raiseError(403, 'Operation is restricted');
        }

        $this->validate($request, [
            'role' => 'nullable',
            'password' => 'min:8'
        ]);

        $role = UserRole::query()
            ->where('name', $request->input('role', UserRole::ROLE_USER))
            ->first();

        $userProfileFields = $this->getUserProfileFromRequest($request, true);
        $profile = new UserProfile($userProfileFields);

        $user = new User([
            'login' => $profile->email,
            'password' => $request->has('password') ? Hash::make($request->input('password')) : null
        ]);
        $user->role()->associate($role);

        try {
            DB::beginTransaction();
            $user->save();
            if ($owner != null) {
                $user->owners()->attach($owner);
            }
            $user->profile()->save($profile);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            $this->raiseError(500, "Cannot to create user");
        }

        if (!$request->has('password')) {
            $this->sendResetPasswordNotification($user, $profile->email);
        }
        return new UserResource($user);
    }

    /**
     * @throws ValidationException
     */
    public function update(Request $request, string $id): UserResource
    {
        $user = User::query()->whereKey(HashUtils::decode($id))->first();
        if ($user == null) {
            throw new ApiException(ErrorStatusCodes::$USER_NOT_FOUND);
        }
        if ($request->user()->cannot(UserPrivileges::VIEW_USERS)) {
            throw new ApiException(ErrorStatusCodes::$RESOURCE_NOT_AVAILABLE);
        }

        $userProfile = $this->getUserProfileFromRequest($request, false);
        try {
            DB::beginTransaction();
            $user->profile()->update($userProfile);
            $userFields = ['login' => $userProfile['email']];

            $roleName = $request->get('role');
            if ($request->user()->can(UserPrivileges::MANAGE_USERS) && UserRole::contains($roleName)) {
                $role = UserRole::query()->where('name', '=', $roleName)->first();
                if ($role != null) {
                    $userFields['role_id'] = $role->id;
                }
            }

            if ($request->has('password')) {
                $userFields['password'] = Hash::make($request->input('password'));
            }
            $user->update($userFields);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            $this->raiseError(500, "Cannot to update user");
        }

        return new UserResource($user);
    }

    public function check(Request $request, string $id = null): JsonResponse
    {
        if ($request->user()->cannot(UserPrivileges::VIEW_USERS)) {
            throw new ApiException(ErrorStatusCodes::$RESOURCE_NOT_AVAILABLE, 403);
        }
        $email = $request->input('email');
        $query = User::query()->where('login', '=', $email);
        if ($id != null) {
            $query->where('id', '!=', HashUtils::decode($id));
        }
        $usersCount = $query->count();
        if ($usersCount > 0) {
            $this->raiseError(422, "Email is already used");
        }
        return $this->respondWithMessage();
    }

    private function sendResetPasswordNotification(User $user, $email)
    {
        $resetPassword = ResetPassword::create([
            'login' => $user->login,
            'hash' => Hash::make(Str::random(12)),
            'expired_at' => (new DateTime())->modify('+7 day')
        ]);
        Notification::route('mail', $email)->notify(new UserCreateNotification($user, $resetPassword));
    }

    /**
     * @throws ValidationException
     */
    private function getUserProfileFromRequest(Request $request, bool $checkUniqueEmail): array
    {
        $this->validate($request, [
            'profile.email' => 'required|email' . ($checkUniqueEmail ? '|unique:user,login' : ''),
            'profile.name' => 'required|max:255',
            'profile.surname' => 'required|max:255',
            'profile.address' => 'required|max:255',
            'profile.phone_number' => 'required|max:255',
            'profile.date_of_birth' => 'date_format:Y-m-d',
        ]);

        return $request->only([
            'profile.name',
            'profile.surname',
            'profile.address',
            'profile.phone_number',
            'profile.email',
            'profile.date_of_birth'
        ])['profile'];
    }
}
