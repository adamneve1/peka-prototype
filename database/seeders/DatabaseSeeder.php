<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,          // bebas duluan
            CounterSeeder::class,       // base
          //  ServiceSeeder::class,       // base (WAJIB)
            StaffSeeder::class,         // base
            CounterServiceSeeder::class,// pivot counters <-> services
            StaffAssignmentSeeder::class,// pakai counters & staff
            RatingSeeder::class,        // pakai counters, services, staff
        ]);
    }
}