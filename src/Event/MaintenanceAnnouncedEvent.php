<?php

namespace MathisVincent\MaintenanceBundle\Event;

/**
 * Dispatched once a maintenance has been announced.
 */
final class MaintenanceAnnouncedEvent
{
    public function __construct(
        public readonly int $startsAt,
    ) {
    }
}
