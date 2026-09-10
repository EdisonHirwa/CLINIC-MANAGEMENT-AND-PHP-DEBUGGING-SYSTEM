<?php
declare(strict_types=1);

namespace ClinicManagement\Models;

use ClinicManagement\Interfaces\IdentifiableInterface;
use ClinicManagement\Interfaces\PayableInterface;
use ClinicManagement\Exceptions\ValidationException;
use DateTimeImmutable;

/**
 * Concrete Model: Prescription
 * Implements PayableInterface. Composed of Medicines and dosages.
 */
class Prescription implements IdentifiableInterface, PayableInterface
{
    private static int $counter = 1000;
    private string $id;
    private string $patientId;
    private string $doctorId;
    private DateTimeImmutable $issuedAt;
    /** @var array<int, array{medicine: Medicine, dosage: string, frequency: string, quantity: int}> */
    private array $items = [];

    public function __construct(string $patientId, string $doctorId)
    {
        $this->id = self::generateId();
        $this->patientId = $patientId;
        $this->doctorId = $doctorId;
        $this->issuedAt = new DateTimeImmutable();
    }

    public static function generateId(): string
    {
        return 'RX-' . (++self::$counter);
    }

    public function getId(): string { return $this->id; }
    public function getPatientId(): string { return $this->patientId; }
    public function getDoctorId(): string { return $this->doctorId; }
    public function getIssuedAt(): DateTimeImmutable { return $this->issuedAt; }
    public function getItems(): array { return $this->items; }

    public function addMedicine(Medicine $medicine, string $dosage, string $frequency, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new ValidationException("Prescription quantity must be greater than zero.");
        }
        $medicine->deductStock($quantity);

        $this->items[] = [
            'medicine' => $medicine,
            'dosage' => $dosage,
            'frequency' => $frequency,
            'quantity' => $quantity
        ];
    }

    public function calculateTotal(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += ($item['medicine']->getUnitPrice() * $item['quantity']);
        }
        return $total;
    }

    public function getPaymentSummary(): string
    {
        return sprintf("Prescription [%s] Pharmacy Billing", $this->id);
    }

    public function getDetails(): string
    {
        $lines = [sprintf("Prescription [%s] Issued: %s", $this->id, $this->issuedAt->format('Y-m-d H:i'))];
        foreach ($this->items as $idx => $item) {
            $m = $item['medicine'];
            $lines[] = sprintf(
                "  %d. %s - %s | %s | Qty: %d ($%.2f)",
                $idx + 1,
                $m->getName(),
                $item['dosage'],
                $item['frequency'],
                $item['quantity'],
                $m->getUnitPrice() * $item['quantity']
            );
        }
        $lines[] = sprintf("  Total Medication Cost: $%.2f", $this->calculateTotal());
        return implode("\n", $lines);
    }
}

