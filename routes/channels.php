<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat-{senderId}-{receiverId}', function ($user, $senderId, $receiverId) {
    return (int)$user->id === (int)$senderId || (int)$user->id === (int)$receiverId;
}, ['guards' => ['sanctum']]);

// Personal channel: a user may only listen to their own.
Broadcast::channel('chat-user.{userId}', function ($user, $userId) {
    return (int)$user->id === (int)$userId;
}, ['guards' => ['sanctum']]);
