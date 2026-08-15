<script setup>
import { computed } from 'vue';
import { usePosStore } from '../../stores/posStore';
import CartItem from './CartItem.vue';

defineProps({
    isResumedSale: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['open-customer']);
const store = usePosStore();

const currentCustomerLabel = computed(() => store.customer?.name ?? 'Walk-in Customer');
</script>

<template>
    <div class="mb-4 flex items-center justify-between gap-4 border-b border-white/8 pb-4">
        <div>
            <p class="text-xs uppercase tracking-[0.22em] text-[var(--pos-muted)]">Current Sale</p>
            <h2 class="mt-1 text-2xl font-bold text-white">{{ isResumedSale ? 'Resumed Sale' : 'New Sale' }}</h2>
        </div>
        <div class="text-right">
            <p class="text-xs uppercase tracking-[0.22em] text-[var(--pos-muted)]">Customer</p>
            <button class="mt-1 font-semibold text-[var(--pos-accent-strong)]" @click="emit('open-customer')">{{ currentCustomerLabel }}</button>
        </div>
    </div>

    <div class="pos-scrollbar flex-1 overflow-y-auto pr-1">
        <div v-if="!store.items.length" class="flex h-56 items-center justify-center rounded-[28px] border border-dashed border-white/10 text-center text-sm text-[var(--pos-muted)]">
            Your cart is empty. Scan or search a product to begin.
        </div>

        <CartItem v-for="item in store.items" :key="item.productId" :item="item" />
    </div>
</template>
