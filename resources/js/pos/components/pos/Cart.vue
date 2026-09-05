<script setup>
import { computed, nextTick, ref, watch } from 'vue';
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
const cartList = ref(null);

const currentCustomerLabel = computed(() => store.customer?.name ?? store.t('walk_in_customer'));
const displayedItems = computed(() => [...store.items].reverse());

watch(() => store.items, async () => {
    await nextTick();
    cartList.value?.scrollTo({ top: 0, behavior: 'smooth' });
});
</script>

<template>
    <div class="mb-4 flex items-start justify-between gap-3 border-b border-white/8 pb-4">
        <div class="min-w-0">
            <p class="text-xs uppercase tracking-[0.22em] text-[var(--pos-muted)]">{{ store.t('current_sale') }}</p>
            <h2 class="mt-1 text-2xl font-bold text-white">{{ store.t('new_sale') }}</h2>
        </div>
        <div class="min-w-0 shrink text-right">
            <p class="text-xs uppercase tracking-[0.22em] text-[var(--pos-muted)]">{{ store.t('customer') }}</p>
            <button class="mt-1 max-w-full truncate font-semibold text-[var(--pos-accent-strong)]" @click="emit('open-customer')">{{ currentCustomerLabel }}</button>
        </div>
    </div>

    <div ref="cartList" class="pos-scrollbar flex-1 overflow-y-auto pr-1">
        <div v-if="!store.items.length" class="flex h-56 items-center justify-center rounded-[28px] border border-dashed border-white/10 text-center text-sm text-[var(--pos-muted)]">
            Your cart is empty. Scan or search a product to begin.
        </div>

        <CartItem v-for="item in displayedItems" :key="item.productId" :item="item" />
    </div>
</template>
