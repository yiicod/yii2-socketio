<?php

namespace yiicod\socketio;

use yiicod\socketio\drivers\RedisDriver;
use yiicod\socketio\events\EventPolicyInterface;
use yiicod\socketio\events\EventPubInterface;
use yiicod\socketio\events\EventRoomInterface;
use yiicod\socketio\events\EventSubInterface;
use Exception;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\HtmlPurifier;
use yii\helpers\Json;
use yiicod\base\helpers\LoggerHelper;

/**
 * Class Broadcast
 *
 * @package yiicod\socketio
 */
class Broadcast
{
    protected static $channels = [];

    /**
     * Subscribe to event from client
     *
     * @param string $event
     * @param array $data
     *
     * @throws Exception
     */
    public static function on(string $event, array $data)
    {
        // Clear data
        array_walk_recursive($data, function (&$item, $key) {
            $item = HtmlPurifier::process($item);
        });

        Yii::info(Json::encode([
            'type' => 'on',
            'name' => $event,
            'data' => $data,
        ]), 'socket.io');

        $eventClassName = self::getManager()->getList()[$event] ?? null;

        try {
            if (null === $eventClassName) {
                throw new Exception("Can not find $event");
            }

            /** @var EventSubInterface|EventPolicyInterface $event */
            $event = new $eventClassName($data);

            if (false === $event instanceof EventSubInterface) {
                throw new Exception('Event should implement EventSubInterface');
            }

            Yii::$app->db->close();
            Yii::$app->db->open();

            if (true === $event instanceof EventPolicyInterface && false === $event->can($data)) {
                return;
            }

            $event->handle($data);
        } catch (Exception $e) {
            Yii::error(LoggerHelper::log($e, Json::encode($data)));
        }
    }

    /**
     * Emit event to client
     *
     * @param string $event
     * @param array $data
     *
     * @throws Exception
     */
    public static function emit(string $event, array $data)
    {
        $eventClassName = self::getManager()->getList()[$event] ?? null;
        try {
            if (null === $eventClassName) {
                throw new Exception("Can not find $event");
            }

            /** @var EventPubInterface|EventRoomInterface $event */
            $event = new $eventClassName($data);

            if (false === $event instanceof EventPubInterface) {
                throw new Exception('Event should implement EventPubInterface');
            }

            $data = $event->fire($data);

            if (true === $event instanceof EventRoomInterface) {
                $data['room'] = $event->room();
            }

            Yii::info(Json::encode([
                'type' => 'emit',
                'name' => $event,
                'data' => $data,
            ]), 'socket.io');
            foreach ($eventClassName::broadcastOn() as $channel) {
                static::publish(static::channelName($channel), [
                    'name' => $eventClassName::name(),
                    'data' => $data,
                ]);
            }
        } catch (Exception $e) {
            Yii::error(LoggerHelper::log($e));
        }
    }

    /**
     * Prepare channel name
     *
     * @param $name
     *
     * @return string
     */
    public static function channelName($name)
    {
        return $name . self::getManager()->nsp;
    }

    /**
     * Publish data to redis channel
     *
     * @param string $channel
     * @param array $data
     */
    public static function publish(string $channel, array $data)
    {
        static::getDriver()->getConnection(true)->publish($channel, Json::encode($data));
    }

    /**
     * Redis channels names
     *
     * @return array
     */
    public static function channels(): array
    {
        if (empty(self::$channels)) {
            foreach (self::getManager()->getList() as $eventClassName) {
                self::$channels = ArrayHelper::merge(self::$channels, $eventClassName::broadcastOn());
            }
            self::$channels = array_unique(self::$channels);

            self::$channels = array_map(function ($channel) {
                return static::channelName($channel);
            }, self::$channels);
            //Yii::info(Json::encode(self::$channels));
        }

        return self::$channels;
    }

    /**
     * @return RedisDriver
     */
    public static function getDriver()
    {
        return Yii::$app->broadcastDriver;
    }

    /**
     * @return EventManager
     */
    public static function getManager()
    {
        return Yii::$app->broadcastEvents;
    }
}
