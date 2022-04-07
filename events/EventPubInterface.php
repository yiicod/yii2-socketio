<?php

namespace hyperia\socketio\events;

/**
 * Interface EventPubInterface
 * Event publish interface
 *
 * @package hyperia\socketio\events
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
