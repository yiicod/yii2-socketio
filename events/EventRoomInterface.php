<?php

namespace yiicod\socketio\events;

/**
 * Interface EventRoomInterface
 * Provide room support for event
 *
 * @package yiicod\socketio\events
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
