<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('ratings', function (Blueprint $t) {
  $t->id();
  $t->foreignId('counter_id')->constrained('counters');
  $t->foreignId('service_id')->constrained('services');
  $t->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
  $t->tinyInteger('score'); // 1–5 (emoji rating)
  $t->string('comment',200)->nullable(); // komentar singkat opsional
  $t->json('flags')->nullable(); // kalau mau tambah metadata
  $t->timestamps();
  $t->index(['staff_id','created_at']);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
