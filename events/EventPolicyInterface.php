<?php

namespace hyperia\socketio\events;

interface EventPolicyInterface
{
    public function can($data): bool;
}
