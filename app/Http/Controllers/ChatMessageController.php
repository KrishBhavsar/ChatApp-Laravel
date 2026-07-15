<?php

namespace App\Http\Controllers;

use App\Http\Base\BaseController;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Services\ChatMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    /**
     * Return the ICE servers (STUN + TURN) for a WebRTC call.
     *
     * When a Metered API key is configured we fetch SHORT-LIVED (ephemeral)
     * TURN credentials from Metered so the permanent secret never reaches the
     * browser. If that call fails — or no API key is set — we fall back to the
     * long-lived static credentials from config, so calls keep working.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function iceServers()
    {
        $app = config('services.metered.app');
        $apiKey = config('services.metered.api_key');

        if ($app && $apiKey) {
            // Cache the minted creds for a few minutes. metered.live is an external
            // HTTPS round-trip that dominates this endpoint's latency (especially on
            // Render's free tier); the creds are valid for hours, so serving a cached
            // copy is safe and keeps the call/accept hot path off the external hop.
            $cached = Cache::remember('turn.ice_servers', now()->addMinutes(10), function () use ($app, $apiKey) {
                try {
                    $res = Http::timeout(4)->get(
                        "https://{$app}.metered.live/api/v1/turn/credentials",
                        ['apiKey' => $apiKey]
                    );

                    // Metered returns a ready-to-use array of iceServers objects.
                    if ($res->successful() && is_array($res->json())) {
                        return $res->json();
                    }

                    Log::warning('Metered TURN credentials request failed', [
                        'status' => $res->status(),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Metered TURN credentials request threw', [
                        'message' => $e->getMessage(),
                    ]);
                }

                // Return null so the failure is NOT cached — next request retries.
                return null;
            });

            if (is_array($cached) && $cached !== []) {
                return response()->json(['iceServers' => $cached]);
            }

            // Don't let a failed fetch linger in the cache.
            Cache::forget('turn.ice_servers');
        }

        // Fallback: static credentials (still keeps calls connecting).
        return response()->json(['iceServers' => $this->staticIceServers()]);
    }

    /**
     * Static ICE-server list used as a fallback when ephemeral creds can't be
     * fetched. Mirrors the client-side default so behaviour is identical.
     *
     * @return array<int, array<string, mixed>>
     */
    private function staticIceServers(): array
    {
        $user = config('services.metered.static_username');
        $cred = config('services.metered.static_credential');

        $servers = [
            ['urls' => 'stun:stun.l.google.com:19302'],
            ['urls' => 'stun:stun1.l.google.com:19302'],
        ];

        if ($user && $cred) {
            foreach ([
                'turn:global.relay.metered.ca:80',
                'turn:global.relay.metered.ca:80?transport=tcp',
                'turn:global.relay.metered.ca:443',
                'turns:global.relay.metered.ca:443?transport=tcp',
            ] as $url) {
                $servers[] = ['urls' => $url, 'username' => $user, 'credential' => $cred];
            }
        }

        return $servers;
    }
}
