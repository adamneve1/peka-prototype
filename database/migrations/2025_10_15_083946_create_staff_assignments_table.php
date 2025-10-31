<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('counter_id')->constrained('counters');
            $t->foreignId('staff_id')->constrained('staff');
            $t->timestamp('starts_at');            // kapan mulai jaga
            $t->timestamp('ends_at')->nullable();  // kapan selesai jaga
            $t->string('note')->nullable();        // optional: "primary", "cadangan"
            $t->boolean('is_primary')->default(false); // <- tambah ini
            $t->timestamps();

            // index komposit biar query waktu + prioritas kenceng
            $t->index(['counter_id','starts_at','ends_at','is_primary'], 'sa_counter_time_primary_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_assignments');
    }
};
