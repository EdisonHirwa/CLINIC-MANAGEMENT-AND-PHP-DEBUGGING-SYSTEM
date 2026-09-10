<?php
declare(strict_types=1);

namespace ClinicManagement\Enums;

enum AppointmentStatus: string
{
    case SCHEDULED = 'Scheduled';
    case COMPLETED = 'Completed';
    case CANCELLED = 'Cancelled';
    case RESCHEDULED = 'Rescheduled';
}

