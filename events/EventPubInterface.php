<?php

namespace yiicod\socketio\events;

/**
 * Interface EventPubInterface
 * Event publish interface
 *
 * @package yiicod\socketio\events
 */
interface EventPubInterface
{
    /**
     * Process event and return result to subscribers
     *
     * @param array $data
     *
     * @return array
     */
    public function fire(array $data): array;
}
