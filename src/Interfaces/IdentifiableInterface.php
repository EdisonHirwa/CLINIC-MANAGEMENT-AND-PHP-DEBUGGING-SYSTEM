<?php
declare(strict_types=1);

namespace ClinicManagement\Interfaces;

/**
 * Contract for entities identifiable by a domain-specific string ID.
 */
interface IdentifiableInterface
{
    public function getId(): string;
}

