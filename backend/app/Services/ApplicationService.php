<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Payment;
use App\Models\StatusLog;
use App\Models\TaxSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApplicationService
{
    public function __construct(
        private CedulaCalculator $calculator,
        private DocumentService $documents,
    ) {}

    public function create(array $data): Application
    {
        return DB::transaction(function () use ($data) {
            $settings = TaxSetting::current();
            $calc = $this->calculator->calculate($data, $settings);

            $application = Application::create([
                'tracking_number' => $this->generateTrackingNumber(),
                'public_token' => Str::random(48),
                'applicant_type' => $data['applicant_type'],
                'first_name' => $data['first_name'] ?? null,
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'corporation_name' => $data['corporation_name'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'tin' => $data['tin'] ?? null,
                'birthdate' => $data['birthdate'] ?? null,
                'civil_status' => $data['civil_status'] ?? null,
                'citizenship' => $data['citizenship'] ?? 'Filipino',
                'occupation' => $data['occupation'] ?? null,
                'address_line' => $data['address_line'],
                'city' => $data['city'] ?? $settings->default_city,
                'province' => $data['province'] ?? $settings->default_province,
                'barangay_id' => $data['barangay_id'],
                'delivery_mode' => $data['delivery_mode'],
                'monthly_salary' => $data['monthly_salary'] ?? null,
                'thirteenth_month' => $data['thirteenth_month'] ?? null,
                'other_bonuses' => $data['other_bonuses'] ?? null,
                'annual_income' => $calc['annual_income'],
                'property_value' => $data['property_value'] ?? null,
                'gross_receipts' => $data['gross_receipts'] ?? null,
                'tax_snapshot' => $calc['settings_snapshot'],
                'breakdown' => $calc,
                'base_tax' => $calc['base_tax'],
                'additional_tax' => $calc['additional_tax'],
                'interest_amount' => $calc['interest_amount'],
                'community_tax_total' => $calc['community_tax_total'],
                'delivery_fee' => $calc['delivery_fee'],
                'convenience_fee' => $calc['convenience_fee'],
                'server_fee' => $calc['server_fee'],
                'payment_processor_fee' => $calc['payment_processor_fee'],
                'total_due' => $calc['total_due'],
                'interest_months' => $calc['interest_months'],
                'status' => Application::STATUS_AWAITING_PAYMENT,
            ]);

            $this->logStatus($application, null, Application::STATUS_AWAITING_PAYMENT, 'Application submitted', 'applicant');

            Payment::create([
                'application_id' => $application->id,
                'method' => 'pending',
                'status' => 'pending',
                'amount' => $application->total_due,
            ]);

            return $application->load(['barangay.deliveryFee', 'payments', 'documents', 'statusLogs']);
        });
    }

    public function markPaid(Application $application, Payment $payment, string $method, ?string $note = null): Application
    {
        return DB::transaction(function () use ($application, $payment, $method, $note) {
            if ($application->isPaid()) {
                return $application->fresh(['barangay', 'payments', 'documents', 'statusLogs', 'paymentProofs']);
            }

            $from = $application->status;
            $next = Application::STATUS_PAID;

            if ($application->delivery_mode === Application::MODE_PICKUP) {
                $next = Application::STATUS_READY_FOR_PICKUP;
            } elseif ($application->delivery_mode === Application::MODE_DELIVERY) {
                $next = Application::STATUS_PROCESSING;
            } elseif ($application->delivery_mode === Application::MODE_SOFT_COPY) {
                $next = Application::STATUS_PAID;
            }

            $payment->update([
                'method' => $method,
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $application->update([
                'status' => $next,
                'paid_at' => now(),
            ]);

            $this->logStatus($application, $from, $next, $note ?? 'Payment confirmed', 'system');
            $this->documents->generateForPaidApplication($application->fresh());

            return $application->fresh(['barangay', 'payments', 'documents', 'statusLogs', 'paymentProofs']);
        });
    }

    public function updateStatus(Application $application, string $toStatus, ?int $userId, string $actorType, ?string $note = null): Application
    {
        $from = $application->status;
        $application->update(['status' => $toStatus]);
        $this->logStatus($application, $from, $toStatus, $note, $actorType, $userId);

        return $application->fresh(['barangay', 'payments', 'documents', 'statusLogs', 'paymentProofs']);
    }

    public function logStatus(
        Application $application,
        ?string $from,
        string $to,
        ?string $note = null,
        string $actorType = 'system',
        ?int $userId = null,
    ): StatusLog {
        return StatusLog::create([
            'application_id' => $application->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'user_id' => $userId,
            'actor_type' => $actorType,
        ]);
    }

    private function generateTrackingNumber(): string
    {
        do {
            $number = 'ECD-'.now()->format('Y').'-'.strtoupper(Str::random(8));
        } while (Application::where('tracking_number', $number)->exists());

        return $number;
    }
}
