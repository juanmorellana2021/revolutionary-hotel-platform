<!-- AI Chat Widget for Revolutionary Hotel Platform -->
<div id="ai-chat-widget" class="chat-widget">
    <div id="chat-toggle" class="chat-toggle">
        <span class="chat-icon">🤖</span>
        <span class="chat-text">AI Assistant</span>
        <span id="chat-notification" class="chat-notification" style="display: none;">1</span>
    </div>
    
    <div id="chat-window" class="chat-window" style="display: none;">
        <div class="chat-header">
            <div class="chat-title">
                <span class="ai-icon">🤖</span>
                <span>AI Hotel Assistant</span>
                <span class="online-indicator">●</span>
            </div>
            <button id="chat-minimize" class="chat-minimize">−</button>
        </div>
        
        <div class="chat-messages" id="chat-messages">
            <div class="message ai-message">
                <div class="message-avatar">🤖</div>
                <div class="message-content">
                    <div class="message-bubble">
                        Hello! I'm your AI assistant. I can help you with HotelCoins, loyalty points, bookings, and local recommendations. How can I assist you today?
                    </div>
                    <div class="message-time"><?php echo date('g:i A'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="quick-responses" id="quick-responses">
            <button class="quick-btn" data-message="How do I earn HotelCoins?">🪙 Earn HotelCoins</button>
            <button class="quick-btn" data-message="What are my loyalty benefits?">💎 Loyalty Benefits</button>
            <button class="quick-btn" data-message="How do I make a booking?">📅 Make Booking</button>
            <button class="quick-btn" data-message="Show local restaurants">🍽️ Local Dining</button>
        </div>
        
        <div class="chat-input-container">
            <div class="chat-typing" id="chat-typing" style="display: none;">
                <span class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
                AI is typing...
            </div>
            <form id="chat-form" class="chat-form">
                <input type="text" id="chat-input" placeholder="Ask me anything about the hotel..." autocomplete="off">
                <button type="submit" id="chat-send" class="chat-send">
                    <span class="send-icon">→</span>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* AI Chat Widget Styles */
.chat-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.chat-toggle {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 25px;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    position: relative;
}

.chat-toggle:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
}

.chat-icon {
    font-size: 20px;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-5px); }
    60% { transform: translateY(-3px); }
}

