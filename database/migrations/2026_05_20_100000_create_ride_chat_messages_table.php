<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ride_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_request_id')->constrained('emergency_requests')->cascadeOnDelete();
            $table->enum('sender_type', ['user', 'driver', 'admin']);
            $table->unsignedBigInteger('sender_id');
            $table->string('sender_name');
            $table->text('message');
            $table->boolean('is_read_driver')->default(false);
            $table->boolean('is_read_admin')->default(false);
            $table->boolean('is_read_user')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_chat_messages');
    }
};
