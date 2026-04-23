<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChatTemplateRequest;
use App\Http\Requests\UpdateChatTemplateRequest;
use App\Http\Resources\ChatTemplateResource;
use App\Models\ChatTemplate;
use App\Services\ChatTemplateService;
use Illuminate\Http\Request;

class ChatTemplateController extends Controller
{
    public function __construct(private readonly ChatTemplateService $chatTemplateService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', ChatTemplate::class);

        return ChatTemplateResource::collection(
            $this->chatTemplateService->paginate($request->user())
        );
    }

    public function store(StoreChatTemplateRequest $request): ChatTemplateResource
    {
        $this->authorize('create', ChatTemplate::class);

        $template = ChatTemplate::query()->create([
            ...$request->validated(),
            'tenant_id' => $request->user()->tenant_id,
            'user_id' => $request->user()->id,
        ]);

        return new ChatTemplateResource($template);
    }

    public function update(UpdateChatTemplateRequest $request, ChatTemplate $chatTemplate): ChatTemplateResource
    {
        $this->authorize('update', $chatTemplate);

        $chatTemplate->update($request->validated());

        return new ChatTemplateResource($chatTemplate->refresh());
    }

    public function destroy(ChatTemplate $chatTemplate)
    {
        $this->authorize('delete', $chatTemplate);

        $chatTemplate->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
