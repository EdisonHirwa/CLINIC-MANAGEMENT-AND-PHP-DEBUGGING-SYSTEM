<?php
declare(strict_types=1);

namespace ClinicManagement\Console;

use ClinicManagement\Models\Clinic;
use ClinicManagement\Models\Patient;
use ClinicManagement\Models\Doctor;
use ClinicManagement\Models\Nurse;
use ClinicManagement\Models\Medicine;
use ClinicManagement\Models\Prescription;
use ClinicManagement\Models\MedicalRecord;
use ClinicManagement\Models\Appointment;
use ClinicManagement\Models\Payment;
use ClinicManagement\Enums\PaymentMethod;
use ClinicManagement\Exceptions\ClinicException;
use ClinicManagement\Exceptions\ValidationException;
use DateTimeImmutable;
use Exception;

/**
 * Presentation Layer: CliApplication
 * Manages ANSI terminal UI, user workflows, and error recovery.
 */
class CliApplication
{
    private Clinic $clinic;

    private const C_RESET  = "\033[0m";
    private const C_BOLD   = "\033[1m";
    private const C_CYAN   = "\033[1;36m";
    private const C_GREEN  = "\033[1;32m";
    private const C_RED    = "\033[1;31m";
    private const C_YELLOW = "\033[1;33m";
    private const C_BLUE   = "\033[1;34m";

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
                    case '1': $this->menuPatientManagement(); break;
                    case '2': $this->menuAppointmentManagement(); break;
                    case '3': $this->menuMedicalRecords(); break;
                    case '4': $this->menuPharmacy(); break;
                    case '5': $this->menuPayments(); break;
                    case '6': $this->viewStaffDirectory(); break;
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
                // Defensive execution block
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
                case '1': $this->registerPatient(); break;
                case '2': $this->searchPatients(); break;
                case '3': $this->updatePatientContact(); break;
                case '4': $this->viewPatientHistory(); break;
                case '5': $this->listPatients(); break;
                case '0': $back = true; break;
                default: $this->printError("Invalid choice.");
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
        if (!$dob) throw new ValidationException("Invalid date format. Expected YYYY-MM-DD.");

