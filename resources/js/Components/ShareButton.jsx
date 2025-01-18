import React, { useState } from 'react';
import { Inertia } from '@inertiajs/inertia';

export default function ShareButton({ videoId }) {
    const [copied, setCopied] = useState(false);

    const handleShare = async () => {
        try {
            const url = `${window.location.origin}/videos/${videoId}`;
            await navigator.clipboard.writeText(url);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (error) {
            console.error('Failed to copy link:', error);
        }
    };

    return (
        <button 
            onClick={handleShare}
            className="flex flex-col items-center text-white"
        >
            <svg 
                className="w-8 h-8" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path 
                    strokeLinecap="round" 
                    strokeLinejoin="round" 
                    strokeWidth={2} 
                    d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" 
                />
            </svg>
            {copied && (
                <span className="text-sm text-green-500">Copied!</span>
            )}
        </button>
    );
}