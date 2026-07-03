<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSentEvent implements ShouldBroadcastNow
{
    use Dispatchable,
        InteractsWithSockets,
        SerializesModels;

    /**
     * @var ChatMessage
     */
    public ChatMessage $message;

    /**
     * @param ChatMessage $message
     */
    public function __construct(ChatMessage $message)
    {
        $this->message = $message;
    }

    /**
     * @return PrivateChannel[]
     */
    public function broadcastOn()
    {
        $conversation = 'chat-' . min($this->message->sender_id, $this->message->receiver_id) . '-' . max($this->message->sender_id, $this->message->receiver_id);

        return [
            // Per-conversation channel: live messages inside an OPEN chat.
            new PrivateChannel($conversation),
            // Per-user channels: let each participant hear about the message even
            // when they don't have this chat open (so a brand-new conversation
            // pops into the sidebar without a refresh).
            new PrivateChannel('chat-user.' . $this->message->sender_id),
            new PrivateChannel('chat-user.' . $this->message->receiver_id),
        ];
    }

    /**
     * @return string
     */
    public function broadcastAs()
    {
        return 'message.sent';
    }

    /**
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'message_text' => $this->message->message_text,
            'message_type' => $this->message->message_type,
            'attachment_url' => $this->message->attachment_url,
            'created_at' => $this->message->created_at->toISOString(),
            'updated_at' => $this->message->updated_at->toISOString(),
        ];
    }
}
