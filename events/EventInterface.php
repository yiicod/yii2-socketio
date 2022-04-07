<?php

namespace hyperia\socketio\events;

/**
 * Interface EventInterface
 * Event name and broadcast nsp
 *
 * @package hyperia\socketio\events
 */
interface EventInterface
{
    /**
     * List broadcast nsp array
     *
     * @return array
     */
    public static function broadcastOn(): array;

    /**
     * Get event name
     *
     * @return string
     */
    public static function name(): string;
}
