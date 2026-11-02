<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('offline_training_users', function (Blueprint $table) {
            if (!Schema::hasColumn('offline_training_users', 'certificate_id')) {
                $table->unsignedBigInteger('certificate_id')
                    ->nullable()
                    ->after('offline_training_id')
                    ->index();
            }
        });

        if (Schema::hasTable('offline_training_users') &&
            Schema::hasTable('certificate_settings') &&
            Schema::hasColumn('offline_training_users', 'certificate_id') &&
            !$this->hasForeignKey('offline_training_users', 'offline_training_users_certificate_id_foreign')) {

            Schema::table('offline_training_users', function (Blueprint $table) {
                $table->foreign('certificate_id')
                    ->references('id')
                    ->on('certificate_settings')
                    ->restrictOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('offline_training_users')) {
            if ($this->hasForeignKey('offline_training_users', 'offline_training_users_certificate_id_foreign')) {
                Schema::table('offline_training_users', function (Blueprint $table) {
                    $table->dropForeign(['certificate_id']);
                });
            }

            if (Schema::hasColumn('offline_training_users', 'certificate_id')) {
                Schema::table('offline_training_users', function (Blueprint $table) {
                    $table->dropColumn('certificate_id');
                });
            }
        }
    }

    /**
     * Check if a foreign key exists in a table.
     */
    protected function hasForeignKey(string $table, string $foreignKeyName): bool
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            $result = DB::select("
                SELECT COUNT(*) as aggregate_count 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = ? 
                  AND CONSTRAINT_NAME = ? 
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ", [$table, $foreignKeyName]);

            return !empty($result) && ($result[0]->aggregate_count ?? 0) > 0;
        }

        return false;
    }
};
