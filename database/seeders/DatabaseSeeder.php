<?php

namespace Database\Seeders;

use App\Models\Classification;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UserSeeder::class);
        $this->call(PageSeeder::class);
        $this->call(RoleAccessPermissionSeeder::class);
        $this->call(LearningHoursPermissionSeeder::class);
        $this->call(RecordPermissionSeeder::class);
        $this->call(ApproverRoleSeeder::class);
        // $this->call(OfflineTrainingDemoSeeder::class);
        // $this->call(OfflineTrainingOptionSeeder::class);
        $this->call(ClassificationSeeder::class);
        $this->call(ClassificationHistorySeeder::class);

        $data = [
            ['classification_name' => 'Leadership'],
            ['classification_name' => 'Basic mindset'],
            ['classification_name' => 'Job functional'],
            ['classification_name' => 'Maintenance'],
            ['classification_name' => 'Language'],
            ['classification_name' => 'Innovation'],
            ['classification_name' => 'SHE'],
            ['classification_name' => 'ISO Standard'],
            ['classification_name' => 'Technology'],
            ['classification_name' => 'Other']
        ];

        foreach ($data as $item) {
            Classification::firstOrCreate($item);
        }
    }
}
