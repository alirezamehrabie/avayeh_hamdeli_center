<?php

namespace App\Data\People;

final readonly class BeneficiaryCaseRecordData
{
    public function __construct(
        public string $recordType,
        public string $title,
        public ?string $description,
        public ?string $recordedAt,
        public ?int $amount,
        public ?string $referenceNumber,
    ) {}

    public function toAttributes(): array
    {
        return [
            'record_type' => $this->recordType,
            'title' => trim($this->title),
            'description' => filled($this->description) ? trim($this->description) : null,
            'recorded_at' => $this->recordedAt,
            'amount' => $this->amount,
            'reference_number' => filled($this->referenceNumber) ? trim($this->referenceNumber) : null,
        ];
    }
}
