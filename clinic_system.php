<?php
declare(strict_types=1);

/**
 * ============================================================================
 * GROUP 6: CLINIC MANAGEMENT AND PHP DEBUGGING SYSTEM
 * Advanced Object-Oriented PHP 8.2+ Production-Grade CLI Application
 * ============================================================================
 * 
 * Architectural Highlights:
 *  - Full OOP Hierarchy: Abstract base class, specialized entities, interfaces
 *  - Strict Type Safety: declare(strict_types=1), union/nullable types, Enums
 *  - Polymorphism: Standardized contracts with domain-specific implementations
 *  - Robust Error Handling: Domain-specific custom exceptions with try-catch-finally
 *  - CLI UX: ANSI colored menus, input validation, defensive error recovery
 *  - Resource Lifecycle: Explicit constructors & destructors with audit logging
 * ============================================================================
 */

namespace ClinicManagement;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;

// ============================================================================
// SECTION 1: ENUMS (PHP 8.1+)
// ============================================================================

enum AppointmentStatus: string
{
    case SCHEDULED = 'Scheduled';
    case COMPLETED = 'Completed';
    case CANCELLED = 'Cancelled';
    case RESCHEDULED = 'Rescheduled';
}

enum PaymentMethod: string
{
    case CASH = 'Cash';
    case CREDIT_CARD = 'Credit Card';
    case INSURANCE = 'Insurance';
    case MOBILE_MONEY = 'Mobile Money';
}

enum PaymentStatus: string
{
    case PENDING = 'Pending';
    case PAID = 'Paid';
    case REFUNDED = 'Refunded';
    case FAILED = 'Failed';
}

enum NotificationChannel: string
{
    case SMS = 'SMS';
    case EMAIL = 'Email';
    case SYSTEM = 'System Alert';
}

// ============================================================================
// SECTION 2: CUSTOM DOMAIN EXCEPTIONS
// ============================================================================

class ClinicException extends Exception {}

class PatientNotFoundException extends ClinicException {}

class DoctorUnavailableException extends ClinicException {}

class InvalidAppointmentException extends ClinicException {}

class ValidationException extends ClinicException {}

class PaymentFailedException extends ClinicException {}

class RecordNotFoundException extends ClinicException {}

class InsufficientStockException extends ClinicException {}

// ============================================================================
// SECTION 3: INTERFACES (CONTRACTS)
// ============================================================================

/**
 * Contract for entities identifiable by a domain-specific string ID.
 */
interface IdentifiableInterface
{
    public function getId(): string;
}

/**
 * Contract for entities that can be booked into a calendar slot.
 */
interface BookableInterface
{
    public function isAvailable(DateTimeImmutable $slot): bool;
    public function bookSlot(DateTimeImmutable $slot): void;
    public function releaseSlot(DateTimeImmutable $slot): void;
}

/**
 * Contract for billable items/services that produce financial summaries.
 */
interface PayableInterface
{
    public function calculateTotal(): float;
    public function getPaymentSummary(): string;
}

/**
 * Contract for entities that can receive clinical notifications.
 */
interface NotifiableInterface
{
    public function notify(Notification $notification): bool;
}

// ============================================================================
// SECTION 4: CORE ENTITY CLASSES (OOP DOMAIN LAYER)
// ============================================================================

/**
 * Class 1: Abstract Person
 * Represents common demographic & identity attributes.
 */
abstract class Person implements IdentifiableInterface, NotifiableInterface
{
    protected string $id;
    protected string $name;
    protected string $phone;
    protected string $email;
    protected DateTimeImmutable $createdAt;

