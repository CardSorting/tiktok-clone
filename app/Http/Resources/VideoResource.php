<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'caption' => $this->caption,
            'video_url' => $this->video_path ? Storage::url($this->video_path) : null,
            'thumbnail_url' => $this->thumbnail_path ? Storage::url($this->thumbnail_path) : null,
            'is_private' => $this->is_private,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'user' => new UserResource($this->whenLoaded('user')),
            'likes_count' => $this->whenCounted('likes'),
            'comments_count' => $this->whenCounted('comments'),
            'is_liked' => $this->when(
                auth()->check(),
                fn() => $this->likes->contains('user_id', auth()->id())
            ),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource),
                'delete' => $request->user()?->can('delete', $this->resource),
            ],
        ];
    }
}