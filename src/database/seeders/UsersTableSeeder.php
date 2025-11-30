<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $param = [
            'name' => '管理者',
            'email' => env('ADMIN_EMAIL', 'admin@example.com'),
            'email_verified_at' => now(),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'adminpassword')),
            'role' => 'admin',
            'uuid' => (string) Str::uuid(),
        ];
        User::create($param);

        $param = [
            'name' => '一般ユーザー1',
            'email' => 'general1@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'staff',
            'uuid' => (string) Str::uuid(),
        ];
        User::create($param);
    }
}
