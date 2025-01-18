<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade')
                ->comment('Foreign key to users table');
            
            $table->string('caption', 255)
                ->nullable()
                ->comment('Video caption text');
            
            $table->string('video_path')
                ->comment('Storage path for video file');
            
            $table->string('thumbnail_path')
                ->nullable()
                ->comment('Storage path for thumbnail image');
            
            $table->unsignedBigInteger('views_count')
                ->default(0)
                ->comment('Total view count');
            
            $table->unsignedBigInteger('likes_count')
                ->default(0)
                ->comment('Total like count');
            
            $table->unsignedBigInteger('comments_count')
                ->default(0)
                ->comment('Total comment count');
            
            $table->unsignedBigInteger('shares_count')
                ->default(0)
                ->comment('Total share count');
            
            $table->unsignedInteger('duration')
                ->default(0)
                ->comment('Video duration in seconds');
            
            $table->boolean('is_private')
                ->default(false)
                ->comment('Whether video is private');
            
            $table->boolean('is_approved')
                ->default(true)
                ->comment('Whether video is approved');
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('created_at');
            $table->index('views_count');
            $table->index('likes_count');
            $table->index('is_private');
            $table->index('is_approved');
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
