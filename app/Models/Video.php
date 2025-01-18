<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'caption',
        'video_path',
        'thumbnail_path',
        'views_count',
        'likes_count',
        'comments_count',
        'shares_count',
        'is_private',
        'is_approved',
        'duration',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_approved' => 'boolean',
        'duration' => 'integer',
    ];

    protected $appends = [
        'video_url',
        'thumbnail_url',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getVideoUrlAttribute(): ?string
    {
        return $this->video_path ? Storage::url($this->video_path) : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path ? Storage::url($this->thumbnail_path) : null;
    }

    public function incrementViews(): void
    {
        $this->timestamps = false;
        $this->increment('views_count');
        $this->timestamps = true;
    }

    public function isLikedBy(User $user): bool
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function canBeViewedBy(User $user): bool
    {
        return !$this->is_private || 
               $this->isOwnedBy($user) || 
               $this->user->followers()->where('follower_id', $user->id)->exists();
    }
}