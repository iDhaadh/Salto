<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('door_opens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('door_name');
            $table->unsignedInteger('salto_ap_id');
            $table->string('salto_uuid')->nullable();
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamp('opened_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('door_opens');
    }
};
