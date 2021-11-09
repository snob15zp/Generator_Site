<?php

namespace Database\Seeders;


use App\Models\UserPrivileges;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

class UsersRoleTableSeeder extends Seeder
{
    public function run()
    {
        UserRole::create(['name' => UserRole::ROLE_ADMIN])
            ->privileges()->saveMany([
                new UserPrivileges(['name' => UserPrivileges::CREATE_USER]),
                new UserPrivileges(['name' => UserPrivileges::MANAGE_PROGRAMS]),
                new UserPrivileges(['name' => UserPrivileges::MANAGE_FIRMWARE]),
                new UserPrivileges(['name' => UserPrivileges::UPLOAD_PROGRAMS]),
                new UserPrivileges(['name' => UserPrivileges::VIEW_USERS]),
                new UserPrivileges(['name' => UserPrivileges::VIEW_PROFILE]),
                new UserPrivileges(['name' => UserPrivileges::VIEW_PROGRAMS])
            ]);

        UserRole::create(['name' => UserRole::ROLE_PROFESSIONAL])
            ->privileges()->saveMany([
                new UserPrivileges(['name' => UserPrivileges::MANAGE_PROGRAMS]),
                new UserPrivileges(['name' => UserPrivileges::VIEW_USERS]),
                new UserPrivileges(['name' => UserPrivileges::VIEW_PROFILE]),
                new UserPrivileges(['name' => UserPrivileges::VIEW_PROGRAMS])
            ]);

        UserRole::create(['name' => UserRole::ROLE_SUPER_PROFESSIONAL])
            ->privileges()->saveMany([
                new UserPrivileges(['name' => UserPrivileges::MANAGE_PROGRAMS]),
                new UserPrivileges(['name' => UserPrivileges::UPLOAD_PROGRAMS]),
                new UserPrivileges(['name' => UserPrivileges::VIEW_USERS]),
                new UserPrivileges(['name' => UserPrivileges::VIEW_PROFILE]),
                new UserPrivileges(['name' => UserPrivileges::VIEW_PROGRAMS])
            ]);

        UserRole::create(['name' => UserRole::ROLE_USER])
            ->privileges()->saveMany([
                new UserPrivileges(['name' => UserPrivileges::VIEW_PROFILE]),
                new UserPrivileges(['name' => UserPrivileges::VIEW_PROGRAMS])
            ]);
    }
}
