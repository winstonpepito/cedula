<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    public const TYPE_INDIVIDUAL = 'individual';

    public const TYPE_CORPORATION = 'corporation';

    public const MODE_SOFT_COPY = 'soft_copy';

    public const MODE_PICKUP = 'pickup';

    public const MODE_DELIVERY = 'delivery';

    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    public const STATUS_PENDING_VERIFICATION = 'pending_verification';

    public const STATUS_PAID = 'paid';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';

    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tracking_number',
        'public_token',
        'applicant_type',
        'first_name',
        'middle_name',
        'last_name',
        'corporation_name',
        'email',
        'phone',
        'tin',
        'birthdate',
        'civil_status',
        'citizenship',
        'occupation',
        'address_line',
        'city',
        'province',
        'barangay_id',
        'delivery_mode',
        'monthly_salary',
        'thirteenth_month',
        'other_bonuses',
        'annual_income',
        'property_value',
        'gross_receipts',
        'tax_snapshot',
        'breakdown',
        'base_tax',
        'additional_tax',
        'interest_amount',
        'community_tax_total',
        'delivery_fee',
        'convenience_fee',
        'server_fee',
        'payment_processor_fee',
        'total_due',
        'interest_months',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'tax_snapshot' => 'array',
            'breakdown' => 'array',
            'monthly_salary' => 'decimal:2',
            'thirteenth_month' => 'decimal:2',
            'other_bonuses' => 'decimal:2',
            'annual_income' => 'decimal:2',
            'property_value' => 'decimal:2',
            'gross_receipts' => 'decimal:2',
            'base_tax' => 'decimal:2',
            'additional_tax' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'community_tax_total' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'convenience_fee' => 'decimal:2',
            'server_fee' => 'decimal:2',
            'payment_processor_fee' => 'decimal:2',
            'total_due' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function paymentProofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(StatusLog::class)->orderBy('created_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function applicantName(): string
    {
        if ($this->applicant_type === self::TYPE_CORPORATION) {
            return (string) $this->corporation_name;
        }

        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])));
    }

    public function isPaid(): bool
    {
        return in_array($this->status, [
            self::STATUS_PAID,
            self::STATUS_PROCESSING,
            self::STATUS_READY_FOR_PICKUP,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERED,
        ], true);
    }

    public function canDownloadSoftCopy(): bool
    {
        return $this->isPaid() && $this->delivery_mode === self::MODE_SOFT_COPY;
    }
}
