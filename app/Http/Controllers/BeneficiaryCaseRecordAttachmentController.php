<?php

namespace App\Http\Controllers;

use App\Models\BeneficiaryCaseRecordAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BeneficiaryCaseRecordAttachmentController extends Controller
{
    public function show(BeneficiaryCaseRecordAttachment $attachment): StreamedResponse
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_name,
        );
    }
}
