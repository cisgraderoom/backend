<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'username' => 'superteacher',
            'password' => bcrypt('superteacher'),
            'role' => 'admin',
            'status' => 'active',
            'name' => 'SuperTeacher',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        DB::table('users')->insert([
            'username' => 'student01',
            'password' => bcrypt('student01'),
            'role' => 'student',
            'status' => 'active',
            'name' => 'นักเรียนทดสอบ',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
