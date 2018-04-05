const logger = require('./logger');

class AccessIO {
    constructor(socket) {
        this.socket = socket;
    }

    /**
     * Get limit number
     *
     * @returns {number}
     */
    getRequestLimit() {
        return process.env.SOCKET_IO_SPEED_LIMIT || 3;
    }

    /**
     * Every request sent faster than 2ms will return false, otherwise the handler get run.
     * @param name Event name
     * @returns {boolean}
     */
    isDdos(name) {
        let data = (this.socket.accessIo || {})[name] || {};
        
        if (this.getRequestLimit() <= 0) {
            return false;
        }

        if (data.timestamp && (new Date().getTime() - data.timestamp) < this.getRequestLimit()) {
            return true;
        } else {
            data.timestamp = new Date().getTime();
        }

        this.socket.accessIo = this.socket.accessIo || {};
        this.socket.accessIo[name] = data;

        return false;
    }

    can(name) {
        return false === this.isDdos(name);
    }
}

module.exports = AccessIO;