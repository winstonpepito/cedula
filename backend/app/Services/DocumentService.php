<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DocumentService
{
    public function generateForPaidApplication(Application $application): void
    {
        $this->generateReceipt($application);
        // Official CTC soft copy is uploaded by admin for applicant download.
        // Do not auto-generate a placeholder PDF that could be mistaken for the real certificate.
    }

    public function generateReceipt(Application $application): Document
    {
        $this->ensurePdfWritablePaths();

        $trackUrl = rtrim((string) config('app.frontend_url'), '/').'/t/'.$application->tracking_number;

        $pdf = Pdf::loadView('pdf.receipt', [
            'application' => $application->loadMissing('barangay'),
            'qrDataUri' => $this->qrDataUri($trackUrl, 180),
            'trackUrl' => $trackUrl,
        ]);

        $path = 'documents/'.$application->tracking_number.'/receipt.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        return Document::updateOrCreate(
            [
                'application_id' => $application->id,
                'type' => Document::TYPE_RECEIPT,
            ],
            [
                'file_path' => $path,
                'disk' => 'local',
                'is_uploaded' => false,
                'original_name' => null,
            ]
        );
    }

    public function generateSoftCopy(Application $application): ?Document
    {
        $existing = Document::query()
            ->where('application_id', $application->id)
            ->where('type', Document::TYPE_SOFT_COPY)
            ->first();

        // Never overwrite an admin-uploaded official CTC soft copy.
        if ($existing?->is_uploaded) {
            return $existing;
        }

        $this->ensurePdfWritablePaths();

        $trackUrl = rtrim((string) config('app.frontend_url'), '/').'/t/'.$application->tracking_number;

        $pdf = Pdf::loadView('pdf.soft-copy', [
            'application' => $application->loadMissing('barangay'),
            'qrDataUri' => $this->qrDataUri($trackUrl, 140),
            'trackUrl' => $trackUrl,
        ]);

        $path = 'documents/'.$application->tracking_number.'/soft-copy.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        return Document::updateOrCreate(
            [
                'application_id' => $application->id,
                'type' => Document::TYPE_SOFT_COPY,
            ],
            [
                'file_path' => $path,
                'disk' => 'local',
                'is_uploaded' => false,
                'original_name' => null,
            ]
        );
    }

    public function downloadApplicationSummary(Application $application): Response
    {
        $this->ensurePdfWritablePaths();
        $application->loadMissing('barangay');

        $trackUrl = rtrim((string) config('app.frontend_url'), '/').'/t/'.$application->tracking_number;

        try {
            $pdf = Pdf::loadView('pdf.application-summary', [
                'application' => $application,
                'qrDataUri' => $this->qrDataUri($trackUrl, 160),
                'trackUrl' => $trackUrl,
            ]);

            $filename = 'application-'.$application->tracking_number.'.pdf';

            return $pdf->download($filename);
        } catch (Throwable $e) {
            Log::error('Application summary PDF failed', [
                'application_id' => $application->id,
                'tracking' => $application->tracking_number,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function storeUploadedSoftCopy(Application $application, UploadedFile $file): Document
    {
        $disk = Storage::disk('local');
        $dir = 'documents/'.$application->tracking_number;
        $disk->makeDirectory($dir);

        $existing = Document::query()
            ->where('application_id', $application->id)
            ->where('type', Document::TYPE_SOFT_COPY)
            ->first();

        if ($existing && $disk->exists($existing->file_path)) {
            $disk->delete($existing->file_path);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $path = $dir.'/soft-copy.'.$extension;
        $disk->putFileAs($dir, $file, 'soft-copy.'.$extension);

        return Document::updateOrCreate(
            [
                'application_id' => $application->id,
                'type' => Document::TYPE_SOFT_COPY,
            ],
            [
                'file_path' => $path,
                'disk' => 'local',
                'is_uploaded' => true,
                'original_name' => $file->getClientOriginalName(),
            ]
        );
    }

    private function ensurePdfWritablePaths(): void
    {
        $fonts = storage_path('fonts');
        if (! is_dir($fonts)) {
            @mkdir($fonts, 0775, true);
        }

        Storage::disk('local')->makeDirectory('documents');
    }

    /**
     * DomPDF handles PNG data-URIs more reliably than SVG on many servers.
     */
    private function qrDataUri(string $url, int $size = 160): ?string
    {
        try {
            if (extension_loaded('gd')) {
                $png = QrCode::format('png')->size($size)->margin(1)->generate($url);

                return 'data:image/png;base64,'.base64_encode($png);
            }

            $svg = QrCode::format('svg')->size($size)->margin(1)->generate($url);

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        } catch (Throwable $e) {
            Log::warning('QR code generation skipped for PDF', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
