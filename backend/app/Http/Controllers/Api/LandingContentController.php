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
            // Prefer mimes over "image" — works even when php-gd is not installed.
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $content = LandingContent::current();

        if ($request->boolean('remove_image') && $content->image_path) {
            Storage::disk('public')->delete($content->image_path);
            $content->image_path = null;
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            if (! $file || ! $file->isValid()) {
                return response()->json([
                    'message' => 'The image failed to upload. Check PHP upload_max_filesize / post_max_size.',
                    'errors' => ['image' => ['Invalid or incomplete upload.']],
                ], 422);
            }

            $disk = Storage::disk('public');
            $disk->makeDirectory('landing');

            if ($content->image_path) {
                $disk->delete($content->image_path);
            }

            try {
                $path = $file->store('landing', 'public');
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'message' => 'Could not store the image. Check storage/app/public permissions for the web server user.',
                    'errors' => ['image' => ['Storage write failed.']],
                ], 500);
            }

            if (! $path) {
                return response()->json([
                    'message' => 'Could not store the image. Check storage/app/public permissions.',
                    'errors' => ['image' => ['Storage write failed.']],
                ], 500);
            }

            $content->image_path = $path;
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
