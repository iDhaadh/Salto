<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lock_id')->constrained()->cascadeOnDelete();
            $table->string('severity'); // low|flat
            $table->string('status')->default('open'); // open|resolved
            $table->timestamp('opened_at');
            $table->timestamp('last_notified_at')->nullable();
            $table->unsignedInteger('notify_count')->default(0);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['lock_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
