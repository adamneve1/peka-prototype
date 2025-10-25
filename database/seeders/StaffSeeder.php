<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $names = [
            "Ali Amran",
            "Deki Permana, S.T",
            "Denanda Fattah, S.T",
            "Desian Muliyanti, S.IP",
            "Dian Anggeraini",
            "Dian Angriani, SE",
            "Diding Kurniati. K",
            "Febri Yanti",
            "Febry Rahmayanti Harahap, S.Pd",
            "Fikri Hilmawan, SE",
            "Golkar Rina,SE",
            "Gunawan",
            "Gustaf Ardiansyah, S.Tr.Sos",
            "Hasnawati, SE",
            "Helmi, SE",
            "Imron Suryadi",
            "Indah Lestari, A.Md",
            "Iskandar",
            "Jainal",
            "Meilani, SE",
            "Mhd. Doni Hadinata, S.Sos",
            "Muhamad Zaini, S.Sos",
            "Muhammad Tazi Irwan",
            "Mu'ijul Ikhwan",
            "Muliadi, S.T",
            "Nurhayati",
            "Rina Juliantika",
            "Romi Ali Jasmanto",
            "Surya Dharma",
            "Susilawati, S.E",
            "Syuzariansyah",
            "Tamran Ramadan",
            "Tarmizi",
            "Venny Carlina",
            "Winarto S",
            "Yopi Zulfikar, S.IP",
            "Yopie Arnoz",
            "Zakaria",
            "Zulkarnedi Hendri",
            "Rudiyanto",
        ];

        DB::transaction(function () use ($now, $names) {
            // 1) Bersihin tabel dependent dulu (RESTRICT)
            if (DB::getSchemaBuilder()->hasTable('staff_assignments')) {
                DB::table('staff_assignments')->delete();
            }

            // 2) Hapus staff (ratings akan auto-null karena nullOnDelete)
            DB::table('staff')->delete();

            // 3) Reset auto increment (hindari TRUNCATE)
            // Lewatin kalau user DB-mu ga punya ALTER privilege
            try {
                DB::statement('ALTER TABLE staff AUTO_INCREMENT = 1');
            } catch (\Throwable $e) {
                // diam aja; bukan critical
            }

            // 4) Insert data baru
            $payload = [];
            foreach ($names as $i => $name) {
                $payload[] = [
                    'name'       => $name,
                    'photo_url'  => 'https://picsum.photos/200?random=' . ($i + 1),
                    'photo_path' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('staff')->insert($payload);
        });
    }
}
