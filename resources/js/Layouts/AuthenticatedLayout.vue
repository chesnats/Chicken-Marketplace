<script setup>
import { ref, computed, watch } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import useDarkMode from '@/Composables/useDarkMode';
import { Link, router, usePage } from '@inertiajs/vue3';

const showAllNotifications = ref(false);
const selectedNotifications = ref([]);
const showingNavigationDropdown = ref(false);
const { props } = usePage();
const user = computed(() => props.auth?.user ?? null);
const { isDark, toggleTheme } = useDarkMode();

const notifications = ref([]);
const unreadNotificationsCount = computed(() => notifications.value.filter(n => !n.read_at).length);
const unreadMessagesCount = computed(() => props.auth?.unreadMessagesCount ?? 0);
const navTitle = computed(() => {
    if (!user.value) return '';
    if (route().current('dashboard')) return `Welcome back, ${user.value.name}!`;
    if (route().current('listings.index')) return 'Chicken Marketplace';
    if (route().current('seller.orders.index')) return 'Incoming Orders';
    if (route().current('buyer.orders.index')) return 'My Purchases';
    return '';
});

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

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedNotifications.value = [];
    } else {
        selectedNotifications.value = notifications.value
            .filter(n => !n.read_at)
            .map(n => n.id);
    }
};

const deleteNotification = (id) => {
    router.delete(`/notifications/${id}`, {
        preserveScroll: true,
        preserveState: true,
        showProgress: false,
        onSuccess: () => {
            notifications.value = notifications.value.filter(n => n.id !== id);
            selectedNotifications.value = selectedNotifications.value.filter(i => i !== id);
        },
    });
};

const markSelectedAsRead = () => {
    if (selectedNotifications.value.length === 0) return;

    router.post(route('notifications.markSelectedAsRead'), {
        ids: selectedNotifications.value
    }, {
        preserveScroll: true,
        preserveState: true,
        showProgress: false,
        onSuccess: () => {
            const now = new Date().toISOString();
            notifications.value.forEach(n => {
                if (selectedNotifications.value.includes(n.id)) n.read_at = now;
            });
            selectedNotifications.value = [];
        }
    });
};

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

const deleteAllNotifications = () => {
    router.delete('/notifications', {
        preserveScroll: true,
        preserveState: true,
        showProgress: false,
        onSuccess: () => {
            notifications.value = [];
            selectedNotifications.value = [];
        },
    });
};

const logout = () => {
    router.post(route('logout'), {}, {
        onSuccess: () => {
            window.location.href = route('listings.index');
        },
    });
};

