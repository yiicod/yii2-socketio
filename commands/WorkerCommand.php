<?php

//namespace yiicod\socketio\commands;
//
//use yii\console\Controller;
//
///**
// * Class SocketIoCommand
// * Run this daemon for listen socketio. Don't forget about run npm install in the folder "server".
// *
// * @package yiicod\socketio\commands
// */
//class WorkerCommand extends Controller
//{
//    use CommandTrait;
//
//    /**
//     * @var string
//     */
//    public $defaultAction = 'work';
//
//    /**
//     * @var int
//     */
//    public $delay = 15;
//
//    /**
//     * @throws \Exception
//     */
//    public function actionWork()
//    {
//        $process = $this->nodejs();
//        $process->disableOutput();
//        $process->start();
//
//        while ($process->isRunning()) {
//            try {
//                $this->predis();
//            } catch (\Throwable $e) {
//                $process->stop(0);
//                die('111');
//                throw $e;
//            }
//        }
//    }
//
//
//    /**
//     * @return FileOutput
//     */
//    protected function output($text)
//    {
//        $this->stdout($text);
//    }
//}

namespace yiicod\socketio\commands;

use Exception;
use yii\console\Controller;

/**
 * Socketio server. You should run two commands: "socketio/node-js-server" and "socketio/php-server". Use pm2 as daemon manager.
 *
 * @package yiicod\socketio\commands
 */
class WorkerCommand extends Controller
{
    use CommandTrait;

    /**
     * @var string
     */
    public $defaultAction = 'work';

    /**
     * @var int
     */
    public $delay = 15;

    /**
     * Node js listener.
     *
     * @throws Exception
     */
    public function actionNodeJsServer()
    {
        $process = $this->nodejs();
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->run();
    }

    /**
     * Php listener
     *
     * @throws Exception
     */
    public function actionPhpServer()
    {
        while (true) {
            $this->predis();
        }
    }

	/**
	 * @param $text
	 * @return void
	 */
    protected function output($text)
    {
        $this->stdout($text);
    }
}
