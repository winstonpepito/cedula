<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DocumentService
{
    public function generateForPaidApplication(Application $application): void
    {
        $this->generateReceipt($application);

        if ($application->delivery_mode === Application::MODE_SOFT_COPY) {
            $this->generateSoftCopy($application);
        }
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
            ]
        );
    }

    public function generateSoftCopy(Application $application): Document
    {
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
            ]
        );
    }
}
