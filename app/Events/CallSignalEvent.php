<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Relays a single WebRTC signaling message (offer / answer / ICE candidate /
 * hangup) from one user to another.
 *
 * Voice audio itself travels peer-to-peer over WebRTC and never touches the
 * server — this event only carries the tiny "let's connect" hand-shake blobs,
 * broadcast on the recipient's personal channel (the SAME channel already used
 * to notify a user of new conversations), so it rings even when the callee does
 * not have the chat open.
 */
class CallSignalEvent implements ShouldBroadcastNow
{
    use Dispatchable,
        InteractsWithSockets,
        SerializesModels;

    /**
     * @var string  offer | answer | ice | end | decline | busy
     */
    public string $type;

    /**
     * @var int  caller's user id
     */
    public int $fromId;

    /**
     * @var int  callee's user id
     */
    public int $toId;

    /**
     * @var string  human-readable name of the caller (so the callee's UI can
     *              show "Alice is calling" without an extra lookup)
     */
    public string $fromName;

    /**
     * @var array|null  the WebRTC payload — an SDP offer/answer object or an
     *                  ICE candidate. Null for control types like "end".
     */
    public ?array $payload;

    /**
     * @param string     $type
     * @param int        $fromId
     * @param int        $toId
     * @param string     $fromName
     * @param array|null $payload
     */
    public function __construct(string $type, int $fromId, int $toId, string $fromName, ?array $payload = null)
    {
        $this->type = $type;
        $this->fromId = $fromId;
        $this->toId = $toId;
        $this->fromName = $fromName;
        $this->payload = $payload;
    }

    /**
     * Deliver to the callee's personal channel — they're already subscribed to
     * it from login, so no separate "call channel" subscription is needed.
     *
     * @return PrivateChannel[]
     */
    public function broadcastOn()
    {
        return [
            new PrivateChannel('chat-user.' . $this->toId),
        ];
    }

    /**
     * @return string
     */
    public function broadcastAs()
    {
        return 'call.signal';
    }

    /**
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'type' => $this->type,
            'from_id' => $this->fromId,
            'to_id' => $this->toId,
            'from_name' => $this->fromName,
            'payload' => $this->payload,
        ];
    }
}
