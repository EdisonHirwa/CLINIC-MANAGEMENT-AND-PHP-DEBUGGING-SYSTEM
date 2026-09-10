<?php
declare(strict_types=1);

namespace ClinicManagement\Models;

/**
 * Concrete Model: Nurse
 * Inherits from Person. Manages outpatient services.
 */
class Nurse extends Person
{
    private static int $counter = 100;
    private string $department;
    private string $shift;

    public function __construct(string $name, string $phone, string $email, string $department, string $shift)
    {
        $id = self::generateId();
        parent::__construct($id, $name, $phone, $email);
        $this->department = $department;
        $this->shift = $shift;
    }

    public static function generateId(): string
    {
        return 'NUR-' . (++self::$counter);
    }

    public function getDepartment(): string { return $this->department; }
    public function getShift(): string { return $this->shift; }
    public function getRole(): string { return 'Nurse'; }

    public function getDetails(): string
    {
        return sprintf(
            "[%s] Nurse %s | Dept: %s | Shift: %s | Contact: %s",
            $this->id, $this->name, $this->department, $this->shift, $this->phone
        );
    }
}

