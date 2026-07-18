<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Services\ActivityCheckInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityCheckInController extends Controller
{
    public function qr(Request $request, Activity $activity, ActivityCheckInService $checkInService): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:2048'],
        ]);

        $result = $checkInService->checkInByQr($activity, $validated['token'], auth()->user());

        return response()->json($result->toArray(), $result->ok ? 200 : 422);
    }

    public function qrCheckOut(Request $request, Activity $activity, ActivityCheckInService $checkInService): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:2048'],
        ]);

        $result = $checkInService->checkOutByQr($activity, $validated['token'], auth()->user());

        return response()->json($result->toArray(), $result->ok ? 200 : 422);
    }
}
