<?php
declare(strict_types=1);

namespace ClinicManagement\Models;

use ClinicManagement\Interfaces\IdentifiableInterface;
use DateTimeImmutable;

/**
 * Concrete Model: MedicalRecord
 * Clinical notes, diagnoses, and linked prescriptions.
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

    public function getId(): string { return $this->id; }
    public function getPatientId(): string { return $this->patientId; }
    public function getDoctorId(): string { return $this->doctorId; }
    public function getRecordedAt(): DateTimeImmutable { return $this->recordedAt; }
    public function getSymptoms(): string { return $this->symptoms; }
    public function getDiagnosis(): string { return $this->diagnosis; }
    public function getTreatmentPlan(): string { return $this->treatmentPlan; }

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

