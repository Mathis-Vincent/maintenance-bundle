<?php

namespace MathisVincent\MaintenanceBundle\Event;

/**
 * Dispatched once the maintenance mode has been activated.
 */
final class MaintenanceActivatedEvent
{
    public function __construct(
        public readonly string $level,
        public readonly ?int $endsAt = null,
    ) {
    }
}
