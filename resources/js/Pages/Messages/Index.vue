<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    conversations: Array,
    composeConversation: {
        type: Object,
        default: null,
    },
});

const selectedConversation = ref(null);
const conversationList = ref(props.conversations ?? []);
const bottomAnchor = ref(null);
const page = usePage();

// Modal states
const confirmingMessageDeletion = ref(false);
const confirmingConversationDeletion = ref(false);
const messageIdToDelete = ref(null);
const conversationUserIdToDelete = ref(null);

const messageForm = useForm({
  receiver_id: null,
  listing_id: null,
  content: '',
  media: null,
});

const mediaInput = ref(null);
const mediaPreviewUrl = ref(null);
const mediaPreviewType = ref(null);

const createDraftConversation = (compose) => ({
    other_user: compose.other_user,
    last_message: {
        id: `draft-${compose.other_user.id}-${compose.listing_id}`,
        content: 'Start your conversation.',
        is_read: 1,
        receiver_id: compose.other_user.id,
        sender_id: page.props.auth.user.id,
        listing_id: compose.listing_id,
        created_at: new Date().toISOString(),
    },
    messages: [],
});

const upsertRealtimeMessage = async (incomingMessage) => {
    const authUserId = page.props.auth.user.id;
    const otherUser =
        incomingMessage.sender_id === authUserId
            ? incomingMessage.receiver
            : incomingMessage.sender;

    if (!otherUser) {
        return;
    }

    const conversationIndex = conversationList.value.findIndex(
        (chat) => chat.other_user.id === otherUser.id
    );

    if (conversationIndex === -1) {
        conversationList.value.unshift({
            other_user: otherUser,
            last_message: incomingMessage,
            messages: [incomingMessage],
        });
    } else {
        const chat = conversationList.value[conversationIndex];

        if (!chat.messages.some((msg) => msg.id === incomingMessage.id)) {
            chat.messages.push(incomingMessage);
        }

        chat.last_message = incomingMessage;

        if (conversationIndex > 0) {
            conversationList.value.unshift(
                conversationList.value.splice(conversationIndex, 1)[0]
            );
        }

        if (selectedConversation.value?.other_user.id === otherUser.id) {
            selectedConversation.value = chat;

            if (incomingMessage.receiver_id === authUserId && !incomingMessage.is_read) {
                incomingMessage.is_read = 1;
                router.post(route('messages.read', otherUser.id), {}, { preserveScroll: true, preserveState: true });
            }

            await nextTick();
            scrollToBottom(true);
        }
    }
};

// ---------------------------
// Real-time Listener
// ---------------------------
onMounted(() => {
    if (props.composeConversation?.other_user?.id) {
        const existingConversation = conversationList.value.find(
            (chat) => chat.other_user.id === props.composeConversation.other_user.id
        );

        if (existingConversation) {
            selectedConversation.value = existingConversation;
            messageForm.receiver_id = existingConversation.other_user.id;
            messageForm.listing_id = existingConversation.last_message.listing_id ?? props.composeConversation.listing_id;
        } else {
            const draftConversation = createDraftConversation(props.composeConversation);
            conversationList.value.unshift(draftConversation);
            selectedConversation.value = draftConversation;
            messageForm.receiver_id = props.composeConversation.other_user.id;
            messageForm.listing_id = props.composeConversation.listing_id;
        }
    }

    // 💡 This check prevents the crash that freezes your dropdown
    if (!window.Echo) {
        console.warn("Real-time listener skipped: Echo is not defined.");
        return;
    }

    const userId = page.props.auth.user.id;
    
    window.Echo.private(`App.Models.User.${userId}`)
        .listen('.MessageSent', async (e) => {
            await upsertRealtimeMessage(e.message);
        });
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave(`App.Models.User.${page.props.auth.user.id}`);
    }
});

// ---------------------------
// Scroll to bottom function
// ---------------------------
const scrollToBottom = (smooth = false) => {
  bottomAnchor.value?.scrollIntoView({ behavior: smooth ? 'smooth' : 'auto', block: 'end' });
};

// ---------------------------
// Watcher: scroll when opening a chat
// ---------------------------
watch(
  () => selectedConversation.value,
  async (val) => {
    if (!val) return;
    await nextTick(); // wait for messages to render
    scrollToBottom(); // instant scroll when opening chat
  }
);

