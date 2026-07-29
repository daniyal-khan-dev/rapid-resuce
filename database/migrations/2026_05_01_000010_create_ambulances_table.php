b<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ambulances', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_number', 20)->unique();
            $table->enum('type', ['1', '2', '3', '4', '5'])->default('1');
            $table->enum('equipment_level', ['1', '2'])->default('1');
            $table->enum('status', ['1', '2', '3', '4'])->default('1');
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->string('card_title', 50);
            $table->text('card_description');
            $table->string('card_image', 255);
            $table->text('card_features');
            $table->decimal('card_rating', 3, 1);
            $table->unsignedInteger('card_trips');
            $table->string('added_by', 50);
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ambulances');
    }
};
