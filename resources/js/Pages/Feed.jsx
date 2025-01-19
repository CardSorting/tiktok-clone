import React, { useEffect, useState, useRef } from 'react';
import { Inertia } from '@inertiajs/inertia';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import VideoCard from '@/Components/VideoCard';
import LoadingSpinner from '@/Components/LoadingSpinner';
import { useSwipeable } from 'react-swipeable';

export default function Feed({ initialVideos }) {
    const [videos, setVideos] = useState(initialVideos.data);
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(false);
    const [hasMore, setHasMore] = useState(initialVideos.next_page_url !== null);
    const [activeIndex, setActiveIndex] = useState(0);
    const videoRefs = useRef([]);

    const handlers = useSwipeable({
        onSwipedUp: () => {
            if (activeIndex < videos.length - 1) {
                setActiveIndex(prev => prev + 1);
                videoRefs.current[activeIndex + 1]?.play();
            }
        },
        onSwipedDown: () => {
            if (activeIndex > 0) {
                setActiveIndex(prev => prev - 1);
                videoRefs.current[activeIndex - 1]?.play();
            }
        },
        preventDefaultTouchmoveEvent: true,
        trackMouse: true
    });

    const loadMoreVideos = async () => {
        if (loading || !hasMore) return;
        
        setLoading(true);
        try {
            const response = await Inertia.get(`/api/videos?page=${page + 1}`, {}, {
                preserveState: true,
                only: ['videos'],
                onSuccess: (page) => {
                    setVideos(prev => [...prev, ...page.props.videos.data]);
                    setPage(prev => prev + 1);
                    setHasMore(page.props.videos.next_page_url !== null);
                }
            });
        } finally {
            setLoading(false);
        }
    };

    const handleScroll = () => {
        // Load more videos when near bottom
        if (window.innerHeight + document.documentElement.scrollTop + 100 >= 
            document.documentElement.offsetHeight) {
            loadMoreVideos();
        }

        // Autoplay video in view
        videoRefs.current.forEach((videoRef, index) => {
            const rect = videoRef.getBoundingClientRect();
            const isVisible = (
                rect.top >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight)
            );

            if (isVisible) {
                videoRef.play();
            } else {
                videoRef.pause();
            }
        });
    };

    useEffect(() => {
        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, [loading, hasMore]);

    return (
        <AuthenticatedLayout>
            <div {...handlers} className="max-w-2xl mx-auto">
                <div className="relative" style={{ height: `${videos.length * 100}vh` }}>
                    {videos.map((video, index) => (
                        <div
                            key={video.id}
                            className="absolute top-0 left-0 w-full h-screen"
                            style={{ transform: `translateY(${index * 100}vh)` }}
                        >
                            <VideoCard 
                                video={video}
                                className={`transition-transform duration-500 ${
                                    index === activeIndex ? 'translate-y-0' : 
                                    index < activeIndex ? '-translate-y-full' : 'translate-y-full'
                                }`}
                                ref={el => videoRefs.current[index] = el?.querySelector('video')}
                            />
                        </div>
                    ))}
                </div>
                
                {loading && (
                    <div className="flex justify-center py-8">
                        <LoadingSpinner className="w-8 h-8" />
                    </div>
                )}
                
                {!hasMore && (
                    <div className="text-center text-gray-500 py-8">
                        No more videos to show
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
