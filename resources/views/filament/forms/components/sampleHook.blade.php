<div class="fixed bottom-4 right-4 z-50"  style="display: absolute; right: 20px; bottom: 20px;">
    <div class="flex flex-wrap gap-3 items-end">
        <div id="chat-window"
             class="chat-window rounded-xl shadow-sm w-[300px] bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white" style="shadow: 0 4px 12px rgba(0, 168, 107, 0.3);">
            <div class="chat-header flex items-center justify-between border-b px-4 py-2 border-gray-700 dark:border-gray-900">
                <h3 class="text-lg font-bold">Bruce Banner</h3>
                <button id="close-chat" class="close-btn text-gray-600 dark:text-gray-300">&times;</button>
            </div>
            <div class="chat-body mt-2 space-y-3 p-4">
                <div class="message incoming">
                    <div class="message-content bg-gray-200 dark:bg-gray-700 p-2 rounded-lg">
                        I'm all good, thanks!
                    </div>
                    <div class="message-sender text-xs text-gray-500 dark:text-gray-400">Tony Stark</div>
                </div>
                <div class="message outgoing text-right">
                    <div class="message-content bg-gray-200 dark:bg-gray-700 dark:text-white p-2 rounded-lg inline-block shadow-sm">
                        Hello, what's up?
                    </div>
                    <div class="message-time text-xs text-gray-500 dark:text-gray-400">You</div>
                </div>
            </div>
            <div class="chat-input mt-3 flex items-center gap-2 p-4">
                <input type="text"
                       placeholder="Type your message here..."
                       class="message-input flex-1 px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button class="send-btn text-blue-600 dark:text-blue-400">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2 21L23 12L2 3V10L17 12L2 14V21Z"/>
                    </svg>
                </button>
            </div>
        </div>
    
        <div id="chat-bubble"
            class="chat-window shadow-lg"
            style="width: 50px; height: 50px;
                    background: #00a86b; color: white; border: none;
                    border-radius: 50%; display: flex; align-items: center; justify-content: center;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M20 2H4C2.9 2 2 2.9 2 4V16C2 17.1 2.9 18 4 18H18L22 22V4C22 2.9 21.1 2 20 2ZM20 17.17L18.83 16H4V4H20V17.17Z" fill="white"/>
                <circle cx="12" cy="10" r="2" fill="white"/>
                <circle cx="8" cy="10" r="1" fill="white"/>
                <circle cx="16" cy="10" r="1" fill="white"/>
            </svg>
        </div>
    </div>
</div>