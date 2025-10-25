<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class CounterSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $driver = DB::connection()->getDriverName();

        // 1) Matikan FK di level koneksi (JANGAN dalam transaction)
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            Schema::disableForeignKeyConstraints();
        }

        // 2) Hapus anak dulu, baru parent
        if (Schema::hasTable('staff_assignments')) {
            DB::table('staff_assignments')->delete();
        }
        if (Schema::hasTable('ratings')) {
            DB::table('ratings')->delete();
        }
        if (Schema::hasTable('counter_service')) {
            DB::table('counter_service')->delete();
        }
        if (Schema::hasTable('counters')) {
            DB::table('counters')->delete();
        }

        // 3) Reset auto-increment / sequence
        if ($driver === 'sqlite') {
            // reset semua sequence terkait
            $tables = ['staff_assignments','ratings','counter_service','counters'];
            foreach ($tables as $t) {
                DB::statement("DELETE FROM sqlite_sequence WHERE name = '$t'");
            }
        } elseif (in_array($driver, ['mysql','mariadb'])) {
            DB::statement('ALTER TABLE staff_assignments AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE ratings AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE counter_service AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE counters AUTO_INCREMENT = 1');
        }

        // 4) Nyalakan lagi FK
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            Schema::enableForeignKeyConstraints();
        }

        // 5) Insert Loket 1–20 (ID eksplisit biar pasti 1..20)
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            $rows[] = [
                'id'         => $i,
                'name'       => 'Loket '.$i,
                'location'   => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('counters')->insert($rows);
    }
}
