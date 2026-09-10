<?php
declare(strict_types=1);

namespace ClinicManagement\Interfaces;

/**
 * Contract for billable items/services that produce financial summaries.
 */
interface PayableInterface
{
    public function calculateTotal(): float;
    public function getPaymentSummary(): string;
}

