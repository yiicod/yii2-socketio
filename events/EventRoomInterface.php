<?php

namespace hyperia\socketio\events;

/**
 * Interface EventRoomInterface
 * Provide room support for event
 *
 * @package hyperia\socketio\events
 */
interface EventRoomInterface
{
    /**
     * Get room name
     *
     * @return string
     */
    public function room(): string;
}