// ---------------------------
// Watcher: scroll when messages update
// ---------------------------
watch(
  () => props.conversations,
  async (newConversations) => {
    conversationList.value = newConversations ?? [];

    if (!selectedConversation.value) return;

    const updated = conversationList.value.find(
      c => c.other_user.id === selectedConversation.value.other_user.id
    );

    if (!updated) {
      selectedConversation.value = null;
      return;
    }

    selectedConversation.value = updated;

    await nextTick();
    scrollToBottom(true);
  },
  { deep: true }
);

// ---------------------------
// Select a chat
// ---------------------------
const selectChat = async (chat) => {
  selectedConversation.value = chat;

  messageForm.receiver_id = chat.other_user.id;
  messageForm.listing_id = chat.last_message.listing_id;

  const authUser = page.props.auth.user;
  if (!chat.last_message.is_read && chat.last_message.receiver_id === authUser.id) {
    chat.last_message.is_read = 1;
    router.post(route('messages.read', chat.other_user.id), {}, { preserveScroll: true });
  }

  await nextTick();
  scrollToBottom(true);
};

// ---------------------------
// Send a message
// ---------------------------
const sendReply = () => {
  if (!messageForm.content.trim() && !messageForm.media) return;

  messageForm.post(route('messages.store'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: async () => {
      messageForm.content = '';
      clearSelectedMedia();
      await nextTick();
      scrollToBottom(true);
    },
    onError: () => {
      // keep selection for retry
    }
  });
};

const openMediaPicker = () => {
  mediaInput.value?.click();
};

const clearSelectedMedia = () => {
  messageForm.media = null;
  mediaPreviewType.value = null;
  if (mediaPreviewUrl.value) {
    URL.revokeObjectURL(mediaPreviewUrl.value);
    mediaPreviewUrl.value = null;
  }
  if (mediaInput.value) {
    mediaInput.value.value = '';
  }
};

const onMediaSelected = (event) => {
  const file = event.target.files?.[0];
  if (!file) return;

  messageForm.media = file;

  if (mediaPreviewUrl.value) {
    URL.revokeObjectURL(mediaPreviewUrl.value);
  }

  mediaPreviewUrl.value = URL.createObjectURL(file);
  mediaPreviewType.value = file.type.startsWith('image/') ? 'image' : 'video';
};

const formatLastMessage = (message) => {
  if (message?.content && message.content !== '[Attachment]') return message.content;
  if (message?.media_type === 'image') return 'Image attachment';
  if (message?.media_type === 'video') return 'Video attachment';
  return message?.content || '';
};

// ---------------------------
// Delete message
// ---------------------------
const confirmMessageDeletion = (id) => {
  messageIdToDelete.value = id;
  confirmingMessageDeletion.value = true;
};

const deleteMessage = () => {
  router.delete(route('messages.destroy', messageIdToDelete.value), {
    preserveScroll: true,
    onSuccess: () => {
      confirmingMessageDeletion.value = false;
      router.reload({ only: ['conversations'] });
    }
  });
};

// ---------------------------
// Delete conversation
// ---------------------------
const confirmConversationDeletion = (otherUserId) => {
  conversationUserIdToDelete.value = otherUserId;
  confirmingConversationDeletion.value = true;
};

const deleteConversation = () => {
  router.delete(route('messages.deleteConversation', conversationUserIdToDelete.value), {
    onSuccess: () => {
      confirmingConversationDeletion.value = false;
      selectedConversation.value = null;
      router.reload({ only: ['conversations'] });
    }
  });
};
</script>


