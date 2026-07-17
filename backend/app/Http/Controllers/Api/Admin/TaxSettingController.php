<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxSetting;
use Illuminate\Http\Request;

class TaxSettingController extends Controller
{
    public function publicShow()
    {
        return response()->json([
            'data' => TaxSetting::current()->toPublicDefaults(),
        ]);
    }

    public function show()
    {
        return response()->json(['data' => TaxSetting::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'individual_base_tax' => ['required', 'numeric', 'min:0'],
            'individual_rate_amount' => ['required', 'numeric', 'min:0'],
            'individual_rate_per' => ['required', 'numeric', 'min:1'],
            'individual_additional_cap' => ['required', 'numeric', 'min:0'],
            'corporation_base_tax' => ['required', 'numeric', 'min:0'],
            'corporation_rate_amount' => ['required', 'numeric', 'min:0'],
            'corporation_rate_per' => ['required', 'numeric', 'min:1'],
            'corporation_additional_cap' => ['required', 'numeric', 'min:0'],
            'interest_rate_percent' => ['required', 'numeric', 'min:0'],
            'deadline_month' => ['required', 'integer', 'min:1', 'max:12'],
            'deadline_day' => ['required', 'integer', 'min:1', 'max:31'],
            'interest_counts_from_january' => ['required', 'boolean'],
            'convenience_fee' => ['required', 'numeric', 'min:0'],
            'server_fee' => ['required', 'numeric', 'min:0'],
            'payment_processor_fee' => ['required', 'numeric', 'min:0'],
            'default_city' => ['required', 'string', 'max:120'],
            'default_province' => ['required', 'string', 'max:120'],
            'manual_payment_only' => ['required', 'boolean'],
            'gcash_number' => ['nullable', 'string', 'max:40'],
        ]);

        $settings = TaxSetting::current();
        $settings->update($data);

        return response()->json(['data' => $settings->fresh()]);
    }
}
