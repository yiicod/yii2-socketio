// Classes
const ArgumentParser = require('argparse').ArgumentParser;

// Library
const server = require('http').createServer(),
    io = require('socket.io')(server),
    winston = require('winston'),
    redis = require("redis"),
    parser = new ArgumentParser({
        version: '1.0.0',
        addHelp: true,
        description: 'Nodejs proxy'
    });

parser.addArgument(
    ['-nsp', '--nsp'],
    {
        defaultValue: '',
        help: 'Redis nsp. This value should be the same with "roadcastEvents" nsp'
    }
);

parser.addArgument(
    ['-server', '--server'],
    {
        defaultValue: 'localhost:1337',
        help: 'Http server: [hostname:port]'
    }
);
parser.addArgument(
    ['-channels', '--channels'],
    {
        defaultValue: 'socket.io',
        help: 'Redis channels. Example: --channels=\'c1,c2,c3\''
    }
);
parser.addArgument(
    ['-sub', '--sub'],
    {
        defaultValue: '{host: localhost, port:6379}',
        help: 'Redis subscriber server credential: [{host: localhost, port:6379}]'
    }
);
parser.addArgument(
    ['-pub', '--pub'],
    {
        defaultValue: '{host: localhost, port:6379}',
        help: 'Redis publisher server credential: [{host: localhost, port:6379}]'
    }
);
parser.addArgument(
    ['-runtime', '--runtime'],
    {
        defaultValue: __dirname,
        help: 'Runtime path in the app'
    }
);

const args = parser.parseArgs(),
    subscriber = redis.createClient(JSON.parse(args.sub)),
    publisher = redis.createClient(JSON.parse(args.pub)),
    channels = args.channels.split(',');

const logger = new (winston.Logger)({
    transports: [
        // new winston.transports.File({filename: args.runtime + '/all-logs.log'}),
        // new (winston.transports.Console)({colorize: true, timestamp: true}),
    ],
    exceptionHandlers: [
        new winston.transports.File({filename: args.runtime + '/exceptions.log'})
    ],
    exitOnError: false
});

class ConnectionsManager {
    constructor(nsp) {
        this.nsp = nsp;
        this.connections = [];
    }

    /**
     * Get io connection nsp
     * @param channel
     * @return string
     */
    getNsp(channel) {
        return channel.replace(this.nsp, '');
    };

    /**
     * Store connection
     * @param channel Redis chanel
     * @param connection
     */
    addConnection(channel, connection) {
        let nsp = this.getNsp(channel);
        this.connections[nsp] = connection;
    };

    /**
     * Restore connection
     * @param channel Redis chanel
     * @returns {*}
     */
    loadConnection(channel) {
        let nsp = this.getNsp(channel);
        if (nsp in this.connections === true) {
            return this.connections[nsp];
        }

        return null;
    };

    /**
     * Delete connection from list
     * @param channel Redis chanel
     */
    removeConnection(channel) {
        let nsp = this.getNsp(channel);
        delete this.connections[nsp];
    };
}

class RoomIO {
    constructor(socket) {
        this.socket = socket;
        this.room = [];
    }

    name() {
        return this.room[this.socket.id] || null;
    }

    join(room) {
        this.leave();
        this.room[this.socket.id] = room;

        this.socket.join(room);
    }

    leave() {
        this.socket.leave(this.room[this.socket.id]);
    }
}

class AccessIO {
    constructor(socket) {
        this.socket = socket;
        this.events = [];

        // Const
        this.REQUEST_LIMIT = 5;
    }

    isDdos(name) {
        let event = this.events[this.socket.id + name] || {};
        if (event.timestamp && (new Date().getTime() - event.timestamp) < this.REQUEST_LIMIT) {
            return true;
        } else {
            event.timestamp = new Date().getTime();
        }
        this.events[this.socket.id + name] = event;

        return false;
    }

    can(name) {
        return false === this.isDdos(name);
    }
}

