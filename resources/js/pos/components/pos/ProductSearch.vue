<script setup>
import { nextTick, onMounted, ref, watch } from 'vue';
import { usePosStore } from '../../stores/posStore';

const store = usePosStore();

const props = defineProps({
    modelValue: {
        type: String,
        required: true,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'search-input', 'submit']);
const input = ref(null);

function focusInput() {
    nextTick(() => input.value?.focus());
}

function onInput(event) {
    emit('update:modelValue', event.target.value);
    emit('search-input', event.target.value);
}

function onKeydown(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        emit('submit');
    }
}

defineExpose({ focusInput });

watch(() => props.disabled, (value) => {
    if (! value) {
        focusInput();
    }
});

onMounted(focusInput);
</script>

<template>
    <div class="mb-4">
        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-[var(--pos-muted)]">
            {{ store.t('search_scan') }}
        </label>
        <div class="flex gap-3">
            <input
                ref="input"
                :value="modelValue"
                :disabled="disabled"
                class="pos-input text-lg"
                :placeholder="store.t('search_placeholder')"
                @input="onInput"
                @keydown="onKeydown"
            >
            <button class="pos-button-primary px-6" :disabled="disabled" @click="emit('submit')">Enter</button>
        </div>
    </div>
</template>
