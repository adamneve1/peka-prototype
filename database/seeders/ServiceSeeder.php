<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        // Matikan FK check (works di SQLite/MySQL)
        Schema::disableForeignKeyConstraints();

        // Kosongin tabel parent
        DB::table('services')->truncate();

        // Nyalain lagi FK check
        Schema::enableForeignKeyConstraints();

        // Seed data
        DB::table('services')->insert([
    ['name' => 'Input / Rekam / Foto Data Diri'],
    ['name' => 'Cetak KK / KTP / KIA'],
    ['name' => 'Akta Kelahiran - KIA - KK'],
    ['name' => 'Akta Kematian - KK'],
    ['name' => 'Akta Perkawinan / Perceraian - KK - KTP'],
    ['name' => 'Pelaporan Pindah Datang / Pindah Keluar'],
    ['name' => 'Pelaporan Peristiwa Penting dari Luar Negeri (Kelahiran / Kematian / Perkawinan / Perceraian)'],
    ['name' => 'Perbaikan Elemen Data Kependudukan (KK / KTP)'],
    ['name' => 'Perbaikan Elemen Data Pencatatan Sipil (Kelahiran / Kematian / Perkawinan / Perceraian)'],
    ['name' => 'Pengaduan dan Konsultasi Warga'],
]);

    }
}
