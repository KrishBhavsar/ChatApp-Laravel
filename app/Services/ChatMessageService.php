<?php

namespace App\Services;

use App\Events\CallSignalEvent;
use App\Events\MessageSentEvent;
use App\Helper\ResponseHelper;
use App\Http\Resources\ChatMessageResource;
use App\Interface\ChatMessageInterface;
use Illuminate\Support\Facades\Log;

class ChatMessageService
{
    /**
     * @var ChatMessageInterface
     */
    protected ChatMessageInterface $chatMessageRepository;

    /**
     * @param ChatMessageInterface $chatMessageRepository
     */
    public function __construct(ChatMessageInterface $chatMessageRepository)
    {
        $this->chatMessageRepository = $chatMessageRepository;
    }

    /**
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(array $data)
    {
        $message = $this->chatMessageRepository->sendMessage($data);

        broadcast(new MessageSentEvent($message))->toOthers();

        return ResponseHelper::success(
            'success',
            'Message sent successfully',
            new ChatMessageResource($message)
        );
    }

    /**
     * Relay one WebRTC signaling blob (offer/answer/ice/end/decline/busy) from
     * the authenticated caller to the callee, over the callee's personal
     * broadcast channel. The server does NOT interpret the payload — it only
     * confirms the sender is who they say they are and re-broadcasts.
     *
     * @param int         $fromId    authenticated user's id (the sender)
     * @param string      $fromName  authenticated user's name
     * @param int         $toId      recipient user id
     * @param string      $type      offer | answer | ice | end | decline | busy
     * @param array|null  $payload   SDP object or ICE candidate (null for control types)
     * @return \Illuminate\Http\JsonResponse
     */
    public function relayCallSignal(int $fromId, string $fromName, int $toId, string $type, ?array $payload = null)
    {
        // TEMP DIAGNOSTIC: time the broadcast() call. Because CallSignalEvent is
        // ShouldBroadcastNow, this line makes a BLOCKING outbound HTTPS call to
        // Pusher inside the request. If Render logs show broadcast_ms in the
        // thousands (~30000), the hang is the outbound Pusher call — not the app.
        $start = microtime(true);

        broadcast(new CallSignalEvent($type, $fromId, $toId, $fromName, $payload));

        $ms = (int) round((microtime(true) - $start) * 1000);
        Log::info('call-signal broadcast timing', ['type' => $type, 'broadcast_ms' => $ms]);

        return ResponseHelper::success(
            'success',
            'Signal relayed'
        );
    }

    /**
     * @param int $senderId
     * @param int $receiverId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatHistory(int $senderId, int $receiverId)
    {
        $messages = $this->chatMessageRepository->getChatHistory($senderId, $receiverId);

        return ResponseHelper::success(
            'success',
            'Chat history retrieved',
            ChatMessageResource::collection($messages)
        );
    }

    /**
     * @param int $senderId
     * @param int $receiverId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(int $senderId, int $receiverId)
    {
        $this->chatMessageRepository->markAsRead($senderId, $receiverId);
        return ResponseHelper::success('success', 'Messages marked as read');
    }

    /**
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getContacts(int $userId)
    {
        $contacts = $this->chatMessageRepository->getContacts($userId);

        return ResponseHelper::success('success', 'Contacts retrieved', $contacts);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllChats()
    {
        $messages = $this->chatMessageRepository->getAllChats();

        return ResponseHelper::success(
            'success',
            'All chat messages fetched successfully',
            ChatMessageResource::collection($messages)
        );
    }

    /**
     * @param string $term
     * @param int $excludeUserId
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchUsers(string $term, int $excludeUserId)
    {
        $users = $this->chatMessageRepository->searchUsers($term, $excludeUserId);

        return ResponseHelper::success('success', 'Users retrieved', $users);
    }
}
