// Const
const ArgumentParser = require('argparse').ArgumentParser;
const parser = new ArgumentParser({
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
parser.addArgument(
    ['-ssl', '--ssl'],
    {
        defaultValue: null,
        help: 'SSL configs'
    }
);
const args = parser.parseArgs();

module.exports = args;
