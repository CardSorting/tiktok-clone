import React from 'react';
import { Link } from '@inertiajs/inertia-react';

export default function UserAvatar({ user, className = '' }) {
    return (
        <Link href={`/users/${user.id}`}>
            <div className={`relative ${className}`}>
                <img
                    src={user.avatar_url || '/images/default-avatar.png'}
                    alt={user.name}
                    className="w-full h-full rounded-full object-cover"
                />
                {user.is_online && (
                    <div className="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-white dark:border-gray-800" />
                )}
            </div>
        </Link>
    );
}