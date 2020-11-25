<?php

namespace Database\Seeders;


use App\Models\UserPrivileges;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

class UsersRoleTableSeeder extends Seeder
{
    public function run() {
        UserRole::create(['name' => 'ROLE_ADMIN'])
            ->privileges()->saveMany([
                new UserPrivileges(['name'=>'manage_profile']),
                new UserPrivileges(['name'=>'manage_programs']),
                new UserPrivileges(['name'=>'create_user'])
            ]);

        UserRole::create(['name' => 'ROLE_USER'])
            ->privileges()->saveMany([
                new UserPrivileges(['name'=>'view_profile']),
                new UserPrivileges(['name'=>'view_programs'])
            ]);
    }
}
