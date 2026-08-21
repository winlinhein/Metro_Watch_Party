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
        socket.join(`user_channel_${userId}`); 
        console.log(`User ${userId} is online and ready for invites.`);
    });

    // Host clicks "Invite" next to a friend's name
    socket.on('send-lobby-invite', (data) => {
        console.log(`Invite sent to User ${data.targetUserId} for Room ${data.roomId}`);
        // Emit only to the specific friend's personal channel
        socket.to(`user_channel_${data.targetUserId}`).emit('receive-invite', {
            hostName: data.hostName,
            roomId: data.roomId
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