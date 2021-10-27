<?php


namespace App\Http\Controllers;


use App\Http\Resources\UserProfileResource;
use App\Models\ResetPassword;
use App\Models\User;
use App\Models\UserPrivileges;
use App\Models\UserProfile;
use App\Models\UserRole;
use App\Notifications\UserCreateNotification;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\ErrorHandler\Debug;
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
            'query' => 'nullable|min:3|max:255',
            'sortBy' => 'nullable|array',
            'sortDir' => 'required_with:sortBy|array'
        ]);

        $query = UserProfile::query();
        $sortBy = $request->input('sortBy', ['name']);
        $sortDir = $request->input('sortDir', ['asc']);
        collect($sortBy)
            ->map(function ($item, $key) use ($sortDir) {
                $dir = array_key_exists($key, $sortDir) ? $sortDir[$key] : 'asc';
                return ["column" => $item, "dir" => $dir];
            })->filter(function ($item) {
                return in_array($item["column"], ['created_at', 'updated_at', 'email', 'name', 'surname', 'address', 'phone_number', 'date_of_birth']);
            })->each(function ($item) use ($query) {
                $query->orderBy($item['column'], $item['dir']);
            });

        $perPage = $request->input('perPage', 10);
        $profiles = null;
        $search = trim($request->input('query', ''));
        if ($search !== '') {
            $where = 'MATCH(name, surname, address, phone_number, email) AGAINST(? IN BOOLEAN MODE)';
            $profiles = $query->whereKeyNot(1)->whereRaw($where, ["*$search*"])->paginate($perPage);
        } else {
            $profiles = $query->whereKeyNot(1)->paginate($perPage);
        }
        return UserProfileResource::collection($profiles);
    }

    public function deleteAll(Request $request)
    {
        if ($request->user()->cannot(UserPrivileges::CREATE_USER)) {
            $this->raiseError(403, 'Operation is restricted');
        }

        $this->validate($request, [
            'ids' => 'required|array|min:1'
        ]);

        $ids = collect($request->input('ids'))->map(function ($id) {
            return Hashids::decode($id)[0];
        });

        UserProfile::query()->findMany($ids)->each(function ($profile) {
            $this->deleteProfile($profile);
        });

        return $this->respondWithMessage();
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
        $this->deleteProfile($profile);
        return $this->respondWithMessage();
    }

    private function deleteProfile($profile)
    {
        $profile->user->folders->each(function ($folder) {
            $folder->programs()->delete();
            $folder->delete();
            if (Storage::exists($folder->path())) {
                Storage::deleteDirectory($folder->path());
            }
        });
        $user = $profile->user;
        $profile->delete();
        $user->delete();
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

        $userProfile = $this->getUserProfileFromRequest($request, false);

        try {
            DB::beginTransaction();
            $profile->update($userProfile);

            $userFields = ['login' => $request->input('email')];
            if ($request->has('password')) {
                $userFields['password'] = Hash::make($request->input('password'));
            }
            $profile->user()->update($userFields);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            $this->raiseError(500, "Cannot to update user");
        }

        return $this->respondWithResource(new UserProfileResource($profile));
    }

    public function create(Request $request)
    {
        if ($request->user()->cannot(UserPrivileges::CREATE_USER)) {
            $this->raiseError(403, 'Operation is restricted');
        }

        $this->validate($request, [
            'role' => 'nullable|in:' . UserRole::ROLE_ADMIN . ',' . UserRole::ROLE_USER,
        ]);
        $role = UserRole::query()->where('name', $request->input('role', UserRole::ROLE_USER))->first();

        $userProfile = $this->getUserProfileFromRequest($request, true);
        $profile = new UserProfile($userProfile);

        $user = new User([
            'login' => $profile->email,
            'password' => $request->has('password') ? Hash::make($request->input('password')) : null
        ]);
        $user->role()->associate($role);
        try {
            DB::beginTransaction();
            $user->save();
            $user->profile()->save($profile);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            $this->raiseError(500, "Cannot to create user");
        }

        if (!$request->has('password')) {
            $resetPassword = ResetPassword::create([
                'login' => $profile->email,
                'hash' => Hash::make(Str::random(12)),
                'expired_at' => (new DateTime())->modify('+7 day')
            ]);
            Notification::route('mail', $profile->email)->notify(new UserCreateNotification($user, $resetPassword));
        }
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

    public function getByUserId(Request $request, $id)
    {
        $user = User::query()->whereKey(Hashids::decode($id))->first();
        if ($user == null) {
            $this->raiseError(404, "User not found");
        }
        if ($request->user()->cannot(UserPrivileges::VIEW_PROFILE, $user->profile)) {
            $this->raiseError(403, 'Resource not available');
        }

        return $this->respondWithResource(new UserProfileResource($user->profile));
    }

    /**
     * @throws ValidationException
     */
    private function getUserProfileFromRequest(Request $request, bool $checkUniqueEmail): array
    {
        $this->validate($request, [
            'email' => 'required|email' . ($checkUniqueEmail ? '|unique:user,login' : ''),
            'name' => 'required|max:255',
            'surname' => 'required|max:255',
            'address' => 'required|max:255',
            'phone_number' => 'required|max:255',
            'date_of_birth' => 'date_format:Y-m-d',
            'password' => 'min:8'
        ]);

        return $request->only(['name', 'surname', 'address', 'phone_number', 'email', 'date_of_birth']);
    }
}
