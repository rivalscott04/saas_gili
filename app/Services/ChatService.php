<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ChatService
{
    public function threadList(?string $search, User $viewer, int $limit = 200): array
    {
        $limit = max(1, min($limit, 500));

        $latestMessages = DB::table('chat_messages')
            ->selectRaw('booking_id, message, created_at, ROW_NUMBER() OVER (PARTITION BY booking_id ORDER BY created_at DESC, id DESC) as row_num');

        $latestPerBooking = DB::query()
            ->fromSub($latestMessages, 'ranked_messages')
            ->where('row_num', 1)
            ->select(['booking_id', 'message', 'created_at']);

        $rows = $viewer->bookingsVisibleQuery()
            ->joinSub($latestPerBooking, 'latest_chat', 'bookings.id', '=', 'latest_chat.booking_id')
            ->when($search, fn ($query, string $value) => $query->where('bookings.customer_name', 'like', '%'.$value.'%'))
            ->orderByDesc('latest_chat.created_at')
            ->limit($limit)
            ->get([
                'bookings.id',
                'bookings.customer_name',
                'bookings.tour_name',
                'latest_chat.message as last_message',
                'latest_chat.created_at as last_message_at',
            ]);

        return $rows->map(fn ($row): array => [
            'booking_id' => $row->id,
            'customer_name' => $row->customer_name,
            'tour_name' => $row->tour_name,
            'last_message' => $row->last_message,
            'last_message_at' => $row->last_message_at
                ? Carbon::parse($row->last_message_at)->toIso8601String()
                : null,
        ])->all();
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
