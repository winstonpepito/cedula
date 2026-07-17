<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\BarangayDeliveryFee;
use App\Models\TaxSetting;
use App\Services\CedulaCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CedulaCalculatorTest extends TestCase
{
    use RefreshDatabase;

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
    }

    public function test_service_fees_added_to_total(): void
    {
        $settings = TaxSetting::current();
        $settings->update([
            'convenience_fee' => 10,
            'server_fee' => 5,
            'payment_processor_fee' => 15,
        ]);

        $calc = app(CedulaCalculator::class)->calculate([
            'applicant_type' => 'individual',
            'delivery_mode' => 'soft_copy',
            'monthly_salary' => 20000,
            'thirteenth_month' => 20000,
            'other_bonuses' => 50000,
        ], $settings->fresh(), now()->setDate(2026, 1, 15));

        $this->assertEquals(10.0, $calc['convenience_fee']);
        $this->assertEquals(5.0, $calc['server_fee']);
        $this->assertEquals(15.0, $calc['payment_processor_fee']);
        $this->assertEquals(345.0, $calc['total_due']);
    }

    public function test_individual_sample_from_spreadsheet(): void
    {
        $calc = app(CedulaCalculator::class)->calculate([
            'applicant_type' => 'individual',
            'delivery_mode' => 'soft_copy',
            'monthly_salary' => 20000,
            'thirteenth_month' => 20000,
            'other_bonuses' => 50000,
        ], TaxSetting::current(), now()->setDate(2026, 1, 15));

        $this->assertEquals(310000, $calc['annual_income']);
        $this->assertEquals(5.0, $calc['base_tax']);
        $this->assertEquals(310.0, $calc['additional_tax']);
        $this->assertEquals(315.0, $calc['community_tax_total']);
        $this->assertEquals(0.0, $calc['interest_amount']);
        $this->assertEquals(315.0, $calc['total_due']);
    }

    public function test_corporation_sample_from_spreadsheet(): void
    {
        $calc = app(CedulaCalculator::class)->calculate([
            'applicant_type' => 'corporation',
            'delivery_mode' => 'soft_copy',
            'property_value' => 1000000,
            'gross_receipts' => 5000000,
        ], TaxSetting::current(), now()->setDate(2026, 1, 15));

        $this->assertEquals(500.0, $calc['base_tax']);
        $this->assertEquals(1200.0, $calc['additional_tax']);
        $this->assertEquals(1700.0, $calc['community_tax_total']);
        $this->assertEquals(1700.0, $calc['total_due']);
    }

    public function test_delivery_fee_added(): void
    {
        $barangay = Barangay::create(['name' => 'Test', 'code' => 'TST', 'is_active' => true]);
        BarangayDeliveryFee::create(['barangay_id' => $barangay->id, 'fee' => 80, 'is_active' => true]);

        $calc = app(CedulaCalculator::class)->calculate([
            'applicant_type' => 'individual',
            'delivery_mode' => 'delivery',
            'barangay_id' => $barangay->id,
            'monthly_salary' => 20000,
            'thirteenth_month' => 20000,
            'other_bonuses' => 50000,
        ], TaxSetting::current(), now()->setDate(2026, 1, 15));

        $this->assertEquals(80.0, $calc['delivery_fee']);
        $this->assertEquals(395.0, $calc['total_due']);
    }
}
