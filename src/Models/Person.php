<?php
declare(strict_types=1);

namespace ClinicManagement\Models;

use ClinicManagement\Interfaces\IdentifiableInterface;
use ClinicManagement\Interfaces\NotifiableInterface;
use DateTimeImmutable;

/**
 * Abstract Base Class: Person
 * Demonstrates Abstraction and Encapsulation for demographic identities.
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
     * Destructor: Resource cleanup and audit hook.
     */
    public function __destruct()
    {
        // Cleanup resources or write session termination logs
    }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getPhone(): string { return $this->phone; }
    public function getEmail(): string { return $this->email; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }

    public function updateContact(string $phone, string $email): void
    {
        $this->phone = $phone;
        $this->email = $email;
    }

    public function notify(Notification $notification): bool
    {
        return $notification->send();
    }

    abstract public function getRole(): string;
    abstract public function getDetails(): string;
}