<template>
    <Head title="Messages" />
    <AuthenticatedLayout>
        <div class="w-full h-[calc(100vh-14.5rem)] md:h-[calc(100vh-12.5rem)] overflow-hidden p-2">
            <div class="flex h-full bg-white dark:bg-gray-800 overflow-hidden relative rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                
                <div class="w-full md:w-1/3 border-r border-gray-200 dark:border-gray-700 flex flex-col" :class="selectedConversation ? 'hidden md:flex' : 'flex'">
                    <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Messages</h2>
                        <input type="text" placeholder="Search messages..." class="mt-3 w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-lg text-sm focus:ring-orange-500 shadow-sm" />
                    </div>
                    
                    <div class="flex-1 overflow-y-auto custom-scrollbar">
                        <div v-if="conversationList.length === 0" class="p-10 text-center text-gray-400 dark:text-gray-300 text-sm italic">
                            No messages yet. Contact a seller to start chatting!
                        </div>
                        <button 
                            v-for="chat in conversationList" :key="chat.other_user.id"
                            @click="selectChat(chat)"
                            class="w-full text-left p-4 flex items-center gap-3 transition border-b border-gray-50 dark:border-gray-700 relative"
                            :class="{
                                'bg-orange-50 dark:bg-orange-900/30 border-r-4 border-r-orange-500': selectedConversation?.other_user.id === chat.other_user.id,
                                'bg-gray-100 dark:bg-gray-700': selectedConversation?.other_user.id !== chat.other_user.id && chat.last_message.is_read == 0 && chat.last_message.receiver_id === $page.props.auth.user.id
                            }"
                        >
                            <div 
                                class="w-12 h-12 rounded-full flex items-center justify-center shrink-0 uppercase relative"
                                :class="{
                                    'bg-orange-300 text-orange-900 font-black': chat.last_message.is_read == 0 && chat.last_message.receiver_id === $page.props.auth.user.id,
                                    'bg-orange-100 text-orange-600 font-bold': chat.last_message.is_read == 1 || chat.last_message.sender_id === $page.props.auth.user.id
                                }"
                            >
                                {{ chat.other_user.name.charAt(0) }}
                                <span 
                                    v-if="chat.last_message.is_read == 0 && chat.last_message.receiver_id === $page.props.auth.user.id"
                                    class="absolute top-0 right-0 block h-3.5 w-3.5 rounded-full bg-red-600 border-2 border-white dark:border-gray-800 shadow-sm"
                                ></span>
                            </div>

                            <div class="flex-1 truncate">
                                <div :class="{
                                    'font-black text-gray-900 dark:text-gray-100': chat.last_message.is_read == 0 && chat.last_message.receiver_id === $page.props.auth.user.id, 
                                    'font-bold text-gray-700 dark:text-gray-200': chat.last_message.is_read == 1 || chat.last_message.sender_id === $page.props.auth.user.id
                                }">
                                    {{ chat.other_user.name }}
                                </div>
                                <div :class="{
                                    'font-bold text-gray-900 dark:text-gray-100': chat.last_message.is_read == 0 && chat.last_message.receiver_id === $page.props.auth.user.id, 
                                    'text-gray-500 dark:text-gray-300 font-normal': chat.last_message.is_read == 1 || chat.last_message.sender_id === $page.props.auth.user.id
                                }" class="text-xs truncate">
                                    {{ formatLastMessage(chat.last_message) }}
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <div class="flex-1 flex-col bg-gray-50 dark:bg-gray-900" :class="selectedConversation ? 'flex' : 'hidden md:flex'">
                    <template v-if="selectedConversation">
                        <div class="relative p-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between shadow-sm z-50">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    class="md:hidden p-1 rounded text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                    @click="selectedConversation = null"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center text-xs font-bold text-orange-600 uppercase">
                                    {{ selectedConversation.other_user.name.charAt(0) }}
                                </div>
                                <div class="font-bold text-gray-800 dark:text-gray-100">{{ selectedConversation.other_user.name }}</div>
                            </div>
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button type="button" class="p-2 text-gray-400 dark:text-gray-300 hover:text-gray-600 dark:hover:text-white transition rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                </template>
                                <template #content>
                                    <div class="py-1 bg-white dark:bg-gray-800">
                                        <button 
                                            type="button"
                                            @click="confirmConversationDeletion(selectedConversation.other_user.id)" 
                                            class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition"
                                        >
                                            Delete Conversation
                                        </button>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>

                        <div
                                :key="selectedConversation?.other_user.id"
                                ref="messageContainer"
                                class="flex-1 overflow-y-auto p-6 flex flex-col bg-gray-50 dark:bg-gray-900 custom-scrollbar"
                            >

                            <template v-if="selectedConversation.messages && selectedConversation.messages.length > 0">
                                <div
                                    v-for="msg in selectedConversation.messages"
                                    :key="msg.id"
                                    class="flex group mb-4"
                                    :class="msg.sender_id === $page.props.auth.user.id ? 'justify-end' : 'justify-start'"
                                >
                                    <div class="flex flex-col max-w-[85%] md:max-w-[70%]" :class="msg.sender_id === $page.props.auth.user.id ? 'items-end' : 'items-start'">
                                        <div 
                                            class="p-3 rounded-2xl text-sm"
                                            :class="msg.sender_id === $page.props.auth.user.id 
                                                ? 'bg-orange-600 text-white rounded-tr-none shadow-md' 
                                                : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-700 rounded-tl-none shadow-sm'"
                                        >
                                            <p v-if="msg.content && msg.content !== '[Attachment]'" class="mb-2 last:mb-0">{{ msg.content }}</p>
                                            <img
                                                v-if="msg.media_type === 'image' && msg.media_url"
                                                :src="msg.media_url"
                                                :alt="msg.media_original_name || 'Image attachment'"
                                                class="max-w-[220px] rounded-lg border border-black/10 dark:border-white/10"
                                            />
                                            <video
                                                v-if="msg.media_type === 'video' && msg.media_url"
                                                :src="msg.media_url"
                                                controls
                                                class="max-w-[220px] rounded-lg border border-black/10 dark:border-white/10"
                                            ></video>
                                        </div>
                                        <span class="text-[10px] text-gray-400 dark:text-gray-300 mt-1 uppercase">
                                            {{ new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                                        </span>
                                    </div>
                                </div>
                            </template>
                             <div ref="bottomAnchor"></div>
                        </div>

                        <div class="p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                            <form @submit.prevent="sendReply" class="space-y-3">
                                <input
                                    ref="mediaInput"
                                    type="file"
                                    class="hidden"
                                    accept="image/*,video/*"
                                    @change="onMediaSelected"
                                />
                                <div v-if="mediaPreviewUrl" class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 p-2">
                                    <img
                                        v-if="mediaPreviewType === 'image'"
                                        :src="mediaPreviewUrl"
                                        alt="Preview"
                                        class="h-14 w-14 rounded-md object-cover"
                                    />
                                    <video
                                        v-else
                                        :src="mediaPreviewUrl"
                                        class="h-14 w-14 rounded-md object-cover"
                                    ></video>
                                    <div class="text-xs text-gray-500 dark:text-gray-300 flex-1 truncate">{{ messageForm.media?.name }}</div>
                                    <button type="button" @click="clearSelectedMedia" class="text-red-500 text-xs font-semibold">Remove</button>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        @click="openMediaPicker"
                                        class="border border-gray-300 dark:border-gray-600 rounded-full px-3 py-2 text-xs font-bold text-gray-600 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
                                    >
                                        Attach
                                    </button>
                                <input 
                                    v-model="messageForm.content"
                                    type="text" 
                                    placeholder="Type a message..." 
                                    class="flex-1 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-full px-4 focus:ring-orange-500 focus:border-orange-500"
                                    :disabled="messageForm.processing"
                                />
                                <button 
                                    type="submit" 
                                    class="bg-orange-600 text-white p-2 rounded-full hover:bg-orange-700 transition disabled:opacity-50"
                                    :disabled="messageForm.processing || (!messageForm.content.trim() && !messageForm.media)"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                    </svg>
                                </button>
                                </div>
                                <p v-if="messageForm.errors.media" class="text-xs text-red-500">
                                    {{ messageForm.errors.media }}
                                </p>
                                <p v-if="messageForm.errors.content" class="text-xs text-red-500">
                                    {{ messageForm.errors.content }}
                                </p>
                            </form>
                        </div>
                    </template>

                    <div v-else class="flex-1 flex flex-col items-center justify-center text-gray-400 dark:text-gray-300">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-4xl shadow-sm">📨</div>
                        <p class="font-medium text-gray-500 dark:text-gray-300 italic">Select a conversation to start chatting</p>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="confirmingMessageDeletion" @close="confirmingMessageDeletion = false" maxWidth="sm">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">Delete Message?</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Are you sure? This action is permanent.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingMessageDeletion = false">Cancel</SecondaryButton>
                    <DangerButton @click="deleteMessage">Delete</DangerButton>
                </div>
            </div>
        </Modal>

        <Modal :show="confirmingConversationDeletion" @close="confirmingConversationDeletion = false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 text-center">Delete Conversation?</h2>
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-300 text-center">This will permanently remove all messages. You cannot undo this.</p>
                <div class="mt-8 flex justify-center gap-4">
                    <SecondaryButton class="w-full justify-center" @click="confirmingConversationDeletion = false">Cancel</SecondaryButton>
                    <DangerButton class="w-full justify-center" @click="deleteConversation">Yes, Delete All</DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>



