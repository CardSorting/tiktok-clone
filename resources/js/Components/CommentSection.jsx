import React, { useState } from 'react';
import { Inertia } from '@inertiajs/inertia';
import UserAvatar from '@/Components/UserAvatar';
import LoadingSpinner from '@/Components/LoadingSpinner';

export default function CommentSection({ videoId }) {
    const [comments, setComments] = useState([]);
    const [newComment, setNewComment] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const loadComments = async () => {
        setLoading(true);
        try {
            const response = await Inertia.get(`/api/videos/${videoId}/comments`);
            setComments(response.props.comments);
        } catch (error) {
            setError('Failed to load comments');
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!newComment.trim()) return;

        try {
            const response = await Inertia.post(`/api/videos/${videoId}/comments`, {
                content: newComment
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    setComments(prev => [response.props.comment, ...prev]);
                    setNewComment('');
                }
            });
        } catch (error) {
            setError('Failed to post comment');
        }
    };

    return (
        <div className="bg-gray-50 dark:bg-gray-700 p-4 space-y-4">
            <form onSubmit={handleSubmit} className="flex items-center space-x-2">
                <input
                    type="text"
                    value={newComment}
                    onChange={(e) => setNewComment(e.target.value)}
                    placeholder="Add a comment..."
                    className="flex-1 p-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                />
                <button
                    type="submit"
                    className="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                >
                    Post
                </button>
            </form>

            {loading && (
                <div className="flex justify-center">
                    <LoadingSpinner className="w-6 h-6" />
                </div>
            )}

            {error && (
                <div className="text-red-500 text-center">
                    {error}
                </div>
            )}

            <div className="space-y-4">
                {comments.map(comment => (
                    <div key={comment.id} className="flex items-start space-x-2">
                        <UserAvatar user={comment.user} className="w-8 h-8" />
                        <div className="flex-1">
                            <div className="font-medium">{comment.user.name}</div>
                            <p className="text-gray-700 dark:text-gray-300">
                                {comment.content}
                            </p>
                            <div className="text-sm text-gray-500">
                                {comment.created_at}
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}