<?php

namespace App\Data\Deliveries;

final readonly class MiscServiceEditData
{
    /**
     * @param  array<int, array<string, mixed>>  $workerGroups
     */
    public function __construct(
        public int $serviceId,
        public ?string $expectedVersion,
        public string $serviceType,
        public ?string $description,
        public string $distributionDate,
        public array $workerGroups,
    ) {}
}
