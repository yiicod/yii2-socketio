const winston = require('winston');
const args = require('./args');

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

module.exports = logger;