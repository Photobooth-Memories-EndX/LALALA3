<?php

namespace Database\Seeders;

use App\Models\Classification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        // \App\Models\User::factory(10)->create();
        $this->call(PageSeeder::class);
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
        // $classification = new Classification([
        //     'classification_name' => 'Leadership'
        // ]);
        // $classification->save();
        Classification::insert($data);
    }
}
