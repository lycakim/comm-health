<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('chatWidget', () => ({
        init() {
           const chatWindow = document.getElementById('chat-window');
            const chatBubble = document.getElementById('chat-bubble');
            const chatIcon = document.getElementById('chat-icon');
            const closeIcon = document.getElementById('close-icon');
            const closeChatBtn = document.getElementById('close-chat');
            
            let isChatOpen = false;
            
            // Function to show chat window
            function showChatWindow() {
                chatWindow.style.display = 'block';
                chatIcon.style.display = 'none';
                closeIcon.style.display = 'block';
                isChatOpen = true;
            }
            
            // Function to hide chat window
            function hideChatWindow() {
                chatWindow.style.display = 'none';
                chatIcon.style.display = 'block';
                closeIcon.style.display = 'none';
                isChatOpen = false;
            }
            
            // Chat bubble functionality - toggle open/close
            if (chatBubble) {
                chatBubble.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (isChatOpen) {
                        hideChatWindow();
                    } else {
                        showChatWindow();
                    }
                });
            }

            if (closeChatBtn) {
                closeChatBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    hideChatWindow();
                });
            }
            
            // Optional: Close chat when clicking outside (uncomment if needed)
            /*
            document.addEventListener('click', function(e) {
                const chatContainer = e.target.closest('#chat-window, #chat-bubble');
                if (!chatContainer && chatWindow.style.display !== 'none') {
                    hideChatWindow();
                }
            });
            */
            
            // Optional: Send message functionality
            const sendBtn = document.querySelector('.send-btn');
            const messageInput = document.querySelector('.message-input');
            
            if (sendBtn && messageInput) {
                // Send on button click
                sendBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    sendMessage();
                });
                
                // Send on Enter key press
                messageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }
            
            function sendMessage() {
                const message = messageInput?.value.trim();
                if (message) {
                    // Add your message sending logic here
                    console.log('Sending message:', message);
                    
                    // Clear input
                    if (messageInput) {
                        messageInput.value = '';
                    }
                    
                    // You can add code here to:
                    // - Add message to chat body
                    // - Send to backend via AJAX
                    // - Handle response
                }
            }
        }
    }))
})
</script>
<div x-data="chatWidget" 
        x-init="init()" 
        class="fixed bottom-4 right-4 z-50 {{ auth()->user()->isResident() ? 'hidden' : '' }}"
        style="position: absolute; right: 20px; bottom: 20px;">
    <div class="flex flex-wrap gap-3 items-end">
        <div id="chat-window"
             class="chat-window rounded w-[320px] bg-white dark:bg-gray-800 text-gray-900 dark:text-white mb-3 border border-gray-200 dark:border-gray-700 overflow-hidden backdrop-blur-sm" 
             style="box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04), 0 0 0 1px rgba(0, 168, 107, 0.1); display: none;">
            <div class="chat-header flex items-center justify-between px-6 py-4 border-b dark:border-gray-700 dark:text-white relative">
                <h3 class="text-lg font-semibold">Bruce Banner</h3>
                <button id="close-chat" class="close-btn text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-gray-100 text-xl leading-none">&times;</button>
            </div>
            <div class="chat-body space-y-4 p-4 max-h-80 overflow-y-auto bg-gray-50 dark:bg-gray-900">
                <div class="message incoming">
                    <div class="flex items-start space-x-2">
                        <div class="flex-1">
                            <div class="message-content bg-white dark:bg-gray-700 p-3 rounded shadow-sm border border-gray-200 dark:border-gray-600">
                                I'm all good, thanks! How can I help you today?
                            </div>
                            <div class="message-time text-xs text-gray-500 dark:text-gray-400 mt-1 ml-2">Bruce Banner • 2:34 PM</div>
                        </div>
                    </div>
                </div>
                <div class="message outgoing">
                    <div class="flex items-start space-x-2 justify-end">
                        <div class="flex-1 text-right">
                            <div class="message-content bg-gray-200 dark:bg-gray-800 dark:text-white p-3 rounded shadow-sm inline-block max-w-xs">
                                Hello, what's up?
                            </div>
                            <div class="message-time text-xs text-gray-500 dark:text-gray-400 mt-1 mr-2">You • 2:33 PM</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="chat-input flex items-center gap-3 p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                <input type="text"
                       placeholder="Type your message..."
                       class="message-input flex-1 px-4 py-3 rounded-full border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200 placeholder-gray-500 dark:placeholder-gray-400">
                <button class="send-btn p-3 dark:text-white rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2 21L23 12L2 3V10L17 12L2 14V21Z"/>
                    </svg>
                </button>
            </div>
        </div>
    
        <div id="chat-bubble"
            class="chat-bubble cursor-pointer hover:scale-105 transition-transform duration-200"
            style="width: 50px; height: 50px;
                    background: #00a86b; color: white; border: none;
                    border-radius: 50%; display: flex; align-items: center; justify-content: center;
                    box-shadow: 0 4px 12px rgba(0, 168, 107, 0.3);">
            <!-- Icon changes based on chat window state -->
            <svg id="chat-icon" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M20 2H4C2.9 2 2 2.9 2 4V16C2 17.1 2.9 18 4 18H18L22 22V4C22 2.9 21.1 2 20 2ZM20 17.17L18.83 16H4V4H20V17.17Z" fill="white"/>
                <circle cx="12" cy="10" r="2" fill="white"/>
                <circle cx="8" cy="10" r="1" fill="white"/>
                <circle cx="16" cy="10" r="1" fill="white"/>
            </svg>
            <!-- Close icon (initially hidden) -->
            <svg id="close-icon" width="24" height="24" viewBox="0 0 24 24" fill="white" style="display: none;">
                <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z"/>
            </svg>
        </div>
    </div>
</div>

<style>
/* Additional styles for smooth transitions */
.chat-window {
    transition: all 0.3s ease-in-out;
}

.chat-bubble {
    transition: all 0.3s ease-in-out;
}

.chat-bubble:hover {
    background: #008f5a !important;
}

.close-btn:hover {
    transform: scale(1.1);
}
</style>