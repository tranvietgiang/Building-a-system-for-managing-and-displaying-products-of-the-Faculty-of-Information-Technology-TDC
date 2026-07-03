<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class userSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $password = Hash::make('12345678');

        DB::table('users')->insert([
            [
                'user_id' => '23211CNTT1',
                'name' => 'Sinh vien CNTT 1',
                'email' => '23211cntt1@student.tdc.edu.vn',
                'password' => $password,
                'role' => 'student',
                'major_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => '23211CNTT5',
                'name' => 'Sinh vien CNTT 5',
                'email' => 'wedgiang@gmail.com',
                'password' => $password,
                'role' => 'student',
                'major_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => '23211AI2',
                'name' => 'Sinh vien AI 2',
                'email' => '23211ai2@student.tdc.edu.vn',
                'password' => $password,
                'role' => 'student',
                'major_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => '23211MMT3',
                'name' => 'Sinh vien MMT 3',
                'email' => '23211mmt3@student.tdc.edu.vn',
                'password' => $password,
                'role' => 'student',
                'major_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => '23211TKDH4',
                'name' => 'Sinh vien Thiet ke do hoa 4',
                'email' => '23211tkdh4@student.tdc.edu.vn',
                'password' => $password,
                'role' => 'student',
                'major_id' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('users')->insert([
            [
                'user_id' => 'GVAI',
                'name' => 'Giang vien AI',
                'email' => 'gvai@tdc.edu.vn',
                'password' => $password,
                'role' => 'teacher',
                'major_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => 'GVCNTT',
                'name' => 'Giang vien CNTT',
                'email' => 'gvcntt@tdc.edu.vn',
                'password' => $password,
                'role' => 'teacher',
                'major_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => 'GVMMT',
                'name' => 'Giang vien MMT',
                'email' => 'gvmmt@tdc.edu.vn',
                'password' => $password,
                'role' => 'teacher',
                'major_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => 'GVTKDH',
                'name' => 'Giang vien Thiet ke do hoa',
                'email' => 'gvtkdh@tdc.edu.vn',
                'password' => $password,
                'role' => 'teacher',
                'major_id' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        User::create([
            'user_id' => 'admin',
            'name' => 'Admin',
            'email' => 'admin@tdc.edu.vn',
            'password' => $password,
            'role' => 'admin',
            'major_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
