<!-- resources/views/livewire/chat.blade.php -->
<div class="flex flex-col h-full rounded-lg bg-gray-50 dark:bg-gray-900" x-data="{ 
    showUserList: true,
    isTyping: false,
    isMobile: window.innerWidth < 768,
    selectedUserId: @entangle('selectedUser.id').defer,
    scrollToBottom() {
        const container = this.$refs.chatMessages;
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    },
    toggleUserList() {
        this.showUserList = !this.showUserList;
    },
    checkMobile() {
        this.isMobile = window.innerWidth < 768;
        if (!this.isMobile) {
            this.showUserList = true;
        }
    }
}"
x-init="
    $nextTick(() => scrollToBottom());
    window.addEventListener('resize', checkMobile);
    $watch('selectedUserId', () => {
        if (isMobile) {
            showUserList = false;
        }
        $nextTick(() => scrollToBottom());
    });
    " @message-sent.window="\
        $nextTick(() => scrollToBottom());
        isTyping = true;
        setTimeout(() => {
            isTyping = false;
            $nextTick(() => scrollToBottom());
        }, 2000);
    ">
     <div class="flex flex-1 overflow-hidden rounded-lg shadow-lg">
        <!-- User List - Hidden on mobile when a chat is selected -->
        <div 
            x-show="showUserList || !isMobile" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform -translate-x-5"
            x-transition:enter-end="opacity-100 transform translate-x-0"
            :class="isMobile ? 'w-full absolute inset-0 z-10 h-full' : 'w-full md:w-1/3 lg:w-1/4'"
            class="border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 flex flex-col max-h-screen"
        >
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="relative">
                    {{-- <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-4 h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg> --}}
                    <input 
                        type="text" 
                        wire:model.debounce.300ms="searchTerm" 
                        placeholder="Search users..." 
                        class="w-full pl-12 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                    >
                </div>
            </div>

            <div class="overflow-y-auto flex-1">
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($users as $user)
                        <li 
                            wire:key="user-{{ $user->id }}" 
                            wire:click="selectUser({{ $user->id }})" 
                            class="px-4 py-3 flex items-center space-x-3 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition-colors duration-150 {{ $selectedUser && $selectedUser->id === $user->id ? 'bg-emerald-50 dark:bg-emerald-900/30 border-r-2 border-emerald-500' : '' }}"
                        >
                            <div class="flex-shrink-0">
                                <div class="relative">
                                    @if($user->avatar)
                                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="h-10 w-10 rounded-full object-cover">
                                    @else
                                        <div class="h-10 w-10 rounded-full bg-emerald-100 dark:bg-emerald-800 flex items-center justify-center">
                                            <span class="text-emerald-700 dark:text-emerald-300 font-medium">{{ substr($user->name, 0, 1) }}</span>
                                        </div>
                                    @endif
                                    
                                    @if(isset($user->is_online) && $user->is_online)
                                        <span class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-white dark:ring-gray-900"></span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="font-semibold text-sm text-gray-900 dark:text-gray-100 truncate">{{ $user->name }}</p>
                                </div>
                                @if(isset($lastMessages[$user->id]))
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                        {{ Str::limit($lastMessages[$user->id], 30) }}
                                    </p>
                                @else
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 italic">No messages yet</p>
                                @endif
                            </div>
                            @if(isset($unreadMessageCounts[$user->id]) && $unreadMessageCounts[$user->id] > 0)
                                <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-2 text-xs font-bold leading-none text-white bg-emerald-600 dark:bg-emerald-500 rounded-full shadow-sm">
                                    {{ $unreadMessageCounts[$user->id] > 99 ? '99+' : $unreadMessageCounts[$user->id] }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Chat Area -->
        <div 
            :class="isMobile ? 'w-full' : 'w-full md:w-2/3 lg:w-3/4'" 
            class="flex flex-col bg-white dark:bg-gray-900 max-h-screen rounded-r-lg"
        >
            @if($selectedUser)
                <!-- Chat Header -->
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between shadow-sm bg-white dark:bg-gray-900 rounded-tr-lg">
                    <div class="flex items-center space-x-3">
                        <button 
                            x-show="isMobile" 
                            @click="toggleUserList()" 
                            class="p-1 rounded-md hover:bg-gray-100 hover:dark:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </button>
                        <div class="flex-shrink-0">
                            @if($selectedUser->avatar)
                                <img src="{{ $selectedUser->avatar }}" alt="{{ $selectedUser->name }}" class="h-10 w-10 rounded-full object-cover">
                            @else
                                <div class="h-10 w-10 rounded-full bg-emerald-100 dark:bg-emerald-800 flex items-center justify-center">
                                    <span class="text-emerald-700 dark:text-emerald-300 font-medium">{{ substr($selectedUser->name, 0, 1) }}</span>
                                </div>
                            @endif
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ $selectedUser->name }}</h3>
                            @if(isset($selectedUser->is_online) && $selectedUser->is_online)
                                <p class="text-xs text-emerald-500">Online</p>
                            @else
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    @if(isset($selectedUser->last_seen))
                                        Last seen {{ $selectedUser->last_seen->diffForHumans() }}
                                    @else
                                        Offline
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Messages -->
                <div class="flex-1 overflow-y-auto p-4 md:p-6 space-y-3 bg-gradient-to-b from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900" x-ref="chatMessages" style="max-height: calc(100vh - 10rem);">
                    @foreach($messages as $message)
                        <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                            @if($message->sender_id !== auth()->id())
                                <div class="flex-shrink-0 mr-2 mt-1">
                                    @if($selectedUser->avatar)
                                        <img src="{{ $selectedUser->avatar }}" alt="{{ $selectedUser->name }}" class="h-8 w-8 rounded-full object-cover">
                                    @else
                                        <div class="h-8 w-8 rounded-full bg-emerald-100 dark:bg-emerald-800 flex items-center justify-center">
                                            <span class="text-emerald-700 dark:text-emerald-300 font-medium">{{ substr($selectedUser->name, 0, 1) }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                            <div class="flex flex-col {{ $message->sender_id === auth()->id() ? 'items-end' : 'items-start' }}">
                                <div class="{{ $message->sender_id === auth()->id() ? 'bg-emerald-600 text-white rounded-tr-none' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-600 rounded-tl-none' }} px-4 py-2.5 rounded-lg max-w-sm sm:max-w-md shadow-sm hover:shadow-md transition-shadow">
                                    <p class="text-sm leading-relaxed whitespace-pre-wrap break-words">{{ $message->content }}</p>
                                </div>
                                <span class="text-xs {{ $message->sender_id === auth()->id() ? 'text-right' : 'text-left' }} text-gray-500 dark:text-gray-400 mt-1.5 px-1">
                                    @if($message->created_at->isToday())
                                        {{ $message->created_at->format('H:i') }}
                                    @elseif($message->created_at->isYesterday())
                                        Yesterday {{ $message->created_at->format('H:i') }}
                                    @else
                                        {{ $message->created_at->format('M d, H:i') }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endforeach

                    <!-- Typing indicator -->
                    <div x-show="isTyping" class="flex justify-start">
                        <div class="flex-shrink-0 mr-2 mt-1">
                            @if($selectedUser->avatar)
                                <img src="{{ $selectedUser->avatar }}" alt="{{ $selectedUser->name }}" class="h-8 w-8 rounded-full object-cover">
                            @else
                                <div class="h-8 w-8 rounded-full bg-emerald-100 dark:bg-emerald-800 flex items-center justify-center">
                                    <span class="text-emerald-700 dark:text-emerald-300 font-medium">{{ substr($selectedUser->name, 0, 1) }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-4 py-2 rounded-lg rounded-tl-none shadow-sm">
                            <div class="flex space-x-1">
                                <div class="w-2 h-2 bg-gray-400 dark:bg-gray-300 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                <div class="w-2 h-2 bg-gray-400 dark:bg-gray-300 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                                <div class="w-2 h-2 bg-gray-400 dark:bg-gray-300 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Message Input -->
                <div class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 p-4 rounded-br-lg">
                    <form wire:submit.prevent="sendMessage" class="flex items-end space-x-2">
                        <input 
                            type="text" 
                            wire:model.defer="message" 
                            class="flex-1 border border-gray-300 dark:border-gray-600 rounded-l-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 transition-all" 
                            placeholder="Type your message..."
                        >
                        <button 
                            type="submit" 
                            class="bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white px-5 py-2.5 rounded-r-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                            </svg>
                        </button>
                    </form>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center p-4 text-center bg-gray-50 dark:bg-gray-800">
                    <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-800 rounded-full flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-emerald-600 dark:text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium mb-2 text-gray-900 dark:text-gray-100">No conversation selected</h3>
                    <p class="text-gray-500 dark:text-gray-400 max-w-md">
                        Choose a user from the list to start chatting or search for a specific person
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>