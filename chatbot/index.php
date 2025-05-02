<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unimaid Resources Bot</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
    /* Reset and Base Styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Roboto', sans-serif;
        background: #F5F6FA;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 10px;
        overflow-x: hidden;
    }

    /* Chat Container */
    #chat-container {
        width: 100%;
        max-width: 400px;
        height: calc(100vh - 20px);
        background: #FFFFFF;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* Chat Header */
    .chat-header {
        padding: 15px 20px;
        background: #5438E0FF;
        color: white;
        font-size: 18px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    .chat-header img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .chat-header .status {
        font-size: 14px;
        opacity: 0.8;
    }

    /* Chat Box */
    #chat-box {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background: #F5F6FA;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    /* Messages */
    .message {
        padding: 12px 18px;
        margin: 5px 0;
        border-radius: 20px;
        max-width: 80%;
        line-height: 1.5;
        font-size: 15px;
        position: relative;
        animation: fadeIn 0.3s ease;
    }

    /* .bot-message {
        background: #FFFFFF;
        color: #333;
        align-self: flex-start;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .user-message {
        background: #5438E0FF;
        color: white;
        align-self: flex-end;
        box-shadow: 0 2px 8px rgba(74, 144, 226, 0.2);
    } */

    /* Input Container */
    .input-container {
        display: flex;
        padding: 15px 20px;
        background: #FFFFFF;
        border-top: 1px solid #E5E7EB;
        flex-shrink: 0;
        align-items: center;
        gap: 10px;
    }

    /* Chat Input */
    #user-input {
        flex: 1;
        padding: 10px 18px;
        border: 1px solid #D1D5DB;
        border-radius: 20px;
        background: #F9FAFB;
        font-size: 15px;
        outline: none;
        transition: all 0.2s ease;
        min-height: 40px;
        max-height: 100px;
        resize: vertical;
    }

    #user-input:focus {
        border-color: #5438E0FF;
        box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.15);
        background: #FFFFFF;
    }

    #user-input::placeholder {
        color: #9CA3AF;
    }

    /* Send Button */
    .send-button {
        width: 40px;
        height: 40px;
        background: #5438E0FF;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(74, 144, 226, 0.2);
    }

    .send-button:hover {
        background: #5438E0FF;
        transform: scale(1.05);
    }

    .send-button:active {
        transform: scale(0.95);
    }

    .send-button::before {
        content: '➤';
        color: white;
        font-size: 18px;
        transform: rotate(-45deg);
    }

    /* Scrollbar */
    #chat-box::-webkit-scrollbar {
        width: 6px;
    }

    #chat-box::-webkit-scrollbar-thumb {
        background: #D1D5DB;
        border-radius: 3px;
    }

    #chat-box::-webkit-scrollbar-thumb:hover {
        background: #9CA3AF;
    }

    /* Animation */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive Design */
    @media (max-width: 480px) {
        body {
            padding: 0;
        }

        #chat-container {
            height: 100vh;
            border-radius: 0;
            box-shadow: none;
        }

        .chat-header {
            font-size: 16px;
            padding: 12px 15px;
        }

        .chat-header img {
            width: 36px;
            height: 36px;
        }

        .chat-header .status {
            font-size: 12px;
        }

        #chat-box {
            padding: 15px;
        }

        .message {
            max-width: 85%;
            padding: 10px 14px;
            font-size: 14px;
        }

        .input-container {
            padding: 10px 15px;
        }

        #user-input {
            padding: 8px 14px;
            font-size: 14px;
            min-height: 36px;
        }

        .send-button {
            width: 36px;
            height: 36px;
        }

        .send-button::before {
            font-size: 16px;
        }
    }

    @media (max-width: 360px) {
        .chat-header {
            font-size: 14px;
        }

        .message {
            font-size: 13px;
        }

        #user-input {
            font-size: 13px;
        }
    }

    @media (max-height: 500px) {
        #chat-container {
            height: 100vh;
        }

        .chat-header {
            padding: 10px 15px;
        }

        .input-container {
            padding: 8px 15px;
        }
    }

    /* Bot Message (Received) */
    .bot-message {
        align-self: flex-start;
        background: #e4e7eb;
        color: #2c2f33;
        padding: 12px 16px;
        border-radius: 18px 18px 18px 4px;
        max-width: 80%;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        font-size: 15px;
        line-height: 1.5;
        position: relative;
    }

    /* User Message (Sent) */
    .user-message {
        align-self: flex-end;
        background: linear-gradient(to right, #5438E0FF, #357ABD);
        color: white;
        padding: 12px 16px;
        border-radius: 18px 18px 4px 18px;
        max-width: 80%;
        box-shadow: 0 2px 6px rgba(74, 144, 226, 0.2);
        font-size: 15px;
        line-height: 1.5;
        position: relative;
    }
    </style>
</head>

<body>
    <div id="chat-container">
        <div class="chat-header">
            <img src="data:image/jpeg;base64,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"
                alt="Avatar">
            <div>
                <div>Unimaid Resources Chat bot</div>
                <div class="status">Online</div>
            </div>
        </div>
        <div id="chat-box">
            <!-- Messages will be added dynamically -->
        </div>
        <div class="input-container">
            <textarea id="user-input" placeholder="Type your message here..."></textarea>
            <button class="send-button"></button>
        </div>
    </div>
    <script>
    const chatBox = document.getElementById('chat-box');
    const userInput = document.getElementById('user-input');

    function appendMessage(message, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', sender === 'user' ? 'user-message' : 'bot-message');
        messageDiv.textContent = message;
        chatBox.appendChild(messageDiv);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function handleSend() {
        const message = userInput.value.trim();
        if (!message) return;

        // Show user message
        appendMessage(message, 'user');
        userInput.value = '';

        // Simulate bot response (replace this with your actual bot logic)
        setTimeout(() => {
            const reply = "You said: " + message; // <-- change this to your bot response logic
            appendMessage(reply, 'bot');
        }, 500);
    }

    // Send on button click or Enter key
    document.querySelector('.send-button').addEventListener('click', handleSend);
    userInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    });
</script>

    <script type="text/javascript" src="script.js"></script>
</body>

</html>