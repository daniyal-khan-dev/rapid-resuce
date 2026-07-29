<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('icon', 30)->default('fas fa-cogs');
            $table->string('title', 50);
            $table->text('description');
            $table->enum('status', ['1', '2'])->default('1');
            $table->string('added_by', 50);
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('services'); }
};
