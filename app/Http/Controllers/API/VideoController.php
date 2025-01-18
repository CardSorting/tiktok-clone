<?php

namespace App\Http\Controllers\API;

use App\Events\VideoCreated;
use App\Events\VideoDeleted;
use App\Events\VideoUpdated;
use App\Exceptions\VideoProcessingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\VideoRequest;
use App\Http\Resources\VideoResource;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Aws\MediaConvert\MediaConvertClient;

class VideoController extends Controller
{
    private const CACHE_TTL = 3600; // 1 hour
    private const RATE_LIMIT = 60; // 60 requests per minute
    private const VIDEO_CACHE_KEY = 'videos_page_';
    private const VIDEO_CACHE_TAG = 'videos';

    public function __construct()
    {
        $this->middleware('auth:api')->except(['index', 'show']);
        $this->middleware('throttle:'.self::RATE_LIMIT.',1')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $cacheKey = self::VIDEO_CACHE_KEY.$page;

        try {
            $videos = Cache::tags(self::VIDEO_CACHE_TAG)->remember($cacheKey, self::CACHE_TTL, function() {
                return Video::with(['user', 'likes', 'comments'])
                    ->where('is_private', false)
                    ->latest()
                    ->paginate(15);
            });

            return response()->json([
                'success' => true,
                'data' => VideoResource::collection($videos),
                'meta' => [
                    'current_page' => $videos->currentPage(),
                    'total_pages' => $videos->lastPage(),
                    'per_page' => $videos->perPage(),
                    'total' => $videos->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve videos: '.$e->getMessage());
            throw new VideoProcessingException('Failed to retrieve videos', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(VideoRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Store original video file
            $videoPath = $this->storeVideoFile($request->file('video'));
            $thumbnailPath = $request->hasFile('thumbnail') 
                ? $this->storeThumbnailFile($request->file('thumbnail'))
                : null;

            // Create video record
            $video = Video::create([
                'user_id' => auth()->id(),
                'caption' => $validated['caption'] ?? null,
                'video_path' => $videoPath,
                'thumbnail_path' => $thumbnailPath,
                'is_private' => $validated['is_private'] ?? false,
                'processing_status' => 'pending',
            ]);

            // Dispatch MediaConvert job
            $this->processVideoWithMediaConvert($video);

            event(new VideoCreated($video));
            Cache::tags(self::VIDEO_CACHE_TAG)->flush();

            return response()->json([
                'success' => true,
                'data' => new VideoResource($video),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Video upload failed: '.$e->getMessage());
            throw new VideoProcessingException('Failed to upload video', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Video $video): JsonResponse
    {
        try {
            $video->load(['user', 'likes', 'comments']);

            if ($video->is_private && !$this->canViewPrivateVideo($video)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this video',
                ], Response::HTTP_FORBIDDEN);
            }

            return response()->json([
                'success' => true,
                'data' => new VideoResource($video),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve video: '.$e->getMessage());
            throw new VideoProcessingException('Failed to retrieve video', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(VideoRequest $request, Video $video): JsonResponse
    {
        try {
            $this->authorize('update', $video);

            $validated = $request->validated();

            $video->update([
                'caption' => $validated['caption'] ?? $video->caption,
                'is_private' => $validated['is_private'] ?? $video->is_private,
            ]);

            event(new VideoUpdated($video));
            Cache::tags(self::VIDEO_CACHE_TAG)->flush();

            return response()->json([
                'success' => true,
                'data' => new VideoResource($video),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update video: '.$e->getMessage());
            throw new VideoProcessingException('Failed to update video', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Video $video): JsonResponse
    {
        try {
            $this->authorize('delete', $video);

            Storage::disk('public')->delete($video->video_path);
            if ($video->thumbnail_path) {
                Storage::disk('public')->delete($video->thumbnail_path);
            }

            $video->delete();
            
            event(new VideoDeleted($video));
            Cache::tags(self::VIDEO_CACHE_TAG)->flush();

            return response()->json([
                'success' => true,
                'message' => 'Video deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete video: '.$e->getMessage());
            throw new VideoProcessingException('Failed to delete video', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function canViewPrivateVideo(Video $video): bool
    {
        return auth()->check() && (
            $video->user_id === auth()->id() ||
            $video->user->followers()->where('follower_id', auth()->id())->exists()
        );
    }

    private function storeVideoFile($file): string
    {
        try {
            return $file->store('videos', 'public');
        } catch (\Exception $e) {
            Log::error('Failed to store video file: '.$e->getMessage());
            throw new VideoProcessingException('Failed to store video file', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function storeThumbnailFile($file): ?string
    {
        try {
            return $file->store('thumbnails', 'public');
        } catch (\Exception $e) {
            Log::error('Failed to store thumbnail file: '.$e->getMessage());
            return null;
        }
    }

    private function processVideoWithMediaConvert(Video $video): void
    {
        try {
            $mediaConvert = app('mediaconvert');
            
            $inputPath = Storage::disk('public')->path($video->video_path);
            $outputPath = 'processed/videos/'.$video->id.'/';

            $jobSettings = [
                'OutputGroups' => [
                    [
                        'Name' => 'File Group',
                        'Outputs' => config('mediaconvert.presets.default.outputs'),
                        'OutputGroupSettings' => [
                            'Type' => 'FILE_GROUP_SETTINGS',
                            'FileGroupSettings' => [
                                'Destination' => 's3://'.env('AWS_BUCKET').'/'.$outputPath
                            ]
                        ]
                    ]
                ],
                'Inputs' => [
                    [
                        'FileInput' => $inputPath,
                        'AudioSelectors' => [
                            'Audio Selector 1' => [
                                'DefaultSelection' => 'DEFAULT'
                            ]
                        ],
                        'VideoSelector' => [
                            'ColorSpace' => 'FOLLOW'
                        ]
                    ]
                ],
                'Role' => env('AWS_MEDIACONVERT_ROLE'),
                'Queue' => env('AWS_MEDIACONVERT_QUEUE'),
                'UserMetadata' => [
                    'video_id' => $video->id
                ]
            ];

            $mediaConvert->createJob($jobSettings);

            $video->update(['processing_status' => 'processing']);
        } catch (\Exception $e) {
            Log::error('MediaConvert job creation failed: '.$e->getMessage());
            throw new VideoProcessingException('Failed to process video', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}