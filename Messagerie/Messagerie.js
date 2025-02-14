document.addEventListener('DOMContentLoaded', function() {
    const messageInput = document.getElementById('messageText');
    const sendMessageButton = document.getElementById('sendMessage');
    const messagesContainer = document.querySelector('.messages');

    function addMessage(text, sent = true) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('message');
        if (sent) {
            messageElement.classList.add('sent');
        }
        messageElement.textContent = text;
        messagesContainer.appendChild(messageElement);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    sendMessageButton.addEventListener('click', () => {
        const messageText = messageInput.value.trim();
        if (messageText !== '') {
            addMessage(messageText);
            messageInput.value = '';
        }
    });

    messageInput.addEventListener('keypress', (event) => {
        if (event.key === 'Enter') {
            sendMessageButton.click();
        }
    });
});
