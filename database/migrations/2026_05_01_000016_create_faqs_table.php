<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 400);
            $table->text('answer');
            $table->enum('status', ['1', '2'])->default('1');
            $table->string('added_by', 50);
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('faqs'); }
};
