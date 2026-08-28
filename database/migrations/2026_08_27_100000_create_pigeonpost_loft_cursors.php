<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pigeonpost_loft_cursors', function (Blueprint $table): void {
            $table->id();
            $table->string('installation_id')->index();
            $table->string('loft');
            $table->string('last_ring_number')->nullable();
            $table->unsignedInteger('dispatches_seen')->default(0);
            $table->timestampTz('updated_at');
            $table->unique(['installation_id', 'loft']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pigeonpost_loft_cursors');
    }
};
