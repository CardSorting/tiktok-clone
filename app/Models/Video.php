<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_approved' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}