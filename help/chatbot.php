<?php
require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_login();
require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
    <div class="card" style="height: 80vh; display: flex; flex-direction: column;">
        
        <!-- Header -->
        <div style="border-bottom: 1px solid var(--border-light); padding-bottom: 16px; margin-bottom: 16px; display:flex; align-items:center; gap:10px;">
            <span style="font-size:1.5rem;">🤖</span>
            <div>
                <h2 style="margin:0; font-size:1.5rem;">Help Chatbot</h2>
                <p class="muted" style="margin:0; font-size:0.9rem;">Ask me anything about English or FeedLearn!</p>
            </div>
        </div>

        <!-- Chat Area -->
        <div id="chat-history" style="flex: 1; overflow-y: auto; padding: 10px; background: rgba(0,0,0,0.02); border-radius: var(--radius-sm); margin-bottom: 16px;">
            <div class="message bot">
                <div class="bubble">Hello! I'm your AI Tutor. How can I help you improve your English today? 👋</div>
            </div>
        </div>

        <!-- Input Area -->
        <form id="chat-form" style="display: flex; gap: 10px;">
            <input type="text" id="chat-input" placeholder="Type your question..." required autocomplete="off" style="flex: 1;">
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
    </div>
    
    <div style="text-align: center; margin-top: 10px;">
        <a href="<?= BASE_URL ?>/dashboard.php" class="btn">Back to Dashboard</a>
    </div>
</section>

<style>
/* Chat Styles */
.message {
    display: flex;
    margin-bottom: 12px;
}
.message.user {
    justify-content: flex-end;
}
.message.bot {
    justify-content: flex-start;
}
.bubble {
    max-width: 70%;
    padding: 12px 18px;
    border-radius: 18px;
    font-size: 1rem;
    line-height: 1.5;
    position: relative;
}
.message.user .bubble {
    background: var(--primary);
    color: #fff;
    border-bottom-right-radius: 4px;
    box-shadow: 0 2px 5px rgba(244, 63, 94, 0.2);
}
.message.bot .bubble {
    background: #fff;
    border: 1px solid var(--border-light);
    color: var(--text-main);
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.typing {
    font-style: italic;
    color: var(--text-muted);
    font-size: 0.8rem;
    margin-left: 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');
    const history = document.getElementById('chat-history');

    function appendMessage(text, isUser) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'message ' + (isUser ? 'user' : 'bot');
        msgDiv.innerHTML = `<div class="bubble">${text}</div>`;
        history.appendChild(msgDiv);
        history.scrollTop = history.scrollHeight;
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const msg = input.value.trim();
        if (!msg) return;

        // User Message
        appendMessage(msg, true);
        input.value = '';
        input.disabled = true;

        // Loading
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'typing';
        loadingDiv.innerText = 'AI is typing...';
        history.appendChild(loadingDiv);
        history.scrollTop = history.scrollHeight;

        try {
            const res = await fetch('<?= BASE_URL ?>/services/api_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: msg })
            });
            
            history.removeChild(loadingDiv); // Remove loading
            
            const data = await res.json();
            
            if (data.reply) {
                appendMessage(data.reply, false);
            } else if (data.error) {
                appendMessage("Error: " + data.error, false);
            }
        } catch (err) {
            history.removeChild(loadingDiv);
            appendMessage("Connection error. Please try again.", false);
        }

        input.disabled = false;
        input.focus();
    });
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
