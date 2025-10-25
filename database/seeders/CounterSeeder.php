<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CounterSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('counters')->truncate();
        for ($i = 1; $i <= 20; $i++) {
            DB::table('counters')->insert(['name' => 'Loket '.$i, 'created_at'=>now(), 'updated_at'=>now()]);
        }
    }
}
