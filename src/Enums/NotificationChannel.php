<?php
declare(strict_types=1);

namespace ClinicManagement\Enums;

enum NotificationChannel: string
{
    case SMS = 'SMS';
    case EMAIL = 'Email';
    case SYSTEM = 'System Alert';
}

