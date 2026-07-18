<?php

use App\Http\Controllers\Api\Admin\ApplicationAdminController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\TaxSettingController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BarangayController;
use App\Http\Controllers\Api\CalculateController;
use App\Http\Controllers\Api\LandingContentController;
use App\Http\Controllers\Api\Webhook\PayMongoWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/calculate', CalculateController::class)->middleware('throttle:60,1');
Route::get('/barangays', [BarangayController::class, 'publicIndex']);
Route::get('/settings', [TaxSettingController::class, 'publicShow']);
Route::get('/landing', [LandingContentController::class, 'show']);

Route::post('/applications', [ApplicationController::class, 'store'])->middleware('throttle:20,1');
Route::get('/applications/{tracking}', [ApplicationController::class, 'show']);
Route::post('/applications/{tracking}/pay', [ApplicationController::class, 'pay']);
Route::post('/applications/{tracking}/mock-pay', [ApplicationController::class, 'mockPay']);
Route::post('/applications/{tracking}/payment-proof', [ApplicationController::class, 'uploadProof']);
Route::get('/applications/{tracking}/documents/{document}', [ApplicationController::class, 'downloadDocument']);

Route::get('/track/{tracking}', [ApplicationController::class, 'track'])->middleware('throttle:60,1');
Route::get('/r/{token}', [ApplicationController::class, 'byToken'])->middleware('throttle:60,1');

Route::post('/webhooks/paymongo', PayMongoWebhookController::class);

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('admin')->group(function () {
        Route::get('/reports/dashboard', [ReportController::class, 'dashboard'])
            ->middleware('staff:admin');

        Route::get('/tax-settings', [TaxSettingController::class, 'show'])->middleware('staff:admin');
        Route::put('/tax-settings', [TaxSettingController::class, 'update'])->middleware('staff:admin');

        Route::get('/landing', [LandingContentController::class, 'adminShow'])->middleware('staff:admin');
        Route::post('/landing', [LandingContentController::class, 'update'])->middleware('staff:admin');

        Route::get('/barangays', [BarangayController::class, 'index'])->middleware('staff:admin');
        Route::post('/barangays', [BarangayController::class, 'store'])->middleware('staff:admin');
        Route::get('/barangays/default', [BarangayController::class, 'showDefault'])->middleware('staff:admin');
        Route::put('/barangays/default', [BarangayController::class, 'updateDefault'])->middleware('staff:admin');
        Route::put('/barangays/{barangay}', [BarangayController::class, 'update'])->middleware('staff:admin');
        Route::delete('/barangays/{barangay}', [BarangayController::class, 'destroy'])->middleware('staff:admin');

        Route::get('/applications', [ApplicationAdminController::class, 'index'])
            ->middleware('staff:admin,delivery');
        Route::get('/applications/{application}', [ApplicationAdminController::class, 'show'])
            ->middleware('staff:admin,delivery');
        Route::patch('/applications/{application}/status', [ApplicationAdminController::class, 'updateStatus'])
            ->middleware('staff:admin,delivery');
        Route::post('/applications/{application}/verify-payment', [ApplicationAdminController::class, 'verifyPayment'])
            ->middleware('staff:admin');
        Route::post('/applications/{application}/regenerate-documents', [ApplicationAdminController::class, 'regenerateDocuments'])
            ->middleware('staff:admin');
        Route::get('/applications/{application}/proofs/{proof}/file', [ApplicationAdminController::class, 'proofFile'])
            ->middleware('staff:admin');
    });
});
