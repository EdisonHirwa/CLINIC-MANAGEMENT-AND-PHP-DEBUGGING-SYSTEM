<?php
declare(strict_types=1);

namespace ClinicManagement\Models;

use ClinicManagement\Interfaces\IdentifiableInterface;
use ClinicManagement\Enums\NotificationChannel;
use DateTimeImmutable;

/**
 * Concrete Model: Notification
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

    public function getId(): string { return $this->id; }
    public function getRecipient(): string { return $this->recipient; }
    public function getChannel(): NotificationChannel { return $this->channel; }
    public function getMessage(): string { return $this->message; }
    public function getSentAt(): DateTimeImmutable { return $this->sentAt; }

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

