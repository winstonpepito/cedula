<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LandingContentController extends Controller
{
    public function show()
    {
        return response()->json([
            'data' => LandingContent::current()->toPublicArray(),
        ]);
    }

    public function adminShow()
    {
        $content = LandingContent::current();

        return response()->json([
            'data' => array_merge($content->toPublicArray(), [
                'image_path' => $content->image_path,
            ]),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'headline' => ['required', 'string', 'max:255'],
            'intro_text' => ['required', 'string', 'max:5000'],
            'image_position' => ['required', Rule::in([
                LandingContent::POSITION_BEFORE,
                LandingContent::POSITION_AFTER,
            ])],
            'image' => ['nullable', 'image', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $content = LandingContent::current();

        if ($request->boolean('remove_image') && $content->image_path) {
            Storage::disk('public')->delete($content->image_path);
            $content->image_path = null;
        }

        if ($request->hasFile('image')) {
            if ($content->image_path) {
                Storage::disk('public')->delete($content->image_path);
            }
            $content->image_path = $request->file('image')->store('landing', 'public');
        }

        $content->headline = $data['headline'];
        $content->intro_text = $data['intro_text'];
        $content->image_position = $data['image_position'];
        $content->save();

        return response()->json([
            'data' => array_merge($content->fresh()->toPublicArray(), [
                'image_path' => $content->image_path,
            ]),
        ]);
    }
}
