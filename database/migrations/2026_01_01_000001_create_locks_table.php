<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locks', function (Blueprint $table) {
            $table->id();
            $table->string('salto_id')->unique();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('battery_status')->default('unknown'); // normal|low|flat|unknown
            $table->timestamp('battery_changed_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('battery_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locks');
    }
};
