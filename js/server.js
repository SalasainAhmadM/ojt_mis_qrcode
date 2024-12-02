const express = require('express');
const http = require('http');
const { Server } = require('socket.io');

const app = express();
const server = http.createServer(app);
const io = new Server(server);

// Handle connection
io.on('connection', (socket) => {
    console.log('A user connected:', socket.id);

    // Listen for messages
    socket.on('sendMessage', (data) => {
        console.log('Message received:', data);

        // Broadcast message to the recipient
        io.to(data.receiverId).emit('receiveMessage', data);
    });

    // Listen for typing status
    socket.on('typing', (data) => {
        io.to(data.receiverId).emit('typing', data.senderId);
    });

    // Handle disconnection
    socket.on('disconnect', () => {
        console.log('User disconnected:', socket.id);
    });
});

server.listen(3000, () => {
    console.log('Server running on http://localhost:3000');
});
