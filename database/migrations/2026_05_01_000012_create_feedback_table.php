<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('request_id')->nullable()->constrained('emergency_requests')->nullOnDelete();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('message')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('driver_viewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
