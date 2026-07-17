<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxSetting extends Model
{
    protected $fillable = [
        'individual_base_tax',
        'individual_rate_amount',
        'individual_rate_per',
        'individual_additional_cap',
        'corporation_base_tax',
        'corporation_rate_amount',
        'corporation_rate_per',
        'corporation_additional_cap',
        'interest_rate_percent',
        'deadline_month',
        'deadline_day',
        'interest_counts_from_january',
        'convenience_fee',
        'server_fee',
        'payment_processor_fee',
        'default_city',
        'default_province',
        'manual_payment_only',
        'gcash_number',
    ];

    protected function casts(): array
    {
        return [
            'individual_base_tax' => 'decimal:2',
            'individual_rate_amount' => 'decimal:2',
            'individual_rate_per' => 'decimal:2',
            'individual_additional_cap' => 'decimal:2',
            'corporation_base_tax' => 'decimal:2',
            'corporation_rate_amount' => 'decimal:2',
            'corporation_rate_per' => 'decimal:2',
            'corporation_additional_cap' => 'decimal:2',
            'interest_rate_percent' => 'decimal:4',
            'interest_counts_from_january' => 'boolean',
            'convenience_fee' => 'decimal:2',
            'server_fee' => 'decimal:2',
            'payment_processor_fee' => 'decimal:2',
            'manual_payment_only' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrFail();
    }

    public function toSnapshot(): array
    {
        return $this->only([
            'individual_base_tax',
            'individual_rate_amount',
            'individual_rate_per',
            'individual_additional_cap',
            'corporation_base_tax',
            'corporation_rate_amount',
            'corporation_rate_per',
            'corporation_additional_cap',
            'interest_rate_percent',
            'deadline_month',
            'deadline_day',
            'interest_counts_from_january',
            'convenience_fee',
            'server_fee',
            'payment_processor_fee',
            'default_city',
            'default_province',
            'manual_payment_only',
            'gcash_number',
        ]);
    }

    public function toPublicDefaults(): array
    {
        return [
            'default_city' => $this->default_city,
            'default_province' => $this->default_province,
            'manual_payment_only' => (bool) $this->manual_payment_only,
            'gcash_number' => $this->gcash_number,
        ];
    }
}
