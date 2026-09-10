<?php
declare(strict_types=1);

namespace ClinicManagement\Interfaces;

use ClinicManagement\Models\Notification;

/**
 * Contract for entities that can receive clinical notifications.
 */
interface NotifiableInterface
{
    public function notify(Notification $notification): bool;
}

