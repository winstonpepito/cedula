<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Barangay;
use App\Models\BarangayDeliveryFee;
use App\Models\TaxSetting;
use App\Models\User;
use App\Services\ApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationFlowTest extends TestCase
{
    use RefreshDatabase;

    private Barangay $barangay;

    protected function setUp(): void
    {
        parent::setUp();

        TaxSetting::create([
            'individual_base_tax' => 5,
            'individual_rate_amount' => 1,
            'individual_rate_per' => 1000,
            'individual_additional_cap' => 5000,
            'corporation_base_tax' => 500,
            'corporation_rate_amount' => 1,
            'corporation_rate_per' => 5000,
            'corporation_additional_cap' => 10000,
            'interest_rate_percent' => 2,
            'deadline_month' => 2,
            'deadline_day' => 28,
            'interest_counts_from_january' => true,
            'convenience_fee' => 0,
            'server_fee' => 0,
            'payment_processor_fee' => 0,
        ]);

        $this->barangay = Barangay::create(['name' => 'Poblacion', 'code' => 'POB', 'is_active' => true]);
        BarangayDeliveryFee::create(['barangay_id' => $this->barangay->id, 'fee' => 50, 'is_active' => true]);
    }

    public function test_create_application_mock_pay_and_documents(): void
    {
        $response = $this->postJson('/api/applications', [
            'applicant_type' => 'individual',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'address_line' => '123 Main St',
            'barangay_id' => $this->barangay->id,
            'delivery_mode' => 'soft_copy',
            'monthly_salary' => 20000,
            'thirteenth_month' => 20000,
            'other_bonuses' => 50000,
        ]);

        $response->assertCreated();
        $tracking = $response->json('data.tracking_number');

        $pay = $this->postJson("/api/applications/{$tracking}/pay");
        $pay->assertOk();
        $this->assertTrue($pay->json('data.mock'));

        $mock = $this->postJson("/api/applications/{$tracking}/mock-pay");
        $mock->assertOk();
        $this->assertTrue($mock->json('data.is_paid'));
        // Soft copy is available only after admin uploads the official CTC.
        $this->assertFalse($mock->json('data.can_download_soft_copy'));

        $application = Application::where('tracking_number', $tracking)->firstOrFail();
        $this->assertDatabaseHas('documents', [
            'application_id' => $application->id,
            'type' => 'receipt',
        ]);
        $this->assertDatabaseMissing('documents', [
            'application_id' => $application->id,
            'type' => 'soft_copy_cedula',
        ]);
    }

    public function test_admin_can_upload_soft_copy_for_applicant_download(): void
    {
        Storage::fake('local');

        $create = $this->postJson('/api/applications', [
            'applicant_type' => 'individual',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'address_line' => '123 Main St',
            'barangay_id' => $this->barangay->id,
            'delivery_mode' => 'soft_copy',
            'monthly_salary' => 20000,
            'thirteenth_month' => 0,
            'other_bonuses' => 0,
        ])->assertCreated();

        $tracking = $create->json('data.tracking_number');
        $this->postJson("/api/applications/{$tracking}/pay")->assertOk();
        $this->postJson("/api/applications/{$tracking}/mock-pay")->assertOk();

        $application = Application::where('tracking_number', $tracking)->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post("/api/admin/applications/{$application->id}/soft-copy", [
                'file' => UploadedFile::fake()->create('cedula.pdf', 120, 'application/pdf'),
            ])
            ->assertOk();

        $show = $this->getJson("/api/applications/{$tracking}")->assertOk();
        $this->assertTrue($show->json('data.can_download_soft_copy'));
        $this->assertTrue(collect($show->json('data.documents'))->contains('type', 'soft_copy_cedula'));

        $documentId = collect($show->json('data.documents'))
            ->firstWhere('type', 'soft_copy_cedula')['id'];

        $this->get("/api/applications/{$tracking}/documents/{$documentId}")
            ->assertOk();
    }

    public function test_payment_proof_admin_verify(): void
    {
        Storage::fake('local');

        $create = $this->postJson('/api/applications', [
            'applicant_type' => 'individual',
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'email' => 'ana@example.com',
            'address_line' => '45 Mabini',
            'barangay_id' => $this->barangay->id,
            'delivery_mode' => 'pickup',
            'monthly_salary' => 15000,
            'thirteenth_month' => 0,
            'other_bonuses' => 0,
        ])->assertCreated();

        $tracking = $create->json('data.tracking_number');

        $this->post("/api/applications/{$tracking}/payment-proof", [
            'proof' => UploadedFile::fake()->image('gcash.png'),
            'notes' => 'Paid via GCash',
        ])->assertOk();

        $application = Application::where('tracking_number', $tracking)->firstOrFail();
        $this->assertEquals(Application::STATUS_PENDING_VERIFICATION, $application->status);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson("/api/admin/applications/{$application->id}/verify-payment", [
                'approve' => true,
            ])
            ->assertOk();

        $application->refresh();
        $this->assertTrue($application->isPaid());
    }

    public function test_delivery_status_update_by_delivery_role(): void
    {
        $application = app(ApplicationService::class)->create([
            'applicant_type' => 'individual',
            'first_name' => 'Rico',
            'last_name' => 'Reyes',
            'email' => 'rico@example.com',
            'address_line' => '9 Luna St',
            'barangay_id' => $this->barangay->id,
            'delivery_mode' => 'delivery',
            'monthly_salary' => 10000,
            'thirteenth_month' => 0,
            'other_bonuses' => 0,
        ]);

        $payment = $application->payments()->first();
        app(ApplicationService::class)->markPaid($application, $payment, 'mock');

        $rider = User::factory()->create(['role' => 'delivery']);

        $this->actingAs($rider)
            ->getJson("/api/admin/applications/{$application->id}")
            ->assertOk();

        $this->actingAs($rider)
            ->get("/api/admin/applications/{$application->id}/summary-pdf")
            ->assertOk();

        $this->actingAs($rider)
            ->patchJson("/api/admin/applications/{$application->id}/status", [
                'status' => 'out_for_delivery',
            ])
            ->assertOk();

        $this->actingAs($rider)
            ->patchJson("/api/admin/applications/{$application->id}/status", [
                'status' => 'delivered',
                'note' => 'Left with guard',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered');
    }

    public function test_admin_can_manage_delivery_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $create = $this->actingAs($admin)
            ->postJson('/api/admin/users', [
                'name' => 'Rider One',
                'email' => 'rider1@example.com',
                'password' => 'password123',
                'role' => 'delivery',
            ])
            ->assertCreated();

        $userId = $create->json('data.id');

        $this->actingAs($admin)
            ->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonFragment(['email' => 'rider1@example.com']);

        $this->actingAs($admin)
            ->putJson("/api/admin/users/{$userId}", [
                'name' => 'Rider Updated',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Rider Updated');

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$userId}")
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_delivery_cannot_access_non_delivery_application(): void
    {
        $application = app(ApplicationService::class)->create([
            'applicant_type' => 'individual',
            'first_name' => 'Mia',
            'last_name' => 'Cruz',
            'email' => 'mia@example.com',
            'address_line' => '1 Oak St',
            'barangay_id' => $this->barangay->id,
            'delivery_mode' => 'pickup',
            'monthly_salary' => 10000,
            'thirteenth_month' => 0,
            'other_bonuses' => 0,
        ]);

        $rider = User::factory()->create(['role' => 'delivery']);

        $this->actingAs($rider)
            ->getJson("/api/admin/applications/{$application->id}")
            ->assertForbidden();
    }
}
