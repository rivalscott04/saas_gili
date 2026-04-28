<?php

namespace App\Http\Controllers;

use App\Models\ChatTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppTemplateController extends Controller
{
    private const TEMPLATE_NAME = 'WhatsApp Booking Reminder';

    /**
     * @var list<string>
     */
    private const REQUIRED_TOKENS = ['{{customerName}}', '{{tourName}}', '{{tourStartTime}}', '{{magicLink}}'];

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function index(Request $request): View|RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->hasTenantPermission('whatsapp_templates.manage')) {
            return redirect()->route('root');
        }

        $templates = $this->templateScopeQuery($viewer)
            ->orderByDesc('updated_at')
            ->get();

        if ($templates->isEmpty()) {
            $this->resolveTemplate($viewer);
            $templates = $this->templateScopeQuery($viewer)->orderByDesc('updated_at')->get();
        }

        $isNew = $request->boolean('new');
        $selectedTemplate = null;
        if (! $isNew) {
            $templateId = (int) $request->query('template', 0);
            $selectedTemplate = $templateId > 0
                ? $templates->firstWhere('id', $templateId)
                : $templates->first();
        }

        $formName = old('name', $selectedTemplate?->name ?? ($isNew ? '' : 'WhatsApp Booking Reminder'));
        $formContent = old('content', $selectedTemplate?->content ?? ($isNew ? $this->placeholderOnlyContent() : $this->defaultContent()));
        $templateMap = $templates->mapWithKeys(function ($item): array {
            return [
                (string) $item->id => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'content' => $item->content,
                ],
            ];
        })->toArray();

        return view('apps-whatsapp-template-message', [
            'templates' => $templates,
            'selectedTemplate' => $selectedTemplate,
            'formName' => $formName,
            'formContent' => $formContent,
            'isNewTemplate' => $isNew,
            'templateMap' => $templateMap,
            'requiredTokens' => self::REQUIRED_TOKENS,
            'samplePreview' => $this->preview($formContent),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->hasTenantPermission('whatsapp_templates.manage')) {
            return redirect()->route('root');
        }

        $payload = $request->validate([
            'template_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        foreach (self::REQUIRED_TOKENS as $token) {
            if (! str_contains($payload['content'], $token)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('system_alert', [
                        'reason' => 'TEMPLATE_TOKEN_MISSING',
                        'icon' => 'warning',
                        'title' => 'Placeholder wajib tidak boleh dihapus',
                        'message' => 'Pastikan token '.$token.' tetap ada pada template.',
                    ]);
            }
        }

        $template = null;
        if (! empty($payload['template_id'])) {
            $template = $this->templateScopeQuery($viewer)->whereKey((int) $payload['template_id'])->first();
        }

        if (! $template) {
            $template = new ChatTemplate();
            $template->tenant_id = $viewer->isSuperAdmin() ? null : $viewer->tenant_id;
            $template->user_id = $viewer->id;
        }

        $template->name = $payload['name'];
        $template->content = $payload['content'];
        $template->save();

        return redirect()
            ->route('whatsapp-template-message.index', ['template' => $template->id])
            ->with('system_alert', [
                'reason' => 'TEMPLATE_UPDATED',
                'icon' => 'success',
                'title' => 'Template berhasil diperbarui',
                'message' => 'Perubahan pesan WhatsApp sudah tersimpan.',
            ]);
    }

    public function destroy(Request $request, int $templateId): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->hasTenantPermission('whatsapp_templates.manage')) {
            return redirect()->route('root');
        }

        $template = $this->templateScopeQuery($viewer)->whereKey($templateId)->first();
        if (! $template) {
            return redirect()->route('whatsapp-template-message.index');
        }

        $template->delete();

        return redirect()
            ->route('whatsapp-template-message.index')
            ->with('system_alert', [
                'reason' => 'TEMPLATE_DELETED',
                'icon' => 'success',
                'title' => 'Template dihapus',
                'message' => 'Template WhatsApp berhasil dihapus.',
            ]);
    }

    private function resolveTemplate($viewer): ChatTemplate
    {
        $tenantId = $viewer->isSuperAdmin() ? null : $viewer->tenant_id;

        return ChatTemplate::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'name' => self::TEMPLATE_NAME,
            ],
            [
                'user_id' => null,
                'content' => $this->defaultContent(),
            ]
        );
    }

    private function defaultContent(): string
    {
        return 'Hey {{customerName}}, your booking {{tourName}} will be held tomorrow at {{tourStartTime}} local time. Let me know your response by clicking this link below: {{magicLink}}';
    }

    private function placeholderOnlyContent(): string
    {
        return implode(' ', self::REQUIRED_TOKENS);
    }

    private function preview(string $content): string
    {
        return strtr($content, [
            '{{customerName}}' => 'James Carter',
            '{{tourName}}' => 'Gili Trawangan Snorkeling Escape',
            '{{tourStartTime}}' => '08:00 AM',
            '{{magicLink}}' => 'https://demo.desma.test/booking/123/respond?token=abc123',
        ]);
    }

    private function templateScopeQuery($viewer)
    {
        $tenantId = $viewer->isSuperAdmin() ? null : $viewer->tenant_id;

        return ChatTemplate::query()
            ->where('name', 'like', 'WhatsApp%')
            ->where('tenant_id', $tenantId);
    }
}
