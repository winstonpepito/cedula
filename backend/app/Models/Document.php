<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    public const TYPE_RECEIPT = 'receipt';

    public const TYPE_SOFT_COPY = 'soft_copy_cedula';

    protected $fillable = [
        'application_id',
        'type',
        'file_path',
        'disk',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
