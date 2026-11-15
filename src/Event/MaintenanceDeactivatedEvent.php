<?php

namespace MathisVincent\MaintenanceBundle\Event;

/**
 * Dispatched once an active maintenance mode has been deactivated.
 */
final class MaintenanceDeactivatedEvent
{
    public function __construct(
        public readonly ?string $level,
    ) {
    }
}
