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


    // --- 2. Watch Party Room & WebRTC Signaling Events ---

    // User joins a specific Watch Party Room
    socket.on('join-room', (roomId, userId) => {
        socket.join(roomId);
        console.log(`User ${userId} joined room ${roomId}`);
        
        // Tell everyone else in the room that a new user connected
        socket.to(roomId).emit('user-connected', userId);
    });

    // WebRTC Signaling: Relaying the "Offer" to connect video
    socket.on('offer', (data) => {
        socket.to(data.targetSocketId).emit('offer', data);
    });

    // WebRTC Signaling: Relaying the "Answer"
    socket.on('answer', (data) => {
        socket.to(data.targetSocketId).emit('answer', data);
    });

    // WebRTC Signaling: Relaying network connection details
    socket.on('ice-candidate', (data) => {
        socket.to(data.targetSocketId).emit('ice-candidate', data);
    });

    // --- 3. Disconnect ---
    socket.on('disconnect', () => {
        console.log(`User disconnected: ${socket.id}`);
    });
});