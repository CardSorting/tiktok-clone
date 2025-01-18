import React, { useState, useRef, useEffect } from 'react';
import { Inertia } from '@inertiajs/inertia';
import { Link } from '@inertiajs/inertia-react';
import LikeButton from '@/Components/LikeButton';
import CommentSection from '@/Components/CommentSection';
import ShareButton from '@/Components/ShareButton';
import UserAvatar from '@/Components/UserAvatar';
import { useDoubleTap } from 'use-double-tap';

export default function VideoCard({ video, className = '' }) {
    const [isPlaying, setIsPlaying] = useState(false);
    const [showComments, setShowComments] = useState(false);
    const [likesCount, setLikesCount] = useState(video.likes_count);
    const [isLiked, setIsLiked] = useState(video.is_liked);
    const [progress, setProgress] = useState(0);
    const [isMuted, setIsMuted] = useState(true);
    const [showLikeAnimation, setShowLikeAnimation] = useState(false);
    const videoRef = useRef(null);

    const bind = useDoubleTap(() => {
        if (!isLiked) {
            handleLike();
            setShowLikeAnimation(true);
            setTimeout(() => setShowLikeAnimation(false), 1000);
        }
    });

    const handleLike = async () => {
        try {
            await Inertia.post(`/api/videos/${video.id}/like`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    setLikesCount(prev => isLiked ? prev - 1 : prev + 1);
                    setIsLiked(prev => !prev);
                }
            });
        } catch (error) {
            console.error('Failed to like video:', error);
        }
    };

    const handleTimeUpdate = () => {
        const duration = videoRef.current.duration;
        const currentTime = videoRef.current.currentTime;
        setProgress((currentTime / duration) * 100);
    };

    const togglePlay = () => {
        if (isPlaying) {
            videoRef.current.pause();
        } else {
            videoRef.current.play();
        }
        setIsPlaying(!isPlaying);
    };

    const toggleMute = () => {
        videoRef.current.muted = !videoRef.current.muted;
        setIsMuted(videoRef.current.muted);
    };

    const renderHashtags = () => {
        if (!video.hashtags?.length) return null;

        return (
            <div className="flex flex-wrap gap-2 mt-2">
                {video.hashtags.map((hashtag, index) => (
                    <Link
                        key={index}
                        href={`/hashtag/${hashtag}`}
                        className="text-blue-500 hover:text-blue-600"
                    >
                        #{hashtag}
                    </Link>
                ))}
            </div>
        );
    };

    return (
        <div className={`bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden ${className}`}>
            <div className="relative aspect-[9/16]" {...bind}>
                {/* Like Animation */}
                {showLikeAnimation && (
                    <div className="absolute inset-0 flex items-center justify-center">
                        <svg 
                            className="w-24 h-24 text-red-500 animate-ping"
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path 
                                strokeLinecap="round" 
                                strokeLinejoin="round" 
                                strokeWidth={2} 
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" 
                            />
                        </svg>
                    </div>
                )}

                <video
                    ref={videoRef}
                    src={video.video_url}
                    className="w-full h-full object-cover"
                    loop
                    muted={isMuted}
                    onTimeUpdate={handleTimeUpdate}
                    onPlay={() => setIsPlaying(true)}
                    onPause={() => setIsPlaying(false)}
                />
                
                {/* Progress Bar */}
                <div className="absolute bottom-0 left-0 right-0 h-1 bg-gray-200">
                    <div 
                        className="h-full bg-blue-500" 
                        style={{ width: `${progress}%` }}
                    />
                </div>

                {/* Video Controls */}
                <div className="absolute bottom-4 left-4 space-x-2">
                    <button 
                        onClick={togglePlay}
                        className="p-2 bg-black/50 rounded-full text-white"
                    >
                        {isPlaying ? (
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 9v6m4-6v6m-9-6h14a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2a2 2 0 012-2z" />
                            </svg>
                        ) : (
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            </svg>
                        )}
                    </button>

                    <button 
                        onClick={toggleMute}
                        className="p-2 bg-black/50 rounded-full text-white"
                    >
                        {isMuted ? (
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                            </svg>
                        ) : (
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                            </svg>
                        )}
                    </button>
                </div>

                {/* Interaction Buttons */}
                <div className="absolute bottom-4 right-4 space-y-4">
                    <LikeButton 
                        count={likesCount}
                        isLiked={isLiked}
                        onClick={handleLike}
                    />
                    
                    <button 
                        onClick={() => setShowComments(!showComments)}
                        className="flex flex-col items-center text-white"
                    >
                        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <span className="text-sm">{video.comments_count}</span>
                    </button>
                    
                    <ShareButton videoId={video.id} />
                </div>
            </div>

            <div className="p-4 space-y-2">
                <Link href={`/users/${video.user.id}`} className="flex items-center space-x-2">
                    <UserAvatar user={video.user} className="w-8 h-8" />
                    <div>
                        <div className="font-medium">{video.user.name}</div>
                        <div className="text-sm text-gray-500">{video.created_at}</div>
                    </div>
                </Link>

                <p className="text-gray-700 dark:text-gray-300">
                    {video.caption}
                </p>

                {renderHashtags()}
            </div>

            {showComments && (
                <CommentSection videoId={video.id} />
            )}
        </div>
    );
}