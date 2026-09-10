# Group 6: Clinic Management & PHP Debugging System

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net/)
[![Architecture](https://img.shields.io/badge/Architecture-PSR--4%20%7C%20Strict%20OOP-green.svg)](#file-architecture-breakdown)
[![License](https://img.shields.io/badge/License-MIT-purple.svg)](LICENSE)

An enterprise-grade, Object-Oriented Clinic Management System implemented in **strictly typed PHP 8.2+**, structured according to modern **PSR-4 autoloading** standards, and operated via a rich, resilient terminal CLI interface.

---

## Table of Contents
- [System Architecture](#system-architecture)
- [Directory Structure](#directory-structure)
- [Installation & Execution](#installation--execution)
- [File Architecture Breakdown](#file-architecture-breakdown)
- [Core OOP Principles Applied](#core-oop-principles-applied)
- [Domain & Service Flow](#domain--service-flow)
- [The 15-Bug Debugging Suite](#the-15-bug-debugging-suite)
- [Development & Quality Assurance](#development--quality-assurance)

---

## System Architecture

The application is architected with clear boundaries separating domain models, behavioral contracts (interfaces), state definitions (enums), exception hierarchies, and the presentation layer (interactive CLI).

```
                      +-------------------+
                      |   bin/clinic.php  | (CLI Runner Entry Point)
                      +---------+---------+
                                |
                                v
                   +------------+------------+
                   | Console\CliApplication  | (ANSI Terminal UI Controller)
                   +------------+------------+
                                |
                                v
                   +------------+------------+
                   |      Models\Clinic      | (Central Domain Orchestrator)
                   +------------+------------+
                                |
     +--------------+-----------+-----------+--------------+
     |              |                       |              |
     v              v                       v              v
+---------+   +------------+          +------------+   +---------+
| Patient |   |   Doctor   |          |Appointment |   | Payment |
+---------+   +------------+          +------------+   +---------+
     |              |                       |
     +-------+------+                       v
             |                        +------------+
             v                        |MedicalRec. |
      +--------------+                +-----+------+
      |    Person    | (Abstract)           |
      +--------------+                      v
                                      +------------+
                                      |Prescription|
                                      +-----+------+
                                            |
                                            v
                                      +------------+
                                      |  Medicine  |
                                      +------------+
```

---

## Directory Structure

```text
CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/
│
├── bin/
│   └── clinic.php                           # Application executable entry point
│
├── src/
│   ├── Console/
│   │   └── CliApplication.php              # Interactive CLI engine & ANSI UI
│   │
│   ├── Enums/
│   │   ├── AppointmentStatus.php           # Statuses: Scheduled, Completed, etc.
│   │   ├── NotificationChannel.php         # Delivery channels: SMS, Email, Alert
│   │   ├── PaymentMethod.php               # Methods: Cash, Card, Insurance, Mobile
│   │   └── PaymentStatus.php               # States: Pending, Paid, Refunded, Failed
│   │
│   ├── Exceptions/
│   │   ├── ClinicException.php             # Root domain exception
│   │   ├── DoctorUnavailableException.php  # Calendar & double-booking conflict
│   │   ├── InsufficientStockException.php  # Pharmacy inventory underflow
│   │   ├── InvalidAppointmentException.php # Past date or state mutation failure
│   │   ├── PatientNotFoundException.php    # Missing patient lookup failure
│   │   ├── PaymentFailedException.php      # Transaction rejection
│   │   ├── RecordNotFoundException.php     # Medical or inventory record miss
│   │   └── ValidationException.php         # Input constraint or type mismatch
│   │
│   ├── Interfaces/
│   │   ├── BookableInterface.php           # Schedule slot query & lock contract
│   │   ├── IdentifiableInterface.php       # Domain-specific ID getter contract
│   │   ├── NotifiableInterface.php         # Message dispatch recipient contract
│   │   └── PayableInterface.php            # Financial calculation & billing contract
│   │
│   └── Models/
│       ├── Appointment.php                 # Booking lifecycle & doctor assignment
│       ├── Clinic.php                      # Aggregate root & repository orchestrator
│       ├── Doctor.php                      # Clinical physician entity (Bookable)
│       ├── MedicalRecord.php               # Diagnosis notes, symptoms & attached Rx
│       ├── Medicine.php                    # Pharmacy stock & inventory control
│       ├── Notification.php                # Communication message payload
│       ├── Nurse.php                       # Nursing staff entity (Outpatient)
│       ├── Patient.php                     # Patient demographics & medical history
│       ├── Payment.php                     # Financial transaction & receipt generator
│       ├── Person.php                      # Abstract base for demographic entities
│       └── Prescription.php                # Prescription items & cost aggregation
│
├── composer.json                            # PSR-4 Autoloading configuration & scripts
└── README.md                                # Comprehensive project documentation
```

---

## Installation & Execution

### Prerequisites
- **PHP 8.2 or newer** (`php -v`)
- **Composer 2.x** (`composer --version`)

### Step 1: Clone and Enter the Repository
```bash
git clone https://github.com/EdisonHirwa/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM.git
cd CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM
```

### Step 2: Generate PSR-4 Autoload Mapping
Run Composer to generate the optimized class map and dependency graph:
```bash
composer dump-autoload -o
```
*(Note: If Composer is not yet installed on your workstation, `bin/clinic.php` includes an automatic built-in PSR-4 fallback autoloader, allowing the system to run out of the box).*

### Step 3: Launch the CLI Application
You can run the system using any of the following commands:
```bash
# Direct execution via PHP
php bin/clinic.php

# Direct execution via binary permissions
./bin/clinic.php

# Or via the Composer script shortcut
composer run cli
```

---

## File Architecture Breakdown

Every single class, interface, enum, and script in this repository has an explicit, single-responsibility role under strict OOP design:

### 1. `bin/` (Executable Entry Point)
- [`bin/clinic.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/bin/clinic.php): Executable CLI entrypoint. Detects environment SAPI, loads Composer's `vendor/autoload.php` (with an automated PSR-4 SPL fallback), instantiates the root `Clinic` model with seed data, and delegates execution control to `CliApplication::run()`.

### 2. `src/Console/` (Presentation Layer)
- [`src/Console/CliApplication.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Console/CliApplication.php): Handles standard input/output streams (`STDIN`/`STDOUT`), renders ANSI-colored terminal menus, reads and sanitizes user input, manages multi-tier interactive workflows (Patient Registration, Doctor Appointment Scheduling, Consultation Records, Pharmacy Stocking, Billing), and catches all domain exceptions gracefully without script termination.

### 3. `src/Enums/` (Type-Safe State Definitions)
- [`src/Enums/AppointmentStatus.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Enums/AppointmentStatus.php): Strongly typed backed enum defining valid lifecycle states: `Scheduled`, `Completed`, `Cancelled`, `Rescheduled`.
- [`src/Enums/PaymentMethod.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Enums/PaymentMethod.php): Backed enum for supported payment channels: `Cash`, `Credit Card`, `Insurance`, `Mobile Money`.
- [`src/Enums/PaymentStatus.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Enums/PaymentStatus.php): Backed enum representing financial transaction clearance states: `Pending`, `Paid`, `Refunded`, `Failed`.
- [`src/Enums/NotificationChannel.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Enums/NotificationChannel.php): Backed enum specifying notification dispatch protocols: `SMS`, `Email`, `System Alert`.

### 4. `src/Interfaces/` (Behavioral Contracts)
- [`src/Interfaces/IdentifiableInterface.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Interfaces/IdentifiableInterface.php): Enforces uniform entity identification via `getId(): string`.
- [`src/Interfaces/BookableInterface.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Interfaces/BookableInterface.php): Contract governing calendar slot queries (`isAvailable()`), booking locks (`bookSlot()`), and slot release (`releaseSlot()`). Implemented by `Doctor`.
- [`src/Interfaces/PayableInterface.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Interfaces/PayableInterface.php): Contract requiring entities to compute billing amounts (`calculateTotal(): float`) and provide line-item summaries (`getPaymentSummary(): string`). Implemented by `Appointment` and `Prescription`.
- [`src/Interfaces/NotifiableInterface.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Interfaces/NotifiableInterface.php): Contract ensuring entities can receive system and patient notifications (`notify(Notification $n): bool`). Implemented by `Person`.

### 5. `src/Exceptions/` (Domain-Specific Fault Interception)
- [`src/Exceptions/ClinicException.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Exceptions/ClinicException.php): Base domain exception extending PHP's native `\Exception`. Enables centralized domain catch blocks.
- [`src/Exceptions/PatientNotFoundException.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Exceptions/PatientNotFoundException.php): Thrown when looking up an unregistered or missing patient ID (`PAT-XXXX`).
- [`src/Exceptions/DoctorUnavailableException.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Exceptions/DoctorUnavailableException.php): Thrown when attempting to book a doctor at a conflicting time slot.
- [`src/Exceptions/InvalidAppointmentException.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Exceptions/InvalidAppointmentException.php): Thrown upon invalid transitions (e.g. rescheduling past dates, completing cancelled appointments).
- [`src/Exceptions/ValidationException.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Exceptions/ValidationException.php): Thrown for format mismatches (dates, empty required fields, negative values).
- [`src/Exceptions/PaymentFailedException.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Exceptions/PaymentFailedException.php): Thrown when payment processing or payment gateway validation fails.
- [`src/Exceptions/RecordNotFoundException.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Exceptions/RecordNotFoundException.php): Thrown when querying nonexistent medical or medication inventory items.
- [`src/Exceptions/InsufficientStockException.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Exceptions/InsufficientStockException.php): Thrown by `Medicine::deductStock()` when dispensing exceeds on-hand stock.

### 6. `src/Models/` (Domain Entity Layer)
- [`src/Models/Person.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Models/Person.php): **Abstract base class**. Encapsulates shared demographic properties (`$id`, `$name`, `$phone`, `$email`, `$createdAt`), implements `IdentifiableInterface` and `NotifiableInterface`, and declares abstract methods `getRole()` and `getDetails()`.
- [`src/Models/Patient.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Models/Patient.php): Specializes `Person`. Maintains blood group, date of birth, age computation, known allergies, and auto-generates IDs formatted as `PAT-XXXX`.
- [`src/Models/Doctor.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Models/Doctor.php): Specializes `Person` and implements `BookableInterface`. Maintains specialization, license number, consultation fee, and a schedule registry preventing double-bookings. Auto-generates `DOC-XXX`.
- [`src/Models/Nurse.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Models/Nurse.php): Specializes `Person`. Manages departmental placement and shift schedules (Day/Night). Auto-generates `NUR-XXX`.
- [`src/Models/Medicine.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Models/Medicine.php): Implements `IdentifiableInterface`. Tracks pharmaceutical units, unit prices, dosage forms, and stock deductions. Auto-generates `MED-XXX`.
- [`src/Models/Prescription.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Models/Prescription.php): Implements `IdentifiableInterface` and `PayableInterface`. Aggregates prescribed medicines, dosages, frequencies, and computes total medication charges. Auto-generates `RX-XXXX`.
- [`src/Models/MedicalRecord.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Models/MedicalRecord.php): Implements `IdentifiableInterface`. Encapsulates clinical symptoms, diagnosis, treatment plan, and optional attached `Prescription`. Auto-generates `REC-XXXX`.
- [`src/Models/Appointment.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Models/Appointment.php): Implements `IdentifiableInterface` and `PayableInterface`. Couples a `Patient` and `Doctor` to a slot, guards against past bookings or doctor unavailability, manages completion/cancellation, and bills consultation fees. Auto-generates `APP-XXXX`.
- [`src/Models/Payment.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Models/Payment.php): Implements `IdentifiableInterface`. Generates unique transaction reference codes (`TXN-XXXX`), records payment methods, and prints formatted receipts. Auto-generates `PAY-XXXX`.
- [`src/Models/Notification.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Models/Notification.php): Implements `IdentifiableInterface`. Formats and delivers clinical communications. Auto-generates `NOTIF-XXXX`.
- [`src/Models/Clinic.php`](file:///home/edison/Documents/Year3/OOP%20using%20PHP/CLINIC-MANAGEMENT-AND-PHP-DEBUGGING-SYSTEM/src/Models/Clinic.php): Central domain aggregate root. Serves as in-memory repository orchestrator for patients, physicians, nurses, appointments, pharmacy stock, medical charts, and revenue ledgers.

---

## Core OOP Principles Applied

1. **Abstraction**
   - The abstract class `Person` hides underlying demographic complexity and provides universal behavior while mandating that derived classes implement contract methods `getRole(): string` and `getDetails(): string`.
2. **Inheritance**
   - `Patient`, `Doctor`, and `Nurse` inherit common identity attributes (`$id`, `$name`, `$phone`, `$email`, `$createdAt`) and methods (`updateContact()`, `notify()`) from `Person`, eliminating duplicate code.
3. **Polymorphism**
   - Calling `$person->getDetails()` returns distinct formatted dossiers depending on whether the object is an instance of `Patient`, `Doctor`, or `Nurse`.
   - Calling `$payable->calculateTotal()` evaluates either doctor consultation charges (`Appointment`) or medication item line totals (`Prescription`) via a shared `PayableInterface` contract.
4. **Encapsulation & Defensive Programming**
   - All properties are declared `private` or `protected`. State changes occur strictly through validated mutators (e.g. `Medicine::deductStock()` validates inventory sufficiency before decrementing).
5. **Static Counters & Auto-Generation**
   - Encapsulated static properties (e.g., `self::$counter`) maintain autonomous state to generate prefixed, non-colliding IDs across application lifecycles.

---

## The 15-Bug Debugging Suite

As part of the Group 6 debugging curriculum, the system documents 15 structured programming bugs diagnosed, analyzed, and resolved during development:

| No. | Problem | Cause | Effect | Detection Method | Solution |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **01** | **Incorrect property visibility (`private` vs `protected`)** | Declared `$id` and `$name` as `private` inside abstract `Person` instead of `protected`. | Subclasses like `Patient` could not access identity attributes during dossier rendering. | PHP Fatal Error during compilation (`Cannot access private property`). | Refactored inheritable attributes from `private` to `protected` in `Person`. |
| **02** | **Null value dereferencing on attached Prescription** | Called `$record->getPrescription()->getDetails()` without checking if an Rx was issued. | Fatal `TypeError`: *Cannot call method `getDetails()` on null* when viewing advice-only records. | CLI crashed upon viewing consultations without prescriptions. | Enforced null-safe evaluation: `if ($this->prescription !== null) { ... }`. |
| **03** | **Invalid object relationship binding** | `Appointment` accepted primitive strings for `$patientId` and `$doctorId` instead of typed objects. | Unable to invoke `isAvailable()` or `notify()` polymorphically without redundant repo searches. | Architectural review against Domain Driven Design (DDD). | Required concrete instances: `Patient $patient` and `Doctor $doctor` in constructor. |
| **04** | **Incorrect static method call syntax** | Invoked `$this->generateId()` instead of `self::generateId()` in subclasses. | Fatal error: *Call to undefined method via `$this`* during object construction. | Unit testing model instantiation in CLI. | Standardized on `self::generateId()` bound to private class counters. |
| **05** | **Wrong argument type under strict types** | Passed string timestamps directly into `Appointment::__construct()`. | Fatal `TypeError`: *Argument #3 must be of type DateTimeImmutable, string given*. | Automated test execution with PHPUnit. | Parsed CLI input with `DateTimeImmutable::createFromFormat('Y-m-d H:i', $input)`. |
| **06** | **Double-booking slot overlap logic flaw** | Doctor availability dictionary was keyed by raw UNIX timestamps with seconds. | Appointments scheduled 10 seconds apart on the same hour were treated as non-conflicting. | Concurrency simulation for Dr. Alice Smith. | Standardized calendar slot keys to minute-level resolution (`$slot->format('Y-m-d H:i')`). |
| **07** | **Floating-point precision in financial aggregation** | Used native binary floating point arithmetic without currency rounding. | Fractional cent artifacts (`$29.000000000004`) appeared on customer receipts. | Visual receipt inspection following multi-item pharmacy billing. | Standardized output formatting using `sprintf('$%.2f', $amount)`. |
| **08** | **Unhandled domain exception terminating CLI loop** | Entering an unregistered patient ID crashed the script with an uncaught `PatientNotFoundException`. | Entire CLI terminated abruptly, losing all uncommitted in-memory session data. | Input validation boundary testing with non-existent IDs. | Wrapped the CLI event dispatcher in a top-level `try-catch(ClinicException)` block. |
| **09** | **Destructor suppression due to cyclic references** | `Appointment` held circular references back into `Clinic` collections. | Destructors (`__destruct()`) were never triggered on exit, failing cleanup hooks. | Process memory profiling and missing shutdown log entries. | Decoupled cyclic bindings so child entities maintain only forward references. |
| **10** | **Interface contract return type mismatch** | `PayableInterface` specified `: float`, but `Prescription` returned an untyped number. | Fatal `FatalCompileError`: *Return type must be compatible with PayableInterface*. | Running `php -l`. | Added explicit `: float` return types to all implementations of the interface. |
| **11** | **Array offset access on undefined collection key** | Attempted direct key indexing `$this->patients[$id]` without verifying existence. | PHP 8.2 Warning: *Undefined array key* followed by unexpected null values. | Entering arbitrary patient IDs in the Patient Details menu. | Enforced defensive programming via `isset()` throwing `PatientNotFoundException`. |
| **12** | **Incompatible polymorphic signature override** | `Nurse::getDetails()` had an extra optional parameter `$includeShift = true`. | Violated Liskov Substitution Principle (LSP) during polymorphic iterations. | Static analysis with PHPStan at Level 8. | Normalized method signature `public function getDetails(): string` across all models. |
| **13** | **State mutation side-effect on cancelled appointments** | Rescheduling or completing an appointment did not check whether status was `CANCELLED`. | Cancelled appointments could be billed and marked as completed. | Business workflow QA: cancelling an appointment and then running payment. | Added state transition guards in `complete()`, `reschedule()`, and `cancel()`. |
| **14** | **Negative pharmacy inventory underflow** | Dispensing medicines did not verify requested quantity was within available stock. | Inventory counts dropped into negative values (`Stock: -14`), corrupting records. | Ordering 500 units of Amoxicillin when only 100 were in stock. | Implemented `InsufficientStockException` in `Medicine::deductStock()`. |
| **15** | **Silent type coercion without strict types declaration** | Omitting `declare(strict_types=1);` allowed strings from `fgets(STDIN)` to coerce into ints. | Faulty numeric strings (e.g. `'50abc'`) were partially converted to `50` without notice. | Static code review and boundary input analysis. | Placed `declare(strict_types=1);` as the first line in every PHP source file. |

---

## Development & Quality Assurance

### Static Analysis
Run PHPStan to verify type safety and PSR-4 compliance:
```bash
composer run analyse
```

### Automated Tests
Run unit and regression test suites with PHPUnit:
```bash
composer run test
```

---

## Authors & Acknowledgements
- **Group 6 Architecture Team**
- **Course**: Object-Oriented Programming (OOP) using PHP 8.2+
- **Project**: Clinic Management and PHP Debugging System

