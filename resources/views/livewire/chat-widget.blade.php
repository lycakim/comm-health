<div x-data="{ 
        isChatOpen: @entangle('isChatOpen'),
        viewMode: @entangle('viewMode'),
        scrollToBottom() {
            this.$nextTick(() => {
                const chatBody = this.$refs.chatBody;
                if (chatBody) {
                    chatBody.scrollTop = chatBody.scrollHeight;
                }
            });
        }
     }"
     x-init="
        $watch('isChatOpen', value => {
            if (value) scrollToBottom();
        });
        $watch('viewMode', value => {
            if (value === 'messages') scrollToBottom();
        });
        // Listen for Livewire events
        Livewire.on('scroll-to-bottom', () => scrollToBottom());
        Livewire.on('message-sent', () => scrollToBottom());
        Livewire.on('message-received', () => scrollToBottom());
        
        // Listen for notification click events
        Livewire.on('open-conversation-from-notification', (event) => {
            const data = event[0] || event;
            const userId = data?.userId || data?.userId || null;
            if (userId) {
                const component = Livewire.find(@js($this->getId()));
                if (component) {
                    component.call('openConversationFromNotification', { userId: userId });
                }
            }
        });
        
        // Watch for message changes
        $watch('$wire.messages', () => scrollToBottom());
     "
     class="fixed bottom-4 right-4 z-50"
     style="position: fixed; right: 20px; bottom: 20px;">
    
    <div class="flex flex-wrap gap-3 items-end">
        <!-- Chat Window -->
        <div x-show="isChatOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95"
             class="rounded w-[320px] bg-white dark:bg-gray-800 text-gray-900 dark:text-white mb-3 border border-gray-200 dark:border-gray-700 overflow-hidden backdrop-blur-sm" 
             style="box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04), 0 0 0 1px rgba(0, 168, 107, 0.1);">
            
            <!-- Chat Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700 dark:text-white">
                <div class="flex items-center gap-2 flex-1">
                    <!-- Back Button (only show in messages view) -->
                    <button x-show="viewMode === 'messages'"
                            @click="$wire.showUserList()"
                            class="text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-gray-100 transition-transform hover:scale-110">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    
                    <!-- Header Title -->
                    <div class="flex-1">
                        <template x-if="viewMode === 'users'">
                            <h3 class="text-sm font-semibold">Select a user to chat</h3>
                        </template>
                        <template x-if="viewMode === 'messages'">
                            <h3 class="text-sm font-semibold">
                                {{ $users[$selectedUserId] ?? 'Chat' }}
                            </h3>
                        </template>
                    </div>
                </div>
                <button @click="isChatOpen = false" 
                        class="text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-gray-100 text-xl leading-none hover:scale-110 transition-transform">
                    &times;
                </button>
            </div>
            
            <!-- User List View -->
            <div x-show="viewMode === 'users'"
                 class="overflow-y-auto bg-gray-50 dark:bg-gray-900"
                 style="height: 350px; max-height: 350px; width: 350px; max-width: 350px;">
                @if(empty($users))
                    <div class="text-center text-gray-500 dark:text-gray-400 py-8 px-4">
                        <p>No other users available</p>
                    </div>
                @else
                    <div>
                        @foreach($users as $userId => $userName)
                            <button wire:click="selectUser({{ $userId }})"
                                    class="w-full px-4 py-5 transition-colors text-left flex items-center justify-between hover:bg-gray-50"
                                    style="border-bottom: 1px solid rgba(75, 85, 99, 0.2);"
                                    onmouseover="
                                        if (document.documentElement.classList.contains('dark')) {
                                            this.style.backgroundColor='rgba(255, 255, 255, 0.05)';
                                        } else {
                                            this.style.backgroundColor='rgba(229, 231, 235, 0.9)'; // gray-200, a bit darker
                                        }
                                    "
                                    onmouseout="this.style.backgroundColor='transparent'">
                                <span class="text-sm font-medium">
                                    {{ $userName }}
                                </span>
                                @if(isset($unreadCounts[$userId]) && $unreadCounts[$userId] > 0)
                                    <span style="background-color: #ef4444; color: white; font-size: 0.75rem; font-weight: 600; border-radius: 9999px; padding: 0.25rem 0.5rem; min-width: 20px; text-align: center; display: inline-block;">
                                        {{ $unreadCounts[$userId] }}
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            
            <!-- Messages View -->
            <div x-show="viewMode === 'messages'">
                <!-- Chat Body -->
                <div x-ref="chatBody"
                    x-init="$watch('$wire.messages', () => { 
                        $nextTick(() => $refs.chatBody.scrollTop = $refs.chatBody.scrollHeight)
                    })"
                    class="space-y-4 p-4 overflow-y-auto bg-gray-50 dark:bg-gray-900"
                    style="height: 350px; max-height: 350px; width: 350px; max-width: 350px;">
                    @forelse($messages as $messageKey => $messageGroup)
                        <div class="flex items-center justify-center">
                            <span class="text-center text-xs text-gray-500 dark:text-gray-400 mb-2">{{ $messageKey }}</span>
                        </div>
                        @foreach($messageGroup as $message)
                            @if($message['is_mine'])
                                <!-- Outgoing Message -->
                                <div class="message outgoing" wire:key="message-{{ $message['id'] }}">
                                    <div class="flex items-start space-x-2 justify-end">
                                        <div class="flex-1 text-right">
                                            <span class="text-center text-xs text-gray-500 dark:text-gray-400">
                                                {{ $message['created_at'] }}
                                            </span>
                                            <div class="message-content bg-gray-200 dark:bg-gray-800 dark:text-white p-3 rounded shadow-sm inline-block max-w-xs text-left">
                                                {{ $message['message'] }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <!-- Incoming Message -->
                                <div class="message incoming" wire:key="message-{{ $message['id'] }}">
                                    <div class="flex items-start space-x-2">
                                        <div class="flex-1">
                                            <div class="message-content bg-white dark:bg-gray-700 p-3 rounded shadow-sm border border-gray-200 dark:border-gray-600 inline-block max-w-xs">
                                                {{ $message['message'] }}
                                            </div>
                                            <span class="text-center text-xs text-gray-500 dark:text-gray-400">
                                                {{ $message['created_at'] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @empty
                        <div class="text-center text-gray-500 dark:text-gray-400 py-5">
                            No messages yet. Start the conversation!
                        </div>
                    @endforelse
                </div>
                
                <!-- Chat Input -->
                <div class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                    <input type="text"
                           wire:model.lazy="message"
                           @keydown.enter="$wire.sendMessage(); scrollToBottom();"
                           placeholder="Type your message..."
                           class="flex-1 px-4 py-3 rounded-full border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200 placeholder-gray-500 dark:placeholder-gray-400">
                    <button @click="$wire.sendMessage(); scrollToBottom();"
                            wire:loading.attr="disabled"
                            class="p-3 dark:text-white rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg wire:loading.remove wire:target="sendMessage" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2 21L23 12L2 3V10L17 12L2 14V21Z"/>
                        </svg>
                        <svg wire:loading wire:target="sendMessage" class="animate-spin" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <circle cx="12" cy="12" r="10" stroke-width="4" stroke="currentColor" stroke-opacity="0.25"/>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke-width="4" stroke="currentColor" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Chat Bubble -->
        <button @click="$wire.toggleChat()"
                class="relative cursor-pointer hover:scale-105 transition-transform duration-200 hover:bg-[#008f5a]"
                style="width: 50px; height: 50px;
                    background: #00a86b; color: white; border: none;
                    border-radius: 50%; display: flex; align-items: center; justify-content: center;
                    box-shadow: 0 4px 12px rgba(0, 168, 107, 0.3);">
            
            <!-- Unread Message Badge -->
            @if($hasUnreadMessages && !$isChatOpen)
                <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center animate-pulse">
                    !
                </span>
            @endif
            
            <!-- Chat Icon (when closed) -->
            <svg x-show="!isChatOpen" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M20 2H4C2.9 2 2 2.9 2 4V16C2 17.1 2.9 18 4 18H18L22 22V4C22 2.9 21.1 2 20 2ZM20 17.17L18.83 16H4V4H20V17.17Z" fill="white"/>
                <circle cx="12" cy="10" r="2" fill="white"/>
                <circle cx="8" cy="10" r="1" fill="white"/>
                <circle cx="16" cy="10" r="1" fill="white"/>
            </svg>
            <!-- Close Icon (when open) -->
            <svg x-show="isChatOpen" width="24" height="24" viewBox="0 0 24 24" fill="white">
                <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z"/>
            </svg>
        </button>
    </div>
</div>

@push('scripts')
@vite('resources/js/app.js')
    <script>
        console.log('Chat widget script loaded');
        
        // Wait for Echo to be available
        const waitForEcho = () => {
            return new Promise((resolve) => {
                if (typeof window.Echo !== 'undefined' && window.Echo) {
                    console.log('✅ Echo is ready');
                    resolve();
                } else {
                    console.log('⏳ Waiting for Echo...');
                    setTimeout(() => waitForEcho().then(resolve), 100);
                }
            });
        };

        document.addEventListener('livewire:init', async () => {
            console.log('Livewire initialized');
            
            // Wait for Echo to be ready
            await waitForEcho();
            
            let currentChannel = null;
            let userNotificationChannel = null;
            const currentUserId = @js(auth()->id());
            
            // Debug Echo connection
            window.Echo.connector.pusher.connection.bind('connected', () => {
                console.log('✅ Connected to Reverb');
            });

            window.Echo.connector.pusher.connection.bind('error', (err) => {
                console.error('❌ Reverb connection error:', err);
            });
            
            // Subscribe to user notification channel
            if (currentUserId) {
                const userChannelName = `App.Models.User.${currentUserId}`;
                console.log('📡 Subscribing to user notification channel:', userChannelName);
                
                userNotificationChannel = window.Echo.private(userChannelName);
                
                userNotificationChannel
                    .notification((notification) => {
                        console.log('🔔 Notification received in chat widget:', notification);
                        
                        // Laravel wraps broadcast notification data in a 'data' property
                        const notificationData = notification.data || notification;
                        const notificationType = notification.type || notificationData.type;
                        
                        if (notificationType === 'message' || 
                            notificationType === 'App\\Notifications\\MessageNotification' ||
                            notificationType?.includes('MessageNotification')) {
                            
                            // Extract data from notification (check both data property and top-level)
                            const senderId = notificationData.sender_id || notification.sender_id;
                            const senderName = notificationData.sender_name || notification.sender_name || 'Someone';
                            const message = notificationData.message_preview || notification.message_preview || '';
                            const receiverId = notificationData.receiver_id || notification.receiver_id;
                            
                            console.log('📨 Extracted notification data:', {
                                senderId,
                                senderName,
                                message,
                                receiverId,
                                currentUserId
                            });
                            
                            // Only process if we have valid data and it's for the current user
                            if (!senderId || !receiverId) {
                                console.warn('⚠️ Invalid notification data:', notification);
                                return;
                            }
                            
                            if (receiverId != currentUserId) {
                                console.log('⚠️ Notification not for current user, skipping');
                                return;
                            }
                            
                            // Trigger handleIncomingMessage to show Filament notification and update UI
                            const component = Livewire.find(@js($this->getId()));
                            if (component) {
                                console.log('✅ Calling handleIncomingMessage on component');
                                component.call('handleIncomingMessage', {
                                    chat: {
                                        sender_id: senderId,
                                        sender_name: senderName,
                                        message: message,
                                        receiver_id: receiverId,
                                    }
                                });
                            } else {
                                console.warn('⚠️ Component not found, using dispatch fallback');
                                // Fallback: dispatch event
                                Livewire.dispatch('message-received-from-echo', {
                                    chat: {
                                        sender_id: senderId,
                                        sender_name: senderName,
                                        message: message,
                                        receiver_id: receiverId,
                                    }
                                });
                            }
                            
                            // Browser notification
                            if ("Notification" in window && Notification.permission === "granted") {
                                new Notification(`New Message from ${senderName}`, {
                                    body: message,
                                    icon: "/favicon.ico",
                                    badge: "/favicon.ico",
                                    tag: 'message-' + senderId,
                                });
                            }
                        }
                    })
                    .subscribed(() => {
                        console.log('✅ Successfully subscribed to user notification channel');
                    })
                    .error((error) => {
                        console.error('❌ User notification channel subscription error:', error);
                    });
            }
            
            // Function to subscribe to conversation channel
            const subscribeToConversation = (conversationId) => {
                if (!conversationId) {
                    console.warn('⚠️ No conversation ID provided');
                    return;
                }

                // Leave previous channel if any
                if (currentChannel) {
                    console.log('👋 Leaving channel:', currentChannel);
                    window.Echo.leave(currentChannel);
                }

                // Join new conversation channel
                const channelName = `chat.${conversationId}`;
                currentChannel = channelName;

                console.log('📡 Subscribing to channel:', channelName);

                const conversationChannel = window.Echo.private(channelName);
                
                conversationChannel
                    .listen('.MessageSent', (e) => {
                        console.log('✅ Message received from Echo:', e);
                        console.log('📨 Chat data:', e.chat);
                        
                        // Get the Livewire component instance
                        const component = Livewire.find(@js($this->getId()));
                        if (component) {
                            console.log('📞 Calling handleIncomingMessage on component');
                            // Call the method directly
                            component.call('handleIncomingMessage', { chat: e.chat });
                        } else {
                            console.warn('⚠️ Component not found, using dispatch fallback');
                            // Fallback to dispatch
                            Livewire.dispatch('message-received-from-echo', { 
                                chat: e.chat 
                            });
                        }
                    })
                    .subscribed(() => {
                        console.log('✅ Successfully subscribed to:', channelName);
                    })
                    .error((error) => {
                        console.error('❌ Channel subscription error:', error);
                    });
                
                // Store channel reference for cleanup
                return conversationChannel;
            };

            // Initial subscription when component loads
            const initialConversationId = @js($this->getConversationId());
            if (initialConversationId) {
                console.log('🎯 Initial conversation ID:', initialConversationId);
                subscribeToConversation(initialConversationId);
            } else {
                console.log('⚠️ No initial conversation ID');
            }

            // Listen for user selection changes
            Livewire.on('conversation-changed', (event) => {
                console.log('🔄 Conversation changed event:', event);
                const conversationId = event.conversationId || event[0]?.conversationId;
                console.log('🔄 Extracted conversation ID:', conversationId);
                if (conversationId) {
                    subscribeToConversation(conversationId);
                } else {
                    console.warn('⚠️ No conversation ID in event');
                }
            });

            // Request notification permission
            if ("Notification" in window && Notification.permission === "default") {
                Notification.requestPermission();
            }

            Livewire.on('play-notification-sound', () => {
                // Play sound
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.5);
            });

            // Listen for Filament notification action clicks (from database notifications panel)
            // Filament will dispatch the event when action is clicked
            Livewire.on('open-conversation-from-notification', (event) => {
                console.log('🔔 Notification action clicked:', event);
                const data = event[0] || event;
                const userId = data?.userId || data?.userId || null;
                
                if (userId) {
                    const component = Livewire.find(@js($this->getId()));
                    if (component) {
                        component.call('openConversationFromNotification', { userId: parseInt(userId) });
                    } else {
                        // Fallback: dispatch to component
                        Livewire.dispatch('open-conversation-from-notification', { userId: parseInt(userId) });
                    }
                }
            });
        });
    </script>
@endpush
