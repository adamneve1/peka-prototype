<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('staff_assignments')->truncate();
        DB::table('staff')->truncate();

        $start = Carbon::now('Asia/Jakarta')->startOfDay();
        $counters = DB::table('counters')->orderBy('id')->get();

        foreach ($counters as $c) {
            $sid = DB::table('staff')->insertGetId([
                'name' => 'Petugas '.$c->id,
                'photo_url' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('staff_assignments')->insert([
                'counter_id' => $c->id,
                'staff_id'   => $sid,
                'starts_at'  => $start,
                'ends_at'    => null,
                'note'       => 'primary',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
