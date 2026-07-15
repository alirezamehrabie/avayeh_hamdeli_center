<?php

namespace App\Contracts\Ai;

use App\Models\Person;

interface GeneratesFollowUpMessage
{
    /**
     * @return array{can_generate: bool, message: string, review_note: string}
     */
    public function generate(
        Person $person,
        string $recipient,
        string $channel,
        string $purpose,
        string $tone,
        string $details,
    ): array;
}
