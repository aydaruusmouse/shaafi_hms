<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Exists;

class DefaultTranslateAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'admin@hms.com';

        $exists = DB::table('ltu_contributors')->where('email', $email)->exists();

        $input = [
            'name' => 'Super Admin',
            'email' => 'admin@hms.com',
            'password' => Hash::make('123456789'),
            'role' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        if (!$exists) {
            DB::table('ltu_contributors')->insert($input);
        }
    }
}
