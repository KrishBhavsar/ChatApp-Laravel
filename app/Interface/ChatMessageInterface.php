<?php

namespace App\Interface;

use App\Models\ChatMessage;

interface ChatMessageInterface
{
    /**
     * @param array $data
     * @return ChatMessage
     */
    public function sendMessage(array $data): ChatMessage;

    /**
     * @param int $senderId
     * @param int $receiverId
     * @return mixed
     */
    public function getChatHistory(int $senderId, int $receiverId);

    /**
     * @param int $senderId
     * @param int $receiverId
     * @return mixed
     */
    public function markAsRead(int $senderId, int $receiverId);

    /**
     * @param int $userId
     * @return mixed
     */
    public function getContacts(int $userId);

    /**
     * Get all chat messages.
     *
     * @return mixed
     */
    public function getAllChats();

    /**
     * Search users by name (or email), excluding the current user.
     *
     * @param string $term
     * @param int $excludeUserId
     * @return mixed
     */
    public function searchUsers(string $term, int $excludeUserId);
}
