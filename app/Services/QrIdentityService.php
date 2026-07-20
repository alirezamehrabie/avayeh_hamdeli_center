<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Models\QrIdentityScanLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QrIdentityService
{
    public function issueFor(Model $subject, ?int $actorId = null, bool $replaceExisting = false, ?string $reason = null): array
    {
        $subjectType = $this->subjectTypeFor($subject);

        return DB::transaction(function () use ($subject, $subjectType, $actorId, $replaceExisting, $reason): array {
            $activeQuery = QrIdentity::query()
                ->where('subject_type', $subjectType)
                ->where('subject_id', $subject->getKey())
                ->where('status', QrIdentity::STATUS_ACTIVE)
                ->lockForUpdate();

            $active = $activeQuery->first();

            if ($active && ! $replaceExisting) {
                return ['identity' => $active, 'token' => null];
            }

            if ($active) {
                $active->update([
                    'status' => QrIdentity::STATUS_REPLACED,
                    'revoked_at' => now(),
                    'revoked_by' => $actorId,
                    'lifecycle_reason' => $reason,
                ]);
            }

            do {
                $token = Str::random(48);
                $tokenHash = $this->hashToken($token);
            } while (QrIdentity::query()->where('token_hash', $tokenHash)->exists());

            $identity = QrIdentity::query()->create([
                'subject_type' => $subjectType,
                'subject_id' => $subject->getKey(),
                'token_hash' => $tokenHash,
                'token_encrypted' => $token,
                'public_code' => $this->nextPublicCode($subjectType),
                'status' => QrIdentity::STATUS_ACTIVE,
                'issued_at' => now(),
                'lifecycle_reason' => $reason,
                'created_by' => $actorId,
            ]);

            return ['identity' => $identity, 'token' => $token];
        });
    }

    public function ensureActiveFor(Model $subject, ?int $actorId = null): QrIdentity
    {
        $issued = $this->issueFor($subject, $actorId, false);

        return $issued['identity'];
    }

    public function replaceFor(Model $subject, ?int $actorId = null, ?string $reason = null): array
    {
        return $this->issueFor($subject, $actorId, true, $reason);
    }

    public function revoke(QrIdentity $identity, ?int $actorId = null, ?string $reason = null): void
    {
        if (! $identity->isActive()) {
            return;
        }

        $identity->update([
            'status' => QrIdentity::STATUS_REVOKED,
            'revoked_at' => now(),
            'revoked_by' => $actorId,
            'lifecycle_reason' => $reason,
        ]);
    }

    public function resolveToken(string $token, ?string $context = null): ?QrIdentity
    {
        $identity = QrIdentity::query()
            ->where('token_hash', $this->hashToken($token))
            ->first();

        if (! $identity || ! $identity->isActive()) {
            $this->logScan($identity, $identity ? 'inactive' : 'not_found', $context);

            return null;
        }

        $subject = $identity->subject;

        if (! $subject || method_exists($subject, 'trashed') && $subject->trashed()) {
            $this->logScan($identity, 'subject_unavailable', $context);

            return null;
        }

        $identity->forceFill(['last_scanned_at' => now()])->save();
        $this->logScan($identity, 'resolved', $context);

        return $identity->setRelation('subject', $subject);
    }

    public function hashToken(string $token): string
    {
        return hash_hmac('sha256', $token, config('app.key'));
    }

    /**
     * Pull the raw token out of a scanned payload. Cards encode the token either bare or inside
     * the /qr/r/{token} resolve URL; anything else is not ours.
     */
    public function extractToken(string $payload): ?string
    {
        $value = trim($payload);

        if ($value === '') {
            return null;
        }

        if (! Str::contains($value, ['http://', 'https://'])) {
            return $value;
        }

        $payloadPath = parse_url($value, PHP_URL_PATH) ?: $value;

        if (! preg_match('/\/qr\/r\/([^\/?#]+)/', (string) $payloadPath, $matches)) {
            return null;
        }

        return isset($matches[1]) ? urldecode($matches[1]) : null;
    }

    /**
     * Fallback for hand-typed short codes (PQR-/GQR-…) printed on the card next to the QR.
     * Mirrors resolveToken(): only an active identity with a live subject resolves.
     */
    public function resolvePublicCode(string $token): ?QrIdentity
    {
        $identity = QrIdentity::query()
            ->where('public_code', strtoupper(trim($token)))
            ->where('status', QrIdentity::STATUS_ACTIVE)
            ->first();

        if (! $identity) {
            return null;
        }

        $subject = $identity->subject;

        if (! $subject || (method_exists($subject, 'trashed') && $subject->trashed())) {
            return null;
        }

        $identity->forceFill(['last_scanned_at' => now()])->save();

        return $identity->setRelation('subject', $subject);
    }

    public function subjectTypeFor(Model $subject): string
    {
        return match (true) {
            $subject instanceof Person => QrIdentity::SUBJECT_PERSON,
            $subject instanceof Guardian => QrIdentity::SUBJECT_GUARDIAN,
            default => throw new \InvalidArgumentException('QR identity subject is not supported.'),
        };
    }

    public function scanUrl(string $token): string
    {
        return route('qr-identities.resolve', ['token' => $token]);
    }

    private function nextPublicCode(string $subjectType): string
    {
        $prefix = $subjectType === QrIdentity::SUBJECT_GUARDIAN ? 'GQR' : 'PQR';

        do {
            $code = $prefix.'-'.strtoupper(Str::random(8));
        } while (QrIdentity::query()->where('public_code', $code)->exists());

        return $code;
    }

    private function logScan(?QrIdentity $identity, string $outcome, ?string $context): void
    {
        QrIdentityScanLog::query()->create([
            'qr_identity_id' => $identity?->id,
            'subject_type' => $identity?->subject_type,
            'subject_id' => $identity?->subject_id,
            'user_id' => Auth::id(),
            'outcome' => $outcome,
            'context' => $context,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
