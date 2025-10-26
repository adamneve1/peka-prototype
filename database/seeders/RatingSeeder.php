<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RatingSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        DB::table('ratings')->delete();

        // Ambil ID valid dari DB (hindari rand(1,N))
        $serviceIds = DB::table('services')->pluck('id')->all();
        $counterIds = DB::table('counters')->pluck('id')->all();
        $staffIds   = DB::table('staff')->pluck('id')->all();

        if (!$serviceIds || !$counterIds || !$staffIds) return;

        $comments = [ null,'Pelayanan oke.','Cepat dan ramah.','Proses agak lama.','Petugas helpful.','Perlu antre lebih rapi.' ];
        $rows = [];

        for ($i = 0; $i < 200; $i++) {
            $rows[] = [
                'service_id' => $serviceIds[array_rand($serviceIds)],
                'counter_id' => $counterIds[array_rand($counterIds)],
                'staff_id'   => $staffIds[array_rand($staffIds)],
                'score'      => rand(1, 5),
                'comment'    => $comments[array_rand($comments)],
                'created_at' => $now->copy()->subDays(rand(0, 90)),
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('ratings')->insert($chunk);
        }
    }
}