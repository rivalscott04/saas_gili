<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Minimal profil bisnis tenant — dipakai sebagai deeplink step #1 Setup Checklist
 * (docs/ux-review/2026-05-14-tenant-onboarding-plan.md §4.1).
 *
 * Field yang dikelola adalah subset tenant.* yang minimal supaya magic link
 * konfirmasi via WhatsApp jalan (nama bisnis untuk display + nomor pengirim WA).
 * Form yang lebih lengkap (logo, alamat, dll.) akan menyusul di phase setting bisnis.
 */
class TenantProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function edit(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTenantAdmin(), 403);

        return view('tenant-profile.edit', [
            'tenant' => $user->tenant,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTenantAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_sender_number' => ['required', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $user->tenant->fill($validated)->save();

        return redirect()->route('tenant-profile.edit')
            ->with('status', __('translation.tenant-profile-saved'));
    }
}
