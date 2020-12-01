<?php


namespace Database\Seeders;


use App\Models\User;
use App\Models\UserRole;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;


class UserTableSeeder extends Seeder
{
    public function run()
    {
        $userAdminRole = UserRole::where('name', 'ROLE_ADMIN')->first();
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

        // $userRole = UserRole::where('name', 'ROLE_USER')->first();
        // $users = User::factory()
        //     ->count(30)
        //     ->make()
        //     ->each(function ($user) use ($userRole) {
        //         $user->role()->associate($userRole);
        //         $user->save();
        //         $user->profile()->save(UserProfile::factory()->make());
        //     });
    }
}
