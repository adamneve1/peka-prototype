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
        $comments = [
            'Pelayanan oke.',
            'Cepat dan ramah.',
            'Proses agak lama.',
            'Petugas helpful.',
            'Perlu antre lebih rapi.',
        ];

        $rows = [];
        // ganti jumlah sesuai kebutuhan
        for ($i = 0; $i < 200; $i++) {
            $rows[] = [
                'service_id' => rand(1, 11),
                'counter_id' => rand(1, 25),
                'staff_id'   => rand(1, 20),
                'score'      => rand(1, 5),
                'comment'    => $comments[array_rand($comments)], // atau Str::random(20)
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // insert in chunks biar aman
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('ratings')->insert($chunk);
        }
    }
}
