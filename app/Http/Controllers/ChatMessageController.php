<?php

namespace App\Http\Controllers;

use App\Http\Base\BaseController;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Services\ChatMessageService;
use Illuminate\Http\Request;

class ChatMessageController extends BaseController
{
    /**x
     * @var ChatMessageService
     */
    protected ChatMessageService $chatMessageService;

    /**
     * @param ChatMessageService $chatMessageService
     */
    public function __construct(ChatMessageService $chatMessageService)
    {
        $this->chatMessageService = $chatMessageService;
    }

    /**
     * @param SendMessageRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(SendMessageRequest $request)
    {
        return $this->chatMessageService->sendMessage($request->validated());
    }

    /**
     * @param int $senderId
     * @param int $receiverId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatHistory(int $senderId, int $receiverId)
    {
        return $this->chatMessageService->getChatHistory($senderId, $receiverId);
    }

    /**
     * @param int $senderId
     * @param int $receiverId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(int $senderId, int $receiverId)
    {
        return $this->chatMessageService->markAsRead($senderId, $receiverId);
    }

    /**
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function contacts(int $userId)
    {
        return $this->chatMessageService->getContacts($userId);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllChats()
    {
        return $this->chatMessageService->getAllChats();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchUsers(Request $request)
    {
        $term = trim((string) $request->query('search', ''));

        return $this->chatMessageService->searchUsers($term, $request->user()->id);
    }

    /**
     * Relay a WebRTC signaling message to another user (voice call handshake).
     * The sender id is taken from the authenticated user — NOT the request body —
     * so a caller cannot spoof someone else.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function callSignal(Request $request)
    {
        $data = $request->validate([
            'to_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'string', 'in:offer,answer,ice,end,decline,busy'],
            'payload' => ['nullable', 'array'],
        ]);

        $user = $request->user();

        return $this->chatMessageService->relayCallSignal(
            $user->id,
            $user->name,
            (int) $data['to_id'],
            $data['type'],
            $data['payload'] ?? null
        );
    }
}
