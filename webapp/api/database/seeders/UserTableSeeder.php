<?php


namespace Database\Seeders;


use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserTableSeeder extends Seeder
{
    public function run() {
        $userRole = UserRole::where('name', 'ROLE_ADMIN')->first();
        $user = new User([
            'login' => 'admin',
            'password' => Hash::make('admin'),
            'salt'=> Str::random(20)
        ]);
        $user->role()->associate($userRole);
        $user->save();

        $user->profile()->save(new UserProfile([
            'name'=>'Administrator',
            'surname' => null,
            'address' => null,
            'phone_number' => null,
            'email' => null,
            'date_of_birth' => null
        ]));
    }
}
