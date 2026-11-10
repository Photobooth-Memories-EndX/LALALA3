<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengoptimasi sistem Task:
     * 1. Menambahkan kolom JSON `answers` di `task_submissions` dan migrasi data dari `task_submission_answers`.
     * 2. Menghapus tabel `task_submission_answers` dan kolom legacy (answer, file_path) di `task_submissions`.
     * 3. Menggabungkan `task_code_redemptions` dan `task_playlist_unlocks` menjadi 1 tabel `task_unlocks`.
     * 4. Menghapus kolom mati `assessment_id` dari tabel `tasks`.
     */
    public function up(): void
    {
        // ── 1. Update task_submissions & migrasi jawaban ke JSON ─────────
        if (Schema::hasTable('task_submissions')) {
            if (!Schema::hasColumn('task_submissions', 'answers')) {
                Schema::table('task_submissions', function (Blueprint $table) {
                    $table->json('answers')->nullable()->after('status');
                });
            }

            // Migrasi isi tabel task_submission_answers ke task_submissions.answers
            if (Schema::hasTable('task_submission_answers')) {
                $allAnswers = DB::table('task_submission_answers')->get();
                $grouped = $allAnswers->groupBy('submission_id');

                foreach ($grouped as $submissionId => $answers) {
                    $answersData = [];
                    foreach ($answers as $ans) {
                        $files = $ans->file_path;
                        if (is_string($files) && ($decoded = json_decode($files, true)) && is_array($decoded)) {
                            $files = $decoded;
                        } elseif (is_string($files) && $files !== '') {
                            $files = [$files];
                        } else {
                            $files = [];
                        }

                        $answersData[$ans->task_field_id] = [
                            'task_field_id' => (int) $ans->task_field_id,
                            'answer_text'   => $ans->answer_text,
                            'file_path'     => $files,
                        ];
                    }

                    DB::table('task_submissions')
                        ->where('id', $submissionId)
                        ->update(['answers' => json_encode($answersData)]);
                }

                // Drop tabel task_submission_answers
                Schema::dropIfExists('task_submission_answers');
            }

            // Hapus kolom legacy yang sudah tidak terpakai
            Schema::table('task_submissions', function (Blueprint $table) {
                if (Schema::hasColumn('task_submissions', 'answer')) {
                    $table->dropColumn('answer');
                }
                if (Schema::hasColumn('task_submissions', 'file_path')) {
                    $table->dropColumn('file_path');
                }
            });
        }

        // ── 2. Gabungkan tabel unlock menjadi task_unlocks ───────────────
        if (!Schema::hasTable('task_unlocks')) {
            Schema::create('task_unlocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('type', 20); // 'standalone' | 'playlist'
                $table->foreignId('task_id')->nullable()->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('training_plan_id')->nullable()->constrained('trainingplan')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'type', 'task_id', 'training_plan_id'], 'task_unlock_unique');
            });
        }

        // Migrasi data task_code_redemptions ke task_unlocks
        if (Schema::hasTable('task_code_redemptions')) {
            $redemptions = DB::table('task_code_redemptions')->get();
            foreach ($redemptions as $r) {
                DB::table('task_unlocks')->updateOrInsert(
                    [
                        'user_id'          => $r->user_id,
                        'type'             => 'standalone',
                        'task_id'          => $r->task_id,
                        'training_plan_id' => null,
                    ],
                    [
                        'created_at' => $r->created_at ?? now(),
                        'updated_at' => $r->updated_at ?? now(),
                    ]
                );
            }
            Schema::dropIfExists('task_code_redemptions');
        }

        // Migrasi data task_playlist_unlocks ke task_unlocks
        if (Schema::hasTable('task_playlist_unlocks')) {
            $playlistUnlocks = DB::table('task_playlist_unlocks')->get();
            foreach ($playlistUnlocks as $p) {
                DB::table('task_unlocks')->updateOrInsert(
                    [
                        'user_id'          => $p->user_id,
                        'type'             => 'playlist',
                        'task_id'          => null,
                        'training_plan_id' => $p->training_plan_id,
                    ],
                    [
                        'created_at' => $p->created_at ?? now(),
                        'updated_at' => $p->updated_at ?? now(),
                    ]
                );
            }
            Schema::dropIfExists('task_playlist_unlocks');
        }

        // ── 3. Bersihkan kolom mati di tabel tasks ───────────────────────
        if (Schema::hasTable('tasks') && Schema::hasColumn('tasks', 'assessment_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                try {
                    $table->dropForeign(['assessment_id']);
                } catch (\Throwable $e) {
                    // Ignore if foreign key was already dropped
                }
                $table->dropColumn('assessment_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate tables if rolled back
        if (!Schema::hasTable('task_code_redemptions')) {
            Schema::create('task_code_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('task_playlist_unlocks')) {
            Schema::create('task_playlist_unlocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('training_plan_id')->constrained('trainingplan')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('task_unlocks')) {
            Schema::dropIfExists('task_unlocks');
        }
    }
};
