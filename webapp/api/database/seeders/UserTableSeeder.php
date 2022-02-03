<?php


namespace Database\Seeders;


use App\Models\User;
use App\Models\UserOwner;
use App\Models\UserProfile;
use App\Models\UserRole;
use Database\Factories\FolderFactory;
use Database\Factories\ProgramFactory;
use Database\Factories\UserProfileFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    public function run()
    {
        $userAdminRole = UserRole::where('name', UserRole::ROLE_ADMIN)->first();
        $user = new User([
            'login' => 'admin',
            'password' => Hash::make('admin')
        ]);
        $user->role()->associate($userAdminRole);
        $user->save();

        $user->profile()->save(new UserProfile([
            'name' => 'Administrator',
            'email' => 'admin@mail.fake'
        ]));

        $userRole = UserRole::where('name', UserRole::ROLE_USER)->first();
        $userIds = collect();
        User::factory()
            ->count(30)
            ->make()
            ->each(function ($user) use ($userRole, $userIds) {
                $userProfile = new UserProfileFactory();
                $user->role()->associate($userRole);
                $user->save();
                $user->profile()->save($userProfile->make());
                $userIds->add($user->id);
            });

        $userRole = UserRole::where('name', UserRole::ROLE_PROFESSIONAL)->first();
        User::factory()
            ->count(10)
            ->make()
            ->each(function ($user) use ($userRole, $userIds) {
                $userProfileFactory = new UserProfileFactory();
                $userProfile = $userProfileFactory->make();
                $userProfile->email = $user->login;
                $user->role()->associate($userRole);
                $user->save();
                $user->profile()->save($userProfile);

//                $userIds->shuffle()->slice(0, 10)->each(function ($id) use ($user) {
//                    $userOwner = new UserOwner([
//                        'owner_id' => $user->id,
//                        'user_id' => $id
//                    ]);
//                    $userOwner->save();
//                });
            });
    }
}
