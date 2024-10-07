document.addEventListener('DOMContentLoaded', function () {
    const adviserItems = document.querySelectorAll('.adviser-item');
    const chatMessages = document.getElementById('chatMessages');
    const chatAdviserName = document.getElementById('chatAdviserName');
    const chatAdviserImage = document.getElementById('chatAdviserImage');
    const messageInput = document.getElementById('messageInput');
    const sendMessageBtn = document.getElementById('sendMessageBtn');

    let selectedAdviserId = null;

    adviserItems.forEach(item => {
        item.addEventListener('click', function () {
            selectedAdviserId = this.getAttribute('data-adviser-id');
            const adviserName = this.querySelector('.adviser-name').textContent;
            const adviserImage = this.querySelector('img').src;

            chatAdviserName.textContent = adviserName;
            chatAdviserImage.src = adviserImage;

            // Enable the input and button
            messageInput.disabled = false;
            sendMessageBtn.disabled = false;

            // Clear previous messages
            chatMessages.innerHTML = '';

            // Hide the unread notification for this adviser
            const unreadNotification = this.querySelector('.unread-count');
            if (unreadNotification) {
                unreadNotification.style.display = 'none';
            }

            // Fetch the conversation with the clicked adviser
            fetchConversation(selectedAdviserId);
        });
    });

    // Function to send a message
    sendMessageBtn.addEventListener('click', function () {
        const message = messageInput.value.trim();

        if (message === '' || !selectedAdviserId) {
            return; // Do nothing if the message is empty or no adviser is selected
        }

        sendMessage(selectedAdviserId, message);
    });

    function fetchConversation(adviserId) {
        fetch('fetch_messages.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `adviser_id=${adviserId}`
        })
            .then(response => response.json())
            .then(messages => {
                if (messages.error) {
                    console.error(messages.error);
                    return;
                }

                // Display the fetched messages
                messages.forEach(msg => {
                    const messageDiv = document.createElement('div');
                    messageDiv.classList.add('message');

                    // Differentiate between sent and received messages
                    if (msg.sender_type === 'company') {
                        messageDiv.classList.add('sent');
                    } else {
                        messageDiv.classList.add('received');
                    }

                    // Message content and timestamp
                    messageDiv.innerHTML = `
            ${msg.message}
            <span class="timestamp">${new Date(msg.timestamp).toLocaleString()}</span>
        `;

                    chatMessages.appendChild(messageDiv);
                });

                // Scroll to the bottom of the chat
                chatMessages.scrollTop = chatMessages.scrollHeight;
            })
            .catch(error => console.error('Error fetching messages:', error));
    }

    function sendMessage(adviserId, message) {
        fetch('send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `adviser_id=${adviserId}&message=${encodeURIComponent(message)}`
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Clear the input field
                    messageInput.value = '';

                    // Display the sent message in the chat box
                    const messageDiv = document.createElement('div');
                    messageDiv.classList.add('message', 'sent');
                    messageDiv.innerHTML = `
            ${message}
            <span class="timestamp">${new Date().toLocaleString()}</span>
        `;

                    chatMessages.appendChild(messageDiv);

                    // Scroll to the bottom of the chat
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                } else {
                    console.error('Error sending message:', result.error);
                }
            })
            .catch(error => console.error('Error sending message:', error));
    }
});
