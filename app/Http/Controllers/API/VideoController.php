<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VideoController extends Controller
{
    public function index(Request $request)
    {
        $videos = Video::with(['user', 'likes', 'comments'])
            ->where('is_private', false)
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $videos,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'caption' => 'nullable|string|max:255',
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime|max:51200',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_private' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $videoPath = $request->file('video')->store('videos', 'public');
        $thumbnailPath = $request->hasFile('thumbnail') 
            ? $request->file('thumbnail')->store('thumbnails', 'public')
            : null;

        $video = Video::create([
            'user_id' => auth()->id(),
            'caption' => $request->caption,
            'video_path' => $videoPath,
            'thumbnail_path' => $thumbnailPath,
            'is_private' => $request->is_private ?? false,
        ]);

        return response()->json([
            'success' => true,
            'data' => $video,
        ], 201);
    }

    public function show(Video $video)
    {
        $video->load(['user', 'likes', 'comments']);

        return response()->json([
            'success' => true,
            'data' => $video,
        ]);
    }

    public function update(Request $request, Video $video)
    {
        $this->authorize('update', $video);

        $validator = Validator::make($request->all(), [
            'caption' => 'nullable|string|max:255',
            'is_private' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $video->update([
            'caption' => $request->caption ?? $video->caption,
            'is_private' => $request->is_private ?? $video->is_private,
        ]);

        return response()->json([
            'success' => true,
            'data' => $video,
        ]);
    }

    public function destroy(Video $video)
    {
        $this->authorize('delete', $video);

        Storage::disk('public')->delete($video->video_path);
        if ($video->thumbnail_path) {
            Storage::disk('public')->delete($video->thumbnail_path);
        }

        $video->delete();

        return response()->json([
            'success' => true,
            'message' => 'Video deleted successfully',
        ]);
    }
}