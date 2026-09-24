import { onBeforeUnmount, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { onClickOutside, onKeyStroke, useMediaQuery, useScrollLock } from '@vueuse/core';

/**
 * Comportamento compartilhado dos layouts administrativos:
 * sidebar em gaveta no mobile e dropdown do usuário.
 */
export function useAdminLayout() {
    const sidebarOpen = ref(false);
    const showUserMenu = ref(false);
    const userMenuRef = ref(null);

    const isDesktop = useMediaQuery('(min-width: 1024px)');
    const bodyLocked = useScrollLock(typeof document !== 'undefined' ? document.body : null);

    const openSidebar = () => { sidebarOpen.value = true; };
    const closeSidebar = () => { sidebarOpen.value = false; };
    const closeUserMenu = () => { showUserMenu.value = false; };

    // Trava a rolagem da página enquanto a gaveta estiver aberta no mobile.
    watch([sidebarOpen, isDesktop], ([open, desktop]) => {
        bodyLocked.value = open && !desktop;
        if (desktop) sidebarOpen.value = false;
    });

    onClickOutside(userMenuRef, closeUserMenu);

    onKeyStroke('Escape', () => {
        closeSidebar();
        closeUserMenu();
    });

    // Fecha menus ao navegar para outra página.
    const removeNavigateListener = router.on('navigate', () => {
        closeSidebar();
        closeUserMenu();
    });

    onBeforeUnmount(() => {
        removeNavigateListener();
        bodyLocked.value = false;
    });

    return {
        sidebarOpen,
        showUserMenu,
        userMenuRef,
        openSidebar,
        closeSidebar,
        closeUserMenu,
    };
}
