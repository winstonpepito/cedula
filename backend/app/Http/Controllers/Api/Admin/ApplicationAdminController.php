<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\PaymentProof;
use App\Services\ApplicationService;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ApplicationAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::with(['barangay', 'latestPayment'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('delivery_mode')) {
            $query->where('delivery_mode', $request->string('delivery_mode'));
        }

        if ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->integer('barangay_id'));
        }

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($builder) use ($q) {
                $builder->where('tracking_number', 'like', $q)
                    ->orWhere('email', 'like', $q)
                    ->orWhere('first_name', 'like', $q)
                    ->orWhere('last_name', 'like', $q)
                    ->orWhere('corporation_name', 'like', $q);
            });
        }

        if ($request->user()?->isDelivery()) {
            $query->where('delivery_mode', Application::MODE_DELIVERY)
                ->whereIn('status', [
                    Application::STATUS_PROCESSING,
                    Application::STATUS_OUT_FOR_DELIVERY,
                    Application::STATUS_DELIVERED,
                    Application::STATUS_PAID,
                ]);
        }

        return response()->json($query->paginate(20));
    }

    public function show(Application $application)
    {
        $application->load([
            'barangay.deliveryFee',
            'payments',
            'paymentProofs.verifier',
            'documents',
            'statusLogs.user',
        ]);

        return response()->json(['data' => $application]);
    }

    public function updateStatus(Request $request, Application $application, ApplicationService $service)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                Application::STATUS_PROCESSING,
                Application::STATUS_READY_FOR_PICKUP,
                Application::STATUS_OUT_FOR_DELIVERY,
                Application::STATUS_DELIVERED,
                Application::STATUS_CANCELLED,
            ])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        if ($user->isDelivery()) {
            if ($application->delivery_mode !== Application::MODE_DELIVERY) {
                return response()->json(['message' => 'Not a delivery application.'], 403);
            }
            if (! in_array($data['status'], [
                Application::STATUS_OUT_FOR_DELIVERY,
                Application::STATUS_DELIVERED,
            ], true)) {
                return response()->json(['message' => 'Delivery staff may only mark out for delivery or delivered.'], 403);
            }
        }

        $updated = $service->updateStatus(
            $application,
            $data['status'],
            $user->id,
            $user->isDelivery() ? 'delivery' : 'admin',
            $data['note'] ?? null
        );

        return response()->json(['data' => $updated]);
    }

    public function verifyPayment(Request $request, Application $application, ApplicationService $service)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'approve' => ['required', 'boolean'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
            'payment_proof_id' => ['nullable', 'exists:payment_proofs,id'],
        ]);

        $proof = ! empty($data['payment_proof_id'])
            ? PaymentProof::where('application_id', $application->id)->findOrFail($data['payment_proof_id'])
            : $application->paymentProofs()->latest()->first();

        if (! $proof) {
            return response()->json(['message' => 'No payment proof found.'], 422);
        }

        if (! $data['approve']) {
            $proof->update([
                'status' => 'rejected',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'admin_notes' => $data['admin_notes'] ?? null,
            ]);

            $from = $application->status;
            $application->update(['status' => Application::STATUS_AWAITING_PAYMENT]);
            $service->logStatus(
                $application,
                $from,
                Application::STATUS_AWAITING_PAYMENT,
                $data['admin_notes'] ?? 'Payment proof rejected',
                'admin',
                $request->user()->id
            );

            return response()->json(['data' => $application->fresh(['paymentProofs', 'statusLogs'])]);
        }

        $proof->update([
            'status' => 'approved',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'admin_notes' => $data['admin_notes'] ?? null,
        ]);

        $payment = $application->payments()->latest()->first();
        $updated = $service->markPaid(
            $application,
            $payment,
            'proof_upload',
            'Payment proof verified by admin'
        );

        return response()->json(['data' => $updated]);
    }

    public function regenerateDocuments(Application $application, DocumentService $documents)
    {
        abort_unless(request()->user()->isAdmin(), 403);
        $documents->generateForPaidApplication($application);

        return response()->json([
            'data' => $application->fresh('documents'),
        ]);
    }

    public function uploadSoftCopy(
        Request $request,
        Application $application,
        DocumentService $documents,
        ApplicationService $service,
    ) {
        abort_unless($request->user()->isAdmin(), 403);

        if (! $application->isPaid()) {
            return response()->json([
                'message' => 'Soft copy can only be uploaded after payment is confirmed.',
            ], 422);
        }

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $document = $documents->storeUploadedSoftCopy($application, $data['file']);

        $service->logStatus(
            $application,
            $application->status,
            $application->status,
            'Official CTC soft copy uploaded for applicant download',
            'admin',
            $request->user()->id,
        );

        return response()->json([
            'data' => $application->fresh([
                'barangay.deliveryFee',
                'payments',
                'paymentProofs.verifier',
                'documents',
                'statusLogs.user',
            ]),
            'document' => $document,
        ]);
    }

    public function proofFile(Application $application, PaymentProof $proof)
    {
        abort_unless($proof->application_id === $application->id, 404);
        abort_unless(Storage::disk('local')->exists($proof->file_path), 404);

        return Storage::disk('local')->response($proof->file_path);
    }
}
