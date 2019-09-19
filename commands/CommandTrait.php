<?php

namespace yiicod\socketio\commands;

use Symfony\Component\Process\Process;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\Json;
use yiicod\socketio\Broadcast;

trait CommandTrait
{
    /**
     * @var string
     */
    public $server = 'locahost:1212';

    /**
     * [
     *     key => 'path to key',
     *     cert => 'path to cert',
     * ]
     *
     * @var array
     */
    public $ssl = [];
	
	/**
	 * Process job by id and connection
	 * @param $handler
	 * @param $data
	 * @param $id
	 */
    public function actionProcess($handler, $data, $id)
    {
        Broadcast::process($handler, @json_decode($data, true) ?? [], $id);
    }

    public function nodejs()
    {
        // Automatically send every new message to available log routes
        Yii::getLogger()->flushInterval = 1;

        $cmd = sprintf('node %s/%s', realpath(dirname(__FILE__) . '/../server'), 'index.js');
        $args = array_filter([
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
            'channels' => implode(',', Broadcast::channels()),
            'nsp' => Broadcast::getManager()->nsp,
            'ssl' => empty($this->ssl) ? null : json_encode($this->ssl),
            'runtime' => Yii::getAlias('@runtime/logs'),
        ], 'strlen');
        foreach ($args as $key => $value) {
            $cmd .= ' -' . $key . '=\'' . $value . '\'';
        }

        $process = new Process($cmd);

        return $process;
    }

    /**
     * Predis proccess
     */
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
			/** @var object $message */
			foreach ($pubsub as $message) {
                switch ($message->kind) {
                    case 'subscribe':
                        $this->output("Subscribed to {$message->channel}\n");
                        break;
                    case 'message':
                        if ('control_channel' == $message->channel) {
                            if ('quit_loop' == $message->payload) {
                                $this->output("Aborting pubsub loop...\n", Console::FG_RED);
                                $pubsub->unsubscribe();
                            } else {
                                $this->output("Received an unrecognized command: {$message->payload}\n", Console::FG_RED);
                            }
                        } else {
                            $payload = Json::decode($message->payload);
                            $data = $payload['data'] ?? [];
							$id = $payload['id'] ?? '';

//                            $pid = pcntl_fork();
//                            if ($pid == -1) {
//                                exit('Error while forking process.');
//                            } elseif ($pid) {
//                                //parent. Wait for the child and continues
//                                pcntl_wait($status);
//                                $exitStatus = pcntl_wexitstatus($status);
//                                if ($exitStatus !== 0) {
//                                    //put job back to queue or other stuff
//                                }
//                            }else {
                            Broadcast::on($payload['name'], $data, $id);
//                                Yii::$app->end();
//                            }
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

        return true;
    }
}
