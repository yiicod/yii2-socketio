<?php

namespace yiicod\socketio\commands;

use Symfony\Component\Process\Process;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\Json;
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
    /**
     * @var string
     */
    public $server = 'locahost:1212';

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

    public function nodejs()
    {
        // Automatically send every new message to available log routes
        Yii::getLogger()->flushInterval = 1;

        $cmd = sprintf('node %s/%s', realpath(dirname(__FILE__) . '/../server'), 'server.es6.js');
        $args = [
            'server' => $this->server,
            'pub' => json_encode(array_filter([
                'host' => Broadcast::getDriver()->hostname,
                'port' => Broadcast::getDriver()->port,
                'password' => Broadcast::getDriver()->password,
            ])),
            'sub' => json_encode(array_filter([
                'host' => Broadcast::getDriver()->hostname,
                'port' => Broadcast::getDriver()->port,
                'password' => Broadcast::getDriver()->password,
            ])),
            'channels' => empty(Broadcast::channels()) ? 'some_unique_keys' : implode(',', Broadcast::channels()),
            'nsp' => Broadcast::getManager()->nsp,
            'runtime' => Yii::getAlias('@runtime/logs'),
        ];
        foreach ($args as $key => $value) {
            $cmd .= ' -' . $key . '=\'' . $value . '\'';
        }

        $process = new Process($cmd);

        return $process;
    }

    public function predis()
    {
        $pubSubLoop = function () {
            $client = Broadcast::getDriver()->getConnection(true);

            // Initialize a new pubsub consumer.
            $pubsub = $client->pubSubLoop();

            $channels = [];
            foreach (Broadcast::channels() as $key => $channel) {
                $channels[$key] = $channel . '.io';
            }

            // Subscribe to your channels
            $pubsub->subscribe(ArrayHelper::merge(['control_channel'], $channels));

            // Start processing the pubsup messages. Open a terminal and use redis-cli
            // to push messages to the channels. Examples:
            //   ./redis-cli PUBLISH notifications "this is a test"
            //   ./redis-cli PUBLISH control_channel quit_loop
            foreach ($pubsub as $message) {
                switch ($message->kind) {
                    case 'subscribe':
                        $this->output("Subscribed to {$message->channel}");
                        break;
                    case 'message':
                        if ($message->channel == 'control_channel') {
                            if ($message->payload == 'quit_loop') {
                                $this->output("Aborting pubsub loop...\n", Console::FG_RED);
                                $pubsub->unsubscribe();
                            } else {
                                $this->output("Received an unrecognized command: {$message->payload}\n", Console::FG_RED);
                            }
                        } else {
                            $payload = Json::decode($message->payload);
                            $data = $payload['data'] ?? [];

                            Broadcast::on($payload['name'], $data);
                            // Received the following message from {$message->channel}:") {$message->payload}";
                        }
                        break;
                }
            }

            // Always unset the pubsub consumer instance when you are done! The
            // class destructor will take care of cleanups and prevent protocol
            // desynchronizations between the client and the server.
            unset($pubsub);
        };

        // Auto recconnect on redis timeout
        try {
            $pubSubLoop();
        } catch (\Predis\Connection\ConnectionException $e) {
            $pubSubLoop();
        }
    }
}
