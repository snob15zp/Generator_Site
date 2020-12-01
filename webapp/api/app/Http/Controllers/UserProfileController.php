<?php


namespace App\Http\Controllers;


use App\Http\Resources\UserProfileResource;
use App\Models\User;
use App\Models\UserPrivileges;
use App\Models\UserProfile;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Password;
use Vinkla\Hashids\Facades\Hashids;

class UserProfileController extends Controller
{
    public function getAll(Request $request)
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_PROFILES)) {
            abort(403, 'Resource not available');
        }
        $this->validate($request, [
            'page' => 'nullable|numeric',
            'perPage' => 'nullable|numeric',
            'query' => 'nullable|between:3,255'
        ]);

        $perPage = $request->input('perPage', 10);
        $profiles = null;
        if ($request->has('query')) {
            $where = 'MATCH(name, surname, address, phone_number, email) AGAINST(? IN BOOLEAN MODE)';
            $profiles = UserProfile::query()->whereRaw($where, [$request->input('query')])->paginate($perPage);
        } else {
            $profiles = UserProfile::query()->paginate($perPage);
        }
        return UserProfileResource::collection($profiles);
    }

    public function create(Request $request)
    {
        if ($request->user()->cannot(UserPrivileges::CREATE_USER)) {
            abort(403, 'Operation is restricted');
        }
        $this->validate($request, [
            'email' => 'required|email',
            'name' => 'required|max:255',
            'surname' => 'required|max:255',
            'address' => 'required|max:255',
            'phone_number' => 'required|max:255',
            'date_of_birth' => 'date_format:Y-m-d'
        ]);

        if (in_array($request->input('role'), [UserRole::ROLE_ADMIN, UserRole::ROLE_USER])) {
            $userRoleName = $request->input('role');
        } else {
            $userRoleName = UserRole::ROLE_USER;
        }

        $role = UserRole::query()->where('name', $userRoleName)->first();
        $profile = new UserProfile($request->only(['name', 'surname', 'address', 'phone_number', 'email', 'date_of_birth']));

        $user = User::create([
            'login' => $profile['email'],
            'one_time_password' => true,
            'password' => Hash::make(str_random(8))
        ]);




    }

    public function get(Request $request, string $id)
    {
        $profile = null;
        try {
            $profile = UserProfile::query()->whereKey(Hashids::decode($id))->firstOrFail();
        } catch (ModelNotFoundException $e) {
            abort(404, "Profile not found");
        }

        $user = $request->user();
        if ($user->cannot(UserPrivileges::VIEW_PROFILE, [$profile])) {
            abort(403, 'Resource not available');
        }
        return new UserProfileResource($profile);
    }
}
