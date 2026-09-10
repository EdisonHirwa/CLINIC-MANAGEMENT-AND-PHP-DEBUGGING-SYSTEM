<?php
declare(strict_types=1);

namespace ClinicManagement\Models;

use ClinicManagement\Interfaces\BookableInterface;
use ClinicManagement\Exceptions\DoctorUnavailableException;
use DateTimeImmutable;

/**
 * Concrete Model: Doctor
 * Inherits from Person, implements BookableInterface.
 */
class Doctor extends Person implements BookableInterface
{
    private static int $counter = 100;
    private string $specialization;
    private string $licenseNumber;
    private float $consultationFee;
    /** @var array<string, bool> Booked slots formatted as 'Y-m-d H:i' */
    private array $bookedSlots = [];

    public function __construct(
        string $name,
        string $phone,
        string $email,
        string $specialization,
        string $licenseNumber,
        float $consultationFee
    ) {
        $id = self::generateId();
        parent::__construct($id, $name, $phone, $email);
        $this->specialization = $specialization;
        $this->licenseNumber = $licenseNumber;
        $this->consultationFee = $consultationFee;
    }

    public static function generateId(): string
    {
        return 'DOC-' . (++self::$counter);
    }

    public function getSpecialization(): string { return $this->specialization; }
    public function getLicenseNumber(): string { return $this->licenseNumber; }
    public function getConsultationFee(): float { return $this->consultationFee; }

    public function isAvailable(DateTimeImmutable $slot): bool
    {
        $key = $slot->format('Y-m-d H:i');
        return !isset($this->bookedSlots[$key]);
    }

    public function bookSlot(DateTimeImmutable $slot): void
    {
        $key = $slot->format('Y-m-d H:i');
        if (!$this->isAvailable($slot)) {
            throw new DoctorUnavailableException("Dr. {$this->name} is already booked at {$key}.");
        }
        $this->bookedSlots[$key] = true;
    }

    public function releaseSlot(DateTimeImmutable $slot): void
    {
        $key = $slot->format('Y-m-d H:i');
        unset($this->bookedSlots[$key]);
    }

    public function getRole(): string { return 'Doctor'; }

    public function getDetails(): string
    {
        return sprintf(
            "[%s] Dr. %s | Specialty: %s | Fee: $%.2f | License: %s",
            $this->id, $this->name, $this->specialization, $this->consultationFee, $this->licenseNumber
        );
    }
}

