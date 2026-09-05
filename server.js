import { Server } from 'socket.io';

const io = new Server(3000, {
    cors: { origin: "*" }
});

console.log("WebSocket Server is running on port 3000...");

io.on('connection', socket => {
    console.log(`New connection: ${socket.id}`);
    
    // --- 1. Lobby & Invite Events ---
    
    // User logs into the website and registers their socket
    socket.on('register-user', (userId) => {
        const id = Number(userId);
        if (!id) return;
        socket.join(`user_channel_${id}`); 
        console.log(`User ${id} is online and ready for invites.`);
    });

    // Host clicks "Invite" next to a friend's name
    socket.on('send-lobby-invite', (data) => {
        const targetUserId = Number(data.targetUserId);
        console.log(`Invite sent to User ${targetUserId} for Room ${data.roomId || data.room_id}`);
        if (!targetUserId) return;
        socket.to(`user_channel_${targetUserId}`).emit('receive-invite', {
            hostName: data.hostName || data.sender_name,
            hostId: data.hostId || data.sender_id || null,
            sender_name: data.hostName || data.sender_name,
            sender_id: data.hostId || data.sender_id || null,
            roomId: data.roomId || data.room_id,
            room_id: data.roomId || data.room_id,
            message: data.message || 'invited you to a watch party.'
        });
    });

    socket.on('send_message', (data) => {
        const room = String(data?.room ?? socket._roomId ?? '');
        if (!room) return;
        socket.to(room).emit('new_message', {
            ...data,
            isSelf: false,
            senderId: socket._userId ?? data?.senderId,
            fromSocketId: socket.id
        });
    });


    // --- 2. Watch Party Room & WebRTC Signaling Events ---

    socket.on('join-room', async (roomId, userId, userName) => {
        const room = String(roomId ?? '');
        if (!room) return;

        const payload = {
            socketId: socket.id,
            userId,
            userName: userName || 'Guest'
        };

        const existing = [];
        const roomSet = io.sockets.adapter.rooms.get(room);
        if (roomSet) {
            for (const id of roomSet) {
                if (id === socket.id) continue;
                const peer = io.sockets.sockets.get(id);
                existing.push({
                    socketId: id,
                    userId: peer?._userId,
                    userName: peer?._userName || 'Guest'
                });
            }
        }

        await socket.join(room);
        socket._roomId = room;
        socket._userId = userId;
        socket._userName = payload.userName;
        console.log(`User ${userId} (${socket.id}) joined room ${room}`);

        socket.emit('existing-users', existing);
        socket.to(room).emit('user-connected', payload);
    });

    socket.on('offer', (data) => {
        if (!data?.targetSocketId) return;
        io.to(data.targetSocketId).emit('offer', {
            ...data,
            fromSocketId: data.fromSocketId || socket.id
        });
    });

    socket.on('answer', (data) => {
        if (!data?.targetSocketId) return;
        io.to(data.targetSocketId).emit('answer', {
            ...data,
            fromSocketId: data.fromSocketId || socket.id
        });
    });

    socket.on('ice-candidate', (data) => {
        if (!data?.targetSocketId) return;
        io.to(data.targetSocketId).emit('ice-candidate', {
            ...data,
            fromSocketId: data.fromSocketId || socket.id
        });
    });

    socket.on('toggle-mic', (isMuted) => {
        if (socket._roomId) {
            socket.to(socket._roomId).emit('peer-mic-changed', {
                userId: socket._userId,
                socketId: socket.id,
                isMuted
            });
        }
    });

    socket.on('toggle-video', (isVideoOn) => {
        if (socket._roomId) {
            socket.to(socket._roomId).emit('peer-video-changed', {
                userId: socket._userId,
                socketId: socket.id,
                isVideoOn
            });
        }
    });

    socket.on('disconnect', () => {
        if (socket._roomId) {
            socket.to(socket._roomId).emit('user-disconnected', {
                userId: socket._userId,
                socketId: socket.id
            });
        }
        console.log(`User disconnected: ${socket.id}`);
    });
});
