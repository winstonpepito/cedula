<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\BarangayDeliveryFee;
use Illuminate\Http\Request;

class BarangayController extends Controller
{
    public function publicIndex()
    {
        $barangays = Barangay::query()
            ->with(['deliveryFee' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Barangay $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'code' => $b->code,
                'delivery_fee' => $b->deliveryFee?->fee ?? 0,
            ]);

        return response()->json(['data' => $barangays]);
    }

    public function index()
    {
        return response()->json([
            'data' => Barangay::with('deliveryFee')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:barangays,code'],
            'is_active' => ['boolean'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        $barangay = Barangay::create([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        BarangayDeliveryFee::create([
            'barangay_id' => $barangay->id,
            'fee' => $data['delivery_fee'] ?? 0,
            'is_active' => true,
        ]);

        return response()->json(['data' => $barangay->load('deliveryFee')], 201);
    }

    public function update(Request $request, Barangay $barangay)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:barangays,code,'.$barangay->id],
            'is_active' => ['boolean'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'fee_is_active' => ['boolean'],
        ]);

        $barangay->update(collect($data)->only(['name', 'code', 'is_active'])->all());

        if (array_key_exists('delivery_fee', $data) || array_key_exists('fee_is_active', $data)) {
            $fee = $barangay->deliveryFee()->firstOrCreate(['barangay_id' => $barangay->id], ['fee' => 0]);
            if (array_key_exists('delivery_fee', $data)) {
                $fee->fee = $data['delivery_fee'];
            }
            if (array_key_exists('fee_is_active', $data)) {
                $fee->is_active = $data['fee_is_active'];
            }
            $fee->save();
        }

        return response()->json(['data' => $barangay->fresh('deliveryFee')]);
    }

    public function destroy(Barangay $barangay)
    {
        $barangay->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
