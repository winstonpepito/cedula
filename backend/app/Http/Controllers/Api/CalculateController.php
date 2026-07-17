<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\CedulaCalculator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CalculateController extends Controller
{
    public function __invoke(Request $request, CedulaCalculator $calculator)
    {
        $data = $request->validate([
            'applicant_type' => ['required', Rule::in([Application::TYPE_INDIVIDUAL, Application::TYPE_CORPORATION])],
            'delivery_mode' => ['required', Rule::in([
                Application::MODE_SOFT_COPY,
                Application::MODE_PICKUP,
                Application::MODE_DELIVERY,
            ])],
            'barangay_id' => ['nullable', 'exists:barangays,id'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'thirteenth_month' => ['nullable', 'numeric', 'min:0'],
            'other_bonuses' => ['nullable', 'numeric', 'min:0'],
            'property_value' => ['nullable', 'numeric', 'min:0'],
            'gross_receipts' => ['nullable', 'numeric', 'min:0'],
            'annual_income' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($data['delivery_mode'] === Application::MODE_DELIVERY) {
            $request->validate(['barangay_id' => ['required', 'exists:barangays,id']]);
        }

        return response()->json($calculator->calculate($data));
    }
}
