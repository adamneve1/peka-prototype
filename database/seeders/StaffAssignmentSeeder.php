<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StaffAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $tz = 'Asia/Jakarta';

        // mapping nama => counter id (null = skip)
        $mapping = [
            "Ali Amran"                           => 14,
            "Deki Permana, S.T"                   =>  9,
            "Denanda Fattah, S.T"                 => null,
            "Desian Muliyanti, S.IP"              => null,
            "Dian Anggeraini"                     =>  6,
            "Dian Angriani, SE"                   => 18,
            "Diding Kurniati. K"                  => 17,
            "Febri Yanti"                         =>  4,
            "Febry Rahmayanti Harahap, S.Pd"      => null,
            "Fikri Hilmawan, SE"                  => 18,
            "Golkar Rina,SE"                      =>  2,
            "Gunawan"                             => 17,
            "Gustaf Ardiansyah, S.Tr.Sos"         => null,
            "Hasnawati, SE"                       =>  7,
            "Helmi, SE"                           => 11,
            "Imron Suryadi"                       =>  2,
            "Indah Lestari, A.Md"                 => 12,
            "Iskandar"                            => 15,
            "Jainal"                              => 20,
            "Meilani, SE"                         => null,
            "Mhd. Doni Hadinata, S.Sos"           =>  5,
            "Muhamad Zaini, S.Sos"                => 13,
            "Muhammad Tazi Irwan"                 => 15,
            "Mu'ijul Ikhwan"                      => 19,
            "Muliadi, S.T"                        => 13,
            "Nurhayati"                           =>  7,
            "Rina Juliantika"                     =>  8,
            "Romi Ali Jasmanto"                   => 17,
            "Surya Dharma"                        => 14,
            "Susilawati, S.E"                     =>  1,
            "Syuzariansyah"                       => 14,
            "Tamran Ramadan"                      => 14,
            "Tarmizi"                             => null,
            "Venny Carlina"                       =>  3,
            "Winarto S"                           => 17,
            "Yopi Zulfikar, S.IP"                 => 16,
            "Yopie Arnoz"                         => null,
            "Zakaria"                             => 13,
            "Zulkarnedi Hendri"                   => 10,
            "Rudiyanto"                           => 20,
        ];

        // Start = hari ini 00:00 WIB; End = 26/10/2026 23:59:59 WIB
        $startJakarta = Carbon::now($tz)->startOfDay();
        $endJakarta   = Carbon::create(2026, 10, 26, 23, 59, 59, $tz);

        DB::transaction(function () use ($mapping, $startJakarta, $endJakarta) {
            $now = Carbon::now();

            // 0) FULL RESET assignments (tanpa TRUNCATE)
            DB::table('staff_assignments')->delete();
            try {
                DB::statement('ALTER TABLE staff_assignments AUTO_INCREMENT = 1');
            } catch (\Throwable $e) {
                // lewatin kalau ga punya privilege
            }

            // 1) Pastikan COUNTERS (ID persis sesuai mapping) ada
            $neededCounterIds = collect($mapping)->filter()->unique()->values();
            if ($neededCounterIds->isNotEmpty()) {
                $existing = DB::table('counters')->whereIn('id', $neededCounterIds)->pluck('id')->all();
                $missing  = $neededCounterIds->diff($existing)->values();
                if ($missing->isNotEmpty()) {
                    // insert explicit id
                    $rows = $missing->map(fn ($id) => [
                        'id'          => $id,
                        'name'        => 'Loket ' . $id,
                        'description' => null,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ])->all();
                    DB::table('counters')->insert($rows);
                }
            }

            // 2) Pastikan STAFF ada (buat yang belum)
            $byName = DB::table('staff')->pluck('id', 'name'); // name => id
            $createStaff = [];
            foreach ($mapping as $name => $counterId) {
                if (empty($counterId)) continue;
                if (!isset($byName[$name])) {
                    $createStaff[] = [
                        'name'       => $name,
                        'photo_url'  => null,
                        'photo_path' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            if (!empty($createStaff)) {
                DB::table('staff')->insert($createStaff);
                $byName = DB::table('staff')->pluck('id', 'name'); // refresh
            }

            // 3) Insert: satu assignment panjang per staff yang punya loket
            $rows = [];
            foreach ($mapping as $name => $counterId) {
                if (empty($counterId)) continue;
                $staffId = $byName[$name] ?? null;
                if (!$staffId) continue;

                $rows[] = [
                    'counter_id' => $counterId,
                    'staff_id'   => $staffId,
                    'starts_at'  => $startJakarta->copy()->timezone('UTC'),
                    'ends_at'    => $endJakarta->copy()->timezone('UTC'),
                    'note'       => 'primary',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($rows)) {
                DB::table('staff_assignments')->insert($rows);
            }
        });
    }
}
