<?php
declare(strict_types=1);

namespace ClinicManagement\Enums;

enum PaymentStatus: string
{
    case PENDING = 'Pending';
    case PAID = 'Paid';
    case REFUNDED = 'Refunded';
    case FAILED = 'Failed';
}

