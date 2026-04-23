<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChatMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Models\Booking;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chatService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Booking::class);

        return response()->json([
            'data' => $this->chatService->threadList($request->query('search'), $request->user()),
        ]);
    }

    public function messages(Request $request, Booking $booking): AnonymousResourceCollection
    {
        $this->authorize('view', $booking);

        $messages = $this->chatService->messages($booking, (int) $request->query('per_page', 15));

        return ChatMessageResource::collection($messages);
    }

    public function sendMessage(StoreChatMessageRequest $request, Booking $booking)
    {
        $this->authorize('update', $booking);

        $message = $this->chatService->sendMessage(
            $booking,
            $request->validated('message'),
            $request->validated('source', 'whatsapp')
        );

        return (new ChatMessageResource($message))->toResponse($request)->setStatusCode(201);
    }
}
