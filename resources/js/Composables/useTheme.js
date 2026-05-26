import { ref, watch, onMounted } from 'vue';

const isDark = ref(false);

export function useTheme() {
    const initializeTheme = () => {
        const stored = localStorage.getItem('theme');

        if (stored) {
            isDark.value = stored === 'dark';
        } else {
            isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        applyTheme();
    };

    const applyTheme = () => {
        const html = document.documentElement;

        if (isDark.value) {
            html.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            html.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    };

    const toggleTheme = () => {
        isDark.value = !isDark.value;
    };

    const setTheme = (theme) => {
        isDark.value = theme === 'dark';
    };

    // Watcher para aplicar cambios
    watch(isDark, applyTheme);

    // Inicializar al montar
    onMounted(initializeTheme);

    return {
        isDark,
        toggleTheme,
        setTheme,
        initializeTheme,
    };
}

