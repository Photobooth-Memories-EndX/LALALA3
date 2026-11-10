<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\CertificateGenerateService;
use App\Services\OfflineCertificate\OfflineCertificateGenerationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class CertificateSettingsEnhancementAndRelationshipTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $regularUser;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'name'       => 'Admin Tester',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'role'       => User::ROLE_SUPER_ADMIN,
            'active'     => 1,
            'ssohash'    => 'test-hash-admin-' . Str::uuid(),
            'sap_code'   => 'SAP-ADM-' . rand(1000, 9999),
        ]);

        $this->regularUser = User::factory()->create([
            'name'       => 'Regular Tester',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'role'       => 1,
            'active'     => 1,
            'ssohash'    => 'test-hash-user-' . Str::uuid(),
            'sap_code'   => 'SAP-USR-' . rand(1000, 9999),
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Enhancement Test Classification'],
            ['slug' => 'enhancement-test-classification']
        );
    }

    /**
     * 1. Add new Certificate Setting with default institution & dates.
     */
    public function test_add_certificate_setting_with_default_institution_and_dates(): void
    {
        $content = Content::create([
            'title'             => 'Test Content For Cert',
            'slug'              => 'test-content-cert-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('certificate.setting.store'), [
            'certificate_type'     => 'content',
            'item_id'              => $content->id,
            'minimum_progress'     => 100,
            'issuer_name'          => 'OWL Training Management',
            'institution'          => '', // empty should default to PT KRAKATAU POSCO
            'issued_date'          => '2026-11-12',
            'expired_date'         => '2027-11-12',
            'use_default_template' => 1,
            'is_active'            => 1,
        ]);

        $response->assertRedirect(route('certificate.setting.index'));

        $setting = CertificateSetting::where('content_id', $content->id)->first();
        $this->assertNotNull($setting);
        $this->assertEquals('PT KRAKATAU POSCO', $setting->institution);
        $this->assertEquals('2026-11-12', $setting->issued_date->format('Y-m-d'));
        $this->assertEquals('2027-11-12', $setting->expired_date->format('Y-m-d'));
        $this->assertEquals('YES', $setting->certificate_available);
    }

    /**
     * 2. Add Certificate Setting with custom manual institution.
     */
    public function test_add_certificate_setting_with_custom_institution(): void
    {
        $content = Content::create([
            'title'             => 'Custom Inst Content',
            'slug'              => 'custom-inst-cert-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('certificate.setting.store'), [
            'certificate_type'     => 'content',
            'item_id'              => $content->id,
            'minimum_progress'     => 100,
            'issuer_name'          => 'PT Krakatau Posco',
            'institution'          => 'PT DAFA',
            'issued_date'          => '2026-11-12',
            'expired_date'         => '2027-11-12',
            'use_default_template' => 1,
            'is_active'            => 0,
        ]);

        $response->assertRedirect(route('certificate.setting.index'));

        $setting = CertificateSetting::where('content_id', $content->id)->first();
        $this->assertNotNull($setting);
        $this->assertEquals('PT DAFA', $setting->institution);
        $this->assertEquals('NO', $setting->certificate_available);
    }

    /**
     * 3. Validation rejects expired_date earlier than issued_date.
     */
    public function test_validation_rejects_expired_date_before_issued_date(): void
    {
        $content = Content::create([
            'title'             => 'Invalid Date Range Content',
            'slug'              => 'invalid-date-range-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $response = $this->actingAs($this->adminUser)->from(route('certificate.setting.index'))->post(route('certificate.setting.store'), [
            'certificate_type'     => 'content',
            'item_id'              => $content->id,
            'minimum_progress'     => 100,
            'issuer_name'          => 'OWL Training Management',
            'institution'          => 'PT KRAKATAU POSCO',
            'issued_date'          => '2026-11-12',
            'expired_date'         => '2026-11-10', // earlier than issued_date!
            'use_default_template' => 1,
            'is_active'            => 1,
        ]);

        $response->assertSessionHasErrors(['expired_date']);
    }

    /**
     * 4. Edit existing Certificate Setting.
     */
    public function test_edit_existing_certificate_setting(): void
    {
        $content = Content::create([
            'title'             => 'Edit Cert Content',
            'slug'              => 'edit-cert-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $setting = CertificateSetting::create([
            'certificate_type'     => 'content',
            'content_id'           => $content->id,
            'minimum_progress'     => 100,
            'minimum_score'        => 80,
            'issuer_name'          => 'OWL Training Management',
            'institution'          => 'PT KRAKATAU POSCO',
            'issued_date'          => '2026-11-12',
            'expired_date'         => '2027-11-12',
            'is_active'            => true,
            'use_default_template' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('certificate.setting.update', $setting), [
            'certificate_type'     => 'content',
            'item_id'              => $content->id,
            'minimum_progress'     => 80,
            'issuer_name'          => 'PT Krakatau Posco',
            'institution'          => 'PT DAFA MANDIRI',
            'issued_date'          => '2026-12-01',
            'expired_date'         => '2028-12-01',
            'use_default_template' => 1,
            'is_active'            => 0,
        ]);

        $response->assertRedirect(route('certificate.setting.index'));

        $setting->refresh();
        $this->assertEquals('PT DAFA MANDIRI', $setting->institution);
        $this->assertEquals('2026-12-01', $setting->issued_date->format('Y-m-d'));
        $this->assertEquals('2028-12-01', $setting->expired_date->format('Y-m-d'));
        $this->assertEquals(0, $setting->is_active);
        $this->assertEquals('NO', $setting->certificate_available);
    }

    /**
     * 5. Historical Snapshot: Modifying CertificateSetting does NOT change already-issued Certificate.
     */
    public function test_historical_snapshot_is_preserved_when_settings_change(): void
    {
        $content = Content::create([
            'title'             => 'Snapshot Preservation Content',
            'slug'              => 'snapshot-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $setting = CertificateSetting::create([
            'certificate_type'     => 'content',
            'content_id'           => $content->id,
            'minimum_progress'     => 100,
            'minimum_score'        => 80,
            'issuer_name'          => 'OWL Training Management',
            'institution'          => 'PT KRAKATAU POSCO',
            'issued_date'          => '2026-11-12',
            'expired_date'         => '2027-11-12',
            'is_active'            => true,
            'use_default_template' => true,
        ]);

        ContentUser::create([
            'user_id'    => $this->regularUser->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        // Generate Certificate
        $service = app(CertificateGenerateService::class);
        $certificate = $service->generate($setting, $this->regularUser);

        $this->assertEquals('PT KRAKATAU POSCO', $certificate->display_institution);
        $this->assertEquals('2026-11-12', Carbon::parse($certificate->display_issued_date)->format('Y-m-d'));
        $this->assertEquals('2027-11-12', Carbon::parse($certificate->display_expired_date)->format('Y-m-d'));

        // Admin subsequently changes the setting's institution and dates
        $setting->update([
            'institution'  => 'PT DAFA',
            'issued_date'  => '2026-12-25',
            'expired_date' => '2028-12-25',
        ]);

        // Certificate record must PRESERVE the historical snapshot!
        $certificate->refresh();
        $this->assertEquals('PT KRAKATAU POSCO', $certificate->display_institution);
        $this->assertEquals('2026-11-12', Carbon::parse($certificate->display_issued_date)->format('Y-m-d'));
        $this->assertEquals('2027-11-12', Carbon::parse($certificate->display_expired_date)->format('Y-m-d'));
    }

    /**
     * 6. Certificate Detail page renders information and dates formatted DD-MM-YYYY.
     */
    public function test_certificate_detail_page_displays_information_fields(): void
    {
        $content = Content::create([
            'title'             => 'Detail Page Content',
            'slug'              => 'detail-page-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $setting = CertificateSetting::create([
            'certificate_type'     => 'content',
            'content_id'           => $content->id,
            'minimum_progress'     => 100,
            'minimum_score'        => 80,
            'issuer_name'          => 'OWL Training Management',
            'institution'          => 'PT KRAKATAU POSCO',
            'issued_date'          => '2026-11-12',
            'expired_date'         => '2027-11-12',
            'is_active'            => true,
            'use_default_template' => true,
        ]);

        ContentUser::create([
            'user_id'    => $this->regularUser->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        $certificate = app(CertificateGenerateService::class)->generate($setting, $this->regularUser);

        $response = $this->actingAs($this->regularUser)->get(route('certificate.show', $certificate->credential_id));
        $response->assertOk();
        $response->assertSee('Certificate Information');
        $response->assertSee('Certificate Available');
        $response->assertSee('YES');
        $response->assertSee('PT KRAKATAU POSCO');
        $response->assertSee('12-11-2026'); // DD-MM-YYYY
        $response->assertSee('12-11-2027'); // DD-MM-YYYY
    }

    /**
     * 7. Public Certificate Verification page remains accessible without Auth.
     */
    public function test_public_certificate_verify_page_remains_accessible(): void
    {
        $content = Content::create([
            'title'             => 'Verify Content',
            'slug'              => 'verify-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $setting = CertificateSetting::create([
            'certificate_type'     => 'content',
            'content_id'           => $content->id,
            'minimum_progress'     => 100,
            'minimum_score'        => 80,
            'issuer_name'          => 'OWL Training Management',
            'institution'          => 'PT KRAKATAU POSCO',
            'issued_date'          => '2026-11-12',
            'expired_date'         => '2027-11-12',
            'is_active'            => true,
            'use_default_template' => true,
        ]);

        ContentUser::create([
            'user_id'    => $this->regularUser->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        $certificate = app(CertificateGenerateService::class)->generate($setting, $this->regularUser);

        // Access verify page anonymously (no auth)
        $response = $this->get(route('certificate.verify', $certificate->credential_id));
        $response->assertOk();
        $response->assertSee('VERIFIED CERTIFICATE');
        $response->assertSee($certificate->certificate_number);
        $response->assertSee('PT KRAKATAU POSCO');
    }

    /**
     * 8. Offline Training Relationship: OfflineTrainingUser <-> CertificateSetting.
     */
    public function test_offline_training_user_relationship_with_certificate_setting(): void
    {
        $training = OfflineTraining::create([
            'title'       => 'Safety Induction Training',
            'course_name' => 'Safety Induction',
            'status'      => 'Closed',
        ]);

        $setting = CertificateSetting::create([
            'certificate_type'    => 'training_offline',
            'offline_training_id' => $training->id,
            'minimum_progress'    => 100,
            'minimum_score'       => 80,
            'issuer_name'         => 'OWL Training Management',
            'institution'         => 'PT KRAKATAU POSCO',
            'is_active'           => true,
        ]);

        // Create participant with nullable certificate_id
        $participant = OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->regularUser->id,
            'employee_id'         => 'EMP-' . rand(1000, 9999),
            'name'                => 'Offline Participant',
            'certificate_id'      => null, // nullable initially
            'progress'            => 100,
            'attendance'          => true,
        ]);

        $this->assertNull($participant->certificateSetting);

        // Associate with CertificateSetting
        $participant->update(['certificate_id' => $setting->id]);
        $participant->refresh();

        $this->assertNotNull($participant->certificateSetting);
        $this->assertEquals($setting->id, $participant->certificateSetting->id);

        // Check inverse relationship
        $this->assertTrue($setting->offlineTrainingUsers->contains('id', $participant->id));
    }

    /**
     * 9. Database rejects invalid foreign key certificate_id.
     */
    public function test_database_rejects_non_existent_certificate_id(): void
    {
        $training = OfflineTraining::create([
            'title'       => 'Constraint Test Training',
            'course_name' => 'Constraint Test',
            'status'      => 'Closed',
        ]);

        $this->expectException(QueryException::class);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->regularUser->id,
            'name'                => 'Bad FK Participant',
            'certificate_id'      => 999999999, // non-existent setting id
        ]);
    }

    /**
     * 10. RestrictOnDelete protects CertificateSetting from accidental deletion when referenced.
     */
    public function test_restrict_on_delete_protects_referenced_certificate_setting(): void
    {
        $training = OfflineTraining::create([
            'title'       => 'Restrict On Delete Training',
            'course_name' => 'Restrict Test',
            'status'      => 'Closed',
        ]);

        $setting = CertificateSetting::create([
            'certificate_type'    => 'training_offline',
            'offline_training_id' => $training->id,
            'issuer_name'         => 'OWL Training Management',
            'institution'         => 'PT KRAKATAU POSCO',
            'is_active'           => true,
        ]);

        $participant = OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'certificate_id'      => $setting->id,
            'name'                => 'Protected Participant',
        ]);

        $this->expectException(QueryException::class);
        $setting->delete(); // Should trigger restrict constraint!
    }

    /**
     * 11. DataTable AJAX returns institution, issued_date, expired_date, and status.
     */
    public function test_datatable_ajax_returns_new_certificate_fields(): void
    {
        $content = Content::create([
            'title'             => 'DataTable Cert Content',
            'slug'              => 'dt-cert-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        CertificateSetting::create([
            'certificate_type'     => 'content',
            'content_id'           => $content->id,
            'minimum_progress'     => 100,
            'issuer_name'          => 'OWL Training Management',
            'institution'          => 'PT KRAKATAU POSCO',
            'issued_date'          => '2026-11-12',
            'expired_date'         => '2027-11-12',
            'is_active'            => true,
            'use_default_template' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('certificate.setting.datatable'));
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['type', 'name', 'institution', 'issued_date', 'expired_date', 'assessment', 'status', 'action']
            ]
        ]);

        $data = $response->json('data');
        $match = collect($data)->first(fn ($item) => str_contains($item['name'], 'DataTable Cert Content'));
        $this->assertNotNull($match);
        $this->assertStringContainsString('PT KRAKATAU POSCO', $match['institution']);
        $this->assertStringContainsString('12-11-2026', $match['issued_date']);
        $this->assertStringContainsString('12-11-2027', $match['expired_date']);
        $this->assertStringContainsString('Available: YES', $match['status']);
    }
}
