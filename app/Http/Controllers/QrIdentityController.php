<?php

namespace App\Http\Controllers;

use App\Models\QrIdentity;
use App\Services\QrIdentityService;
use Illuminate\Http\RedirectResponse;

class QrIdentityController extends Controller
{
    public function resolve(string $token, QrIdentityService $qrIdentityService): RedirectResponse
    {
        abort_unless(auth()->check(), 403);
        abort_unless(auth()->user()->can('access-admin-panel') || auth()->user()->can('access-social-worker-panel'), 403);

        $identity = $qrIdentityService->resolveToken($token, 'web-scan');

        if (! $identity) {
            return redirect()
                ->to(auth()->user()->getPanelRedirectPath())
                ->with('error', 'QR code is invalid, revoked, or unavailable.');
        }

        if (auth()->user()->can('access-social-worker-panel')) {
            return redirect()
                ->route('social-worker.dashboard')
                ->with('success', 'QR code resolved. Use the service delivery QR field to attach it to a delivery.');
        }

        if ($identity->subject_type === QrIdentity::SUBJECT_PERSON) {
            return redirect()
                ->route('admin.dashboard', ['section' => 'person-edit', 'id' => $identity->subject_id])
                ->with('success', 'Beneficiary QR code resolved.');
        }

        return redirect()
            ->route('admin.dashboard', ['section' => 'guardian-edit', 'id' => $identity->subject_id])
            ->with('success', 'Guardian QR code resolved.');
    }
}
