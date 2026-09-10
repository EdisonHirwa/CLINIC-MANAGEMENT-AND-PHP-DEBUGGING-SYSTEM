<?php
declare(strict_types=1);

namespace ClinicManagement\Models;

use ClinicManagement\Interfaces\IdentifiableInterface;
use ClinicManagement\Exceptions\ValidationException;
use ClinicManagement\Exceptions\InsufficientStockException;

/**
 * Concrete Model: Medicine
 * Represents pharmaceutical inventory stock.
 */
class Medicine implements IdentifiableInterface
{
    private static int $counter = 500;
    private string $id;
    private string $name;
    private string $dosageForm;
    private float $unitPrice;
    private int $stockQuantity;

    public function __construct(string $name, string $dosageForm, float $unitPrice, int $stockQuantity)
    {
        if ($unitPrice < 0) {
            throw new ValidationException("Medicine unit price cannot be negative.");
        }
        if ($stockQuantity < 0) {
            throw new ValidationException("Stock quantity cannot be negative.");
        }
        $this->id = self::generateId();
        $this->name = $name;
        $this->dosageForm = $dosageForm;
        $this->unitPrice = $unitPrice;
        $this->stockQuantity = $stockQuantity;
    }

    public static function generateId(): string
    {
        return 'MED-' . (++self::$counter);
    }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDosageForm(): string { return $this->dosageForm; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getStockQuantity(): int { return $this->stockQuantity; }

    public function deductStock(int $quantity): void
    {
        if ($quantity > $this->stockQuantity) {
            throw new InsufficientStockException("Insufficient stock for {$this->name}. In stock: {$this->stockQuantity}.");
        }
        $this->stockQuantity -= $quantity;
    }

    public function addStock(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new ValidationException("Stock increment must be positive.");
        }
        $this->stockQuantity += $quantity;
    }

    public function getDetails(): string
    {
        return sprintf("[%s] %s (%s) - $%.2f | In Stock: %d", $this->id, $this->name, $this->dosageForm, $this->unitPrice, $this->stockQuantity);
    }
}

