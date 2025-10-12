<?php

namespace Database\Seeders;

// to run this file type this in terminal
// php artisan db:seed --class=UsersTableSeeder

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'johndoe@gmail.com',
                'status' => 'active',
                'password' => Hash::make('#@1homenest'),
                'role' => 'System Admin',
                'lastlogin' => now(),
                'photo_url' => null,
                'phone' => '256770000000',
                'gender' => 'Male',
            ],
            [
                'name' => 'Jane Doe',
                'email' => 'janedoe@gmail.com',
                'status' => 'active',
                'password' => Hash::make('#@1homenest'),
                'role' => 'Customer',
                'lastlogin' => now(),
                'photo_url' => null,
                'phone' => '256700000001',
                'gender' => 'Female',
            ],
            // Add additional users as needed
        ];

        $existingUsers = [];
        $createdUsers = [];

        foreach ($users as $userData) {
            // Remove 'role' from the array before creating the user
            $roleName = $userData['role'];
            unset($userData['role']);

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            if ($user->wasRecentlyCreated) {
                $createdUsers[] = $user->email;

                // Check if the role exists and assign it to the user
                if (Role::where('name', $roleName)->exists()) {
                    $user->assignRole($roleName);
                }
            } else {
                $existingUsers[] = $user->email;
                // Check if the role exists and assign it to the user
                if (Role::where('name', $roleName)->exists()) {
                    $user->assignRole($roleName);
                }
            }
        }

        // Output to the console
        $this->command->info('Existing Users: ' . implode(', ', $existingUsers));
        $this->command->info('Created Users: ' . implode(', ', $createdUsers));
    }
}
