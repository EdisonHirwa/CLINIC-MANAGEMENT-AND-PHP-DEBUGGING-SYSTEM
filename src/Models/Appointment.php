<?php
declare(strict_types=1);

namespace ClinicManagement\Models;

use ClinicManagement\Interfaces\IdentifiableInterface;
use ClinicManagement\Interfaces\PayableInterface;
use ClinicManagement\Enums\AppointmentStatus;
use ClinicManagement\Exceptions\InvalidAppointmentException;
use ClinicManagement\Exceptions\DoctorUnavailableException;
use DateTimeImmutable;

/**
 * Concrete Model: Appointment
 * Manages clinical bookings, schedule verification, and billing.
 */
class Appointment implements IdentifiableInterface, PayableInterface
{
    private static int $counter = 1000;
    private string $id;
    private Patient $patient;
    private Doctor $doctor;
    private DateTimeImmutable $scheduledAt;
    private AppointmentStatus $status;
    private ?string $cancellationReason = null;

    public function __construct(
        Patient $patient,
        Doctor $doctor,
        DateTimeImmutable $scheduledAt
    ) {
        if ($scheduledAt < new DateTimeImmutable()) {
            throw new InvalidAppointmentException("Cannot book an appointment in the past.");
        }

        if (!$doctor->isAvailable($scheduledAt)) {
            throw new DoctorUnavailableException(
                "Booking conflict: Dr. {$doctor->getName()} is unavailable at {$scheduledAt->format('Y-m-d H:i')}."
            );
        }

        $this->id = self::generateId();
        $this->patient = $patient;
        $this->doctor = $doctor;
        $this->scheduledAt = $scheduledAt;
        $this->status = AppointmentStatus::SCHEDULED;

        $this->doctor->bookSlot($this->scheduledAt);
    }

    public static function generateId(): string
    {
        return 'APP-' . (++self::$counter);
    }

    public function getId(): string { return $this->id; }
    public function getPatient(): Patient { return $this->patient; }
    public function getDoctor(): Doctor { return $this->doctor; }
    public function getScheduledAt(): DateTimeImmutable { return $this->scheduledAt; }
    public function getStatus(): AppointmentStatus { return $this->status; }
    public function getCancellationReason(): ?string { return $this->cancellationReason; }

    public function complete(): void
    {
        if ($this->status === AppointmentStatus::CANCELLED) {
            throw new InvalidAppointmentException("Cannot complete an already cancelled appointment.");
        }
        $this->status = AppointmentStatus::COMPLETED;
    }

    public function cancel(string $reason): void
    {
        if ($this->status === AppointmentStatus::CANCELLED) {
            throw new InvalidAppointmentException("Appointment is already cancelled.");
        }
        $this->status = AppointmentStatus::CANCELLED;
        $this->cancellationReason = $reason;
        $this->doctor->releaseSlot($this->scheduledAt);
    }

    public function reschedule(DateTimeImmutable $newSlot): void
    {
        if ($this->status === AppointmentStatus::CANCELLED) {
            throw new InvalidAppointmentException("Cannot reschedule a cancelled appointment.");
        }
        if ($newSlot < new DateTimeImmutable()) {
            throw new InvalidAppointmentException("Rescheduled date cannot be in the past.");
        }
        if (!$this->doctor->isAvailable($newSlot)) {
            throw new DoctorUnavailableException("Dr. {$this->doctor->getName()} is unavailable at {$newSlot->format('Y-m-d H:i')}.");
        }

        $this->doctor->releaseSlot($this->scheduledAt);
        $this->doctor->bookSlot($newSlot);

        $this->scheduledAt = $newSlot;
        $this->status = AppointmentStatus::RESCHEDULED;
    }

    public function calculateTotal(): float
    {
        return $this->doctor->getConsultationFee();
    }

    public function getPaymentSummary(): string
    {
        return sprintf("Consultation with Dr. %s (Appointment %s)", $this->doctor->getName(), $this->id);
    }

    public function getDetails(): string
    {
        return sprintf(
            "[%s] %s | Patient: %s (%s) | Doctor: Dr. %s | Status: %s",
            $this->id,
            $this->scheduledAt->format('Y-m-d H:i'),
            $this->patient->getName(),
            $this->patient->getId(),
            $this->doctor->getName(),
            $this->status->value
        );
    }
}

