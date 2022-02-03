<?php


namespace App\Http\Controllers;


use App\Http\Resources\UserProfileResource;
use App\Http\Resources\UserResource;
use App\Models\ResetPassword;
use App\Models\User;
use App\Models\UserPrivileges;
use App\Models\UserProfile;
use App\Models\UserRole;
use App\Notifications\UserCreateNotification;
use App\Utils\HashUtils;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
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
    public function getAll(Request $request): ResourceCollection
    {
        if ($request->user()->cannot(UserPrivileges::VIEW_USERS)) {
            $this->raiseError(403, 'Resource not available');
        }
        $this->validate($request, [
            'page' => 'nullable|numeric',
            'perPage' => 'nullable|numeric',
            'query' => 'nullable|min:3|max:255',
            'sortBy' => 'nullable|array',
            'sortDir' => 'required_with:sortBy|array',
            'roles' => 'nullable|array',
            'owner_id' => 'nullable|string'
        ]);

        $query = User::with(['profile', 'role'])
            ->join('user_profile', 'user_profile.user_id', '=', 'user.id');

        $select = collect(['user.id', 'user.role_id', 'user.created_at', 'user.updated_at',
            'user_profile.email', 'user_profile.name',
            'user_profile.surname', 'user_profile.address', 'user_profile.phone_number', 'user_profile.date_of_birth'
        ]);

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

        $search = trim($request->input('query', ''));
        if ($search !== '') {
            $where = 'MATCH(user_profile.name, surname, address, phone_number, email) AGAINST(? IN BOOLEAN MODE)';
            $query->whereKeyNot(1)->whereRaw($where, ["*$search*"]);
        } else {
            $query->whereKeyNot(1);
        }

        $user = $request->user();
        $ownerId = $request->get('owner_id');
        $roles = collect($request->input('roles'))->filter(function ($role) use ($user) {
            return UserRole::compare($role, $user->role->name) > 0;
        });
        if ($user->can(UserPrivileges::MANAGE_USERS)) {
            if ($ownerId != null) {
                $select->add('user_owner.owner_id');
                $query->join('user_owner', 'user_owner.user_id', '=', 'user_profile.user_id')
                    ->where('user_owner.owner_id', '=', HashUtils::decode($ownerId));
            }

            if ($roles->count() > 0) {
                $query->join('user_role', "user_role.id", "=", "user.role_id")
                    ->whereIn('user_role.name', $roles);
            }
        } elseif ($user->can(UserPrivileges::MANAGE_OWN_USERS)) {
            $select->add('user_owner.owner_id');
            $query->join('user_owner', "user_owner.user_id", "=", "user_profile.user_id")
                ->where("user_owner.owner_id", "=", $user->id);
        } else {
            $this->raiseError(403, 'Resource not available');
        }

        $perPage = $request->input('perPage', 10);
        return UserResource::collection($query->select($select->all())->paginate($perPage));
    }

    public function get(Request $request, string $id)
    {
        $profile = UserProfile::query()->whereKey(HashUtils::decode($id))->first();
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
        $user = User::query()->whereKey(HashUtils::decode($id))->first();
        if ($user == null) {
            $this->raiseError(404, "User not found");
        }
        if ($request->user()->cannot(UserPrivileges::VIEW_PROFILE, $user->profile)) {
            $this->raiseError(403, 'Resource not available');
        }

        return $this->respondWithResource(new UserProfileResource($user->profile));
    }
}
