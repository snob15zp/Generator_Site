<?php


namespace App\Http\Controllers;


use App\Http\Resources\UserProfileResource;
use App\Models\ResetPassword;
use App\Models\User;
use App\Models\UserPrivileges;
use App\Models\UserProfile;
use App\Models\UserRole;
use App\Notifications\UserCreateNotification;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;

class UserProfileController extends Controller
{
    public function getAll(Request $request)
    {
        if ($request->user()->cannot(UserPrivileges::MANAGE_PROFILES)) {
            $this->raiseError(403, 'Resource not available');
        }
        $this->validate($request, [
            'page' => 'nullable|numeric',
            'perPage' => 'nullable|numeric',
            'query' => 'nullable|between:3,255',
            'sortBy' => 'nullable|array',
            'sortDir' => 'required_with:sortBy|array'
        ]);

        $query = UserProfile::query();
        $sortBy = $request->input('sortBy', ['name']);
        $sortDir = $request->input('sortDir', ['asc']);
        collect($sortBy)
            ->map(function ($item, $key) use ($sortDir) {
                $dir = array_key_exists($key, $sortDir) ? $sortDir[$key] : 'asc';
                return [$item => $dir];
            })->filter(function ($item, $key) {
                in_array($key, ['created_at', 'updated_at', 'email', 'name', 'surname', 'address', 'phone_number', 'date_of_birth']);
            })->each(function ($item, $key) use ($query) {
                $query->orderBy($key, $item);
            });

        $perPage = $request->input('perPage', 10);
        $profiles = null;
        if ($request->has('query')) {
            $where = 'MATCH(name, surname, address, phone_number, email) AGAINST(? IN BOOLEAN MODE)';
            $profiles = $query->whereRaw($where, [$request->input('query')])->paginate($perPage);
        } else {
            $profiles = $query->paginate($perPage);
        }
        return $this->respondWithResource(UserProfileResource::collection($profiles));
    }

    public function delete(Request $request, $id)
    {
        if ($request->user()->cannot(UserPrivileges::CREATE_USER)) {
            $this->raiseError(403, 'Operation is restricted');
        }

        $profile = UserProfile::query()->whereKey(Hashids::decode($id))->first();
        if ($profile == null) {
            $this->raiseError(404, 'Profile not found');
        }

        $profile->user->delete();
        $profile->delete();

        return $this->respondWithMessage();
    }

    public function update(Request $request, $id)
    {
        $profile = UserProfile::query()->whereKey(Hashids::decode($id))->first();
        if ($profile == null) {
            $this->raiseError(404, 'Profile not found');
        }

        if ($request->user()->cannot(UserPrivileges::VIEW_PROFILE, $profile)) {
            $this->raiseError(403, 'Operation is restricted');
        }

        $this->validate($request, [
            'name' => 'required|max:255',
            'surname' => 'required|max:255',
            'address' => 'required|max:255',
            'phone_number' => 'required|max:255',
            'date_of_birth' => 'date_format:Y-m-d'
        ]);

        $profile->update($request->only(['name', 'surname', 'address', 'phone_number', 'date_of_birth']));

        return $this->respondWithResource(new UserProfileResource($profile));
    }

    public function create(Request $request)
    {
        if ($request->user()->cannot(UserPrivileges::CREATE_USER)) {
            $this->raiseError(403, 'Operation is restricted');
        }
        $this->validate($request, [
            'email' => 'required|email|unique:user,login',
            'name' => 'required|max:255',
            'surname' => 'required|max:255',
            'address' => 'required|max:255',
            'phone_number' => 'required|max:255',
            'date_of_birth' => 'date_format:Y-m-d',
            'role' => 'nullable|in:' . UserRole::ROLE_ADMIN . ',' . UserRole::ROLE_USER
        ]);

        $role = UserRole::query()->where('name', $request->input('role', UserRole::ROLE_USER))->first();
        $profile = new UserProfile($request->only(['name', 'surname', 'address', 'phone_number', 'email', 'date_of_birth']));

        $user = new User([
            'login' => $profile->email,
            'password' => null
        ]);
        $user->role()->associate($role);
        $user->save();

        $resetPassword = ResetPassword::create([
            'login' => $profile->email,
            'hash' => Hash::make(Str::random(12)),
            'expired_at' => (new DateTime())->modify('+7 day')
        ]);

        $user->profile()->save($profile);
        Notification::route('mail', $profile->email)->notify(new UserCreateNotification($user, $resetPassword));
        return $this->respondWithResource(new UserProfileResource($profile));
    }

    public function get(Request $request, string $id)
    {
        $profile = UserProfile::query()->whereKey(Hashids::decode($id))->first();
        if ($profile == null) {
            $this->raiseError(404, "Profile not found");
        }

        if ($request->user()->cannot(UserPrivileges::VIEW_PROFILE, $profile)) {
            $this->raiseError(403, 'Resource not available');
        }
        return $this->respondWithResource(new UserProfileResource($profile));
    }
}
