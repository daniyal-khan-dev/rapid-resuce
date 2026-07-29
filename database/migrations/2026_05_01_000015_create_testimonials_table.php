<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30);
            $table->string('role', 50)->nullable();
            $table->text('content');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->enum('status', ['1', '2'])->default('1');
            $table->string('added_by', 50);
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('testimonials'); }
};
