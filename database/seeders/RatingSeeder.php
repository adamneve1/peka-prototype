<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RatingSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ratings')->delete();

        $counters = DB::table('counters')->pluck('id');
        $services = DB::table('services')->pluck('id');
        $staff    = DB::table('staff')->pluck('id');

        $rows = [];
        $now = Carbon::now();

        foreach ($staff as $sid) {
            // ambil counter random untuk staff ini
            $counter = $counters->random();
            // generate minimal 10 rating (bisa lebih kalau mau variasi)
            $votes = rand(10, 20); 

            for ($i = 0; $i < $votes; $i++) {
                $rows[] = [
                    'counter_id' => $counter,
                    'service_id' => $services->random(),
                    'staff_id'   => $sid,
                    'score'      => rand(1, 5),
                    'comment'    => fake()->optional()->sentence(),
                    'flags'      => null,
                    'created_at' => $now->copy()->subDays(rand(0, 90)),
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('ratings')->insert($rows);
    }
}
