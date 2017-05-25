<?php

namespace yiicod\socketio\events;

interface EventPolicyInterface
{
    public function can($data): bool;
}
