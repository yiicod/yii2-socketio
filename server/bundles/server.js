const args = require('./args');
const https = require('https');
const http = require('http');
const fs = require('fs');
const ssl = JSON.parse(args.ssl);
const dotenv = require('dotenv').config();

const server = args.ssl ? https.createServer({
    key: fs.readFileSync(ssl.key),
    cert: fs.readFileSync(ssl.cert)
}) : http.createServer();

module.exports = server;