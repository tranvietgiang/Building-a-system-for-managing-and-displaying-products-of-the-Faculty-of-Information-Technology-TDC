<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MajorSeeder::class,
            userSeeder::class,
            // CategorySeeder::class,
            // ProductSeeder::class,
            // ProductImageSeeder::class,
            // ProductFileSeeder::class,
            // ProductTagSeeder::class,
            // ProductStatisticSeeder::class,
            // ReviewSeeder::class,
        ]);
    }
}
