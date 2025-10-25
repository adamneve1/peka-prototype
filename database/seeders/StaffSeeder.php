<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $driver = DB::connection()->getDriverName();

        // 1) Matikan FK di level koneksi (JANGAN di dalam transaction)
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            Schema::disableForeignKeyConstraints();
        }

        // 2) Bersihkan CHILD dulu baru PARENT (karena staff_assignments -> staff adalah RESTRICT)
        if (Schema::hasTable('staff_assignments')) {
            DB::table('staff_assignments')->delete();
        }

        // ratings.staff_id sudah nullOnDelete, tapi itu cuma aktif saat DELETE di parent.
        // Kita tetap bisa hapus staff langsung; SQLite/MySQL akan set NULL otomatis.
        if (Schema::hasTable('ratings')) {
            // optional: kalau mau bersih total ratings juga, uncomment baris di bawah
            // DB::table('ratings')->delete();
        }

        // 3) Bersihkan PARENT
        DB::table('staff')->delete();

        // 4) Reset sequence/auto increment
        if ($driver === 'sqlite') {
            // reset sequence untuk tabel yang tadi dihapus
            foreach (['staff_assignments', 'staff'] as $t) {
                DB::statement("DELETE FROM sqlite_sequence WHERE name = '$t'");
            }
            // kalau kamu tadi hapus ratings juga, reset sekalian:
            // DB::statement("DELETE FROM sqlite_sequence WHERE name = 'ratings'");
        } elseif (in_array($driver, ['mysql','mariadb'])) {
            // reset AI; kalau user DB ga punya ALTER privilege, ini skip aja
            try { DB::statement('ALTER TABLE staff_assignments AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}
            try { DB::statement('ALTER TABLE staff AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}
            // try { DB::statement('ALTER TABLE ratings AUTO_INCREMENT = 1'); } catch (\Throwable $e) {}
        }

        // 5) Nyalakan lagi FK
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            Schema::enableForeignKeyConstraints();
        }

        // 6) Insert data staff baru (isi sesuai daftar lo)
        $names = [
            "Ali Amran","Deki Permana, S.T","Denanda Fattah, S.T","Desian Muliyanti, S.IP",
            "Dian Anggeraini","Dian Angriani, SE","Diding Kurniati. K","Febri Yanti",
            "Febry Rahmayanti Harahap, S.Pd","Fikri Hilmawan, SE","Golkar Rina,SE","Gunawan",
            "Gustaf Ardiansyah, S.Tr.Sos","Hasnawati, SE","Helmi, SE","Imron Suryadi",
            "Indah Lestari, A.Md","Iskandar","Jainal","Meilani, SE","Mhd. Doni Hadinata, S.Sos",
            "Muhamad Zaini, S.Sos","Muhammad Tazi Irwan","Mu'ijul Ikhwan","Muliadi, S.T",
            "Nurhayati","Rina Juliantika","Romi Ali Jasmanto","Surya Dharma","Susilawati, S.E",
            "Syuzariansyah","Tamran Ramadan","Tarmizi","Venny Carlina","Winarto S",
            "Yopi Zulfikar, S.IP","Yopie Arnoz","Zakaria","Zulkarnedi Hendri","Rudiyanto",
        ];

        $rows = [];
        foreach ($names as $i => $name) {
            $rows[] = [
                'name'       => $name,
                'photo_url'  => "https://picsum.photos/200?random=" . ($i + 1),
                'photo_path' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('staff')->insert($rows);
    }
}
