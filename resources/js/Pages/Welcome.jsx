import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useEffect, useRef } from 'react';

export default function Welcome({ auth }) {
    const videoRef = useRef(null);

    useEffect(() => {
        // Auto-play video when component mounts
        if (videoRef.current) {
            videoRef.current.play();
        }
    }, []);

    return (
        <>
            <Head title="Welcome" />
            <div className="relative h-screen overflow-hidden bg-black">
                {/* Video Feed */}
                <div className="h-full overflow-y-scroll snap-y snap-mandatory">
                    {/* Video Item */}
                    <div className="relative h-screen snap-start">
                        <video
                            ref={videoRef}
                            className="object-cover w-full h-full"
                            loop
                            muted
                            playsInline
                            src="/videos/sample.mp4"
                        />
                        
                        {/* Overlay Content */}
                        <div className="absolute inset-0 flex flex-col justify-end p-4 bg-gradient-to-t from-black/80 via-transparent to-transparent">
                            {/* User Info */}
                            <div className="flex items-center gap-3 mb-4">
                                <img 
                                    src="https://randomuser.me/api/portraits/men/1.jpg" 
                                    alt="User"
                                    className="w-10 h-10 rounded-full"
                                />
                                <div>
                                    <p className="font-semibold text-white">@username</p>
                                    <p className="text-sm text-gray-300">Location</p>
                                </div>
                                <button className="px-4 py-1 ml-auto text-sm font-semibold text-white bg-tiktok-pink rounded-full">
                                    Follow
                                </button>
                            </div>

                            {/* Video Caption */}
                            <p className="text-white">This is a sample video caption #fyp #tiktok</p>

                            {/* Action Buttons */}
                            <div className="absolute right-4 bottom-24 flex flex-col items-center space-y-4">
                                <button className="flex flex-col items-center text-white">
                                    <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    <span className="text-xs">24.5K</span>
                                </button>
                                <button className="flex flex-col items-center text-white">
                                    <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <span className="text-xs">1.2K</span>
                                </button>
                                <button className="flex flex-col items-center text-white">
                                    <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                    </svg>
                                    <span className="text-xs">Share</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Bottom Navigation */}
                <nav className="fixed bottom-0 left-0 right-0 bg-black/50 backdrop-blur-sm">
                    <div className="flex items-center justify-around p-2">
                        <Link href="/" className="flex flex-col items-center text-white">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span className="text-xs">Home</span>
                        </Link>
                        <Link href="/discover" className="flex flex-col items-center text-white">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span className="text-xs">Discover</span>
                        </Link>
                        <Link href="/upload" className="flex items-center justify-center w-12 h-12 -mt-6 bg-tiktok-pink rounded-full">
                            <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                            </svg>
                        </Link>
                        <Link href="/inbox" className="flex flex-col items-center text-white">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <span className="text-xs">Inbox</span>
                        </Link>
                        <Link href="/profile" className="flex flex-col items-center text-white">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span className="text-xs">Profile</span>
                        </Link>
                    </div>
                </nav>
            </div>
        </>
    );
}
