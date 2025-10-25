<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Service;
use App\Models\Counter;

class CounterServiceSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Pastikan Loket 1..25 ada
        $now = now();
        $existing = Counter::whereBetween('id', [1, 25])->pluck('id')->all();
        $toInsert = [];
        for ($i = 1; $i <= 25; $i++) {
            if (! in_array($i, $existing, true)) {
                $toInsert[] = [
                    'id'         => $i,
                    'name'       => "Loket {$i}",
                    'location'   => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        if ($toInsert) {
            DB::table('counters')->upsert($toInsert, ['id'], ['name','location','updated_at']);
        }

        // 2) Definisikan roster layanan => daftar loket
        $map = [
            'Input/Rekam/Foto Data Diri' => [13, 19],
            'Pengajuan/Cetak KK/KTP/KIA' => [10, 11, 14, 17],
            'Akta Kelahiran-KIA-KK' => array_merge(range(1, 8), [17], range(21, 25)),
            'Akta Kematian-KK' => array_merge(range(1, 8), [17], range(21, 25)),
            'Akta Perkawinan/Perceraian-KK-KTP' => [2, 17],
            'Pelaporan Pindah Datang/Pindah Keluar' => array_merge(range(1, 8), [15], range(21, 25)),
            'Pelaporan Peristiwa Penting dari Luar Negeri (Kelahiran/Kematian/Perkawinan/Perceraian)' => [9],
            'Perbaikan Elemen Data Kependudukan (KK/KTP)' => [13, 10, 11],
            'Perbaikan Elemen Data Pencatatan Sipil (Kelahiran/Kematian/Perkawinan/Perceraian)' => [12],
            'Informasi, pengaduan dan Konsultasi Warga' => [18, 20],
            'Legalisir Akta dan KTP' => [16],
        ];

        // 3) Pastikan service ada (create kalau belum), lalu mapping ke loket
        foreach ($map as $serviceName => $counterIds) {
            /** @var Service $service */
            $service = Service::firstOrCreate(['name' => $serviceName], ['name' => $serviceName]);

            // validasi loket yang bener-bener ada
            $validCounterIds = Counter::whereIn('id', $counterIds)->pluck('id')->all();
            if (empty($validCounterIds)) {
                $this->command?->warn("Skip: tidak ada loket valid untuk '{$serviceName}'");
                continue;
            }

            // attach tanpa menghapus mapping lama
            $service->counters()->syncWithoutDetaching($validCounterIds);

            $this->command?->info("OK: '{$serviceName}' -> loket ".implode(',', $validCounterIds));
        }
    }
}
