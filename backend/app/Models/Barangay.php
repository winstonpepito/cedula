<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Barangay extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function deliveryFee(): HasOne
    {
        return $this->hasOne(BarangayDeliveryFee::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
