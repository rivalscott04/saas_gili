<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ChatService
{
    public function threadList(?string $search, User $viewer): array
    {
        return $viewer->bookingsVisibleQuery()
            ->with(['chatMessages' => fn ($query) => $query->latest()->limit(1)])
            ->when($search, fn ($query, string $value) => $query->where('customer_name', 'like', '%'.$value.'%'))
            ->whereHas('chatMessages')
            ->orderByDesc(
                ChatMessage::query()
                    ->select('created_at')
                    ->whereColumn('chat_messages.booking_id', 'bookings.id')
                    ->latest()
                    ->limit(1)
            )
            ->get()
            ->map(fn (Booking $booking) => [
                'booking_id' => $booking->id,
                'customer_name' => $booking->customer_name,
                'tour_name' => $booking->tour_name,
                'last_message' => $booking->chatMessages->first()?->message,
                'last_message_at' => $booking->chatMessages->first()?->created_at?->toISOString(),
            ])
            ->all();
    }

    public function messages(Booking $booking, int $perPage): LengthAwarePaginator
    {
        return $booking->chatMessages()->latest()->paginate($perPage);
    }

    public function sendMessage(Booking $booking, string $message, string $source = 'whatsapp'): ChatMessage
    {
        return $booking->chatMessages()->create([
            'sender' => 'operator',
            'message' => $message,
            'source' => $source,
        ]);
    }
}
