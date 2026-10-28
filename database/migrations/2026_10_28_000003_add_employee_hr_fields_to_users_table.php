<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'id_team')) {
                    $table->string('id_team', 20)->nullable()->after('id_org_unit');
                }
                if (!Schema::hasColumn('users', 'id_department')) {
                    $table->string('id_department', 20)->nullable()->after('id_team');
                }
                if (!Schema::hasColumn('users', 'id_division')) {
                    $table->string('id_division', 20)->nullable()->after('id_department');
                }
                if (!Schema::hasColumn('users', 'cell_phone')) {
                    $table->string('cell_phone', 30)->nullable()->after('email');
                }
                if (!Schema::hasColumn('users', 'sex')) {
                    $table->string('sex', 10)->nullable()->after('cell_phone');
                }
                if (!Schema::hasColumn('users', 'orgunit')) {
                    $table->string('orgunit', 255)->nullable()->after('division');
                }
                if (!Schema::hasColumn('users', 'id_job_position')) {
                    $table->string('id_job_position', 20)->nullable()->after('orgunit');
                }
                if (!Schema::hasColumn('users', 'jobposition')) {
                    $table->string('jobposition', 100)->nullable()->after('id_job_position');
                }
                if (!Schema::hasColumn('users', 'positionID')) {
                    $table->string('positionID', 30)->nullable()->after('jobposition');
                }
                if (!Schema::hasColumn('users', 'appr1')) {
                    $table->string('appr1', 30)->nullable()->after('positionID');
                }
                if (!Schema::hasColumn('users', 'appr2')) {
                    $table->string('appr2', 30)->nullable()->after('appr1');
                }
                if (!Schema::hasColumn('users', 'resign')) {
                    $table->string('resign', 10)->nullable()->default('N')->after('sap_code');
                }
                if (!Schema::hasColumn('users', 'flag')) {
                    $table->tinyInteger('flag')->nullable()->default(1)->after('resign');
                }
                if (!Schema::hasColumn('users', 'jobgrade')) {
                    $table->string('jobgrade', 50)->nullable()->after('flag');
                }
                if (!Schema::hasColumn('users', 'id_job_clan')) {
                    $table->string('id_job_clan', 20)->nullable()->after('jobgrade');
                }
                if (!Schema::hasColumn('users', 'job_clan')) {
                    $table->string('job_clan', 100)->nullable()->after('id_job_clan');
                }
                if (!Schema::hasColumn('users', 'id_job_family')) {
                    $table->string('id_job_family', 20)->nullable()->after('job_clan');
                }
                if (!Schema::hasColumn('users', 'job_family')) {
                    $table->string('job_family', 100)->nullable()->after('id_job_family');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $columns = [
                    'id_team',
                    'id_department',
                    'id_division',
                    'cell_phone',
                    'sex',
                    'orgunit',
                    'id_job_position',
                    'jobposition',
                    'positionID',
                    'appr1',
                    'appr2',
                    'resign',
                    'flag',
                    'jobgrade',
                    'id_job_clan',
                    'job_clan',
                    'id_job_family',
                    'job_family',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('users', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
