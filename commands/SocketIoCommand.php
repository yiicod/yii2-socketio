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

//        // Init connection for each channel
//        foreach (Broadcast::channels() as $channel) {
//            var_dump($channel);
//            Broadcast::publish($channel, ['name' => __CLASS__]);
//        }
//        $process->setTimeout(360000);
//        $process->setIdleTimeout(360000);
//        $process->wait(function ($type, $buffer) {
//            if (Process::ERR === $type) {
//                echo 'ERR > ' . $buffer;
//            } else {
//                echo 'OUT > ' . $buffer;
//            }
//        });
        while ($process->isRunning()) {
            $this->predis();
        }
    }
}
