<script setup>
import { ref, computed, watch } from 'vue'; // Added computed
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';

const showingNavigationDropdown = ref(false);
const showAllNotifications = ref(false);
const selectedNotifications = ref([]); // ✅ Track selected IDs
const { props } = usePage();

// Local copy of notifications so we can update UI without a server reload
const notifications = ref([]);
const unreadNotificationsCount = computed(() => notifications.value.filter(n => !n.read_at).length);

// ✅ Check if all visible unread notifications are selected
const isAllSelected = computed(() => {
    const unread = notifications.value.filter(n => !n.read_at);
    return unread.length > 0 && selectedNotifications.value.length === unread.length;
});

watch(
    () => props.auth.notifications,
    (newVal) => {
        if (newVal) {
            notifications.value = [...newVal];
        }
    },
    { immediate: true }
);
// ✅ Toggle select all unread items
const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedNotifications.value = [];
    } else {
        selectedNotifications.value = notifications.value
            .filter(n => !n.read_at)
            .map(n => n.id);
    }
};

const deleteNotification = async (id) => {
    try {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrf = tokenMeta ? tokenMeta.getAttribute('content') : null;
        const url = `/notifications/${id}`;

        const res = await fetch(url, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
            },
            credentials: 'same-origin'
        });

        if (!res.ok) throw new Error('Failed to delete notification');

        // remove locally
        notifications.value = notifications.value.filter(n => n.id !== id);
        selectedNotifications.value = selectedNotifications.value.filter(i => i !== id);
    } catch (err) {
        console.error(err);
        router.delete(route('notifications.destroy', id));
    }
};

// ✅ Mark specific checked items as read
const markSelectedAsRead = () => {
    if (selectedNotifications.value.length === 0) return;
    
    router.post(route('notifications.markSelectedAsRead'), {
        ids: selectedNotifications.value
    }, {
        preserveScroll: true,
        preserveState: true,
        showProgress: false,
        onSuccess: () => {
            // optimistically mark read locally
            const now = new Date().toISOString();
            notifications.value.forEach(n => {
                if (selectedNotifications.value.includes(n.id)) n.read_at = now;
            });
            selectedNotifications.value = [];
        }
    });
};

// ✅ Mark every notification as read globally
const markAllAsRead = () => {
    router.post(route('notifications.markAllAsRead'), {}, {
        preserveScroll: true,
        preserveState: true,
        showProgress: false,
        onSuccess: () => {
            const now = new Date().toISOString();
            notifications.value.forEach(n => n.read_at = now);
        }
    });
};

// ✅ Delete all notifications for the current user (AJAX, no full page refresh)
const deleteAllNotifications = async () => {
    try {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrf = tokenMeta ? tokenMeta.getAttribute('content') : null;

        const url = '/notifications';

        const res = await fetch(url, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
            },
            credentials: 'same-origin'
        });

        if (!res.ok) throw new Error('Failed to delete notifications');

        // clear locally without any page reload
        notifications.value = [];
        selectedNotifications.value = [];
    } catch (err) {
        console.error(err);
        // fallback: navigate via Inertia to keep behavior predictable
        router.delete(route('notifications.destroyAll'));
    }
};

