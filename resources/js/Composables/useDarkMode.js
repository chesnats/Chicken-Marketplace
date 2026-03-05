import { onMounted, ref } from 'vue';

const STORAGE_KEY = 'theme';
const isDark = ref(false);

const applyTheme = (dark) => {
    isDark.value = dark;
    document.documentElement.classList.toggle('dark', dark);
};

const setTheme = (dark) => {
    applyTheme(dark);
    localStorage.setItem(STORAGE_KEY, dark ? 'dark' : 'light');
};

const initTheme = () => {
    const saved = localStorage.getItem(STORAGE_KEY);

    if (saved === 'dark' || saved === 'light') {
        applyTheme(saved === 'dark');
        return;
    }

    applyTheme(window.matchMedia('(prefers-color-scheme: dark)').matches);
};

export default function useDarkMode() {
    onMounted(initTheme);

    return {
        isDark,
        setTheme,
        toggleTheme: () => setTheme(!isDark.value),
    };
}
