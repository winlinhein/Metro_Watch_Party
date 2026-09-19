(function (w) {
    w.NEXUS_STUN_SERVERS = [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        { urls: 'stun:stun2.l.google.com:19302' },
        { urls: 'stun:stun.relay.metered.ca:80' },
        { urls: 'stun:stun.cloudflare.com:3478' }
    ];

    w.nexusIceServers = function () {
        if (Array.isArray(w.NEXUS_ICE_SERVERS) && w.NEXUS_ICE_SERVERS.length) {
            return w.NEXUS_ICE_SERVERS;
        }
        return w.NEXUS_STUN_SERVERS.slice();
    };

    w.nexusLoadIceServers = async function () {
        try {
            const res = await fetch('/user_backend/get_ice_servers.php', { credentials: 'same-origin' });
            const data = await res.json();
            if (data && data.success && Array.isArray(data.iceServers) && data.iceServers.length) {
                w.NEXUS_ICE_SERVERS = data.iceServers;
                return data.iceServers;
            }
        } catch (e) {}
        return w.nexusIceServers();
    };
})(window);