        $allergiesRaw = $this->prompt("Known Allergies (comma-separated, or Enter for none)");
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
        foreach ($results as $p) echo "  " . $p->getDetails() . PHP_EOL;
    }

    private function updatePatientContact(): void
    {
        $id = $this->promptRequired("Patient ID");
        $patient = $this->clinic->getPatient($id);

        echo "Current Phone: {$patient->getPhone()} | Current Email: {$patient->getEmail()}" . PHP_EOL;
        $newPhone = $this->promptRequired("New Phone Number");
        $newEmail = $this->promptRequired("New Email Address");

        $patient->updateContact($newPhone, $newEmail);
        $this->printSuccess("Contact updated for patient {$patient->getName()}.");
    }

    private function viewPatientHistory(): void
    {
        $id = $this->promptRequired("Patient ID");
        $patient = $this->clinic->getPatient($id);

        echo PHP_EOL . self::C_BOLD . "=== PATIENT DOSSIER: {$patient->getName()} ({$patient->getId()}) ===" . self::C_RESET . PHP_EOL;
        echo $patient->getDetails() . PHP_EOL . PHP_EOL;

        echo self::C_CYAN . "--- Appointment History ---" . self::C_RESET . PHP_EOL;
        $appointments = $this->clinic->getPatientAppointments($id);
        if (empty($appointments)) {
            echo "  No appointments recorded." . PHP_EOL;
        } else {
            foreach ($appointments as $app) echo "  " . $app->getDetails() . PHP_EOL;
        }

        echo PHP_EOL . self::C_CYAN . "--- Clinical Medical Records ---" . self::C_RESET . PHP_EOL;
        $records = $this->clinic->getPatientRecords($id);
        if (empty($records)) {
            echo "  No clinical records recorded." . PHP_EOL;
        } else {
            foreach ($records as $rec) echo $rec->getFullRecord() . PHP_EOL;
        }
    }

    private function listPatients(): void
    {
        $patients = $this->clinic->getAllPatients();
        echo PHP_EOL . self::C_BOLD . sprintf("Total Registered Patients: %d", count($patients)) . self::C_RESET . PHP_EOL;
        foreach ($patients as $p) echo "  " . $p->getDetails() . PHP_EOL;
    }

    // ------------------------------------------------------------------------
    // MODULE 2: APPOINTMENT SCHEDULING
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
                case '1': $this->bookAppointment(); break;
                case '2': $this->cancelAppointment(); break;
                case '3': $this->rescheduleAppointment(); break;
                case '4': $this->listAppointments(); break;
                case '0': $back = true; break;
                default: $this->printError("Invalid choice.");
            }
        }
    }

    private function bookAppointment(): void
    {
        echo PHP_EOL . self::C_BOLD . ">> Book New Appointment <<" . self::C_RESET . PHP_EOL;
        $patientId = $this->promptRequired("Patient ID (e.g. PAT-1001)");
        $this->clinic->getPatient($patientId);

        echo "\nAvailable Doctors:\n";
        foreach ($this->clinic->getAllDoctors() as $doc) echo "  " . $doc->getDetails() . PHP_EOL;

        $docId = $this->promptRequired("Doctor ID (e.g. DOC-101)");
        $slotStr = $this->promptRequired("Date & Time (YYYY-MM-DD HH:MM)");

        $slot = DateTimeImmutable::createFromFormat('Y-m-d H:i', $slotStr);
        if (!$slot) throw new ValidationException("Invalid format. Expected: YYYY-MM-DD HH:MM.");

        $appointment = $this->clinic->bookAppointment($patientId, $docId, $slot);
        $this->printSuccess("Appointment successfully booked! Assigned ID: {$appointment->getId()}");
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
        if (!$newSlot) throw new ValidationException("Invalid format. Expected: YYYY-MM-DD HH:MM.");

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
        foreach ($apps as $app) echo "  " . $app->getDetails() . PHP_EOL;
    }

    // ------------------------------------------------------------------------
    // MODULE 3: CONSULTATIONS & RECORDS
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
                case '1': $this->createConsultationRecord(); break;
                case '2': $this->viewPatientHistory(); break;
                case '0': $back = true; break;
                default: $this->printError("Invalid choice.");
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
        $treatment = $this->promptRequired("Treatment Plan");

        $record = new MedicalRecord($patient->getId(), $doctor->getId(), $symptoms, $diagnosis, $treatment);

        $addRx = strtolower($this->prompt("Issue Prescription for this consultation? (y/n)"));
        if ($addRx === 'y' || $addRx === 'yes') {
            $prescription = new Prescription($patient->getId(), $doctor->getId());

            echo "\nAvailable Medications in Pharmacy:\n";
            foreach ($this->clinic->getAllMedicines() as $med) echo "  " . $med->getDetails() . PHP_EOL;

            $addingMeds = true;
            while ($addingMeds) {
                $medId = $this->promptRequired("Enter Medicine ID (or 'done' to finish)");
                if (strtolower($medId) === 'done') break;

                $medicine = $this->clinic->getMedicine($medId);
                $dosage = $this->promptRequired("Dosage (e.g., 500mg, 1 tablet)");
                $freq = $this->promptRequired("Frequency (e.g., Twice daily)");
                $qty = (int)$this->promptRequired("Quantity to dispense");

                $prescription->addMedicine($medicine, $dosage, $freq, $qty);
                echo self::C_GREEN . "  [+] Added {$medicine->getName()} to prescription." . self::C_RESET . PHP_EOL;

                $more = strtolower($this->prompt("Add another medication? (y/n)"));
                if ($more !== 'y' && $more !== 'yes') $addingMeds = false;
            }

            $record->setPrescription($prescription);
        }

        $this->clinic->addMedicalRecord($record);
        $this->printSuccess("Medical record {$record->getId()} successfully logged!");
        echo PHP_EOL . $record->getFullRecord() . PHP_EOL;
    }

    // ------------------------------------------------------------------------
    // MODULE 4: PHARMACY
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
                case '1': $this->listMedicines(); break;
                case '2': $this->addNewMedicine(); break;
                case '3': $this->restockMedicine(); break;
                case '0': $back = true; break;
                default: $this->printError("Invalid choice.");
            }
        }
    }

    private function listMedicines(): void
    {
        $meds = $this->clinic->getAllMedicines();
        echo PHP_EOL . self::C_BOLD . "Current Pharmacy Inventory:" . self::C_RESET . PHP_EOL;
        foreach ($meds as $m) echo "  " . $m->getDetails() . PHP_EOL;
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
    // MODULE 5: BILLING & PAYMENTS
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
                case '1': $this->processAppointmentPayment(); break;
                case '2': $this->listPayments(); break;
                case '0': $back = true; break;
                default: $this->printError("Invalid choice.");
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

    private function viewStaffDirectory(): void
    {
        echo PHP_EOL . self::C_BOLD . "=== CLINIC STAFF DIRECTORY ===" . self::C_RESET . PHP_EOL;
        echo self::C_CYAN . "Physicians & Specialists:" . self::C_RESET . PHP_EOL;
        foreach ($this->clinic->getAllDoctors() as $doc) echo "  " . $doc->getDetails() . PHP_EOL;

        echo PHP_EOL . self::C_CYAN . "Nursing Staff:" . self::C_RESET . PHP_EOL;
        foreach ($this->clinic->getAllNurses() as $nurse) echo "  " . $nurse->getDetails() . PHP_EOL;
    }

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
            if ($val !== '') return $val;
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
