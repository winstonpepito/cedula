<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LandingContent extends Model
{
    public const POSITION_BEFORE = 'before';

    public const POSITION_AFTER = 'after';

    protected $fillable = [
        'headline',
        'intro_text',
        'image_path',
        'image_position',
    ];

    public static function current(): self
    {
        return static::query()->firstOrFail();
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return '/storage/'.$this->image_path;
    }

    public function toPublicArray(): array
    {
        return [
            'headline' => $this->headline,
            'intro_text' => $this->intro_text,
            'image_url' => $this->imageUrl(),
            'image_position' => $this->image_position,
        ];
    }
}
