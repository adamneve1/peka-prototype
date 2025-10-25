<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // jalankan seeder custom
        $this->call([
            UserSeeder::class,
            //ServiceSeeder::class,
            CounterSeeder::class,
            StaffSeeder::class,
            CounterServiceSeeder::class,
            RatingSeeder::class,
            
        StaffAssignmentSeeder::class,  // jadwal harian s/d 31 Des tahun depan
        ]);
    }
}
