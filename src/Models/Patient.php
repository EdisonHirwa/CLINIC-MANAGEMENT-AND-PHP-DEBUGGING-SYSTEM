<?php
declare(strict_types=1);

namespace ClinicManagement\Models;

use DateTimeImmutable;

/**
 * Concrete Model: Patient
 * Inherits from Person. Demonstrates inheritance and polymorphism.
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

    public function getBloodGroup(): string { return $this->bloodGroup; }
    public function getDateOfBirth(): DateTimeImmutable { return $this->dateOfBirth; }
    public function getAge(): int
    {
        return (new DateTimeImmutable())->diff($this->dateOfBirth)->y;
    }
    public function getAllergies(): array { return $this->allergies; }

    public function addAllergy(string $allergy): void
    {
        if (!in_array($allergy, $this->allergies, true)) {
            $this->allergies[] = $allergy;
        }
    }

    public function getRole(): string { return 'Patient'; }

    public function getDetails(): string
    {
        $allergyList = empty($this->allergies) ? 'None' : implode(', ', $this->allergies);
        return sprintf(
            "[%s] %s | Age: %d | Blood: %s | Phone: %s | Allergies: %s",
            $this->id, $this->name, $this->getAge(), $this->bloodGroup, $this->phone, $allergyList
        );
    }
}