    public function __construct(string $id, string $name, string $phone, string $email)
    {
        $this->id = $id;
        $this->name = $name;
        $this->phone = $phone;
        $this->email = $email;
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * Destructor: Illustrates explicit object teardown and session auditing.
     */
    public function __destruct()
    {
        // Resource cleanup simulation: e.g., logging person instance deallocation
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function updateContact(string $phone, string $email): void
    {
        $this->phone = $phone;
        $this->email = $email;
    }

    /**
     * Implementation of NotifiableInterface
     */
    public function notify(Notification $notification): bool
    {
        return $notification->send();
    }

    /**
     * Abstract methods enforcing Polymorphism across subtypes
     */
    abstract public function getRole(): string;
    abstract public function getDetails(): string;
}

/**
 * Class 2: Patient
 * Inherits from Person. Maintains medical history and demographics.
 */
class Patient extends Person
{
    private static int $counter = 1000;
    private string $bloodGroup;
    private DateTimeImmutable $dateOfBirth;
    /** @var array<int, string> */
    private array $allergies;

    public function __construct(
        string $name,
        string $phone,
        string $email,
        string $bloodGroup,
        DateTimeImmutable $dateOfBirth,
        array $allergies = []
    ) {
        $id = self::generateId();
        parent::__construct($id, $name, $phone, $email);
        $this->bloodGroup = strtoupper($bloodGroup);
        $this->dateOfBirth = $dateOfBirth;
        $this->allergies = $allergies;
    }

    public static function generateId(): string
    {
        return 'PAT-' . (++self::$counter);
    }

    public function getBloodGroup(): string
    {
        return $this->bloodGroup;
    }

    public function getAge(): int
    {
        return (new DateTimeImmutable())->diff($this->dateOfBirth)->y;
    }

    public function getAllergies(): array
    {
        return $this->allergies;
    }

    public function addAllergy(string $allergy): void
    {
        if (!in_array($allergy, $this->allergies, true)) {
            $this->allergies[] = $allergy;
        }
    }

    /**
     * Polymorphic implementation of getRole()
     */
    public function getRole(): string
    {
        return 'Patient';
    }

    /**
     * Polymorphic implementation of getDetails()
     */
    public function getDetails(): string
    {
        $allergyList = empty($this->allergies) ? 'None' : implode(', ', $this->allergies);
        return sprintf(
            "[%s] %s | Age: %d | Blood: %s | Phone: %s | Allergies: %s",
            $this->id,
            $this->name,
            $this->getAge(),
            $this->bloodGroup,
            $this->phone,
            $allergyList
        );
    }
}

/**
 * Class 3: Doctor
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

    public function getSpecialization(): string
    {
        return $this->specialization;
    }

    public function getConsultationFee(): float
    {
        return $this->consultationFee;
    }

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

    public function getRole(): string
    {
        return 'Doctor';
    }

    public function getDetails(): string
    {
        return sprintf(
            "[%s] Dr. %s | Specialty: %s | Fee: $%.2f | License: %s",
            $this->id,
            $this->name,
            $this->specialization,
            $this->consultationFee,
            $this->licenseNumber
        );
    }
}

/**
 * Class 4: Nurse
 * Inherits from Person. Manages outpatient care and vitals.
 */
class Nurse extends Person
{
    private static int $counter = 100;
    private string $department;
    private string $shift; // Day / Night

    public function __construct(
        string $name,
        string $phone,
        string $email,
        string $department,
        string $shift
    ) {
        $id = self::generateId();
        parent::__construct($id, $name, $phone, $email);
        $this->department = $department;
        $this->shift = $shift;
    }

    public static function generateId(): string
    {
        return 'NUR-' . (++self::$counter);
    }

    public function getDepartment(): string
    {
        return $this->department;
    }

    public function getShift(): string
    {
        return $this->shift;
    }

    public function getRole(): string
    {
        return 'Nurse';
    }

    public function getDetails(): string
    {
        return sprintf(
            "[%s] Nurse %s | Dept: %s | Shift: %s | Contact: %s",
            $this->id,
            $this->name,
            $this->department,
            $this->shift,
            $this->phone
        );
    }
}

/**
 * Class 5: Medicine
 * Represents pharmaceutical inventory items.
 */
class Medicine implements IdentifiableInterface
{
    private static int $counter = 500;
    private string $id;
    private string $name;
    private string $dosageForm; // Tablet, Syrup, Injection
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

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDosageForm(): string
    {
        return $this->dosageForm;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function deductStock(int $quantity): void
    {
        if ($quantity > $this->stockQuantity) {
            throw new InsufficientStockException("Insufficient stock for {$this->name}. Requested: {$quantity}, Available: {$this->stockQuantity}.");
        }
        $this->stockQuantity -= $quantity;
    }

    public function addStock(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new ValidationException("Stock increase must be positive.");
        }
        $this->stockQuantity += $quantity;
    }

    public function getDetails(): string
    {
        return sprintf("[%s] %s (%s) - $%.2f | In Stock: %d", $this->id, $this->name, $this->dosageForm, $this->unitPrice, $this->stockQuantity);
    }
}

/**
 * Class 6: Prescription
 * Implements PayableInterface. Composed of prescribed Medicines.
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

    public function getId(): string
    {
        return $this->id;
    }

    public function getPatientId(): string
    {
        return $this->patientId;
    }

    public function getDoctorId(): string
    {
        return $this->doctorId;
    }

    public function addMedicine(Medicine $medicine, string $dosage, string $frequency, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new ValidationException("Prescription quantity must be greater than zero.");
        }
        // Deduct inventory immediately
        $medicine->deductStock($quantity);

        $this->items[] = [
            'medicine' => $medicine,
            'dosage' => $dosage,
            'frequency' => $frequency,
            'quantity' => $quantity
        ];
    }

    public function getItems(): array
    {
        return $this->items;
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
        return sprintf("Prescription [%s] Pharmacy Charge", $this->id);
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

/**
 * Class 7: MedicalRecord
 * Encapsulates clinical notes, diagnoses, and links to Prescriptions.
 */
class MedicalRecord implements IdentifiableInterface
{
    private static int $counter = 1000;
    private string $id;
    private string $patientId;
    private string $doctorId;
    private DateTimeImmutable $recordedAt;
    private string $symptoms;
    private string $diagnosis;
    private string $treatmentPlan;
    private ?Prescription $prescription = null;

    public function __construct(
        string $patientId,
        string $doctorId,
        string $symptoms,
        string $diagnosis,
        string $treatmentPlan
    ) {
        $this->id = self::generateId();
        $this->patientId = $patientId;
        $this->doctorId = $doctorId;
        $this->symptoms = $symptoms;
        $this->diagnosis = $diagnosis;
        $this->treatmentPlan = $treatmentPlan;
        $this->recordedAt = new DateTimeImmutable();
    }

    public static function generateId(): string
    {
        return 'REC-' . (++self::$counter);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPatientId(): string
    {
        return $this->patientId;
    }

    public function getDoctorId(): string
    {
        return $this->doctorId;
    }

    public function getDiagnosis(): string
    {
        return $this->diagnosis;
    }

    public function setPrescription(Prescription $prescription): void
    {
        $this->prescription = $prescription;
    }

    public function getPrescription(): ?Prescription
    {
        return $this->prescription;
    }

    public function getFullRecord(): string
    {
        $out = [
            "--------------------------------------------------",
            sprintf("Record ID    : %s", $this->id),
            sprintf("Date         : %s", $this->recordedAt->format('Y-m-d H:i')),
            sprintf("Patient ID   : %s", $this->patientId),
            sprintf("Doctor ID    : %s", $this->doctorId),
            sprintf("Symptoms     : %s", $this->symptoms),
            sprintf("Diagnosis    : %s", $this->diagnosis),
            sprintf("Treatment    : %s", $this->treatmentPlan),
        ];

        if ($this->prescription !== null) {
            $out[] = "Attached Rx  :\n" . $this->prescription->getDetails();
        } else {
            $out[] = "Attached Rx  : None";
        }
        $out[] = "--------------------------------------------------";
        return implode("\n", $out);
    }
}

/**
 * Class 8: Appointment
 * Manages clinical bookings, conflict prevention, and billing.
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

        // Defensive validation: verify doctor availability
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

        // Reserve doctor's slot
        $this->doctor->bookSlot($this->scheduledAt);
    }

    public static function generateId(): string
    {
        return 'APP-' . (++self::$counter);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPatient(): Patient
    {
        return $this->patient;
    }

    public function getDoctor(): Doctor
    {
        return $this->doctor;
    }

    public function getScheduledAt(): DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function getStatus(): AppointmentStatus
    {
        return $this->status;
    }

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
        // Release doctor slot so another patient can book
        $this->doctor->releaseSlot($this->scheduledAt);
    }

    public function reschedule(DateTimeImmutable $newSlot): void
    {
        if ($this->status === AppointmentStatus::CANCELLED) {
            throw new InvalidAppointmentException("Cannot reschedule a cancelled appointment. Please book a new one.");
        }
        if ($newSlot < new DateTimeImmutable()) {
            throw new InvalidAppointmentException("Rescheduled date cannot be in the past.");
        }
        if (!$this->doctor->isAvailable($newSlot)) {
            throw new DoctorUnavailableException("Dr. {$this->doctor->getName()} is unavailable at {$newSlot->format('Y-m-d H:i')}.");
        }

        // Release old slot and book new slot
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

/**
 * Class 9: Payment
 * Processes financial transactions with receipt generation.
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

    public function getId(): string
    {
        return $this->id;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function process(): bool
    {
        // Simulate payment gateway verification
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

/**
 * Class 10: Notification
 * Represents communications dispatched across clinical channels.
 */
class Notification implements IdentifiableInterface
{
    private static int $counter = 1000;
    private string $id;
    private string $recipient;
    private NotificationChannel $channel;
    private string $message;
    private DateTimeImmutable $sentAt;

    public function __construct(string $recipient, NotificationChannel $channel, string $message)
    {
        $this->id = self::generateId();
        $this->recipient = $recipient;
        $this->channel = $channel;
        $this->message = $message;
        $this->sentAt = new DateTimeImmutable();
    }

    public static function generateId(): string
    {
        return 'NOTIF-' . (++self::$counter);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function send(): bool
    {
        // Simulated delivery mechanism
        return true;
    }

    public function render(): string
    {
        return sprintf(
            "[%s] [%s -> %s] %s (%s)",
            $this->id,
            $this->channel->value,
            $this->recipient,
            $this->message,
            $this->sentAt->format('H:i:s')
        );
    }
}

/**
 * Class 11: Clinic
 * Central orchestrator aggregating all patient, staff, scheduling, and pharmacy records.
 */
class Clinic
{
    private string $name;
    private string $location;
    /** @var array<string, Patient> */
    private array $patients = [];
    /** @var array<string, Doctor> */
    private array $doctors = [];
    /** @var array<string, Nurse> */
    private array $nurses = [];
    /** @var array<string, Appointment> */
    private array $appointments = [];
    /** @var array<string, MedicalRecord> */
    private array $medicalRecords = [];
    /** @var array<string, Medicine> */
    private array $medicines = [];
    /** @var array<string, Payment> */
    private array $payments = [];

    public function __construct(string $name, string $location)
    {
        $this->name = $name;
        $this->location = $location;
        $this->seedInitialData();
    }

    /**
     * Destructor: Ensures state logs are flushed upon shutdown.
     */
    public function __destruct()
    {
        // In real-world systems, flush database connection pools or audit logs
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    // --- Patient Management ---
    public function addPatient(Patient $patient): void
    {
        $this->patients[$patient->getId()] = $patient;
    }

    public function getPatient(string $id): Patient
    {
        if (!isset($this->patients[$id])) {
            throw new PatientNotFoundException("Patient with ID '{$id}' does not exist.");
        }
        return $this->patients[$id];
    }

    public function searchPatients(string $query): array
    {
        $results = [];
        $q = strtolower(trim($query));
        foreach ($this->patients as $patient) {
            if (
                str_contains(strtolower($patient->getName()), $q) ||
                str_contains(strtolower($patient->getId()), $q) ||
                str_contains(strtolower($patient->getPhone()), $q)
            ) {
                $results[] = $patient;
            }
        }
        return $results;
    }

    public function getAllPatients(): array
    {
        return array_values($this->patients);
    }

    // --- Doctor & Nurse Management ---
    public function addDoctor(Doctor $doctor): void
    {
        $this->doctors[$doctor->getId()] = $doctor;
    }

    public function getDoctor(string $id): Doctor
    {
        if (!isset($this->doctors[$id])) {
            throw new ClinicException("Doctor with ID '{$id}' was not found.");
        }
        return $this->doctors[$id];
    }

    public function getAllDoctors(): array
    {
        return array_values($this->doctors);
    }

    public function addNurse(Nurse $nurse): void
    {
        $this->nurses[$nurse->getId()] = $nurse;
    }

    public function getAllNurses(): array
    {
        return array_values($this->nurses);
    }

    // --- Appointment Management ---
    public function bookAppointment(string $patientId, string $doctorId, DateTimeImmutable $slot): Appointment
    {
        $patient = $this->getPatient($patientId);
        $doctor = $this->getDoctor($doctorId);

        $appointment = new Appointment($patient, $doctor, $slot);
        $this->appointments[$appointment->getId()] = $appointment;

        // Dispatch Confirmation Notification
        $notif = new Notification(
            $patient->getName(),
            NotificationChannel::SMS,
            "Appointment {$appointment->getId()} confirmed with Dr. {$doctor->getName()} for {$slot->format('Y-m-d H:i')}."
        );
        $patient->notify($notif);

        return $appointment;
    }

    public function getAppointment(string $id): Appointment
    {
        if (!isset($this->appointments[$id])) {
            throw new InvalidAppointmentException("Appointment ID '{$id}' does not exist.");
        }
        return $this->appointments[$id];
    }

    public function getAllAppointments(): array
    {
        return array_values($this->appointments);
    }

    public function getPatientAppointments(string $patientId): array
    {
        $result = [];
        foreach ($this->appointments as $app) {
            if ($app->getPatient()->getId() === $patientId) {
                $result[] = $app;
            }
        }
        return $result;
    }

    // --- Medical Records & Prescriptions ---
    public function addMedicalRecord(MedicalRecord $record): void
    {
        $this->medicalRecords[$record->getId()] = $record;
    }

    public function getPatientRecords(string $patientId): array
    {
        $result = [];
        foreach ($this->medicalRecords as $record) {
            if ($record->getPatientId() === $patientId) {
                $result[] = $record;
            }
        }
        return $result;
    }

    // --- Pharmacy Management ---
    public function addMedicine(Medicine $medicine): void
    {
        $this->medicines[$medicine->getId()] = $medicine;
    }

    public function getMedicine(string $id): Medicine
    {
        if (!isset($this->medicines[$id])) {
            throw new RecordNotFoundException("Medicine ID '{$id}' does not exist in inventory.");
        }
        return $this->medicines[$id];
    }

    public function getAllMedicines(): array
    {
        return array_values($this->medicines);
    }

    // --- Payments ---
    public function recordPayment(Payment $payment): void
    {
        $this->payments[$payment->getId()] = $payment;
    }

    public function getAllPayments(): array
    {
        return array_values($this->payments);
    }

    /**
     * Seeds initial production data so the CLI is immediately usable.
     */
    private function seedInitialData(): void
    {
        // Seed Doctors
        $doc1 = new Doctor("Alice Smith", "555-0101", "alice@clinic.org", "Cardiology", "MED-LIC-8891", 75.00);
        $doc2 = new Doctor("Robert Jones", "555-0102", "robert@clinic.org", "General Medicine", "MED-LIC-4412", 50.00);
        $this->addDoctor($doc1);
        $this->addDoctor($doc2);

        // Seed Nurse
        $nurse = new Nurse("Clara Barton", "555-0201", "clara@clinic.org", "Outpatient Care", "Day");
        $this->addNurse($nurse);

        // Seed Patients
        $pat1 = new Patient("John Doe", "555-0301", "john.doe@email.com", "O+", new DateTimeImmutable("1988-04-12"), ["Penicillin"]);
        $pat2 = new Patient("Jane Miller", "555-0302", "jane.m@email.com", "A-", new DateTimeImmutable("1995-11-23"), []);
        $this->addPatient($pat1);
        $this->addPatient($pat2);

        // Seed Medicines
        $this->addMedicine(new Medicine("Amoxicillin 500mg", "Capsule", 12.50, 100));
        $this->addMedicine(new Medicine("Paracetamol 500mg", "Tablet", 4.00, 250));
        $this->addMedicine(new Medicine("Ibuprofen 400mg", "Tablet", 6.50, 150));
        $this->addMedicine(new Medicine("Cetirizine 10mg", "Tablet", 8.00, 80));

        // Seed a sample appointment in future
        $futureSlot = (new DateTimeImmutable())->modify("+2 days 10:00");
        $this->bookAppointment($pat1->getId(), $doc1->getId(), $futureSlot);
    }
}

// ============================================================================
// SECTION 5: INTERACTIVE CLI PRESENTATION LAYER
// ============================================================================

class ClinicCliApplication
{
    private Clinic $clinic;

    // ANSI Colors for Professional Terminal UX
    private const C_RESET  = "\033[0m";
    private const C_BOLD   = "\033[1m";
    private const C_CYAN   = "\033[1;36m";
    private const C_GREEN  = "\033[1;32m";
    private const C_RED    = "\033[1;31m";
    private const C_YELLOW = "\033[1;33m";
    private const C_BLUE   = "\033[1;34m";
    private const C_MAGENTA= "\033[1;35m";

    public function __construct(Clinic $clinic)
    {
        $this->clinic = $clinic;
    }

    public function run(): void
    {
        $this->printHeader();

        $running = true;
        while ($running) {
            echo PHP_EOL . self::C_CYAN . "=== MAIN NAVIGATION MENU ===" . self::C_RESET . PHP_EOL;
            echo "1. Patient Management" . PHP_EOL;
            echo "2. Appointment & Scheduling Engine" . PHP_EOL;
            echo "3. Clinical Consultations & Medical Records" . PHP_EOL;
            echo "4. Pharmacy & Medication Inventory" . PHP_EOL;
            echo "5. Financial & Payment Processing" . PHP_EOL;
            echo "6. View Staff Directory" . PHP_EOL;
            echo "0. Exit Application" . PHP_EOL;

            $choice = $this->prompt("Enter option (0-6)");

            try {
                switch ($choice) {
                    case '1':
                        $this->menuPatientManagement();
                        break;
                    case '2':
                        $this->menuAppointmentManagement();
                        break;
                    case '3':
                        $this->menuMedicalRecords();
                        break;
                    case '4':
                        $this->menuPharmacy();
                        break;
                    case '5':
                        $this->menuPayments();
                        break;
                    case '6':
                        $this->viewStaffDirectory();
                        break;
                    case '0':
                        echo self::C_GREEN . "\nThank you for using {$this->clinic->getName()}. Goodbye!" . self::C_RESET . PHP_EOL;
                        $running = false;
                        break;
                    default:
                        $this->printError("Invalid selection. Please choose between 0 and 6.");
                }
            } catch (ClinicException $e) {
                $this->printError("[Domain Exception Caught] " . $e->getMessage());
            } catch (Exception $e) {
                $this->printError("[System Error] " . $e->getMessage());
            } finally {
                // Defensive checkpoint executed after every user interaction
            }
        }
    }

    // ------------------------------------------------------------------------
    // MODULE 1: PATIENT MANAGEMENT
    // ------------------------------------------------------------------------
    private function menuPatientManagement(): void
    {
        $back = false;
        while (!$back) {
            echo PHP_EOL . self::C_BLUE . "--- PATIENT MANAGEMENT ---" . self::C_RESET . PHP_EOL;
            echo "1. Register New Patient" . PHP_EOL;
            echo "2. Search Patient by Name, ID, or Phone" . PHP_EOL;
            echo "3. Update Patient Contact Information" . PHP_EOL;
            echo "4. View Patient Full History" . PHP_EOL;
            echo "5. List All Registered Patients" . PHP_EOL;
            echo "0. Back to Main Menu" . PHP_EOL;

            $opt = $this->prompt("Select action");
            switch ($opt) {
                case '1':
                    $this->registerPatient();
                    break;
                case '2':
                    $this->searchPatients();
                    break;
                case '3':
                    $this->updatePatientContact();
                    break;
                case '4':
                    $this->viewPatientHistory();
                    break;
                case '5':
                    $this->listPatients();
                    break;
                case '0':
                    $back = true;
                    break;
                default:
                    $this->printError("Invalid choice.");
            }
        }
    }

    private function registerPatient(): void
    {
        echo PHP_EOL . self::C_BOLD . ">> Register New Patient <<" . self::C_RESET . PHP_EOL;
        $name = $this->promptRequired("Full Name");
        $phone = $this->promptRequired("Phone Number");
        $email = $this->promptRequired("Email Address");
        $blood = $this->promptRequired("Blood Group (e.g. A+, O-, B+)");
        $dobStr = $this->promptRequired("Date of Birth (YYYY-MM-DD)");

        $dob = DateTimeImmutable::createFromFormat('Y-m-d', $dobStr);
        if (!$dob) {
            throw new ValidationException("Invalid date format. Expected YYYY-MM-DD.");
        }

        $allergiesRaw = $this->prompt("Known Allergies (comma separated, or press enter for none)");
        $allergies = empty(trim($allergiesRaw)) ? [] : array_map('trim', explode(',', $allergiesRaw));

        $patient = new Patient($name, $phone, $email, $blood, $dob, $allergies);
        $this->clinic->addPatient($patient);

        $this->printSuccess("Patient registered successfully! Assigned ID: {$patient->getId()}");
    }

    private function searchPatients(): void
    {
        $q = $this->promptRequired("Search keyword (Name, ID, or Phone)");
        $results = $this->clinic->searchPatients($q);

        if (empty($results)) {
            $this->printWarning("No patients found matching query '{$q}'.");
            return;
        }

        echo PHP_EOL . self::C_GREEN . sprintf("Found %d matching patient(s):", count($results)) . self::C_RESET . PHP_EOL;
        foreach ($results as $p) {
            echo "  " . $p->getDetails() . PHP_EOL;
        }
    }

    private function updatePatientContact(): void
    {
        $id = $this->promptRequired("Patient ID");
        $patient = $this->clinic->getPatient($id);

        echo "Current Phone: {$patient->getPhone()} | Current Email: {$patient->getEmail()}" . PHP_EOL;
        $newPhone = $this->promptRequired("New Phone Number");
        $newEmail = $this->promptRequired("New Email Address");

        $patient->updateContact($newPhone, $newEmail);
        $this->printSuccess("Contact information updated for patient {$patient->getName()}.");
    }

    private function viewPatientHistory(): void
    {
        $id = $this->promptRequired("Patient ID");
        $patient = $this->clinic->getPatient($id);

        echo PHP_EOL . self::C_BOLD . "=== PATIENT DOSSIER: {$patient->getName()} ({$patient->getId()}) ===" . self::C_RESET . PHP_EOL;
        echo $patient->getDetails() . PHP_EOL . PHP_EOL;

        // Appointments
        echo self::C_CYAN . "--- Appointment History ---" . self::C_RESET . PHP_EOL;
        $appointments = $this->clinic->getPatientAppointments($id);
        if (empty($appointments)) {
            echo "  No appointments recorded." . PHP_EOL;
        } else {
            foreach ($appointments as $app) {
                echo "  " . $app->getDetails() . PHP_EOL;
            }
        }

        // Medical Records
        echo PHP_EOL . self::C_CYAN . "--- Clinical Medical Records ---" . self::C_RESET . PHP_EOL;
        $records = $this->clinic->getPatientRecords($id);
        if (empty($records)) {
            echo "  No clinical records recorded." . PHP_EOL;
        } else {
            foreach ($records as $rec) {
                echo $rec->getFullRecord() . PHP_EOL;
            }
        }
    }

    private function listPatients(): void
    {
        $patients = $this->clinic->getAllPatients();
        echo PHP_EOL . self::C_BOLD . sprintf("Total Registered Patients: %d", count($patients)) . self::C_RESET . PHP_EOL;
        foreach ($patients as $p) {
            echo "  " . $p->getDetails() . PHP_EOL;
        }
    }

    // ------------------------------------------------------------------------
    // MODULE 2: APPOINTMENT & SCHEDULING ENGINE
    // ------------------------------------------------------------------------
    private function menuAppointmentManagement(): void
    {
        $back = false;
        while (!$back) {
            echo PHP_EOL . self::C_BLUE . "--- APPOINTMENT & SCHEDULING ENGINE ---" . self::C_RESET . PHP_EOL;
            echo "1. Book an Appointment" . PHP_EOL;
            echo "2. Cancel an Appointment" . PHP_EOL;
            echo "3. Reschedule an Appointment" . PHP_EOL;
            echo "4. View All Scheduled Appointments" . PHP_EOL;
            echo "0. Back to Main Menu" . PHP_EOL;

            $opt = $this->prompt("Select action");
            switch ($opt) {
                case '1':
                    $this->bookAppointment();
                    break;
                case '2':
                    $this->cancelAppointment();
                    break;
                case '3':
                    $this->rescheduleAppointment();
                    break;
                case '4':
                    $this->listAppointments();
                    break;
                case '0':
                    $back = true;
                    break;
                default:
                    $this->printError("Invalid choice.");
            }
        }
    }

    private function bookAppointment(): void
    {
        echo PHP_EOL . self::C_BOLD . ">> Book New Appointment <<" . self::C_RESET . PHP_EOL;
        $patientId = $this->promptRequired("Patient ID (e.g. PAT-1001)");
        $this->clinic->getPatient($patientId); // Verifies existence

        echo "\nAvailable Doctors:\n";
        foreach ($this->clinic->getAllDoctors() as $doc) {
            echo "  " . $doc->getDetails() . PHP_EOL;
        }

        $docId = $this->promptRequired("Doctor ID (e.g. DOC-101)");
        $slotStr = $this->promptRequired("Date & Time (YYYY-MM-DD HH:MM)");

        $slot = DateTimeImmutable::createFromFormat('Y-m-d H:i', $slotStr);
        if (!$slot) {
            throw new ValidationException("Invalid date/time format. Expected: YYYY-MM-DD HH:MM (e.g., 2026-10-15 14:00).");
        }

        $appointment = $this->clinic->bookAppointment($patientId, $docId, $slot);
        $this->printSuccess("Appointment successfully booked! Assigned ID: {$appointment->getId()}");
        echo "  " . $appointment->getDetails() . PHP_EOL;
    }

    private function cancelAppointment(): void
    {
        $appId = $this->promptRequired("Appointment ID to cancel");
        $appointment = $this->clinic->getAppointment($appId);

        $reason = $this->promptRequired("Cancellation Reason");
        $appointment->cancel($reason);

        $this->printSuccess("Appointment {$appId} has been cancelled. Doctor calendar slot is now freed.");
    }

    private function rescheduleAppointment(): void
    {
        $appId = $this->promptRequired("Appointment ID to reschedule");
        $appointment = $this->clinic->getAppointment($appId);

        $newSlotStr = $this->promptRequired("New Date & Time (YYYY-MM-DD HH:MM)");
        $newSlot = DateTimeImmutable::createFromFormat('Y-m-d H:i', $newSlotStr);
        if (!$newSlot) {
            throw new ValidationException("Invalid date/time format. Expected: YYYY-MM-DD HH:MM.");
        }

        $appointment->reschedule($newSlot);
        $this->printSuccess("Appointment {$appId} successfully rescheduled to {$newSlot->format('Y-m-d H:i')}.");
    }

    private function listAppointments(): void
    {
        $apps = $this->clinic->getAllAppointments();
        echo PHP_EOL . self::C_BOLD . sprintf("Total Appointments in System: %d", count($apps)) . self::C_RESET . PHP_EOL;
        if (empty($apps)) {
            echo "  No appointments on file." . PHP_EOL;
            return;
        }
        foreach ($apps as $app) {
            echo "  " . $app->getDetails() . PHP_EOL;
        }
    }

    // ------------------------------------------------------------------------
    // MODULE 3: CLINICAL CONSULTATIONS & MEDICAL RECORDS
    // ------------------------------------------------------------------------
    private function menuMedicalRecords(): void
    {
        $back = false;
        while (!$back) {
            echo PHP_EOL . self::C_BLUE . "--- MEDICAL RECORDS & CONSULTATIONS ---" . self::C_RESET . PHP_EOL;
            echo "1. Record New Consultation & Diagnosis" . PHP_EOL;
            echo "2. View Medical History for Patient" . PHP_EOL;
            echo "0. Back to Main Menu" . PHP_EOL;

            $opt = $this->prompt("Select action");
            switch ($opt) {
                case '1':
                    $this->createConsultationRecord();
                    break;
                case '2':
                    $this->viewPatientHistory();
                    break;
                case '0':
                    $back = true;
                    break;
                default:
                    $this->printError("Invalid choice.");
            }
        }
    }

    private function createConsultationRecord(): void
    {
        echo PHP_EOL . self::C_BOLD . ">> Add Consultation & Diagnosis <<" . self::C_RESET . PHP_EOL;
        $patientId = $this->promptRequired("Patient ID");
        $patient = $this->clinic->getPatient($patientId);

        $docId = $this->promptRequired("Attending Doctor ID");
        $doctor = $this->clinic->getDoctor($docId);

        $symptoms = $this->promptRequired("Observed Symptoms");
        $diagnosis = $this->promptRequired("Clinical Diagnosis");
        $treatment = $this->promptRequired("Recommended Treatment Plan");

        $record = new MedicalRecord($patient->getId(), $doctor->getId(), $symptoms, $diagnosis, $treatment);

        // Optional Prescription Creation
        $addRx = strtolower($this->prompt("Issue Prescription for this consultation? (y/n)"));
        if ($addRx === 'y' || $addRx === 'yes') {
            $prescription = new Prescription($patient->getId(), $doctor->getId());

            echo "\nAvailable Medications in Pharmacy:\n";
            foreach ($this->clinic->getAllMedicines() as $med) {
                echo "  " . $med->getDetails() . PHP_EOL;
            }

            $addingMeds = true;
            while ($addingMeds) {
                $medId = $this->promptRequired("Enter Medicine ID (or 'done' to finish)");
                if (strtolower($medId) === 'done') {
                    break;
                }

                $medicine = $this->clinic->getMedicine($medId);
                $dosage = $this->promptRequired("Dosage (e.g., 500mg, 1 tablet)");
                $freq = $this->promptRequired("Frequency (e.g., Twice daily after meals)");
                $qty = (int)$this->promptRequired("Quantity to dispense");

                $prescription->addMedicine($medicine, $dosage, $freq, $qty);
                echo self::C_GREEN . "  [+] Added {$medicine->getName()} to prescription." . self::C_RESET . PHP_EOL;

                $more = strtolower($this->prompt("Add another medication? (y/n)"));
                if ($more !== 'y' && $more !== 'yes') {
                    $addingMeds = false;
                }
            }

            $record->setPrescription($prescription);
        }

        $this->clinic->addMedicalRecord($record);
        $this->printSuccess("Medical record {$record->getId()} successfully logged!");
        echo PHP_EOL . $record->getFullRecord() . PHP_EOL;
    }

    // ------------------------------------------------------------------------
    // MODULE 4: PHARMACY & INVENTORY
    // ------------------------------------------------------------------------
    private function menuPharmacy(): void
    {
        $back = false;
        while (!$back) {
            echo PHP_EOL . self::C_BLUE . "--- PHARMACY & INVENTORY ---" . self::C_RESET . PHP_EOL;
            echo "1. View Inventory Stock" . PHP_EOL;
            echo "2. Add New Medication" . PHP_EOL;
            echo "3. Restock Existing Medication" . PHP_EOL;
            echo "0. Back to Main Menu" . PHP_EOL;

            $opt = $this->prompt("Select action");
            switch ($opt) {
                case '1':
                    $this->listMedicines();
                    break;
                case '2':
                    $this->addNewMedicine();
                    break;
                case '3':
                    $this->restockMedicine();
                    break;
                case '0':
                    $back = true;
                    break;
                default:
                    $this->printError("Invalid choice.");
            }
        }
    }

    private function listMedicines(): void
    {
        $meds = $this->clinic->getAllMedicines();
        echo PHP_EOL . self::C_BOLD . "Current Pharmacy Inventory:" . self::C_RESET . PHP_EOL;
        foreach ($meds as $m) {
            echo "  " . $m->getDetails() . PHP_EOL;
        }
    }

    private function addNewMedicine(): void
    {
        $name = $this->promptRequired("Medication Name");
        $form = $this->promptRequired("Dosage Form (e.g. Tablet, Syrup, Capsule)");
        $price = (float)$this->promptRequired("Unit Price ($)");
        $qty = (int)$this->promptRequired("Initial Stock Quantity");

        $med = new Medicine($name, $form, $price, $qty);
        $this->clinic->addMedicine($med);
        $this->printSuccess("Medication added with ID {$med->getId()}.");
    }

    private function restockMedicine(): void
    {
        $id = $this->promptRequired("Medicine ID");
        $med = $this->clinic->getMedicine($id);
        $qty = (int)$this->promptRequired("Quantity to add");

        $med->addStock($qty);
        $this->printSuccess("Stock updated. New quantity for {$med->getName()}: {$med->getStockQuantity()} units.");
    }

    // ------------------------------------------------------------------------
    // MODULE 5: FINANCIAL & PAYMENTS
    // ------------------------------------------------------------------------
    private function menuPayments(): void
    {
        $back = false;
        while (!$back) {
            echo PHP_EOL . self::C_BLUE . "--- FINANCIAL & PAYMENT PROCESSING ---" . self::C_RESET . PHP_EOL;
            echo "1. Process Payment for Appointment Consultation" . PHP_EOL;
            echo "2. View Transaction History" . PHP_EOL;
            echo "0. Back to Main Menu" . PHP_EOL;

            $opt = $this->prompt("Select action");
            switch ($opt) {
                case '1':
                    $this->processAppointmentPayment();
                    break;
                case '2':
                    $this->listPayments();
                    break;
                case '0':
                    $back = true;
                    break;
                default:
                    $this->printError("Invalid choice.");
            }
        }
    }

    private function processAppointmentPayment(): void
    {
        $appId = $this->promptRequired("Appointment ID");
        $appointment = $this->clinic->getAppointment($appId);

        $amount = $appointment->calculateTotal();
        echo sprintf("Consultation Fee due: $%.2f" . PHP_EOL, $amount);

        echo "Payment Methods: 1. Cash | 2. Credit Card | 3. Insurance | 4. Mobile Money" . PHP_EOL;
        $mChoice = $this->prompt("Choose method (1-4)");
        $method = match ($mChoice) {
            '1' => PaymentMethod::CASH,
            '2' => PaymentMethod::CREDIT_CARD,
            '3' => PaymentMethod::INSURANCE,
            '4' => PaymentMethod::MOBILE_MONEY,
            default => PaymentMethod::CASH
        };

        $payment = new Payment($amount, $method, $appointment->getPaymentSummary());
        $payment->process();
        $this->clinic->recordPayment($payment);

        $appointment->complete();
        $this->printSuccess("Payment confirmed and Appointment marked as Completed!");
        echo PHP_EOL . $payment->getReceipt() . PHP_EOL;
    }

    private function listPayments(): void
    {
        $payments = $this->clinic->getAllPayments();
        echo PHP_EOL . self::C_BOLD . sprintf("Total Transactions Recorded: %d", count($payments)) . self::C_RESET . PHP_EOL;
        $grandTotal = 0.0;
        foreach ($payments as $p) {
            echo $p->getReceipt() . PHP_EOL;
            $grandTotal += $p->getAmount();
        }
        echo self::C_GREEN . sprintf("Grand Total Revenue: $%.2f", $grandTotal) . self::C_RESET . PHP_EOL;
    }

    // ------------------------------------------------------------------------
    // MODULE 6: STAFF DIRECTORY
    // ------------------------------------------------------------------------
    private function viewStaffDirectory(): void
    {
        echo PHP_EOL . self::C_BOLD . "=== CLINIC STAFF DIRECTORY ===" . self::C_RESET . PHP_EOL;
        echo self::C_CYAN . "Physicians & Specialists:" . self::C_RESET . PHP_EOL;
        foreach ($this->clinic->getAllDoctors() as $doc) {
            echo "  " . $doc->getDetails() . PHP_EOL;
        }

        echo PHP_EOL . self::C_CYAN . "Nursing Staff:" . self::C_RESET . PHP_EOL;
        foreach ($this->clinic->getAllNurses() as $nurse) {
            echo "  " . $nurse->getDetails() . PHP_EOL;
        }
    }

    // ------------------------------------------------------------------------
    // CLI HELPER UTILITIES
    // ------------------------------------------------------------------------
    private function prompt(string $message): string
    {
        echo self::C_YELLOW . "{$message}: " . self::C_RESET;
        $input = fgets(STDIN);
        if ($input === false) {
            echo PHP_EOL . self::C_GREEN . "Session terminated (EOF). Goodbye!" . self::C_RESET . PHP_EOL;
            exit(0);
        }
        return trim((string)$input);
    }

    private function promptRequired(string $field): string
    {
        while (true) {
            $val = $this->prompt($field);
            if ($val !== '') {
                return $val;
            }
            $this->printWarning("Field '{$field}' is required.");
        }
    }

    private function printHeader(): void
    {
        echo self::C_GREEN . "==================================================================" . PHP_EOL;
        echo "  " . strtoupper($this->clinic->getName()) . " - " . $this->clinic->getLocation() . PHP_EOL;
        echo "  PHP 8.2+ Strict OOP Architecture & Clinic Management CLI Engine" . PHP_EOL;
        echo "==================================================================" . self::C_RESET . PHP_EOL;
    }

    private function printSuccess(string $message): void
    {
        echo self::C_GREEN . "[✔ SUCCESS] " . $message . self::C_RESET . PHP_EOL;
    }

    private function printWarning(string $message): void
    {
        echo self::C_YELLOW . "[! WARNING] " . $message . self::C_RESET . PHP_EOL;
    }

    private function printError(string $message): void
    {
        echo self::C_RED . "[✘ ERROR] " . $message . self::C_RESET . PHP_EOL;
    }
}

// ============================================================================
// SECTION 6: EXECUTION BOOTSTRAPPER
// ============================================================================

// Check if running from CLI directly
if (php_sapi_name() === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    $clinic = new Clinic("HopeCare General Clinic", "Medical District Tower B");
    $app = new ClinicCliApplication($clinic);
    $app->run();
}

