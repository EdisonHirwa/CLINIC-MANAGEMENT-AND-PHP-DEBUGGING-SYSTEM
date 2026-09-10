<?php
declare(strict_types=1);

namespace ClinicManagement\Models;

use ClinicManagement\Interfaces\IdentifiableInterface;
use ClinicManagement\Enums\PaymentMethod;
use ClinicManagement\Enums\PaymentStatus;
use ClinicManagement\Exceptions\ValidationException;
use DateTimeImmutable;

/**
 * Concrete Model: Payment
 * Processes financial receipts and transaction verification.
 */
class Payment implements IdentifiableInterface
{
    private static int $counter = 1000;
    private string $id;
    private string $transactionRef;
    private float $amount;
    private PaymentMethod $method;
    private PaymentStatus $status;
    private DateTimeImmutable $processedAt;
    private string $description;

    public function __construct(
        float $amount,
        PaymentMethod $method,
        string $description
    ) {
        if ($amount <= 0.0) {
            throw new ValidationException("Payment amount must be greater than zero.");
        }
        $this->id = self::generateId();
        $this->transactionRef = 'TXN-' . strtoupper(bin2hex(random_bytes(4)));
        $this->amount = $amount;
        $this->method = $method;
        $this->description = $description;
        $this->status = PaymentStatus::PENDING;
        $this->processedAt = new DateTimeImmutable();
    }

    public static function generateId(): string
    {
        return 'PAY-' . (++self::$counter);
    }

    public function getId(): string { return $this->id; }
    public function getTransactionRef(): string { return $this->transactionRef; }
    public function getAmount(): float { return $this->amount; }
    public function getMethod(): PaymentMethod { return $this->method; }
    public function getStatus(): PaymentStatus { return $this->status; }
    public function getProcessedAt(): DateTimeImmutable { return $this->processedAt; }
    public function getDescription(): string { return $this->description; }

    public function process(): bool
    {
        $this->status = PaymentStatus::PAID;
        return true;
    }

    public function getReceipt(): string
    {
        return sprintf(
            "================ RECEIPT ================\n" .
            "Receipt No   : %s\n" .
            "Ref Code     : %s\n" .
            "Description  : %s\n" .
            "Amount Paid  : $%.2f\n" .
            "Method       : %s\n" .
            "Status       : %s\n" .
            "Date         : %s\n" .
            "=========================================",
            $this->id,
            $this->transactionRef,
            $this->description,
            $this->amount,
            $this->method->value,
            $this->status->value,
            $this->processedAt->format('Y-m-d H:i:s')
        );
    }
}

