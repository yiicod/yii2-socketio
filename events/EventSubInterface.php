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
	 * @param string $id
	 *
	 * @return mixed
	 */
    public function handle(array $data, string $id);
}
