<script setup>
import useDarkMode from '@/Composables/useDarkMode';

const { isDark, toggleTheme } = useDarkMode();
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center bg-gray-100 dark:bg-gray-900 pt-6 sm:justify-center sm:pt-0"
    >
        <button
            type="button"
            @click="toggleTheme"
            class="absolute right-4 top-4 rounded-lg p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 transition"
            :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        >
            <span v-if="isDark" class="text-xs font-semibold">Light</span>
            <span v-else class="text-xs font-semibold">Dark</span>
        </button>
 
        <div
            class="mt-6 w-full overflow-hidden bg-white dark:bg-gray-800 px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
