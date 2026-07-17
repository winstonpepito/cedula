<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function dashboard(Request $request)
    {
        $paidStatuses = [
            Application::STATUS_PAID,
            Application::STATUS_PROCESSING,
            Application::STATUS_READY_FOR_PICKUP,
            Application::STATUS_OUT_FOR_DELIVERY,
            Application::STATUS_DELIVERED,
        ];

        $from = $request->date('from')?->startOfDay();
        $to = $request->date('to')?->endOfDay();

        $base = Application::query();
        if ($from) {
            $base->where('created_at', '>=', $from);
        }
        if ($to) {
            $base->where('created_at', '<=', $to);
        }

        $totalApplications = (clone $base)->count();
        $pendingVerification = (clone $base)->where('status', Application::STATUS_PENDING_VERIFICATION)->count();
        $awaitingPayment = (clone $base)->where('status', Application::STATUS_AWAITING_PAYMENT)->count();
        $paidCount = (clone $base)->whereIn('status', $paidStatuses)->count();
        $revenue = (clone $base)->whereIn('status', $paidStatuses)->sum('total_due');
        $lateFilings = (clone $base)->where('interest_amount', '>', 0)->count();

        $byMode = (clone $base)
            ->select('delivery_mode', DB::raw('count(*) as total'), DB::raw('sum(total_due) as amount'))
            ->groupBy('delivery_mode')
            ->get();

        $byBarangay = (clone $base)
            ->join('barangays', 'barangays.id', '=', 'applications.barangay_id')
            ->select('barangays.name', DB::raw('count(*) as total'), DB::raw('sum(applications.total_due) as amount'))
            ->groupBy('barangays.name')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        $byStatus = (clone $base)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        $recent = Application::with('barangay')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return response()->json([
            'data' => [
                'totals' => [
                    'applications' => $totalApplications,
                    'paid' => $paidCount,
                    'awaiting_payment' => $awaitingPayment,
                    'pending_verification' => $pendingVerification,
                    'revenue' => (float) $revenue,
                    'late_filings' => $lateFilings,
                ],
                'by_mode' => $byMode,
                'by_barangay' => $byBarangay,
                'by_status' => $byStatus,
                'recent' => $recent,
            ],
        ]);
    }
}