.chat-notification {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.chat-window {
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 350px;
    height: 500px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.chat-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}

.online-indicator {
    color: #2ecc71;
    font-size: 12px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.chat-minimize {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    padding: 5px;
    line-height: 1;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.message {
    display: flex;
    gap: 10px;
}

.message.user-message {
    flex-direction: row-reverse;
}

.message-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.ai-message .message-avatar {
    background: #f0f0f0;
}

.user-message .message-avatar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.message-content {
    flex: 1;
    max-width: 250px;
}

.message-bubble {
    padding: 12px 16px;
    border-radius: 18px;
    line-height: 1.4;
    word-wrap: break-word;
}

.ai-message .message-bubble {
    background: #f0f0f0;
    color: #333;
}

.user-message .message-bubble {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.message-time {
    font-size: 11px;
    color: #999;
    margin-top: 5px;
    text-align: center;
}

.quick-responses {
    padding: 15px 20px;
    border-top: 1px solid #eee;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.quick-btn {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    padding: 8px 12px;
    border-radius: 15px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.quick-btn:hover {
    background: #e9ecef;
    transform: translateY(-1px);
}

.chat-input-container {
    border-top: 1px solid #eee;
    padding: 15px 20px;
}

.chat-typing {
    padding: 10px 0;
    color: #666;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.typing-indicator {
    display: flex;
    gap: 3px;
}

.typing-indicator span {
    width: 4px;
    height: 4px;
    background: #666;
    border-radius: 50%;
    animation: typing 1.4s infinite ease-in-out;
}

.typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
.typing-indicator span:nth-child(2) { animation-delay: -0.16s; }

@keyframes typing {
    0%, 80%, 100% {
        transform: scale(0);
        opacity: 0.5;
    }
    40% {
        transform: scale(1);
        opacity: 1;
    }
}

.chat-form {
    display: flex;
    gap: 10px;
    align-items: center;
}

#chat-input {
    flex: 1;
    border: 1px solid #e9ecef;
    padding: 12px 15px;
    border-radius: 20px;
    outline: none;
    font-size: 14px;
}

#chat-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.chat-send {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.chat-send:hover {
    transform: scale(1.05);
}

.chat-send:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.send-icon {
    font-size: 16px;
    font-weight: bold;
}

/* Mobile Responsive */
@media (max-width: 480px) {
    .chat-window {
        width: calc(100vw - 40px);
        height: 70vh;
        bottom: 80px;
        right: 20px;
    }
    
    .chat-widget {
        right: 20px;
        bottom: 20px;
    }
}

/* Animation for new messages */
.message.new-message {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
class AIChat {
    constructor() {
        this.isOpen = false;
        this.isTyping = false;
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadChatHistory();
    }
    
    bindEvents() {
        // Toggle chat window
        document.getElementById('chat-toggle').addEventListener('click', () => {
            this.toggleChat();
        });
        
        // Minimize chat
        document.getElementById('chat-minimize').addEventListener('click', () => {
            this.closeChat();
        });
        
        // Send message form
        document.getElementById('chat-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
        
        // Quick response buttons
        document.querySelectorAll('.quick-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const message = btn.dataset.message;
                this.sendMessage(message);
            });
        });
        
        // Enter key to send
        document.getElementById('chat-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
    }
    
    toggleChat() {
        const chatWindow = document.getElementById('chat-window');
        const chatToggle = document.getElementById('chat-toggle');
        
        if (this.isOpen) {
            this.closeChat();
        } else {
            chatWindow.style.display = 'flex';
            this.isOpen = true;
            this.hideNotification();
            document.getElementById('chat-input').focus();
        }
    }
    
    closeChat() {
        document.getElementById('chat-window').style.display = 'none';
        this.isOpen = false;
    }
    
    async sendMessage(message = null) {
        const input = document.getElementById('chat-input');
        const messageText = message || input.value.trim();
        
        if (!messageText || this.isTyping) return;
        
        // Clear input
        input.value = '';
        
        // Add user message to chat
        this.addMessage(messageText, 'user');
        
        // Show typing indicator
        this.showTyping();
        
        try {
            // Send to AI API
            const response = await fetch('ai_chat_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: messageText,
                    context: this.getContextData()
                })
            });
            
            const data = await response.json();
            
            // Hide typing indicator
            this.hideTyping();
            
            if (data.success) {
                // Add AI response
                this.addMessage(data.response, 'ai');
            } else {
                // Show error message
                this.addMessage('Sorry, I\'m having trouble right now. Please try again or contact our staff for assistance.', 'ai');
            }
            
        } catch (error) {
            this.hideTyping();
            this.addMessage('Sorry, I\'m having trouble connecting right now. Please try again later.', 'ai');
        }
    }
    
    addMessage(text, sender) {
        const messagesContainer = document.getElementById('chat-messages');
        const messageElement = document.createElement('div');
        messageElement.className = `message ${sender}-message new-message`;
        
        const avatar = sender === 'ai' ? '🤖' : '👤';
        const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        messageElement.innerHTML = `
            <div class="message-avatar">${avatar}</div>
            <div class="message-content">
                <div class="message-bubble">${this.formatMessage(text)}</div>
                <div class="message-time">${time}</div>
            </div>
        `;
        
        messagesContainer.appendChild(messageElement);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Show notification if chat is closed
        if (!this.isOpen) {
            this.showNotification();
        }
    }
    
    formatMessage(text) {
        // Basic formatting for AI responses
        return text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/\n/g, '<br>');
    }
    
    showTyping() {
        this.isTyping = true;
        document.getElementById('chat-typing').style.display = 'flex';
        document.getElementById('chat-send').disabled = true;
        
        const messagesContainer = document.getElementById('chat-messages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    hideTyping() {
        this.isTyping = false;
        document.getElementById('chat-typing').style.display = 'none';
        document.getElementById('chat-send').disabled = false;
    }
    
    showNotification() {
        document.getElementById('chat-notification').style.display = 'flex';
    }
    
    hideNotification() {
        document.getElementById('chat-notification').style.display = 'none';
    }
    
    getContextData() {
        // Add any relevant page context
        return {
            page: window.location.pathname,
            timestamp: new Date().toISOString()
        };
    }
    
    loadChatHistory() {
        // Could implement chat history loading here
        // For now, just show welcome message
    }
}

// Initialize chat when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.aiChat = new AIChat();
});
</script>