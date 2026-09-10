<?php
declare(strict_types=1);

namespace ClinicManagement\Enums;

enum PaymentMethod: string
{
    case CASH = 'Cash';
    case CREDIT_CARD = 'Credit Card';
    case INSURANCE = 'Insurance';
    case MOBILE_MONEY = 'Mobile Money';
}