const handleNotificationClick = (notif) => {
    router.post(route('notifications.markAsRead', notif.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            const path = props.auth.user.role === 'seller' ? 'seller.orders.index' : 'buyer.orders.index';
            router.get(route(path));
        }
    });
};
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav class="border-b border-gray-100 bg-white">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</NavLink>
                                <NavLink :href="route('listings.index')" :active="route().current('listings.index')">Marketplace</NavLink>
                                <NavLink :href="route('messages.index')" :active="route().current('messages.index')" class="relative">
                                    Messages
                                    <span v-if="$page.props.auth.unreadMessagesCount > 0" class="absolute -right-3 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white border-2 border-white">
                                        {{ $page.props.auth.unreadMessagesCount }}
                                    </span>
                                </NavLink>
                                <NavLink v-if="$page.props.auth.user.role === 'seller'" :href="route('seller.orders.index')" :active="route().current('seller.orders.index')" class="relative">
                                    Incoming Orders
                                    <span v-if="$page.props.auth.sellerOrderCount > 0" class="absolute -right-3 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white border-2 border-white">
                                        {{ $page.props.auth.sellerOrderCount }}
                                    </span>
                                </NavLink>
                                <NavLink v-if="$page.props.auth.user.role === 'buyer'" :href="route('buyer.orders.index')" :active="route().current('buyer.orders.index')" class="relative">
                                    My Purchases
                                    <span v-if="$page.props.auth.buyerOrderCount > 0" class="absolute -right-3 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white border-2 border-white">
                                        {{ $page.props.auth.buyerOrderCount }}
                                    </span>
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center gap-1">
                            <div v-if="$page.props.auth.user.role === 'buyer'" class="relative mr-2">
                                <Link :href="route('cart.index')" class="p-2 text-gray-400 hover:text-gray-600 transition block relative">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span v-if="$page.props.auth.cartCount > 0" class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white border-2 border-white">
                                        {{ $page.props.auth.cartCount }}
                                    </span>
                                </Link>
                            </div>

                            <div class="relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button class="relative p-2 text-gray-400 hover:text-gray-600 focus:outline-none transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                            </svg>
                                            <span v-if="unreadNotificationsCount > 0" class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white border-2 border-white">
                                                {{ unreadNotificationsCount }}
                                            </span>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="px-4 py-2 border-b bg-gray-50/50">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Notifications</span>
                                                
                                                <div class="flex items-center gap-2">
                                                    <button v-if="selectedNotifications.length > 0" @click.stop="markSelectedAsRead" class="text-blue-600 hover:text-blue-800 transition relative group">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span class="absolute bottom-full right-0 mb-2 hidden group-hover:block bg-gray-800 text-white text-[10px] px-2 py-1 rounded whitespace-nowrap shadow-lg z-50">Mark Selected Read</span>
                                                    </button>

                                                    <button v-else-if="unreadNotificationsCount > 0" @click.stop="markAllAsRead" class="text-gray-400 hover:text-blue-600 transition relative group">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                                                        </svg>
                                                        <span class="absolute bottom-full right-0 mb-2 hidden group-hover:block bg-gray-800 text-white text-[10px] px-2 py-1 rounded whitespace-nowrap shadow-lg z-50">Mark all read</span>
                                                    </button>
                                                    <button v-else-if="notifications.length > 0" @click.stop="deleteAllNotifications" class="text-red-400 hover:text-red-600 transition relative group">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        <span class="absolute bottom-full right-0 mb-2 hidden group-hover:block bg-gray-800 text-white text-[10px] px-2 py-1 rounded whitespace-nowrap shadow-lg z-50">Delete all</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <div v-if="notifications.some(n => !n.read_at)" class="flex items-center gap-2">
                                                <input type="checkbox" :checked="isAllSelected" @change.stop="toggleSelectAll" @click.stop class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 h-3 w-3 cursor-pointer" />
                                                <span class="text-[10px] text-gray-500">Select All Unread</span>
                                            </div>
                                        </div>
                                        
                                        <div class="max-h-96 overflow-y-auto custom-scrollbar">
                                            <div v-if="notifications.length === 0" class="p-4 text-sm text-gray-500 text-center italic">
                                                No new updates
                                            </div>
                                            
                                            <div v-for="notif in (showAllNotifications ? notifications : notifications.slice(0, 5))" 
                                                :key="notif.id" 
                                                class="group relative flex items-center border-b last:border-0 hover:bg-gray-50 transition pl-2"
                                            >
                                                <input v-if="!notif.read_at" type="checkbox" v-model="selectedNotifications" :value="notif.id" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 h-3 w-3 cursor-pointer" @click.stop />
                                                <div v-else class="w-3 h-3 ml-2"></div> <button @click="handleNotificationClick(notif)" class="flex-1 text-left px-3 py-3 pr-10" :class="notif.read_at ? 'opacity-60' : 'font-semibold text-gray-900'">
                                                    <div class="flex items-start gap-2">
                                                        <div v-if="!notif.read_at" class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-500"></div>
                                                        <div class="flex-1">
                                                            <p class="text-xs leading-tight">{{ notif.data.message }}</p>
                                                            <p class="text-[10px] text-gray-400 mt-1 uppercase">{{ new Date(notif.created_at).toLocaleDateString() }}</p>
                                                        </div>
                                                    </div>
                                                </button>

                                                <button @click.stop="deleteNotification(notif.id)" class="absolute right-2 text-gray-300 hover:text-red-500 p-2 transition rounded-md hover:bg-red-50">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div v-if="notifications.length > 5" class="bg-gray-50 border-t sticky bottom-0">
                                            <button @click.stop="showAllNotifications = !showAllNotifications" class="w-full py-2 text-xs font-bold text-blue-600 hover:text-blue-800 transition text-center focus:outline-none">
                                                {{ showAllNotifications ? 'Show Less' : 'See All Notifications' }}
                                            </button>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>

                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none">
                                                {{ $page.props.auth.user.name }}
                                                <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button"> Log Out </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <header class="bg-white shadow" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main><slot /></main>
        </div>
    </div>
</template>