const handleNotificationClick = (notif) => {
    router.post(route('notifications.markAsRead', notif.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            const path = user.value?.role === 'seller' ? 'seller.orders.index' : 'buyer.orders.index';
            router.get(route(path));
        }
    });
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
        <aside v-if="user" class="group fixed inset-y-0 left-0 z-[120] hidden w-20 overflow-hidden border-r border-gray-200 bg-white px-3 py-6 transition-all duration-300 ease-out hover:w-64 dark:border-gray-700 dark:bg-gray-800 lg:flex lg:flex-col">
            <Link :href="route('dashboard')" class="inline-flex items-center gap-3 px-2">
                <img src="/chickenlogo.png" alt="Chicken Marketplace" class="h-10 w-10 rounded-md object-contain shrink-0" />
                <span class="max-w-0 overflow-hidden whitespace-nowrap text-sm font-extrabold tracking-wide text-gray-800 opacity-0 transition-all duration-300 ease-out group-hover:max-w-[180px] group-hover:opacity-100 dark:text-gray-100">Chicken Marketplace</span>
            </Link>

            <nav class="mt-8 flex flex-1 flex-col gap-2">
                <Link
                    v-if="user"
                    :href="route('dashboard')"
                    class="flex items-center rounded-xl px-3 py-3 transition-colors"
                    :class="route().current('dashboard') ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="ml-3 max-w-0 overflow-hidden whitespace-nowrap text-sm font-semibold opacity-0 transition-all duration-300 ease-out group-hover:max-w-[140px] group-hover:opacity-100">Dashboard</span>
                </Link>

                <Link
                    :href="route('listings.index')"
                    class="flex items-center rounded-xl px-3 py-3 transition-colors"
                    :class="route().current('listings.index') ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
                    </svg>
                    <span class="ml-3 max-w-0 overflow-hidden whitespace-nowrap text-sm font-semibold opacity-0 transition-all duration-300 ease-out group-hover:max-w-[140px] group-hover:opacity-100">Marketplace</span>
                </Link>

                <Link
                    v-if="user?.role === 'seller'"
                    :href="route('seller.orders.index')"
                    class="relative flex items-center rounded-xl px-3 py-3 transition-colors"
                    :class="route().current('seller.orders.index') ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span class="ml-3 max-w-0 overflow-hidden whitespace-nowrap text-sm font-semibold opacity-0 transition-all duration-300 ease-out group-hover:max-w-[140px] group-hover:opacity-100">Incoming Orders</span>
                    <span v-if="$page.props.auth.sellerOrderCount > 0" class="absolute right-2 top-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">
                        {{ $page.props.auth.sellerOrderCount }}
                    </span>
                </Link>

                <Link
                    v-if="user?.role === 'buyer'"
                    :href="route('buyer.orders.index')"
                    class="relative flex items-center rounded-xl px-3 py-3 transition-colors"
                    :class="route().current('buyer.orders.index') ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10v10m0 0v2m0-2a3 3 0 003-3m-3 3a3 3 0 01-3-3" />
                    </svg>
                    <span class="ml-3 max-w-0 overflow-hidden whitespace-nowrap text-sm font-semibold opacity-0 transition-all duration-300 ease-out group-hover:max-w-[140px] group-hover:opacity-100">My Purchases</span>
                    <span v-if="$page.props.auth.buyerOrderCount > 0" class="absolute right-2 top-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">
                        {{ $page.props.auth.buyerOrderCount }}
                    </span>
                </Link>
            </nav>
        </aside>

        <div class="min-h-screen" :class="{ 'lg:pl-20': user }">
            <nav class="relative z-[80] border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="w-full px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-500 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white lg:hidden"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>

                            <Link :href="route('dashboard')" class="inline-flex items-center gap-2 lg:hidden">
                                <img src="/chickenlogo.png" alt="Chicken Marketplace" class="h-9 w-9 rounded-md object-contain" />
                                <span class="text-sm font-bold text-gray-700 dark:text-gray-100">Chicken Marketplace</span>
                            </Link>

                            <div v-if="navTitle" class="hidden md:flex md:items-center md:gap-2">
                                <p class="text-sm font-bold text-gray-800 dark:text-gray-100">
                                    {{ navTitle }}
                                </p>
                                <slot name="nav_title_suffix" />
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            <div v-if="user?.role === 'buyer'" class="relative mr-2">
                                <Link :href="route('cart.index')" class="p-2 text-gray-400 dark:text-gray-300 hover:text-gray-600 dark:hover:text-white transition block relative">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span v-if="$page.props.auth.cartCount > 0" class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white border-2 border-white">
                                        {{ $page.props.auth.cartCount }}
                                    </span>
                                </Link>
                            </div>

                            <div v-if="user" class="relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button class="relative p-2 text-gray-400 dark:text-gray-300 hover:text-gray-600 dark:hover:text-white focus:outline-none transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                            </svg>
                                            <span v-if="unreadNotificationsCount > 0" class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white border-2 border-white">
                                                {{ unreadNotificationsCount }}
                                            </span>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="px-4 py-2 border-b dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/60">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs font-semibold text-gray-400 dark:text-gray-300 uppercase tracking-wider">Notifications</span>

                                                <div class="flex items-center gap-2">
                                                    <button v-if="selectedNotifications.length > 0" @click.stop="markSelectedAsRead" class="text-blue-600 hover:text-blue-800 transition relative group">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span class="absolute bottom-full right-0 mb-2 hidden group-hover:block bg-gray-800 text-white text-[10px] px-2 py-1 rounded whitespace-nowrap shadow-lg z-50">Mark Selected Read</span>
                                                    </button>

                                                    <button v-else-if="unreadNotificationsCount > 0" @click.stop="markAllAsRead" class="text-gray-400 dark:text-gray-300 hover:text-blue-600 transition relative group">
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
                                                <input type="checkbox" :checked="isAllSelected" @change.stop="toggleSelectAll" @click.stop class="rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-blue-600 shadow-sm focus:ring-blue-500 h-4 w-4 cursor-pointer" />
                                                <span class="text-[12px] text-gray-500 dark:text-gray-300">Select All Unread</span>
                                            </div>
                                        </div>

                                        <div class="max-h-96 overflow-y-auto custom-scrollbar">
                                            <div v-if="notifications.length === 0" class="p-4 text-sm text-gray-500 dark:text-gray-300 text-center italic">
                                                No new updates
                                            </div>

                                            <div
                                                v-for="notif in (showAllNotifications ? notifications : notifications.slice(0, 5))"
                                                :key="notif.id"
                                                class="group relative flex items-center border-b dark:border-gray-700 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700 transition pl-2"
                                            >
                                                <input v-if="!notif.read_at" type="checkbox" v-model="selectedNotifications" :value="notif.id" class="rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-blue-600 shadow-sm focus:ring-blue-500 h-4 w-4 cursor-pointer ms-2" @click.stop />
                                                <div v-else class="w-4 h-4 ms-2"></div>
                                                <button @click="handleNotificationClick(notif)" class="flex-1 text-left px-4 py-3 pr-10" :class="notif.read_at ? 'opacity-60' : 'font-semibold text-gray-900 dark:text-gray-100'">
                                                    <div class="flex items-start gap-2">
                                                        <div v-if="!notif.read_at" class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-500"></div>
                                                        <div class="flex-1">
                                                            <p class="text-xs leading-tight">{{ notif.data.message }}</p>
                                                            <p class="text-[10px] text-gray-400 dark:text-gray-300 mt-1 uppercase">{{ new Date(notif.created_at).toLocaleDateString() }}</p>
                                                        </div>
                                                    </div>
                                                </button>

                                                <button @click.stop="deleteNotification(notif.id)" class="absolute right-2 text-gray-300 dark:text-gray-400 hover:text-red-500 p-2 transition rounded-md hover:bg-red-50 dark:hover:bg-red-900/40">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div v-if="notifications.length > 5" class="bg-gray-50 dark:bg-gray-800 border-t dark:border-gray-700 sticky bottom-0">
                                            <button @click.stop="showAllNotifications = !showAllNotifications" class="w-full py-2 text-xs font-bold text-blue-600 hover:text-blue-800 transition text-center focus:outline-none">
                                                {{ showAllNotifications ? 'Show Less' : 'See All Notifications' }}
                                            </button>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>

                            <div v-if="user" class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center rounded-md border border-transparent bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium leading-4 text-gray-500 dark:text-gray-300 transition duration-150 ease-in-out hover:text-gray-700 dark:hover:text-white focus:outline-none">
                                                {{ $page.props.auth.user.name }}
                                                <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>

                                        <button
                                            type="button"
                                            @click="logout"
                                            class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-200 transition duration-150 ease-in-out hover:bg-gray-100 dark:hover:bg-gray-700 focus:bg-gray-100 dark:focus:bg-gray-700 focus:outline-none"
                                        >
                                            Log Out
                                        </button>
                                    </template>
                                </Dropdown>
                            </div>

                            <div v-else class="flex items-center gap-2 ms-2">
                                <Link
                                    :href="route('login')"
                                    class="inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                                >
                                    Login
                                </Link>
                                <Link
                                    :href="route('login', { mode: 'register' })"
                                    class="inline-flex items-center rounded-md bg-orange-600 px-3 py-2 text-sm font-medium text-white hover:bg-orange-700 transition"
                                >
                                    Register
                                </Link>
                            </div>

                            <button
                                type="button"
                                @click="toggleTheme"
                                class="rounded-lg p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 transition"
                                :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                            >
                                <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="4" />
                                    <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3a7 7 0 1 0 9 9 9 9 0 1 1-9-9z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-show="showingNavigationDropdown" class="border-t border-gray-200 dark:border-gray-700 lg:hidden">
                    <div class="space-y-1 pb-3 pt-2">
                        <Link v-if="user" :href="route('dashboard')" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200">Dashboard</Link>
                        <Link :href="route('listings.index')" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200">Marketplace</Link>
                        <Link v-if="user?.role === 'seller'" :href="route('seller.orders.index')" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200">Incoming Orders</Link>
                        <Link v-if="user?.role === 'buyer'" :href="route('buyer.orders.index')" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200">My Purchases</Link>
                        <Link v-if="user?.role === 'buyer'" :href="route('cart.index')" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200">Cart</Link>
                    </div>

                    <div v-if="user" class="border-t border-gray-200 pb-1 pt-4 dark:border-gray-700">
                        <div class="px-4">
                            <div class="text-base font-medium text-gray-800 dark:text-gray-200">{{ user.name }}</div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ user.email }}</div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <Link
                                :href="route('profile.edit')"
                                class="block w-full px-4 py-2 text-left text-base font-medium text-gray-600 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                            >
                                Profile
                            </Link>
                            <button
                                type="button"
                                @click="logout"
                                class="block w-full px-4 py-2 text-left text-base font-medium text-gray-600 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                            >
                                Log Out
                            </button>
                        </div>
                    </div>

                    <div v-else class="border-t border-gray-200 pb-3 pt-4 dark:border-gray-700">
                        <div class="px-4 flex gap-2">
                            <Link
                                :href="route('login')"
                                class="inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                            >
                                Login
                            </Link>
                            <Link
                                :href="route('login', { mode: 'register' })"
                                class="inline-flex items-center rounded-md bg-orange-600 px-3 py-2 text-sm font-medium text-white hover:bg-orange-700 transition"
                            >
                                Register
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>

            <header class="bg-white dark:bg-gray-800 shadow" v-if="$slots.header">
                <div class="w-full px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main class="w-full px-4 py-6 sm:px-6 lg:px-8"><slot /></main>
        </div>

        <button
            v-if="user"
            type="button"
            class="fixed bottom-6 right-6 z-[95] inline-flex h-14 w-14 items-center justify-center rounded-full bg-orange-600 text-white shadow-lg transition hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2"
            aria-label="Open messages"
            @click="router.get(route('messages.index'))"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <span v-if="unreadMessagesCount > 0" class="absolute -top-1 -right-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white border-2 border-white">
                {{ unreadMessagesCount }}
            </span>
        </button>
    </div>
</template>

