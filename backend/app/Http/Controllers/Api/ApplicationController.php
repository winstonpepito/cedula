<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Document;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\TaxSetting;
use App\Services\ApplicationService;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function store(Request $request, ApplicationService $service)
    {
        $data = $this->validatedApplication($request);
        $application = $service->create($data);

        return response()->json(['data' => $this->publicPayload($application)], 201);
    }

    public function show(string $tracking)
    {
        $application = Application::with(['barangay', 'payments', 'documents', 'statusLogs', 'paymentProofs'])
            ->where('tracking_number', $tracking)
            ->firstOrFail();

        return response()->json(['data' => $this->publicPayload($application)]);
    }

    public function track(string $tracking)
    {
        return $this->show($tracking);
    }

    public function byToken(string $token)
    {
        $application = Application::with(['barangay', 'payments', 'documents', 'statusLogs'])
            ->where('public_token', $token)
            ->firstOrFail();

        return response()->json(['data' => $this->publicPayload($application)]);
    }

    public function pay(Request $request, string $tracking, PayMongoService $payMongo, ApplicationService $service)
    {
        $application = Application::where('tracking_number', $tracking)->firstOrFail();

        if ($application->isPaid()) {
            return response()->json(['message' => 'Already paid', 'data' => $this->publicPayload($application)]);
        }

        if (TaxSetting::current()->manual_payment_only) {
            return response()->json([
                'message' => 'Online payment is disabled. Please upload a payment proof instead.',
            ], 422);
        }

        $data = $request->validate([
            'methods' => ['nullable', 'array'],
            'methods.*' => ['in:card,gcash'],
        ]);

        $payment = $application->payments()->latest()->first()
            ?? Payment::create([
                'application_id' => $application->id,
                'method' => 'pending',
                'status' => 'pending',
                'amount' => $application->total_due,
            ]);

        $checkout = $payMongo->createCheckoutSession(
            $application,
            $payment,
            $data['methods'] ?? ['card', 'gcash']
        );

        $payment->update([
            'method' => ($checkout['mock'] ?? false) ? 'mock' : 'paymongo',
            'paymongo_checkout_id' => $checkout['checkout_id'],
            'checkout_url' => $checkout['checkout_url'],
            'meta' => $checkout['raw'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'data' => [
                'checkout_url' => $checkout['checkout_url'],
                'checkout_id' => $checkout['checkout_id'],
                'mock' => $checkout['mock'] ?? false,
                'application' => $this->publicPayload($application->fresh(['barangay', 'payments', 'documents', 'statusLogs'])),
            ],
        ]);
    }

    public function mockPay(Request $request, string $tracking, ApplicationService $service)
    {
        $application = Application::where('tracking_number', $tracking)->firstOrFail();
        $payment = $application->payments()->latest()->firstOrFail();

        if (! str_starts_with((string) $payment->paymongo_checkout_id, 'mock_cs_')) {
            return response()->json(['message' => 'Mock payment not available'], 422);
        }

        $application = $service->markPaid($application, $payment, 'mock', 'Mock online payment completed');

        return response()->json(['data' => $this->publicPayload($application)]);
    }

    public function uploadProof(Request $request, string $tracking, ApplicationService $service)
    {
        $application = Application::where('tracking_number', $tracking)->firstOrFail();

        if ($application->isPaid()) {
            return response()->json(['message' => 'Already paid'], 422);
        }

        $data = $request->validate([
            'proof' => ['required', 'image', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $path = $request->file('proof')->store('payment-proofs/'.$application->tracking_number, 'local');

        PaymentProof::create([
            'application_id' => $application->id,
            'file_path' => $path,
            'original_name' => $request->file('proof')->getClientOriginalName(),
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);

        $from = $application->status;
        $application->update(['status' => Application::STATUS_PENDING_VERIFICATION]);
        $service->logStatus($application, $from, Application::STATUS_PENDING_VERIFICATION, 'Payment proof uploaded', 'applicant');

        $application->payments()->latest()->first()?->update([
            'method' => 'proof_upload',
            'status' => 'pending',
        ]);

        return response()->json([
            'data' => $this->publicPayload($application->fresh(['barangay', 'payments', 'documents', 'statusLogs', 'paymentProofs'])),
        ]);
    }

    public function downloadDocument(string $tracking, Document $document)
    {
        $application = Application::where('tracking_number', $tracking)->firstOrFail();

        if ($document->application_id !== $application->id) {
            abort(404);
        }

        if ($document->type === Document::TYPE_SOFT_COPY && ! $application->canDownloadSoftCopy()) {
            abort(403, 'Soft copy not available.');
        }

        if (! $application->isPaid() && $document->type === Document::TYPE_RECEIPT) {
            abort(403, 'Receipt not available until payment is confirmed.');
        }

        abort_unless(Storage::disk($document->disk)->exists($document->file_path), 404);

        return Storage::disk($document->disk)->download(
            $document->file_path,
            $document->type.'-'.$application->tracking_number.'.pdf'
        );
    }

    private function validatedApplication(Request $request): array
    {
        $data = $request->validate([
            'applicant_type' => ['required', Rule::in([Application::TYPE_INDIVIDUAL, Application::TYPE_CORPORATION])],
            'first_name' => ['required_if:applicant_type,individual', 'nullable', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required_if:applicant_type,individual', 'nullable', 'string', 'max:100'],
            'corporation_name' => ['required_if:applicant_type,corporation', 'nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'tin' => ['nullable', 'string', 'max:40'],
            'birthdate' => ['nullable', 'date'],
            'civil_status' => ['nullable', 'in:Single,Married'],
            'citizenship' => ['nullable', 'string', 'max:80'],
            'occupation' => ['nullable', 'string', 'max:120'],
            'address_line' => ['required', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'barangay_id' => ['required', 'exists:barangays,id'],
            'delivery_mode' => ['required', Rule::in([
                Application::MODE_SOFT_COPY,
                Application::MODE_PICKUP,
                Application::MODE_DELIVERY,
            ])],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'thirteenth_month' => ['nullable', 'numeric', 'min:0'],
            'other_bonuses' => ['nullable', 'numeric', 'min:0'],
            'property_value' => ['nullable', 'numeric', 'min:0'],
            'gross_receipts' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($data['applicant_type'] === Application::TYPE_INDIVIDUAL) {
            $request->validate([
                'monthly_salary' => ['required', 'numeric', 'min:0'],
            ]);
        } else {
            $request->validate([
                'property_value' => ['required', 'numeric', 'min:0'],
                'gross_receipts' => ['required', 'numeric', 'min:0'],
            ]);
        }

        return $data;
    }

    private function publicPayload(Application $application): array
    {
        return [
            'tracking_number' => $application->tracking_number,
            'public_token' => $application->public_token,
            'applicant_type' => $application->applicant_type,
            'applicant_name' => $application->applicantName(),
            'email' => $application->email,
            'phone' => $application->phone,
            'address_line' => $application->address_line,
            'city' => $application->city,
            'province' => $application->province,
            'barangay' => $application->barangay,
            'delivery_mode' => $application->delivery_mode,
            'status' => $application->status,
            'breakdown' => $application->breakdown,
            'base_tax' => $application->base_tax,
            'additional_tax' => $application->additional_tax,
            'interest_amount' => $application->interest_amount,
            'interest_months' => $application->interest_months,
            'community_tax_total' => $application->community_tax_total,
            'delivery_fee' => $application->delivery_fee,
            'convenience_fee' => $application->convenience_fee,
            'server_fee' => $application->server_fee,
            'payment_processor_fee' => $application->payment_processor_fee,
            'total_due' => $application->total_due,
            'paid_at' => $application->paid_at,
            'created_at' => $application->created_at,
            'can_download_soft_copy' => $application->canDownloadSoftCopy(),
            'is_paid' => $application->isPaid(),
            'payments' => $application->payments,
            'documents' => $application->documents->map(fn (Document $d) => [
                'id' => $d->id,
                'type' => $d->type,
                'download_url' => '/api/applications/'.$application->tracking_number.'/documents/'.$d->id,
            ]),
            'status_logs' => $application->statusLogs,
            'payment_proofs' => $application->paymentProofs?->map(fn (PaymentProof $p) => [
                'id' => $p->id,
                'status' => $p->status,
                'original_name' => $p->original_name,
                'notes' => $p->notes,
                'created_at' => $p->created_at,
            ]),
            'track_url' => rtrim(config('app.frontend_url'), '/').'/t/'.$application->tracking_number,
            'receipt_url' => rtrim(config('app.frontend_url'), '/').'/r/'.$application->public_token,
        ];
    }
}
