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
     * Store/Set connection
     * @param channel Redis chanel
     * @param connection
     */
    addConnection(channel, connection) {
        let nsp = this.getNsp(channel);
        this.connections[nsp] = connection;
    };

    /**
     * Restore/Get connection
     * @param channel Redis chanel
     * @returns {*}
     */
    getConnection(channel) {
        let nsp = this.getNsp(channel);
        if (nsp in this.connections === true) {
            return this.connections[nsp];
        }

        return null;
    };

    /**
     * Force remove connection from list
     * @param channel Redis chanel
     */
    rmfConnection(channel) {
        let nsp = this.getNsp(channel);
        delete this.connections[nsp];
    };
}

module.exports = ConnectionsManager;
