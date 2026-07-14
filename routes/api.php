<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatMessageController;
use Illuminate\Support\Facades\Route;


Route::controller(AuthController::class)->group(function () {
    // Public routes
    Route::post('register', 'register');
    Route::post('login', 'login');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', 'userProfile');
        Route::get('logout', 'userLogout');

        // Search users by name/email (to start a new conversation)
        Route::get('users/search', [ChatMessageController::class, 'searchUsers']);

        // Nested Chat routes under auth
        Route::prefix('chat')->group(function () {
            Route::post('/send', [ChatMessageController::class, 'sendMessage']);
            // Voice-call WebRTC signaling relay (offer/answer/ice/hangup)
            Route::post('/call-signal', [ChatMessageController::class, 'callSignal']);
            // Ephemeral ICE servers (STUN + short-lived TURN creds) for a call
            Route::get('/ice-servers', [ChatMessageController::class, 'iceServers']);
            Route::get('/history/{sender_id}/{receiver_id}', [ChatMessageController::class, 'getChatHistory']);
            Route::post('/mark-as-read/{sender_id}/{receiver_id}', [ChatMessageController::class, 'markAsRead']);
            Route::get('/contacts/{sender_id}', [ChatMessageController::class, 'contacts']);
            Route::get('/all', [ChatMessageController::class, 'getAllChats'])
                ->middleware('admin');
        });
    });
});

