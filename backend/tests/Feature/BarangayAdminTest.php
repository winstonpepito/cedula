<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\BarangayDeliveryFee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarangayAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_delivery_fee(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $barangay = Barangay::create(['name' => 'Lahug', 'code' => 'LAH', 'is_active' => true]);
        BarangayDeliveryFee::create(['barangay_id' => $barangay->id, 'fee' => 50, 'is_active' => true]);

        $response = $this->actingAs($admin)->putJson("/api/admin/barangays/{$barangay->id}", [
            'delivery_fee' => 125.5,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.delivery_fee', 125.5)
            ->assertJsonPath('data.deliveryFee.fee', 125.5);

        $this->assertDatabaseHas('barangay_delivery_fees', [
            'barangay_id' => $barangay->id,
            'fee' => 125.5,
        ]);

        $list = $this->actingAs($admin)->getJson('/api/admin/barangays');
        $list->assertOk();
        $row = collect($list->json('data'))->firstWhere('id', $barangay->id);
        $this->assertEquals(125.5, (float) $row['delivery_fee']);
        $this->assertEquals(125.5, (float) $row['deliveryFee']['fee']);
    }
}
