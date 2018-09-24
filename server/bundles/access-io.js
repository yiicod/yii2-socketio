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

module.exports = AccessIO;