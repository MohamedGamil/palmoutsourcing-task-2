<?php

declare(strict_types=1);

namespace Domain\Product\Event;

use Domain\Product\Entity\Product;
use DateTimeInterface;
use DateTime;

/**
 * Base Domain Event
 * 
 * All domain events extend from this base class.
 * Events represent significant business occurrences in the domain.
 */
abstract class DomainEvent
{
    private DateTimeInterface $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new DateTime();
    }

    public function occurredAt(): DateTimeInterface
    {
        return $this->occurredAt;
    }

    abstract public function getEventName(): string;

    abstract public function toArray(): array;
}
