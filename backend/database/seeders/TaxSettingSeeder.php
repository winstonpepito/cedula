<?php

namespace Database\Seeders;

use App\Models\TaxSetting;
use Illuminate\Database\Seeder;

class TaxSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
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
            'default_city' => 'Cebu City',
            'default_province' => 'Cebu',
            'manual_payment_only' => false,
            'gcash_number' => null,
        ];

        $existing = TaxSetting::query()->first();

        if ($existing) {
            // Keep admin-tuned rates/fees; only backfill city defaults if empty.
            if (! filled($existing->default_city)) {
                $existing->default_city = 'Cebu City';
            }
            if (! filled($existing->default_province)) {
                $existing->default_province = 'Cebu';
            }
            $existing->save();

            return;
        }

        TaxSetting::query()->create($defaults);
    }
}
