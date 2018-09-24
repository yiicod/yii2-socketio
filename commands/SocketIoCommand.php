<?php

namespace yiicod\socketio\commands;

use Symfony\Component\Process\Process;
use yiicod\cron\commands\DaemonController;
use yiicod\socketio\Broadcast;

/**
 * Class SocketIoCommand
 * Run this daemon for listen socketio. Don't forget about run npm install in the folder "server".
 *
 * @package yiicod\socketio\commands
 */
class SocketIoCommand extends DaemonController
{
    use CommandTrait;

    /**
     * Daemon name
     *
     * @return string
     */
    protected function daemonName(): string
    {
        return 'socket.io';
    }

    /**
     * SocketOI worker
     */
    public function worker()
    {
        $process = $this->nodejs();
        $process->disableOutput();
        $process->start();

        // Save node proccess pid
        $this->addPid($process->getPid());

        while ($process->isRunning()) {
            $this->predis();
        }
    }
}
