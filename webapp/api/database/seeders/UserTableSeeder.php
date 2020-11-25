<?php


namespace Database\Seeders;


use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserTableSeeder extends Seeder
{
    public function run() {
        $userRole = UserRole::where('name', 'ROLE_ADMIN')->first();
        $user = new User([
            'email' => 'admin',
            'password' => app('hash')->make('admin'),
            'salt'=> Str::random(20)
        ]);
        $user->role()->associate($userRole);
        $user->save();
    }
}
