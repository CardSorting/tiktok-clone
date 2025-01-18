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
    private const HASHTAG_CACHE_KEY = 'hashtags_';

    public function __construct()
    {
        $this->middleware('auth:api')->except(['index', 'show', 'trending', 'hashtag']);
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

    public function trending(Request $request): JsonResponse
    {
        $cacheKey = self::HASHTAG_CACHE_KEY.'trending';

        try {
            $trendingVideos = Cache::remember($cacheKey, self::CACHE_TTL, function() {
                return Video::with(['user', 'likes', 'comments'])
                    ->where('is_private', false)
                    ->orderBy('views_count', 'desc')
                    ->orderBy('likes_count', 'desc')
                    ->take(50)
                    ->get();
            });

            return response()->json([
                'success' => true,
                'data' => VideoResource::collection($trendingVideos),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve trending videos: '.$e->getMessage());
            throw new VideoProcessingException('Failed to retrieve trending videos', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function hashtag(Request $request, string $hashtag): JsonResponse
    {
        $cacheKey = self::HASHTAG_CACHE_KEY.$hashtag;

        try {
            $videos = Cache::remember($cacheKey, self::CACHE_TTL, function() use ($hashtag) {
                return Video::with(['user', 'likes', 'comments'])
                    ->where('is_private', false)
                    ->whereJsonContains('hashtags', $hashtag)
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
            Log::error('Failed to retrieve hashtag videos: '.$e->getMessage());
            throw new VideoProcessingException('Failed to retrieve hashtag videos', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(VideoRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Store video and thumbnail
            $videoPath = $request->file('video')->store('videos', 'public');
            $thumbnailPath = $request->hasFile('thumbnail') 
                ? $request->file('thumbnail')->store('thumbnails', 'public')
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

            // Extract and save hashtags
            $video->updateHashtags();

            // Queue MediaConvert processing
            $this->processVideoWithMediaConvert($video);

            event(new VideoCreated($video));
            Cache::tags(self::VIDEO_CACHE_TAG)->flush();

            return response()->json([
                'success' => true,
                'data' => new VideoResource($video),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // Cleanup any stored files if creation fails
            if (isset($videoPath)) {
                Storage::disk('public')->delete($videoPath);
            }
            if (isset($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }

            Log::error('Video upload failed: '.$e->getMessage());
            throw new VideoProcessingException('Failed to upload video', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Video $video): JsonResponse
    {
        try {
            // Track video view
            $video->incrementViews();

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

            // Update hashtags if caption changed
            if (array_key_exists('caption', $validated)) {
                $video->updateHashtags();
            }

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

    public function like(Video $video): JsonResponse
    {
        try {
            $video->likes()->create([
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Video liked successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to like video: '.$e->getMessage());
            throw new VideoProcessingException('Failed to like video', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function unlike(Video $video): JsonResponse
    {
        try {
            $video->likes()->where('user_id', auth()->id())->delete();

            return response()->json([
                'success' => true,
                'message' => 'Video unliked successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to unlike video: '.$e->getMessage());
            throw new VideoProcessingException('Failed to unlike video', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function canViewPrivateVideo(Video $video): bool
    {
        return auth()->check() && (
            $video->user_id === auth()->id() ||
            $video->user->followers()->where('follower_id', auth()->id())->exists()
        );
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