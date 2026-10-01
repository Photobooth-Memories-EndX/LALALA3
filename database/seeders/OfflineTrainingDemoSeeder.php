<?php

namespace Database\Seeders;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class OfflineTrainingDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Keeps offline_trainings table completely clean for fresh data entry.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        OfflineTrainingUser::truncate();
        OfflineTraining::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
