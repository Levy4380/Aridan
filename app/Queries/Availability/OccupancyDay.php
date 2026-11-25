<?php

namespace App\Queries\Availability;

use Carbon\CarbonImmutable;

/**
 * A day of the occupancy map, split in morning and afternoon.
 */
final readonly class OccupancyDay
{
    public const FREE = 'free';

    public const FIXED = 'fixed';

    public const MOVABLE = 'movable';

    public function __construct(
        public CarbonImmutable $date,
        public string $morning,
        public string $afternoon,
        public bool $morningRequested,
        public bool $afternoonRequested,
        public string $title,
    ) {}

    public function isRequested(): bool
    {
        return $this->morningRequested || $this->afternoonRequested;
    }
}
