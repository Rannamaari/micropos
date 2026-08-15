<script setup>
import { usePosStore } from '../../stores/posStore';

defineProps({
    results: {
        type: Array,
        required: true,
    },
    selectedIndex: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['add', 'select-index']);
const store = usePosStore();
</script>

<template>
    <div class="flex-1 overflow-hidden rounded-[28px] border border-white/8 bg-black/10">
        <div class="flex items-center justify-between border-b border-white/8 px-4 py-3 text-xs uppercase tracking-[0.22em] text-[var(--pos-muted)]">
            <span>Products</span>
            <span v-if="store.loading.searchingProducts">Searching…</span>
        </div>
        <div class="pos-scrollbar h-full max-h-[calc(100vh-22rem)] overflow-y-auto">
            <button
                v-for="(result, index) in results"
                :key="result.id"
                class="flex w-full items-center justify-between gap-4 border-b border-white/6 px-4 py-4 text-left transition hover:bg-white/6"
                :class="{ 'bg-white/8': index === selectedIndex }"
                @mouseenter="emit('select-index', index)"
                @click="emit('add', result)"
            >
                <div>
                    <p class="font-semibold text-white">{{ result.name }}</p>
                    <p class="mt-1 text-sm text-[var(--pos-muted)]">{{ result.sku }}<span v-if="result.barcode"> • {{ result.barcode }}</span></p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-[var(--pos-accent-strong)]">{{ store.formatMoney(result.price) }}</p>
                    <p class="mt-1 text-sm text-[var(--pos-muted)]">{{ result.stock_label ? `Stock: ${result.stock_label}` : 'Stock unavailable' }}</p>
                </div>
            </button>
            <div v-if="!results.length" class="flex h-64 items-center justify-center px-6 text-center text-sm text-[var(--pos-muted)]">
                Scan a barcode or type to search the catalog.
            </div>
        </div>
    </div>
</template>
