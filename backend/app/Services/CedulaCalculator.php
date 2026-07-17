<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Barangay;
use App\Models\TaxSetting;
use Carbon\Carbon;

class CedulaCalculator
{
    public function calculate(array $input, ?TaxSetting $settings = null, ?Carbon $asOf = null): array
    {
        $settings ??= TaxSetting::current();
        $asOf ??= now();

        $type = $input['applicant_type'];
        $deliveryMode = $input['delivery_mode'] ?? Application::MODE_SOFT_COPY;
        $barangayId = $input['barangay_id'] ?? null;

        if ($type === Application::TYPE_INDIVIDUAL) {
            $monthly = (float) ($input['monthly_salary'] ?? 0);
            $thirteenth = (float) ($input['thirteenth_month'] ?? 0);
            $bonuses = (float) ($input['other_bonuses'] ?? 0);
            $annualIncome = round(($monthly * 12) + $thirteenth + $bonuses, 2);

            $baseTax = (float) $settings->individual_base_tax;
            $rateAmount = (float) $settings->individual_rate_amount;
            $ratePer = max((float) $settings->individual_rate_per, 1);
            $cap = (float) $settings->individual_additional_cap;

            $units = (int) floor($annualIncome / $ratePer);
            $additionalTax = min($units * $rateAmount, $cap);

            $details = [
                'monthly_salary' => $monthly,
                'annual_from_salary' => round($monthly * 12, 2),
                'thirteenth_month' => $thirteenth,
                'other_bonuses' => $bonuses,
                'annual_income' => $annualIncome,
                'rate_units' => $units,
            ];
        } else {
            $property = (float) ($input['property_value'] ?? 0);
            $grossReceipts = (float) ($input['gross_receipts'] ?? $input['annual_income'] ?? 0);

            $baseTax = (float) $settings->corporation_base_tax;
            $rateAmount = (float) $settings->corporation_rate_amount;
            $ratePer = max((float) $settings->corporation_rate_per, 1);
            $cap = (float) $settings->corporation_additional_cap;

            $propertyUnits = (int) floor($property / $ratePer);
            $incomeUnits = (int) floor($grossReceipts / $ratePer);
            $propertyTax = $propertyUnits * $rateAmount;
            $incomeTax = $incomeUnits * $rateAmount;
            $additionalTax = min($propertyTax + $incomeTax, $cap);
            $annualIncome = $grossReceipts;

            $details = [
                'property_value' => $property,
                'gross_receipts' => $grossReceipts,
                'property_units' => $propertyUnits,
                'income_units' => $incomeUnits,
                'property_additional_tax' => $propertyTax,
                'income_additional_tax' => $incomeTax,
                'annual_income' => $annualIncome,
            ];
        }

        $communityTax = round($baseTax + $additionalTax, 2);
        [$interestMonths, $interestAmount] = $this->computeInterest($communityTax, $settings, $asOf);

        $deliveryFee = 0.0;
        if ($deliveryMode === Application::MODE_DELIVERY && $barangayId) {
            $barangay = Barangay::with('deliveryFee')->find($barangayId);
            if ($barangay?->deliveryFee?->is_active) {
                $deliveryFee = (float) $barangay->deliveryFee->fee;
            }
        }

        $convenienceFee = (float) $settings->convenience_fee;
        $serverFee = (float) $settings->server_fee;
        $paymentProcessorFee = (float) $settings->payment_processor_fee;

        $totalDue = round(
            $communityTax + $interestAmount + $deliveryFee + $convenienceFee + $serverFee + $paymentProcessorFee,
            2
        );

        return [
            'applicant_type' => $type,
            'delivery_mode' => $deliveryMode,
            'base_tax' => round($baseTax, 2),
            'additional_tax' => round($additionalTax, 2),
            'community_tax_total' => $communityTax,
            'interest_months' => $interestMonths,
            'interest_amount' => $interestAmount,
            'delivery_fee' => round($deliveryFee, 2),
            'convenience_fee' => round($convenienceFee, 2),
            'server_fee' => round($serverFee, 2),
            'payment_processor_fee' => round($paymentProcessorFee, 2),
            'total_due' => $totalDue,
            'annual_income' => $details['annual_income'] ?? 0,
            'details' => $details,
            'settings_snapshot' => $settings->toSnapshot(),
            'as_of' => $asOf->toDateString(),
        ];
    }

    /**
     * @return array{0:int,1:float}
     */
    private function computeInterest(float $communityTax, TaxSetting $settings, Carbon $asOf): array
    {
        $year = $asOf->year;
        $deadline = Carbon::create(
            $year,
            (int) $settings->deadline_month,
            (int) $settings->deadline_day
        )->endOfDay();

        if ($asOf->lte($deadline)) {
            return [0, 0.0];
        }

        if ($settings->interest_counts_from_january) {
            $months = $asOf->month;
        } else {
            $months = max(1, $asOf->diffInMonths($deadline) + 1);
        }

        $rate = ((float) $settings->interest_rate_percent) / 100;
        $interest = round($communityTax * $rate * $months, 2);

        return [$months, $interest];
    }
}
