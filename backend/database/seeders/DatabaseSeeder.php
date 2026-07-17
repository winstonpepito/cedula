<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\BarangayDeliveryFee;
use App\Models\LandingContent;
use App\Models\TaxSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@ecedula.local'],
            [
                'name' => 'eCedula Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'delivery@ecedula.local'],
            [
                'name' => 'Delivery Rider',
                'password' => Hash::make('password'),
                'role' => User::ROLE_DELIVERY,
            ]
        );

        if (! TaxSetting::query()->exists()) {
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
                'default_city' => 'Cebu City',
                'default_province' => 'Cebu',
                'manual_payment_only' => false,
                'gcash_number' => null,
            ]);
        }

        LandingContent::query()->firstOrCreate([], [
            'headline' => 'Apply for your Community Tax Certificate online',
            'intro_text' => 'Transparent computation, secure payment, and trackable delivery — built for residents and businesses.',
            'image_path' => null,
            'image_position' => LandingContent::POSITION_AFTER,
        ]);

        $barangays = [
            ['name' => 'Poblacion', 'code' => 'POB', 'fee' => 50],
            ['name' => 'San Antonio', 'code' => 'SAN', 'fee' => 60],
            ['name' => 'San Jose', 'code' => 'SJO', 'fee' => 70],
            ['name' => 'Santa Cruz', 'code' => 'SCR', 'fee' => 80],
            ['name' => 'Bagumbong', 'code' => 'BAG', 'fee' => 90],
            ['name' => 'Malhacan', 'code' => 'MAL', 'fee' => 75],
            ['name' => 'Ibayo', 'code' => 'IBA', 'fee' => 85],
            ['name' => 'Lawang Bato', 'code' => 'LAW', 'fee' => 100],
        ];

        foreach ($barangays as $row) {
            $barangay = Barangay::query()->updateOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'is_active' => true]
            );

            BarangayDeliveryFee::query()->updateOrCreate(
                ['barangay_id' => $barangay->id],
                ['fee' => $row['fee'], 'is_active' => true]
            );
        }
    }
}
