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

        // 1) Matikan FK (level koneksi)
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            Schema::disableForeignKeyConstraints();
        }

        // 2) Child → parent
        foreach (['staff_assignments','ratings','counter_service','counters'] as $t) {
            if (Schema::hasTable($t)) DB::table($t)->delete();
        }

        // 3) Reset AI/sequence
        if ($driver === 'sqlite') {
            DB::statement("DELETE FROM sqlite_sequence WHERE name IN ('staff_assignments','ratings','counter_service','counters')");
        } else {
            foreach (['staff_assignments','ratings','counter_service','counters'] as $t) {
                try { DB::statement("ALTER TABLE {$t} AUTO_INCREMENT = 1"); } catch (\Throwable $e) {}
            }
        }

        // 4) Nyalain lagi FK
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            Schema::enableForeignKeyConstraints();
        }

        // 5) Seed counters 1..20
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            $rows[] = ['id'=>$i,'name'=>"Loket {$i}",'location'=>null,'created_at'=>$now,'updated_at'=>$now];
        }
        DB::table('counters')->insert($rows);
    }
}
