<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\VideoController;

Route::middleware('auth:sanctum')->group(function () {
    // Video routes
    Route::apiResource('videos', VideoController::class)->except(['index', 'show']);
    Route::get('/videos', [VideoController::class, 'index']);
    Route::get('/videos/{video}', [VideoController::class, 'show']);
    
    // Like routes
    Route::post('/videos/{video}/like', [VideoController::class, 'like']);
    Route::delete('/videos/{video}/like', [VideoController::class, 'unlike']);
    
    // Comment routes
    Route::post('/videos/{video}/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    
    // Follow routes
    Route::post('/users/{user}/follow', [UserController::class, 'follow']);
    Route::delete('/users/{user}/follow', [UserController::class, 'unfollow']);
    
    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
});

Route::middleware('guest')->group(function () {
    Route::get('/videos', [VideoController::class, 'index']);
    Route::get('/videos/{video}', [VideoController::class, 'show']);
});