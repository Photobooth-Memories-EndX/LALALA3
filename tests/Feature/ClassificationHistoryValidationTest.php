<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\OfflineTrainingOption;
use Database\Seeders\ClassificationHistorySeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClassificationHistoryValidationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Scenario 1: Seeder memasukkan seluruh master option spreadsheet.
     */
    public function test_scenario_1_seeder_populates_all_master_options(): void
    {
        $owlTypes = ['Document', 'Video', 'MOOC', 'Playlist'];
        foreach ($owlTypes as $type) {
            $this->assertDatabaseHas('offline_training_options', [
                'option_type' => 'type',
                'value'       => $type,
                'is_active'   => 1,
            ]);
        }

        $owlCategories = ['Playlist', 'All Content', 'Content Playlist'];
        foreach ($owlCategories as $cat) {
            $this->assertDatabaseHas('offline_training_options', [
                'option_type' => 'category',
                'value'       => $cat,
                'is_active'   => 1,
            ]);
        }

        $this->assertDatabaseHas('offline_training_options', [
            'option_type' => 'category',
            'value'       => 'NON KP-OWL',
            'is_active'   => 1,
        ]);
    }

    /**
     * Scenario 2: Seeder kedua kali tidak membuat duplicate record.
     */
    public function test_scenario_2_seeder_second_run_creates_no_duplicates(): void
    {
        $classCountBefore = Classification::count();
        $optionCountBefore = OfflineTrainingOption::count();

        // Run seeder second time
        $this->seed(ClassificationHistorySeeder::class);

        $this->assertSame($classCountBefore, Classification::count(), 'Classification count must remain identical.');
        $this->assertSame($optionCountBefore, OfflineTrainingOption::count(), 'OfflineTrainingOption count must remain identical.');
    }

    /**
     * Scenario 3: Main Classification spreadsheet tersedia lengkap di database.
     */
    public function test_scenario_3_main_classifications_are_available(): void
    {
        $expectedMain = [
            'Basic & Common Duties',
            'Compliance',
            'Innovation',
            'Job Functional',
            'Language',
            'Leadership',
            'Assessment/Test',
        ];

        foreach ($expectedMain as $main) {
            $this->assertDatabaseHas('classification', [
                'classification_name' => $main,
                'is_active'           => 1,
            ]);
        }
    }

    /**
     * Scenario 4: Sub Classification memiliki pemetaan yang benar.
     */
    public function test_scenario_4_sub_classifications_are_all_present_and_active(): void
    {
        $expectedSubs = [
            'Production',
            'QHSE',
            'Sales & Marketing',
            'Business & Management',
            'Management Philosophy',
            'General Skill',
            'Certification',
            'Recertification',
            'Quick Six Sigma',
            '1% Better Project',
            'Others',
            'Technology-General',
            'Technology-Mechanical',
            'Technology-Electrical/Instrument',
            'Non-Technology',
            'Korean',
            'Indonesian',
            'English',
            'Equipping',
            'Fostering',
            'Language',
            'Global Assessment',
        ];

        foreach ($expectedSubs as $sub) {
            $this->assertDatabaseHas('offline_training_options', [
                'option_type'      => 'sub_classification',
                'normalized_value' => OfflineTrainingOption::normalizeString($sub),
                'is_active'        => 1,
            ]);
        }
    }

    /**
     * Scenario 5: Course Example memiliki parent Sub Classification yang benar.
     */
    public function test_scenario_5_course_examples_have_correct_parent_sub_classification(): void
    {
        $sampleChecks = [
            'Robotic (RPA)'           => 'Production',
            'Phyton'                  => 'Technology-General',
            'ICOfR'                   => 'Non-Technology',
            'POPAL'                   => 'Certification',
            'POIPPU'                  => 'Certification',
            'OPLB3'                   => 'Certification',
            'Operator Overhead Crane' => 'Certification',
            'GLA'                     => 'Fostering',
            'TOPIK I Preparation'     => 'Korean',
            'TOPIK II Preparation'    => 'Korean',
            'English Conversation'    => 'English',
            'Global Assessment'       => 'Global Assessment',
        ];

        foreach ($sampleChecks as $course => $expectedParent) {
            $courseOpt = OfflineTrainingOption::where('option_type', 'course_name')
                ->where('normalized_value', OfflineTrainingOption::normalizeString($course))
                ->with('parent')
                ->first();

            $this->assertNotNull($courseOpt, "Course '{$course}' must exist.");
            $this->assertNotNull($courseOpt->parent, "Course '{$course}' must have a parent.");
            $this->assertSame($expectedParent, $courseOpt->parent->value, "Course '{$course}' parent must be '{$expectedParent}'.");
        }
    }

    /**
     * Scenario 6: Delivery Method dan Sub Delivery Method terhubung dengan benar.
     */
    public function test_scenario_6_delivery_methods_and_sub_methods_are_linked(): void
    {
        $expectedLinks = [
            'On-site'    => 'Offline',
            'Off-site'   => 'Offline',
            'E-learning' => 'Online',
            'Webinar'    => 'Online',
            'Hybrid'     => 'Blended',
        ];

        foreach ($expectedLinks as $sub => $parent) {
            $subOpt = OfflineTrainingOption::where('option_type', 'sub_delivery_method')
                ->where('normalized_value', OfflineTrainingOption::normalizeString($sub))
                ->with('parent')
                ->first();

            $this->assertNotNull($subOpt, "Sub Delivery Method '{$sub}' must exist.");
            $this->assertNotNull($subOpt->parent, "Sub Delivery Method '{$sub}' must have a parent.");
            $this->assertSame($parent, $subOpt->parent->value, "Sub Delivery Method '{$sub}' parent must be '{$parent}'.");
        }
    }

    /**
     * Scenario 7: Provider dan Sub Provider terhubung dengan benar.
     */
    public function test_scenario_7_providers_and_sub_providers_are_linked(): void
    {
        $expectedLinks = [
            'Internal-Training Team' => 'Internal',
            'Internal-Unit'          => 'Internal',
            'Institution'            => 'External',
            'Individual'             => 'External',
        ];

        foreach ($expectedLinks as $sub => $parent) {
            $subOpt = OfflineTrainingOption::where('option_type', 'sub_provider')
                ->where('normalized_value', OfflineTrainingOption::normalizeString($sub))
                ->with('parent')
                ->first();

            $this->assertNotNull($subOpt, "Sub Provider '{$sub}' must exist.");
            $this->assertNotNull($subOpt->parent, "Sub Provider '{$sub}' must have a parent.");
            $this->assertSame($parent, $subOpt->parent->value, "Sub Provider '{$sub}' parent must be '{$parent}'.");
        }
    }

    /**
     * Scenario 8: Option lama tetap tersedia dan tidak terhapus.
     */
    public function test_scenario_8_historical_options_are_preserved(): void
    {
        // Historical types & categories
        $this->assertDatabaseHas('offline_training_options', ['option_type' => 'type', 'value' => 'In-House']);
        $this->assertDatabaseHas('offline_training_options', ['option_type' => 'category', 'value' => 'Advance']);

        // Historical sub providers
        $this->assertDatabaseHas('offline_training_options', ['option_type' => 'sub_provider', 'value' => 'Vendor']);
        $this->assertDatabaseHas('offline_training_options', ['option_type' => 'sub_provider', 'value' => 'Public']);

        // Historical sub classifications
        $this->assertDatabaseHas('offline_training_options', ['option_type' => 'sub_classification', 'value' => 'Technology-Electrical']);
        $this->assertDatabaseHas('offline_training_options', ['option_type' => 'sub_classification', 'value' => 'QSS']);

        // Historical classifications
        $this->assertDatabaseHas('classification', ['classification_name' => 'Maintenance']);
        $this->assertDatabaseHas('classification', ['classification_name' => 'SHE']);
    }

    /**
     * Scenario 9: Scope OWL dan NON KP-OWL tidak tercampur secara tidak sah.
     */
    public function test_scenario_9_scope_owl_and_non_kp_owl_are_valid(): void
    {
        // Global scope is uniformly used for shared vocabulary
        $options = OfflineTrainingOption::whereIn('option_type', ['type', 'category', 'delivery_method', 'sub_delivery_method', 'provider', 'sub_provider'])->get();
        foreach ($options as $opt) {
            $this->assertSame('global', $opt->scope_key, "Option '{$opt->value}' ({$opt->option_type}) must have 'global' scope.");
        }

        // NON KP-OWL category is distinct
        $nonKpOwl = OfflineTrainingOption::where('option_type', 'category')->where('normalized_value', 'non kp-owl')->first();
        $this->assertNotNull($nonKpOwl);
        $this->assertSame('NON KP-OWL', $nonKpOwl->value);
    }

    /**
     * Scenario 10: Tidak ada record yang terhapus setelah seeder dijalankan ulang.
     */
    public function test_scenario_10_no_records_deleted_after_reseed(): void
    {
        $existingOptionIds = OfflineTrainingOption::pluck('id')->all();
        $existingClassIds = Classification::pluck('id')->all();

        // Reseed
        $this->seed(ClassificationHistorySeeder::class);

        foreach ($existingOptionIds as $id) {
            $this->assertDatabaseHas('offline_training_options', ['id' => $id]);
        }

        foreach ($existingClassIds as $id) {
            $this->assertDatabaseHas('classification', ['id' => $id]);
        }
    }
}
