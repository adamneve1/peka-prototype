<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('counters', function (Blueprint $t) {
            $t->id();
            $t->string('name');                 // WAJIB: nama loket (contoh: "Loket 1")
            $t->string('location')->nullable(); // opsional
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counters');
    }
};
