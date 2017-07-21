const args = require('./bundles/args');
const server = require('./bundles/server');
const io = require('socket.io')(server);
const redis = require("redis");
const subscriber = redis.createClient(JSON.parse(args.sub));
const publisher = redis.createClient(JSON.parse(args.pub));
const RedisIO = require('./bundles/redis-io');

(new RedisIO(args.nsp, io, subscriber, publisher, args.channels.split(',')))
    .listen();


server.listen(args.server.split(':')[1], args.server.split(':')[0]);

