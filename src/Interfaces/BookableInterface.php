<?php
declare(strict_types=1);

namespace ClinicManagement\Interfaces;

use DateTimeImmutable;

/**
 * Contract for entities that can be booked into a calendar slot.
 */
interface BookableInterface
{
    public function isAvailable(DateTimeImmutable $slot): bool;
    public function bookSlot(DateTimeImmutable $slot): void;
    public function releaseSlot(DateTimeImmutable $slot): void;
}

