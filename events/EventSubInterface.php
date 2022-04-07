<?php

namespace hyperia\socketio\events;

/**
 * Interface EventSubInterface
 * Event subscriber interface
 *
 * @package hyperia\socketio\events
 */
interface EventSubInterface
{
    /**
     * Handle published event data
     *
     * @param array $data
     *
     * @return mixed
     */
    public function handle(array $data);
}
