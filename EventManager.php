<?php

namespace yiicod\socketio;

use Yii;
use yii\base\Component;
//use yii\helpers\Json;

/**
 *
 * @property array $list
 */
class EventManager extends Component
{
    /**
     * Array of events namespaces
     *
     * @var array
     */
    public $namespaces = [];

    /**
     * You can set unique nsp for channels
     *
     * @var string
     */
    public $nsp = '';

    /**
     * List with all events
     *
     * @var array
     */
    protected static $list = [];

    public function getList(): array
    {
        if (empty(static::$list)) {
            foreach ($this->namespaces as $key => $namespace) {
                $alias = Yii::getAlias('@' . str_replace('\\', '/', trim($namespace, '\\')));
                foreach (glob(sprintf('%s/**.php', $alias)) as $file) {
                    $className = sprintf('%s\%s', $namespace, basename($file, '.php'));
                    if (method_exists($className, 'name')) {
                        static::$list[$className::name()] = $className;
                    }
                }
            }
            //Yii::info(Json::encode(static::$list));
        }

        return static::$list;
    }
}
