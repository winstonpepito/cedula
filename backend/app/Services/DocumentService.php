<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
        $trackUrl = rtrim(config('app.frontend_url'), '/').'/t/'.$application->tracking_number;
        $qrSvg = base64_encode(QrCode::format('svg')->size(180)->generate($trackUrl));

        $pdf = Pdf::loadView('pdf.receipt', [
            'application' => $application->loadMissing('barangay'),
            'qrBase64' => $qrSvg,
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

        $trackUrl = rtrim(config('app.frontend_url'), '/').'/t/'.$application->tracking_number;
        $qrSvg = base64_encode(QrCode::format('svg')->size(140)->generate($trackUrl));

        $pdf = Pdf::loadView('pdf.soft-copy', [
            'application' => $application->loadMissing('barangay'),
            'qrBase64' => $qrSvg,
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
        $application->loadMissing('barangay');

        $trackUrl = rtrim(config('app.frontend_url'), '/').'/t/'.$application->tracking_number;
        $qrSvg = base64_encode(QrCode::format('svg')->size(160)->generate($trackUrl));

        $pdf = Pdf::loadView('pdf.application-summary', [
            'application' => $application,
            'qrBase64' => $qrSvg,
            'trackUrl' => $trackUrl,
        ]);

        $filename = 'application-'.$application->tracking_number.'.pdf';

        return $pdf->download($filename);
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
}
