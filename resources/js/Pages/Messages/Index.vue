<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch, nextTick } from 'vue';

const props = defineProps({ conversations: Array });

const selectedConversation = ref(null);
const bottomAnchor = ref(null);

// Modal states
const confirmingMessageDeletion = ref(false);
const confirmingConversationDeletion = ref(false);
const messageIdToDelete = ref(null);
const conversationUserIdToDelete = ref(null);

const messageForm = useForm({
  receiver_id: null,
  listing_id: null,
  content: '',
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
    if (!selectedConversation.value) return;

    const updated = newConversations.find(
      c => c.other_user.id === selectedConversation.value.other_user.id
    );

    if (!updated) return;

    // Update messages only
    selectedConversation.value.messages = updated.messages;

    await nextTick(); // wait for DOM
    scrollToBottom(true); // smooth scroll for new messages
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

  const authUser = router.page.props.auth.user;
  if (!chat.last_message.is_read && chat.last_message.receiver_id === authUser.id) {
    chat.last_message.is_read = 1;
    router.post(route('messages.read', chat.other_user.id), {}, { preserveScroll: true });
  }
};

// ---------------------------
// Send a message
// ---------------------------
const sendReply = () => {
  if (!messageForm.content.trim()) return;

  messageForm.post(route('messages.store'), {
    preserveScroll: true,
    onSuccess: () => {
      messageForm.content = '';
      router.reload({
        only: ['conversations'],
        onSuccess: () => scrollToBottom(true)
      });
    }
  });
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
        <div class=" mx-auto max-w-6xl">
            <div class="flex h-[calc(100vh-64px)] bg-white overflow-hidden relative rounded-xl shadow-lg border border-gray-200">
                
                <div class="w-1/3 border-r border-gray-200 flex flex-col">
                    <div class="p-4 border-b border-gray-100">
                        <h2 class="text-xl font-bold text-gray-800">Messages</h2>
                        <input type="text" placeholder="Search messages..." class="mt-3 w-full border-gray-300 rounded-lg text-sm focus:ring-orange-500 shadow-sm" />
                    </div>
                    
                    <div class="flex-1 overflow-y-auto">
                        <div v-if="conversations.length === 0" class="p-10 text-center text-gray-400 text-sm italic">
                            No messages yet. Contact a seller to start chatting!
                        </div>
                        <button 
                            v-for="chat in conversations" :key="chat.other_user.id"
                            @click="selectChat(chat)"
                            class="w-full text-left p-4 flex items-center gap-3 transition border-b border-gray-50 relative"
                            :class="{
                                'bg-orange-50 border-r-4 border-r-orange-500': selectedConversation?.other_user.id === chat.other_user.id,
                                'bg-gray-100': selectedConversation?.other_user.id !== chat.other_user.id && chat.last_message.is_read == 0 && chat.last_message.receiver_id === $page.props.auth.user.id
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
                                    class="absolute top-0 right-0 block h-3.5 w-3.5 rounded-full bg-red-600 border-2 border-white shadow-sm"
                                ></span>
                            </div>

                            <div class="flex-1 truncate">
                                <div :class="{
                                    'font-black text-gray-900': chat.last_message.is_read == 0 && chat.last_message.receiver_id === $page.props.auth.user.id, 
                                    'font-bold text-gray-700': chat.last_message.is_read == 1 || chat.last_message.sender_id === $page.props.auth.user.id
                                }">
                                    {{ chat.other_user.name }}
                                </div>
                                <div :class="{
                                    'font-bold text-gray-900': chat.last_message.is_read == 0 && chat.last_message.receiver_id === $page.props.auth.user.id, 
                                    'text-gray-500 font-normal': chat.last_message.is_read == 1 || chat.last_message.sender_id === $page.props.auth.user.id
                                }" class="text-xs truncate">
                                    {{ chat.last_message.content }}
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <div class="flex-1 flex flex-col bg-gray-50">
                    <template v-if="selectedConversation">
                        <div class="p-4 bg-white border-b border-gray-200 flex items-center justify-between shadow-sm z-10">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center text-xs font-bold text-orange-600 uppercase">
                                    {{ selectedConversation.other_user.name.charAt(0) }}
                                </div>
                                <div class="font-bold text-gray-800">{{ selectedConversation.other_user.name }}</div>
                            </div>

                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="p-2 text-gray-400 hover:text-gray-600 transition rounded-full hover:bg-gray-100 focus:outline-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                </template>
                                <template #content>
                                    <button @click="confirmConversationDeletion(selectedConversation.other_user.id)" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                                        Delete Conversation
                                    </button>
                                </template>
                            </Dropdown>
                        </div>

                        <div
                                :key="selectedConversation?.other_user.id"
                                ref="messageContainer"
                                class="flex-1 overflow-y-auto p-6 flex flex-col bg-gray-50"
                            >

                            <template v-if="selectedConversation.messages && selectedConversation.messages.length > 0">
                                <div
                                    v-for="msg in selectedConversation.messages"
                                    :key="msg.id"
                                    class="flex group mb-4"
                                    :class="msg.sender_id === $page.props.auth.user.id ? 'justify-end' : 'justify-start'"
                                >
                                    <div class="flex flex-col max-w-[70%]" :class="msg.sender_id === $page.props.auth.user.id ? 'items-end' : 'items-start'">
                                        <div 
                                            class="p-3 rounded-2xl text-sm"
                                            :class="msg.sender_id === $page.props.auth.user.id 
                                                ? 'bg-orange-600 text-white rounded-tr-none shadow-md' 
                                                : 'bg-white text-gray-800 border border-gray-200 rounded-tl-none shadow-sm'"
                                        >
                                            {{ msg.content }}
                                        </div>
                                        <span class="text-[10px] text-gray-400 mt-1 uppercase">
                                            {{ new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                                        </span>
                                    </div>
                                </div>
                            </template>
                             <div ref="bottomAnchor"></div>
                        </div>

                        <div class="p-4 bg-white border-t border-gray-200">
                            <form @submit.prevent="sendReply" class="flex gap-2">
                                <input 
                                    v-model="messageForm.content"
                                    type="text" 
                                    placeholder="Type a message..." 
                                    class="flex-1 border-gray-300 rounded-full px-4 focus:ring-orange-500 focus:border-orange-500"
                                    :disabled="messageForm.processing"
                                />
                                <button 
                                    type="submit" 
                                    class="bg-orange-600 text-white p-2 rounded-full hover:bg-orange-700 transition disabled:opacity-50"
                                    :disabled="messageForm.processing || !messageForm.content.trim()"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </template>

                    <div v-else class="flex-1 flex flex-col items-center justify-center text-gray-400">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-4xl shadow-sm">📨</div>
                        <p class="font-medium text-gray-500 italic">Select a conversation to start chatting</p>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="confirmingMessageDeletion" @close="confirmingMessageDeletion = false" maxWidth="sm">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900">Delete Message?</h2>
                <p class="mt-2 text-sm text-gray-600">Are you sure? This action is permanent.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingMessageDeletion = false">Cancel</SecondaryButton>
                    <DangerButton @click="deleteMessage">Delete</DangerButton>
                </div>
            </div>
        </Modal>

        <Modal :show="confirmingConversationDeletion" @close="confirmingConversationDeletion = false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 text-center">Delete Conversation?</h2>
                <p class="mt-4 text-sm text-gray-600 text-center">This will permanently remove all messages. You cannot undo this.</p>
                <div class="mt-8 flex justify-center gap-4">
                    <SecondaryButton class="w-full justify-center" @click="confirmingConversationDeletion = false">Cancel</SecondaryButton>
                    <DangerButton class="w-full justify-center" @click="deleteConversation">Yes, Delete All</DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>