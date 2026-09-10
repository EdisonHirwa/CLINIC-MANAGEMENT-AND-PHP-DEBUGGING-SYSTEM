<?php
declare(strict_types=1);

namespace ClinicManagement\Models;

use ClinicManagement\Enums\NotificationChannel;
use ClinicManagement\Exceptions\ClinicException;
use ClinicManagement\Exceptions\PatientNotFoundException;
use ClinicManagement\Exceptions\InvalidAppointmentException;
use ClinicManagement\Exceptions\RecordNotFoundException;
use DateTimeImmutable;

/**
 * Orchestrator Model: Clinic
 * Central aggregation layer managing patients, staff, appointments, pharmacy, and billing.
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

    public function __destruct()
    {
        // Destruction hook: flush cached data or close file/db descriptors
    }

    public function getName(): string { return $this->name; }
    public function getLocation(): string { return $this->location; }

    // --- Patient Operations ---
    public function addPatient(Patient $patient): void
    {
        $this->patients[$patient->getId()] = $patient;
    }

    public function getPatient(string $id): Patient
    {
        if (!isset($this->patients[$id])) {
            throw new PatientNotFoundException("Patient '{$id}' not found.");
        }
        return $this->patients[$id];
    }

    public function getAllPatients(): array
    {
        return array_values($this->patients);
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

    // --- Staff Operations ---
    public function addDoctor(Doctor $doctor): void
    {
        $this->doctors[$doctor->getId()] = $doctor;
    }

    public function getDoctor(string $id): Doctor
    {
        if (!isset($this->doctors[$id])) {
            throw new ClinicException("Doctor '{$id}' not found.");
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

    // --- Scheduling Operations ---
    public function bookAppointment(string $patientId, string $doctorId, DateTimeImmutable $slot): Appointment
    {
        $patient = $this->getPatient($patientId);
        $doctor = $this->getDoctor($doctorId);

        $appointment = new Appointment($patient, $doctor, $slot);
        $this->appointments[$appointment->getId()] = $appointment;

        $notif = new Notification(
            $patient->getName(),
            NotificationChannel::SMS,
            "Appointment {$appointment->getId()} booked with Dr. {$doctor->getName()} on {$slot->format('Y-m-d H:i')}."
        );
        $patient->notify($notif);

        return $appointment;
    }

    public function getAppointment(string $id): Appointment
    {
        if (!isset($this->appointments[$id])) {
            throw new InvalidAppointmentException("Appointment '{$id}' does not exist.");
        }
        return $this->appointments[$id];
    }

    public function getAllAppointments(): array
    {
        return array_values($this->appointments);
    }

    public function getPatientAppointments(string $patientId): array
    {
        return array_values(array_filter(
            $this->appointments,
            fn(Appointment $a) => $a->getPatient()->getId() === $patientId
        ));
    }

    // --- Medical Records ---
    public function addMedicalRecord(MedicalRecord $record): void
    {
        $this->medicalRecords[$record->getId()] = $record;
    }

    public function getPatientRecords(string $patientId): array
    {
        return array_values(array_filter(
            $this->medicalRecords,
            fn(MedicalRecord $r) => $r->getPatientId() === $patientId
        ));
    }

    // --- Pharmacy & Inventory ---
    public function addMedicine(Medicine $medicine): void
    {
        $this->medicines[$medicine->getId()] = $medicine;
    }

    public function getMedicine(string $id): Medicine
    {
        if (!isset($this->medicines[$id])) {
            throw new RecordNotFoundException("Medicine '{$id}' not found.");
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
     * Seeds initial records for development and interactive testing.
     */
    private function seedInitialData(): void
    {
        $doc1 = new Doctor("Alice Smith", "555-0101", "alice@clinic.org", "Cardiology", "MED-LIC-8891", 75.00);
        $doc2 = new Doctor("Robert Jones", "555-0102", "robert@clinic.org", "General Medicine", "MED-LIC-4412", 50.00);
        $this->addDoctor($doc1);
        $this->addDoctor($doc2);

        $nurse = new Nurse("Clara Barton", "555-0201", "clara@clinic.org", "Outpatient Care", "Day");
        $this->addNurse($nurse);

        $pat1 = new Patient("John Doe", "555-0301", "john.doe@email.com", "O+", new DateTimeImmutable("1988-04-12"), ["Penicillin"]);
        $pat2 = new Patient("Jane Miller", "555-0302", "jane.m@email.com", "A-", new DateTimeImmutable("1995-11-23"), []);
        $this->addPatient($pat1);
        $this->addPatient($pat2);

        $this->addMedicine(new Medicine("Amoxicillin 500mg", "Capsule", 12.50, 100));
        $this->addMedicine(new Medicine("Paracetamol 500mg", "Tablet", 4.00, 250));
        $this->addMedicine(new Medicine("Ibuprofen 400mg", "Tablet", 6.50, 150));

        $futureSlot = (new DateTimeImmutable())->modify("+2 days 10:00");
        $this->bookAppointment($pat1->getId(), $doc1->getId(), $futureSlot);
    }
}

