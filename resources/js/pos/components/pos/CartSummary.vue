<script setup>
import { usePosStore } from '../../stores/posStore';

defineProps({
    canOpenPayment: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['hold', 'payment', 'new-sale']);
const store = usePosStore();
</script>

<template>
    <div class="mt-4 rounded-[28px] border border-white/8 bg-white/[0.03] p-4">
        <div class="space-y-2 text-sm text-[var(--pos-muted)]">
            <div class="flex justify-between"><span>Subtotal</span><strong class="text-white">{{ store.formatMoney(store.totals.subtotal) }}</strong></div>
            <div class="flex justify-between"><span>Discount</span><strong class="text-white">{{ store.formatMoney(store.totals.discountTotal) }}</strong></div>
            <div class="flex justify-between"><span>Tax</span><strong class="text-white">{{ store.formatMoney(store.totals.taxTotal) }}</strong></div>
        </div>

        <div class="mt-4 rounded-[26px] bg-[var(--pos-accent)]/12 p-4">
            <p class="text-xs uppercase tracking-[0.24em] text-[var(--pos-accent-strong)]">Total</p>
            <p class="mt-2 font-[var(--font-display)] text-4xl font-black text-white md:text-5xl">{{ store.formatMoney(store.totals.grandTotal) }}</p>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            <button type="button" class="pos-button-secondary min-h-12" :disabled="store.loading.holdingSale || !store.canHoldSale" @click="emit('hold')">
                {{ store.loading.holdingSale ? 'Holding…' : 'Hold Sale' }}
            </button>
            <button type="button" class="pos-button-primary min-h-12" :disabled="!canOpenPayment || store.loading.completingSale" @click="emit('payment')">
                {{ store.loading.completingSale ? 'Processing…' : 'Payment (F8)' }}
            </button>
            <button type="button" class="pos-button-secondary min-h-12" :disabled="!store.items.length" @click="emit('new-sale')">New Sale</button>
        </div>
    </div>
</template>
