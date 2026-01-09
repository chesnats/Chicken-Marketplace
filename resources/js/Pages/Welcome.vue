<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

function handleImageError() {
    document.getElementById('screenshot-container')?.classList.add('!hidden');
    document.getElementById('docs-card')?.classList.add('!row-span-1');
    document.getElementById('docs-card-content')?.classList.add('!flex-row');
    document.getElementById('background')?.classList.add('!hidden');
}
</script>

<template>
    <div class="bg-orange-50 min-h-screen flex flex-col items-center justify-center">
        <h1 class="text-5xl font-extrabold text-orange-600">Premium Chicken Marketplace</h1>
        <p class="text-xl mt-4 text-gray-700">The safest place to buy and sell quality breeds.</p>
        
        <div class="mt-8 space-x-4">
            <Link :href="route('login')" class="bg-orange-600 text-white px-6 py-3 rounded-lg font-bold">Login to Browse</Link>
            <Link :href="route('register')" class="bg-white border-2 border-orange-600 text-orange-600 px-6 py-3 rounded-lg font-bold">Start Selling</Link>
        </div>
    </div>
</template>