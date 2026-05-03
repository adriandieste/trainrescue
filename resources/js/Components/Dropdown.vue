<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
    },
    width: {
        type: String,
        default: '48',
    },
    contentClasses: {
        type: String,
        default: 'py-1 bg-white',
    },
});

const triggerRef = ref(null);
const panelRef = ref(null);
const open = ref(false);
const panelStyle = ref({});

const widthValue = computed(() => {
    return {
        48: 192,
        72: 288,
    }[props.width.toString()] ?? 192;
});

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

const updatePosition = () => {
    const trigger = triggerRef.value;
    const panel = panelRef.value;

    if (!open.value || !trigger) {
        return;
    }

    const rect = trigger.getBoundingClientRect();
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    const dropdownWidth = widthValue.value;
    const gap = 8;
    const panelHeight = panel?.offsetHeight ?? 0;

    let left = rect.left;

    if (props.align === 'right') {
        left = rect.right - dropdownWidth;
    } else if (props.align !== 'left') {
        left = rect.left + (rect.width / 2) - (dropdownWidth / 2);
    }

    left = Math.max(12, Math.min(left, viewportWidth - dropdownWidth - 12));

    let top = rect.bottom + gap;

    if (panelHeight > 0 && top + panelHeight > viewportHeight - 12) {
        top = Math.max(12, rect.top - gap - panelHeight);
    }

    panelStyle.value = {
        position: 'fixed',
        top: `${top}px`,
        left: `${left}px`,
        width: `${dropdownWidth}px`,
        zIndex: '60',
    };
};

const toggleDropdown = async () => {
    open.value = !open.value;

    if (open.value) {
        await nextTick();
        updatePosition();
        requestAnimationFrame(updatePosition);
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

onMounted(() => {
    window.addEventListener('resize', updatePosition);
    window.addEventListener('scroll', updatePosition, true);
});

onUnmounted(() => {
    window.removeEventListener('resize', updatePosition);
    window.removeEventListener('scroll', updatePosition, true);
});

watch(open, async (isOpen) => {
    if (!isOpen) {
        return;
    }

    await nextTick();
    updatePosition();
});

const widthClass = computed(() => {
    return {
        48: 'w-48',
        72: 'w-72',
    }[props.width.toString()];
});

</script>

<template>
    <div class="relative">
        <div ref="triggerRef" @click="toggleDropdown">
            <slot name="trigger" />
        </div>

        <Teleport to="body">
            <div
                v-show="open"
                class="fixed inset-0 z-50"
                @click="open = false"
            ></div>

            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    ref="panelRef"
                    v-show="open"
                    class="rounded-md shadow-lg"
                    :class="[widthClass]"
                    :style="panelStyle"
                    @click="open = false"
                >
                    <div
                        class="rounded-md ring-1 ring-black ring-opacity-5"
                        :class="contentClasses"
                    >
                        <slot name="content" />
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
