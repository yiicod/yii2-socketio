<?php

namespace hyperia\socketio;

use Yii;
use yii\helpers\HtmlPurifier;

/**
 * Class Process
 *
 * @package SfCod\SocketIoBundle
 */

/**
 * Class Process
 *
 * @package hyperia\socketio
 */
class Process
{
    /**
     * @var array
     */
    private static $_inWork = [];

    /**
     * @var
     */
    public $yiiAlias;

    /**
     * @return int
     */
    public function getParallelEnv(): int
    {
        return getenv('SOCKET_IO.PARALLEL') ? getenv('SOCKET_IO.PARALLEL') : 10;
    }

    /**
     * Run process. If more then limit then wait and try run process on more time.
     *
     * @param string $handle
     * @param array $data
     *
     * @return \Symfony\Component\Process\Process
     */
    public function run(string $handle, array $data)
    {
        $this->inWork();

        while (count(self::$_inWork) >= $this->getParallelEnv()) {
            usleep(100);

            $this->inWork();
        }

        return $this->push($handle, $data);
    }

    /**
     * In work processes
     */
    private function inWork()
    {
        foreach (self::$_inWork as $i => $proccess) {
            if (false === $proccess->isRunning()) {
                unset(self::$_inWork[$i]);
            }
        }
    }

    /**
     * Create cmd process and push to queue.
     *
     * @param string $handle
     * @param array $data
     *
     * @return \Symfony\Component\Process\Process
     */
    private function push(string $handle, array $data): \Symfony\Component\Process\Process
    {
        $cmd = HtmlPurifier::process(sprintf('php yii socketio/process %s %s', escapeshellarg($handle), escapeshellarg(json_encode($data))));

        if (is_null($this->yiiAlias)) {
            if (file_exists(Yii::getAlias('@app/yii'))) {
                $this->yiiAlias = '@app';
            } elseif (file_exists(Yii::getAlias('@app/../yii'))) {
                $this->yiiAlias = '@app/../';
            }
        }

        $process = new \Symfony\Component\Process\Process($cmd, Yii::getAlias($this->yiiAlias));
        $process->setTimeout(10);
        $process->start();

        self::$_inWork[] = $process;

        return $process;
    }
}