class RedisIO {
    constructor(io, sub, pub, channels) {
        this.io = io;
        this.sub = sub;
        this.pub = pub;
        this.channels = channels;
    }

    /**
     * Set connections manager
     * @param manager
     * @returns {RedisIO}
     */
    setConnectionsManager(manager) {
        this.connectionsManager = manager;

        return this;
    }

    /**
     * Get event from data
     * @param data
     */
    parseEvent(data) {
        return Object.assign({name: '', data: {}}, JSON.parse(data));
    };

    /**
     * Init all events on '*'
     * @param socket
     * @return {*}
     */
    wildcard(socket) {
        let Emitter = require('events').EventEmitter;
        let emit = Emitter.prototype.emit;
        let onevent = socket.onevent;
        socket.onevent = function (packet) {
            let args = packet.data || [];
            onevent.call(this, packet);    // original call
            emit.apply(this, ["*"].concat(args));      // additional call to catch-all
        };
        return socket;
    };

    /**
     * Init connection
     * @param channel
     * @param data
     */
    init(channel, data) {
        logger.info('SocketIO > Listening');

        let nsp = this.connectionsManager.getNsp(channel),
            connection = this.connectionsManager.loadConnection(channel);

        if (connection === null) {
            logger.info('SocketIO > Connection init nsp: %s', nsp);

            let nspio = this.io.of('/' + nsp);
            nspio.on('connection', (socket) => {
                socket.roomIO = new RoomIO(socket);
                socket.access = new AccessIO(socket);

                socket = this.wildcard(socket);

                logger.info('SocketIO > Connected socket: %s, nsp: %s, room: %s', socket.id, nsp, socket.roomIO.name());

                socket.on('disconnect', () => {
                    logger.info('SocketIO > Disconnected socket: %s, nsp: %s, room: %s', socket.id, nsp, socket.roomIO.name());
                });

                socket.on('*', (name, data) => {
                    data = data || {};
                    if (false === socket.access.can(name)) {
                        logger.info('SocketIO > On failed socket: %s, nsp: %s, room: %s, name: %s, data: %s', socket.id, nsp, socket.roomIO.name(), name, JSON.stringify(data));
                    } else {
                        logger.info('SocketIO > On success socket: %s, nsp: %s, room: %s, name: %s, data: %s', socket.id, nsp, socket.roomIO.name(), name, JSON.stringify(data));
                        switch (name) {
                            case 'join' :
                                socket.roomIO.join(data.room);
                                break;
                            case 'leave':
                                socket.roomIO.leave();
                                break;
                            default:
                                data.room = socket.roomIO.name();
                                this.pub.publish(channel + '.io', JSON.stringify({
                                    name: name,
                                    data: data
                                }));
                        }
                    }
                });
            });

            this.connectionsManager.addConnection(channel, nspio);
        }
    };

    /**
     * Emit event to exist connection
     * @param channel
     * @param data
     */
    emit(channel, data) {
        let event = this.parseEvent(data),
            room = event.data.room,
            connection = this.connectionsManager.loadConnection(channel);

        if (null !== connection) {
            logger.info('SocketIO > Emit ' + JSON.stringify(event.name) + ' ' + JSON.stringify(event.data));
            if (room) {
                delete event.data.room;
                connection.to(room).emit(event.name, event.data);
            } else {
                connection.emit(event.name, event.data);
            }
        }
    };

    /**
     * List redis/socket.io
     */
    listen() {
        for (let i = 0; i < this.channels.length; i++) {
            this.sub.subscribe(this.channels[i]);
            this.init(this.channels[i], JSON.stringify({}));
        }

        this.sub.on("message", (channel, data) => {
            this.emit(channel, data);
        });
    }

}

(new RedisIO(io, subscriber, publisher, args.channels.split(',')))
    .setConnectionsManager(new ConnectionsManager(args.nsp))
    .listen();


server.listen(args.server.split(':')[1], args.server.split(':')[0]);

