<?php

namespace yiicod\socketio\events;

/**
 * Interface EventSubInterface
 * Event subscriber interface
 *
 * @package yiicod\socketio\events
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
