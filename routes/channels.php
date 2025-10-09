<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    // Parse the conversation ID (e.g., "1-5" means users 1 and 5)
    $userIds = explode('-', $conversationId);
    
    // Verify the authenticated user is one of the participants
    return in_array($user->id, array_map('intval', $userIds));
});