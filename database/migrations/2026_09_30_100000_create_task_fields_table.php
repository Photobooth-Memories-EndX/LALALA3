<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel task_fields — "pertanyaan" dinamis milik sebuah task,
 * ala Google Form. Admin bisa menambah banyak field: upload file
 * (bisa 2, 3, dst.), jawaban singkat, atau jawaban panjang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('label');                       // cth: "Upload laporan PDF"
            $table->string('type', 20)->default('file');   // file | text | textarea
            $table->boolean('required')->default(false);   // wajib diisi user?
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_fields');
    }
};
