import React from 'react';

export default function LikeButton({ count, isLiked, onClick }) {
    return (
        <button 
            onClick={onClick}
            className="flex flex-col items-center text-white"
        >
            <svg 
                className={`w-8 h-8 transition-all duration-200 ${
                    isLiked ? 'text-red-500 fill-red-500' : 'text-white'
                }`}
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
            <span className="text-sm">{count}</span>
        </button>
    );
}