function createNexusLiveRoom(options) {
    const opts = options || {};
    const peerConnections = {};
    const pendingCandidates = {};
    const speakingMonitors = {};
    const lastSpokenAt = {};
    const audioEls = {};
    let localStream = null;
    let socket = null;
    let roomChannel = null;
    let syncTimer = null;
    let audioCtx = null;
    let lastBroadcastSpeaking = null;
    let stopped = false;
    const iceServers = (typeof window.nexusIceServers === 'function')
        ? window.nexusIceServers()
        : [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' },
            { urls: 'stun:stun2.l.google.com:19302' }
        ];

    const self = {
        roomId: String(opts.roomId || ''),
        peerId: opts.peerId || ('peer-' + Date.now()),
        userId: Number(opts.userId || window.CURRENT_USER_ID || 0),
        userName: opts.userName || window.USER_NAME || (window.NEXUS_USER && window.NEXUS_USER.username) || 'You',
        avatar: opts.avatar || window.USER_AVATAR || (window.NEXUS_USER && window.NEXUS_USER.avatar_url) || '',
        border: opts.border || window.USER_BORDER || (window.NEXUS_USER && window.NEXUS_USER.border_preview) || '',
        audioHost: opts.audioHost || null,
        pusherClient: opts.pusherClient || null
    };

    function emitSpeaking(payload) {
        if (typeof opts.onSpeaking === 'function') opts.onSpeaking(payload);
    }

    function emitParticipants(peers) {
        if (typeof opts.onParticipants === 'function') opts.onParticipants(peers || []);
    }

    function emitEnded(message) {
        if (typeof opts.onRoomEnded === 'function') opts.onRoomEnded(message || 'This watch party has ended.');
    }

    function signalingUrl() {
        return window.NEXUS_SIGNALING_URL
            || ((location.port && location.port !== '3000')
                ? (location.protocol + '//' + location.hostname + ':3000')
                : undefined);
    }

    function ensureAudioHost() {
        if (self.audioHost && self.audioHost.isConnected) return self.audioHost;
        let host = document.getElementById('nexus-live-audio');
        if (!host) {
            host = document.createElement('div');
            host.id = 'nexus-live-audio';
            host.setAttribute('aria-hidden', 'true');
            host.style.cssText = 'position:absolute;width:0;height:0;overflow:hidden;opacity:0;pointer-events:none;';
            document.body.appendChild(host);
        }
        self.audioHost = host;
        return host;
    }

    function attachRemoteAudio(key, stream) {
        if (!key || !stream) return;
        const host = ensureAudioHost();
        let el = audioEls[key];
        if (!el) {
            el = document.createElement('audio');
            el.autoplay = true;
            el.playsInline = true;
            host.appendChild(el);
            audioEls[key] = el;
        }
        el.srcObject = stream;
        el.muted = false;
        el.play().catch(() => {});
    }

    function dropRemoteAudio(key) {
        const el = audioEls[key];
        if (!el) return;
        try { el.srcObject = null; } catch (e) {}
        try { el.remove(); } catch (e) {}
        delete audioEls[key];
    }

    function resumeAudio() {
        const ctx = ensureAudioContext();
        if (ctx && ctx.state === 'suspended') ctx.resume().catch(() => {});
        Object.keys(audioEls).forEach((key) => {
            try { audioEls[key].play().catch(() => {}); } catch (e) {}
        });
        return ctx;
    }

    function ensureAudioContext() {
        if (audioCtx && audioCtx.state !== 'closed') return audioCtx;
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return null;
        try { audioCtx = new Ctx(); } catch (e) { audioCtx = null; }
        return audioCtx;
    }

    function stopSpeakingWatch(key) {
        const monitor = speakingMonitors[key];
        if (!monitor) return;
        if (monitor.raf) cancelAnimationFrame(monitor.raf);
        try { monitor.source.disconnect(); } catch (e) {}
        try { monitor.analyser.disconnect(); } catch (e) {}
        delete speakingMonitors[key];
    }

    const lastSpeaking = {};
    function setSpeaking(key, speaking, meta) {
        const next = !!speaking;
        if (meta && meta.muted) {
            if (lastSpeaking[key] === false) return;
            lastSpeaking[key] = false;
            emitSpeaking({ ...meta, key, speaking: false });
            if (meta.isSelf) broadcastSpeaking(false);
            return;
        }
        if (lastSpeaking[key] === next) return;
        lastSpeaking[key] = next;
        emitSpeaking({ ...(meta || {}), key, speaking: next });
        if (meta && meta.isSelf) broadcastSpeaking(next);
    }

    function broadcastSpeaking(speaking) {
        const flag = !!speaking;
        if (lastBroadcastSpeaking === flag) return;
        lastBroadcastSpeaking = flag;
        if (socket && socket.connected) {
            socket.emit('peer-speaking', {
                speaking: flag,
                userId: self.userId,
                peerId: self.peerId,
                socketId: socket.id
            });
        }
    }

    function watchSpeaking(key, stream, meta) {
        stopSpeakingWatch(key);
        const muted = !!(meta && meta.muted);
        if (!key || !stream || muted) {
            setSpeaking(key, false, meta);
            return;
        }
        const liveAudio = stream.getAudioTracks().filter((t) => t.readyState === 'live' && t.enabled !== false);
        if (!liveAudio.length) {
            setSpeaking(key, false, meta);
            return;
        }
        const ctx = resumeAudio();
        if (!ctx) return;
        try {
            const source = ctx.createMediaStreamSource(new MediaStream(liveAudio));
            const analyser = ctx.createAnalyser();
            analyser.fftSize = 512;
            analyser.smoothingTimeConstant = 0.35;
            source.connect(analyser);
            const bins = new Uint8Array(analyser.frequencyBinCount);
            const monitor = { source, analyser, raf: 0 };
            speakingMonitors[key] = monitor;
            const tick = () => {
                if (speakingMonitors[key] !== monitor || stopped) return;
                analyser.getByteFrequencyData(bins);
                let sum = 0;
                for (let i = 0; i < bins.length; i++) sum += bins[i];
                const talking = (sum / bins.length) > 14;
                if (talking) lastSpokenAt[key] = Date.now();
                const hold = Date.now() - (lastSpokenAt[key] || 0) < 350;
                setSpeaking(key, talking || hold, meta);
                monitor.raf = requestAnimationFrame(tick);
            };
            monitor.raf = requestAnimationFrame(tick);
        } catch (e) {
            setSpeaking(key, false, meta);
        }
    }

    function normalizePeer(data) {
        if (data && typeof data === 'object') {
            const peerId = data.peerId || data.fromPeerId || data.targetPeerId || data.socketId || data.fromSocketId || null;
            return {
                peerId,
                socketId: data.socketId || data.fromSocketId || peerId,
                userId: data.userId || data.targetUserId || data.fromUserId,
                userName: data.userName || data.name || 'Guest',
                avatar_url: data.avatar_url,
                border_preview: data.border_preview
            };
        }
        return { peerId: null, socketId: null, userId: data, userName: 'Guest' };
    }

    function peerKey(peer) {
        return peer && (peer.peerId || peer.socketId) || null;
    }

    function closePc(key) {
        if (!key || !peerConnections[key]) return;
        try { peerConnections[key].close(); } catch (e) {}
        delete peerConnections[key];
        delete pendingCandidates[key];
        stopSpeakingWatch(key);
        dropRemoteAudio(key);
    }

    function removePeer(payload) {
        const peer = normalizePeer(payload || {});
        const key = peerKey(peer);
        closePc(key);
        closePc(payload && payload.socketId);
        if (typeof opts.onPeerLeave === 'function') opts.onPeerLeave(peer);
    }

    function createPeerConnection(key, peerMeta) {
        if (!key || key === self.peerId || (socket && key === socket.id)) return null;
        if (peerConnections[key]) {
            const existing = peerConnections[key];
            const state = existing.connectionState;
            if (state === 'closed' || state === 'failed' || state === 'disconnected') {
                closePc(key);
            } else {
                return existing;
            }
        }
        const pc = new RTCPeerConnection({ iceServers, iceCandidatePoolSize: 8 });
        peerConnections[key] = pc;
        if (localStream) {
            localStream.getTracks().forEach((track) => pc.addTrack(track, localStream));
        }
        pc.onicecandidate = (event) => {
            if (!event.candidate) return;
            signal('ice-candidate', {
                targetPeerId: key,
                targetSocketId: peerMeta.socketId || key,
                candidate: event.candidate,
                fromPeerId: self.peerId,
                fromSocketId: socket && socket.id
            });
        };
        pc.ontrack = (event) => {
            const stream = event.streams[0] || new MediaStream([event.track]);
            attachRemoteAudio(key, stream);
            watchSpeaking(key, stream, {
                isSelf: false,
                userId: peerMeta.userId,
                peerId: key,
                name: peerMeta.userName
            });
            emitParticipants([{
                peerId: key,
                socketId: peerMeta.socketId || key,
                userId: peerMeta.userId,
                name: peerMeta.userName || 'Guest',
                avatar_url: peerMeta.avatar_url,
                border_preview: peerMeta.border_preview
            }]);
        };
        pc.onconnectionstatechange = () => {
            const state = pc.connectionState;
            if (state === 'failed' || state === 'closed') {
                if (peerConnections[key] === pc) removePeer({ peerId: key, socketId: key, userId: peerMeta.userId });
            }
        };
        return pc;
    }

    async function callPeer(peer) {
        const normalized = normalizePeer(peer);
        const key = peerKey(normalized);
        if (!key || key === self.peerId || (socket && key === socket.id)) return;
        const pc = createPeerConnection(key, normalized);
        if (!pc || pc.localDescription) return;
        try {
            const offer = await pc.createOffer();
            await pc.setLocalDescription(offer);
            signal('offer', {
                targetPeerId: key,
                targetSocketId: normalized.socketId || key,
                sdp: pc.localDescription,
                fromPeerId: self.peerId,
                fromSocketId: socket && socket.id,
                userId: self.userId,
                userName: self.userName,
                avatar_url: self.avatar,
                border_preview: self.border
            });
        } catch (e) {
            console.error('live room offer failed', e);
        }
    }

    async function handleOffer(data) {
        const from = normalizePeer(data);
        const fromKey = peerKey(from);
        if (!fromKey || fromKey === self.peerId) return;
        if (data.targetPeerId && data.targetPeerId !== self.peerId && socket && data.targetSocketId !== socket.id) return;
        const pc = createPeerConnection(fromKey, from);
        if (!pc) return;
        try {
            if (pc.signalingState === 'have-local-offer') {
                try { await pc.setLocalDescription({ type: 'rollback' }); } catch (e) {}
            }
            if (pc.signalingState !== 'stable' && pc.currentRemoteDescription) return;
            await pc.setRemoteDescription(new RTCSessionDescription(data.sdp));
            await flushPending(fromKey);
            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);
            signal('answer', {
                targetPeerId: fromKey,
                targetSocketId: from.socketId || fromKey,
                sdp: pc.localDescription,
                fromPeerId: self.peerId,
                fromSocketId: socket && socket.id,
                userId: self.userId,
                userName: self.userName
            });
        } catch (e) {
            console.error('live room answer failed', e);
        }
    }

    async function handleAnswer(data) {
        const from = normalizePeer(data);
        const fromKey = peerKey(from);
        if (data.targetPeerId && data.targetPeerId !== self.peerId && socket && data.targetSocketId !== socket.id) return;
        const pc = peerConnections[fromKey];
        if (!pc) return;
        try {
            if (!pc.currentRemoteDescription) {
                await pc.setRemoteDescription(new RTCSessionDescription(data.sdp));
                await flushPending(fromKey);
            }
        } catch (e) {}
    }

    async function handleIce(data) {
        const from = normalizePeer(data);
        const fromKey = peerKey(from);
        if (!fromKey || !data.candidate) return;
        const pc = peerConnections[fromKey];
        if (pc && pc.remoteDescription) {
            try { await pc.addIceCandidate(new RTCIceCandidate(data.candidate)); } catch (e) {}
            return;
        }
        if (!pendingCandidates[fromKey]) pendingCandidates[fromKey] = [];
        pendingCandidates[fromKey].push(data.candidate);
    }

    async function flushPending(key) {
        const pc = peerConnections[key];
        const queued = pendingCandidates[key] || [];
        pendingCandidates[key] = [];
        if (!pc) return;
        for (const candidate of queued) {
            try { await pc.addIceCandidate(new RTCIceCandidate(candidate)); } catch (e) {}
        }
    }

    function signal(event, data) {
        if (socket && socket.connected) socket.emit(event, data);
        sendPusher(event, data);
    }

    function sendPusher(event, data) {
        if (!self.roomId) return;
        const mapped = event === 'send_message' ? 'new_message' : event;
        const allowed = { offer: true, answer: true, 'ice-candidate': true, 'peer-leave': true };
        if (!allowed[mapped]) return;
        const form = new FormData();
        form.append('room_id', self.roomId);
        form.append('event', mapped);
        form.append('payload', JSON.stringify({ ...data, fromPeerId: self.peerId }));
        fetch('/user_backend/room_signal.php', { method: 'POST', body: form }).catch(() => {});
    }

    function onRoomEvent(event, data) {
        if (stopped) return;
        if (event === 'room-ended') {
            emitEnded((data && data.message) || 'This watch party has ended.');
            return;
        }
        if (event === 'force-leave') {
            const uid = Number(self.userId);
            if (data && Number(data.targetUserId) === uid) {
                emitEnded((data && data.message) || 'The host removed you from the room.');
            }
            return;
        }
        if (event === 'peer-leave' || event === 'user-disconnected') {
            const leavingMe = data && (
                (data.peerId && String(data.peerId) === String(self.peerId))
                || (data.userId && Number(data.userId) === Number(self.userId) && !data.targetUserId)
            );
            if (!leavingMe) removePeer(data || {});
            return;
        }
        if (event === 'peer-speaking') {
            emitSpeaking({
                speaking: !!(data && data.speaking),
                userId: data && data.userId,
                peerId: data && data.peerId,
                socketId: data && data.socketId,
                isSelf: false
            });
            return;
        }
        if (!data) return;
        if (data.fromPeerId && data.fromPeerId === self.peerId) return;
        if (event === 'peer-join' || event === 'user-connected') {
            const peer = normalizePeer(data);
            const key = peerKey(peer);
            if (!key || key === self.peerId) return;
            createPeerConnection(key, peer);
            if (String(self.peerId) > String(key)) callPeer(peer);
            return;
        }
        if (event === 'existing-users') {
            (data || []).forEach((peer) => callPeer(peer));
            return;
        }
        if (event === 'offer') return handleOffer(data);
        if (event === 'answer') return handleAnswer(data);
        if (event === 'ice-candidate') return handleIce(data);
    }

    function bindPusher() {
        if (typeof Pusher === 'undefined' || !self.roomId) return;
        if (!self.pusherClient) {
            self.pusherClient = new Pusher(window.PUSHER_KEY || 'f4b5637ef4b8952b6eb8', {
                cluster: window.PUSHER_CLUSTER || 'ap1',
                encrypted: true
            });
        }
        const channelName = 'watch-party-' + self.roomId;
        roomChannel = self.pusherClient.subscribe(channelName);
        ['peer-join', 'peer-leave', 'offer', 'answer', 'ice-candidate', 'room-ended', 'force-leave'].forEach((event) => {
            roomChannel.bind(event, (data) => onRoomEvent(event, data));
        });
    }

    async function connectSocket() {
        if (typeof io !== 'function') return;
        const url = signalingUrl();
        socket = url
            ? io(url, { transports: ['websocket', 'polling'] })
            : io({ transports: ['websocket', 'polling'] });
        socket.on('connect', () => {
            if (self.userId) socket.emit('register-user', self.userId);
            if (self.roomId) socket.emit('join-room', String(self.roomId), self.userId, self.userName, self.peerId);
        });
        [
            'existing-users', 'user-connected', 'user-disconnected', 'peer-leave',
            'offer', 'answer', 'ice-candidate', 'peer-speaking', 'room-ended', 'force-leave'
        ].forEach((event) => {
            socket.on(event, (data) => onRoomEvent(event, data));
        });
    }

    async function announce() {
        if (!self.roomId || !self.peerId) return;
        const form = new FormData();
        form.append('room_id', self.roomId);
        form.append('peer_id', self.peerId);
        const res = await fetch('/user_backend/join_room.php', { method: 'POST', body: form });
        const data = await res.json().catch(() => ({}));
        if (data && (data.is_ended || data.is_kicked)) {
            emitEnded(data.message);
            return;
        }
        if (data && data.success && Array.isArray(data.peers)) {
            emitParticipants(data.peers.map((p) => ({
                peerId: p.peer_id || p.peerId,
                userId: p.user_id || p.userId,
                name: p.user_name || p.userName,
                avatar_url: p.avatar_url,
                border_preview: p.border_preview
            })));
            data.peers.forEach((peer) => callPeer({
                peerId: peer.peer_id || peer.peerId,
                userId: peer.user_id || peer.userId,
                userName: peer.user_name || peer.userName,
                avatar_url: peer.avatar_url,
                border_preview: peer.border_preview
            }));
        }
    }

    function startSync() {
        if (syncTimer) clearInterval(syncTimer);
        syncTimer = setInterval(() => {
            if (stopped || !self.roomId || !self.peerId) return;
            const form = new FormData();
            form.append('room_id', self.roomId);
            form.append('peer_id', self.peerId);
            form.append('heartbeat', '1');
            fetch('/user_backend/join_room.php', { method: 'POST', body: form })
                .then((r) => r.json())
                .then((data) => {
                    if (data && (data.is_ended || data.is_kicked)) {
                        emitEnded(data.message);
                        return;
                    }
                    if (!data || !data.success || !Array.isArray(data.peers)) return;
                    data.peers.forEach((peer) => {
                        const key = peer.peer_id || peer.peerId;
                        if (!key) return;
                        const pc = peerConnections[key];
                        if (pc && (pc.localDescription || pc.remoteDescription)) return;
                        callPeer({
                            peerId: key,
                            userId: peer.user_id || peer.userId,
                            userName: peer.user_name || peer.userName,
                            avatar_url: peer.avatar_url,
                            border_preview: peer.border_preview
                        });
                    });
                })
                .catch(() => {});
        }, 8000);
    }

    async function startLocalMedia() {
        localStream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true
            }
        });
        watchSpeaking('local', localStream, {
            isSelf: true,
            userId: self.userId,
            peerId: self.peerId,
            name: self.userName
        });
    }

    return {
        async start() {
            stopped = false;
            const unlock = () => resumeAudio();
            document.addEventListener('click', unlock, { once: true });
            try {
                const servers = (typeof window.nexusLoadIceServers === 'function')
                    ? await window.nexusLoadIceServers()
                    : iceServers;
                if (Array.isArray(servers) && servers.length) {
                    iceServers.splice(0, iceServers.length, ...servers);
                }
            } catch (e) {}
            bindPusher();
            await connectSocket();
            try { await startLocalMedia(); } catch (e) {
                console.warn('Dashboard live camera/mic unavailable', e);
            }
            await announce();
            startSync();
        },
        stop() {
            stopped = true;
            if (syncTimer) {
                clearInterval(syncTimer);
                syncTimer = null;
            }
            Object.keys(speakingMonitors).forEach(stopSpeakingWatch);
            Object.keys(peerConnections).forEach(closePc);
            Object.keys(audioEls).forEach(dropRemoteAudio);
            if (localStream) {
                localStream.getTracks().forEach((t) => t.stop());
                localStream = null;
            }
            if (roomChannel && self.pusherClient) {
                try { self.pusherClient.unsubscribe('watch-party-' + self.roomId); } catch (e) {}
                roomChannel = null;
            }
            if (socket) {
                try { socket.disconnect(); } catch (e) {}
                socket = null;
            }
            if (audioCtx) {
                try { audioCtx.close(); } catch (e) {}
                audioCtx = null;
            }
        }
    };
}

window.createNexusLiveRoom = createNexusLiveRoom;
