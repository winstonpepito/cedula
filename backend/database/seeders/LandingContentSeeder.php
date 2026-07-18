<?php

namespace Database\Seeders;

use App\Models\LandingContent;
use Illuminate\Database\Seeder;

class LandingContentSeeder extends Seeder
{
    public function run(): void
    {
        LandingContent::query()->firstOrCreate([], [
            'headline' => 'Apply for your Community Tax Certificate online',
            'intro_text' => 'Transparent computation, secure payment, and trackable delivery — built for residents and businesses of Cebu City.',
            'image_path' => null,
            'image_position' => LandingContent::POSITION_AFTER,
        ]);
    }
}
