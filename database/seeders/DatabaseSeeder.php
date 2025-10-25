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
    UserSeeder::class,        // bebas duluan
    CounterSeeder::class,     // base
   // ServiceSeeder::class,     // base (WAJIB nyala)
    StaffSeeder::class,       // base
    CounterServiceSeeder::class, // pivot counters<->services
    StaffAssignmentSeeder::class, // pakai counters & staff
    RatingSeeder::class,      // pakai counters, services, staff
]);
    }
}
