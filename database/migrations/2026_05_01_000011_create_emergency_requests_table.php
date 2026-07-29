<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_requests', function (Blueprint $table) {
            $table->id();
            $table->string('rreb_id', 20)->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hospital_name')->nullable();
            $table->decimal('hospital_lat', 10, 7)->nullable();
            $table->decimal('hospital_lng', 10, 7)->nullable();
            $table->string('mobile_no');
            $table->string('email')->nullable();
            $table->text('pickup_address');
            $table->decimal('pickup_lat', 10, 7)->nullable();
            $table->decimal('pickup_lng', 10, 7)->nullable();
            $table->enum('type', ['1', '2'])->default('1');
            $table->enum('status', ['1', '2', '3', '4', '5', '6', '7', '8'])->default('1');
            $table->foreignId('ambulance_id')->nullable()->constrained('ambulances')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->decimal('accepted_lat', 10, 7)->nullable();
            $table->decimal('accepted_lng', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_requests');
    }
};
