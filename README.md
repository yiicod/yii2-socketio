Socket.io Yii extensions
========================

Use all power socket.io in your Yii 2 project.

Config
------

###### Install node + npm packages
```bash
    cd ~
    curl -sL https://deb.nodesource.com/setup_6.x -o nodesource_setup.sh
    sudo bash nodesource_setup.sh
    
    npm install --prefix ./vendor/yiicod/yii2-socketio/server
```

###### Console config
```php
    'controllerMap' => [
        'socketio' => [
            'class' => \yiicod\socketio\commands\SocketIoCommand::class,
            'server' => 'localhost:1367'
        ],
    ]
```

###### Common config
```php
    'components' =>[
        'broadcastEvents' => [
            'class' => \yiicod\socketio\EventManager::class,
            // If you use onse redis on more then one project
            'nsp' => 'some_unique_key',
            // Namespaces with events folders
            'namespaces' => [
                'app\socketio',
            ]
        ],
        'broadcastDriver' => [
            'class' => \yiicod\socketio\drivers\RedisDriver::class,
            'hostname' => 'locahost',
            'port' => 6379,
        ],    
    ]
```
Usage
-----
###### Start sockeio server
```bash
    php yii socketio/start
```
###### Stop sockeio server
```bash
    php yii socketio/stop
```

###### Create publisher from server to client
```php
    namespace app\socketio;
    
    use yiicod\socketio\events\EventInterface;
    use yiicod\socketio\events\EventSubInterface;
    
    class CountEvent implements EventInterface, EventPubInterface
    {
        /**
         * Changel name. For client side this is nsp.
         */
        public static function broadcastOn(): array
        {
            return ['notifications'];
        }
    
        /**
         * Event name
         */
        public static function name(): string
        {
            return 'update_notification_count';
        }
            
        /**
         * Emit client event
         * @param array $data
         * @return array
         */
        public function fire(array $data): array
        {
            return [
                'count' => 10,
            ];
        }
    }
```
```js
    var socket = io('localhost:1367/notifications');
    socket.on('update_notification_count', function(data){
        console.log(data)
    });
```

###### Create receiver from client to server
```php
    namespace app\socketio;

    use yiicod\socketio\events\EventInterface;
    use yiicod\socketio\events\EventSubInterface;

    class MarkAsReadEvent implements EventInterface, EventSubInterface
    {
        /**
         * Changel name. For client side this is nsp.
         */
        public static function broadcastOn(): array
        {
            return ['notifications'];
        }
    
        /**
         * Event name
         */
        public static function name(): string
        {
            return 'mark_as_read_notification';
        }
            
        /**
         * Emit client event
         * @param array $data
         * @return array
         */
        public function handle(array $data)
        {
            // Mark notification as read
            // And call client update
            Broadcast::emit('update_notification_count', ['some key' => 'some value']);
        }
    }
```
```js
    var socket = io('localhost:1367/notifications');
    socket.emit('mark_as_read_notification', {id: 10});
```

You can have publisher and receiver in one event. If you need check data from client to server you should use: 
- EventPolicyInterface

###### Receiver with checking from client to server
```php
    namespace app\socketio;

    use yiicod\socketio\events\EventInterface;
    use yiicod\socketio\events\EventSubInterface;
    use yiicod\socketio\events\EventPolicyInterface;
    
    class MarkAsReadEvent implements EventInterface, EventSubInterface, EventPolicyInterface
    {
        /**
         * Changel name. For client side this is nsp.
         */
        public static function broadcastOn(): array
        {
            return ['notifications'];
        }
    
        /**
         * Event name
         */
        public static function name(): string
        {
            return 'mark_as_read_notification';
        }
         
        public function can($data): bool
        {
            // Check data from client    
            return true;
        }        
        
        /**
         * Emit client event
         * @param array $data
         * @return array
         */
        public function handle(array $data)
        {
            // Mark notification as read
            // And call client update
            Broadcast::emit('update_notification_count', ['some key' => 'some value']);
        }
    }
```

Soket.io has room functionl. If you need it, you should implement:
- EventRoomInterface
```php
    namespace app\socketio;

    use yiicod\socketio\events\EventInterface;
    use yiicod\socketio\events\EventPubInterface;
    use yiicod\socketio\events\EventRoomInterface;
    
    class CountEvent implements EventInterface, EventPubInterface, EventRoomInterface
    {
        /**
         * Changel name. For client side this is nsp.
         */
        public static function broadcastOn(): array
        {
            return ['notifications'];
        }
    
        /**
         * Event name
         */
        public static function name(): string
        {
            return 'update_notification_count';
        }
           
        /**
         * Socket.io room
         * @return string
         */
        public function room(): string
        {
            return md5('notifications' . 'room-1');
        }            
            
        /**
         * Emit client event
         * @param array $data
         * @return array
         */
        public function fire(array $data): array
        {
            return [
                'count' => 10,
            ];
        }
    }
```
```js
    var socket = io('localhost:1367/notifications');
    socket.emit('join', {room: 'room-1'});
    // Now you will receive data from 'room-1'
    socket.on('update_notification_count', function(data){
        console.log(data)
    });
    // You can leave room
    socket.emit('leave');
```
