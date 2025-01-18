<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'name' => $this->name,
            'avatar_url' => $this->avatar_path ? Storage::url($this->avatar_path) : null,
            'bio' => $this->when(
                $this->shouldIncludePrivateInfo(),
                $this->bio
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'followers_count' => $this->whenCounted('followers'),
            'following_count' => $this->whenCounted('following'),
            'is_following' => $this->when(
                auth()->check(),
                fn() => $this->followers->contains('follower_id', auth()->id())
            ),
            'can' => [
                'follow' => $request->user()?->can('follow', $this->resource),
                'message' => $request->user()?->can('message', $this->resource),
            ],
        ];
    }

    private function shouldIncludePrivateInfo(): bool
    {
        return auth()->check() && (
            $this->id === auth()->id() ||
            $this->followers->contains('follower_id', auth()->id())
        );
    }
}