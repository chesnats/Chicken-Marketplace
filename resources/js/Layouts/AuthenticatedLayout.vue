<script setup>
import { ref } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const showAllNotifications = ref(false);
const { props } = usePage();

const deleteNotification = (id) => {
    router.delete(route('notifications.destroy', id), {
        preserveScroll: true,
    });
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
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Dashboard
                                </NavLink>
                                
                                <NavLink :href="route('listings.index')" :active="route().current('listings.index')">
                                    Marketplace
                                </NavLink>
                                
                                <NavLink :href="route('messages.index')" :active="route().current('messages.index')" class="relative">
                                    Messages
                                    <span 
                                        v-if="$page.props.auth.unreadMessagesCount > 0 && !route().current('messages.index')" 
                                        class="absolute -right-3 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white border-2 border-white"
                                    >
                                        {{ $page.props.auth.unreadMessagesCount }}
                                    </span>
                                </NavLink>

                            <NavLink v-if="$page.props.auth.user.role === 'seller'"
                                :href="route('seller.orders.index')" 
                                :active="route().current('seller.orders.index')"
                                class="relative"
                            >
                                Incoming Orders
                                <span v-if="$page.props.auth.sellerOrderCount > 0 && !route().current('seller.orders.index')"
                                       class="absolute -right-3 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white border-2 border-white">
                                    {{ $page.props.auth.sellerOrderCount }}
                                </span>
                            </NavLink>

                            <NavLink v-if="$page.props.auth.user.role === 'buyer'"
                                :href="route('buyer.orders.index')" 
                                :active="route().current('buyer.orders.index')"
                                class="relative"
                            >
                                My Purchases
                                <span v-if="$page.props.auth.buyerOrderCount > 0 && !route().current('buyer.orders.index')"
                                      class="absolute -right-3 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white border-2 border-white">
                                    {{ $page.props.auth.buyerOrderCount }}
                                </span>
                            </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center gap-1">
                            
                            <div v-if="$page.props.auth.user.role === 'buyer'" class="relative mr-2">
                                <Link 
                                    :href="route('cart.index')" 
                                    class="p-2 text-gray-400 hover:text-gray-600 transition block relative"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span 
                                        v-if="$page.props.auth.cartCount > 0" 
                                        class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white border-2 border-white"
                                    
                                    >
                                        {{ $page.props.auth.cartCount }}
                                    </span>
                                </Link>
                            </div>

                        <div class="relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button @click="showAllNotifications = false" class="relative p-2 text-gray-400 hover:text-gray-600 focus:outline-none transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                        <span v-if="$page.props.auth.unreadNotificationsCount > 0" class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white border-2 border-white">
                                            {{ $page.props.auth.unreadNotificationsCount }}
                                        </span>
                                    </button>
                                </template>

                                <template #content>
                                    <div class="block px-4 py-2 text-xs font-semibold text-gray-400 uppercase border-b bg-gray-50/50">
                                        Notifications
                                    </div>
                                    
                                    <div class="max-h-96 overflow-y-auto custom-scrollbar">
                                        <div v-if="$page.props.auth.notifications.length === 0" class="p-4 text-sm text-gray-500 text-center italic">
                                            No new updates
                                        </div>
                                        
                                        <div 
                                            v-for="notif in (showAllNotifications ? $page.props.auth.notifications : $page.props.auth.notifications.slice(0, 5))" 
                                            :key="notif.id" 
                                            class="group relative flex items-center border-b last:border-0 hover:bg-gray-50 transition"
                                        >
                                            <button 
                                                @click="handleNotificationClick(notif)"
                                                class="flex-1 text-left px-4 py-3 pr-10"
                                                :class="notif.read_at ? 'opacity-60' : 'font-semibold text-gray-900'"
                                            >
                                                <div class="flex items-start gap-2">
                                                    <div v-if="!notif.read_at" class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-500"></div>
                                                    <div class="flex-1">
                                                        <p class="text-xs leading-tight">{{ notif.data.message }}</p>
                                                        <p class="text-[10px] text-gray-400 mt-1 uppercase">{{ new Date(notif.created_at).toLocaleDateString() }}</p>
                                                    </div>
                                                </div>
                                            </button>

                                            <button 
                                                @click.stop="deleteNotification(notif.id)"
                                                class="absolute right-2 text-gray-300 hover:text-red-500 p-2 transition rounded-md hover:bg-red-50"
                                                title="Delete notification"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="$page.props.auth.notifications.length > 5" class="bg-gray-50 border-t sticky bottom-0">
                                        <button 
                                            @click.stop="showAllNotifications = !showAllNotifications"
                                            class="w-full py-2 text-xs font-bold text-blue-600 hover:text-blue-800 transition text-center focus:outline-none"
                                        >
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

                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden">
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('listings.index')" :active="route().current('listings.index')">Marketplace</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('messages.index')" :active="route().current('messages.index')" class="flex justify-between items-center">
                            Messages
                            <span v-if="$page.props.auth.unreadMessagesCount > 0" class="flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white border-2 border-white">
                                {{ $page.props.auth.unreadMessagesCount }}
                            </span>
                        </ResponsiveNavLink>
                        
                        <ResponsiveNavLink 
                            v-if="$page.props.auth.user.role === 'seller'"
                            :href="route('seller.orders.index')" 
                            :active="route().current('seller.orders.index')"
                            class="flex justify-between items-center"
                        >
                            Incoming Orders
                            <span 
                                v-if="$page.props.auth.sellerOrderCount > 0" 
                                class="flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white border-2 border-white">
                            >
                                {{ $page.props.auth.sellerOrderCount }}
                            </span>
                        </ResponsiveNavLink>
                        <ResponsiveNavLink 
                            v-if="$page.props.auth.user.role === 'buyer'"
                            :href="route('buyer.orders.index')" 
                            :active="route().current('buyer.orders.index')"
                            class="flex justify-between items-center"
                        >
                            My Purchases
                            <span 
                                v-if="$page.props.auth.buyerOrderCount > 0" 
                                class="flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white border-2 border-white">
                            >
                                {{ $page.props.auth.buyerOrderCount }}
                            </span>
                        </ResponsiveNavLink>

                        <ResponsiveNavLink 
                            v-if="$page.props.auth.user.role === 'buyer'"
                            :href="route('cart.index')"
                            class="flex justify-between"
                        >
                            Cart
                            <span v-if="$page.props.auth.cartCount > 0" class="text-white font-bold">({{ $page.props.auth.cartCount }})</span>
                        </ResponsiveNavLink>
                    </div>

                    <div class="border-t border-gray-200 pb-1 pt-4">
                        <div class="px-4">
                            <div class="text-base font-medium text-gray-800">{{ $page.props.auth.user.name }}</div>
                            <div class="text-sm font-medium text-gray-500">{{ $page.props.auth.user.email }}</div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')"> Profile </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button"> Log Out </ResponsiveNavLink>
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
<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
</style>