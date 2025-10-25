<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counter_service', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('counter_id')->constrained()->cascadeOnDelete();

            // bisa pilih salah satu:
            $table->unique(['service_id', 'counter_id']); // cukup untuk pivot
            // atau
            // $table->primary(['service_id','counter_id']); // kalau mau strict

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counter_service');
    }
};
