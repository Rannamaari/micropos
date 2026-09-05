<script setup>
import { computed } from 'vue';
import { usePosStore } from '../../stores/posStore';

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});

const store = usePosStore();

const lineTotal = computed(() => {
    const subtotal = props.item.quantity * props.item.price;
    const taxable = Math.max(0, subtotal - props.item.discountAmount);
    const tax = taxable * (props.item.taxRate / 100);
    return taxable + tax;
});
</script>

<template>
    <article class="mb-3 rounded-[26px] border border-white/8 bg-white/[0.03] p-4 sm:p-5">
        <div class="mb-3 flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h3 class="font-semibold text-white">{{ item.name }}</h3>
                <p class="mt-1 truncate text-sm text-[var(--pos-muted)]">{{ item.sku }}<span v-if="item.barcode"> • {{ item.barcode }}</span></p>
            </div>
            <button type="button" class="min-h-11 shrink-0 rounded-xl px-2 text-sm font-semibold text-rose-200 transition hover:bg-rose-100/10 hover:text-rose-100" @click="store.removeItem(item.productId)">{{ store.t('remove') }}</button>
        </div>

        <div class="grid gap-4 2xl:grid-cols-[auto_minmax(0,1fr)_auto] 2xl:items-end">
            <div class="flex items-center gap-2">
                <button type="button" class="pos-button-secondary h-12 w-12 rounded-2xl px-0 py-0 text-2xl" aria-label="Decrease quantity" @click="store.decrementItem(item.productId)">-</button>
                <input
                    class="pos-input !w-24 !min-w-0 text-center text-lg font-semibold tabular-nums"
                    :value="item.quantity"
                    inputmode="decimal"
                    aria-label="Quantity"
                    @change="store.updateItemQuantity(item.productId, $event.target.value)"
                >
                <button type="button" class="pos-button-secondary h-12 w-12 rounded-2xl px-0 py-0 text-2xl" aria-label="Increase quantity" @click="store.incrementItem(item.productId)">+</button>
            </div>

            <div class="grid min-w-0 gap-3 sm:grid-cols-3">
                <label class="min-w-0 text-sm text-[var(--pos-muted)]">
                    Price
                    <input
                        class="pos-input mt-1"
                        :disabled="!store.canOverridePrice"
                        :value="item.price"
                        @change="store.updateItemPrice(item.productId, $event.target.value)"
                    >
                </label>
                <label class="min-w-0 text-sm text-[var(--pos-muted)]">
                    Discount
                    <input
                        class="pos-input mt-1"
                        :disabled="!store.canDiscount"
                        :value="item.discountAmount"
                        @change="store.updateItemDiscount(item.productId, $event.target.value)"
                    >
                </label>
                <div class="min-w-0 text-sm text-[var(--pos-muted)]">
                    Tax
                    <div class="pos-input mt-1 flex min-h-12 items-center tabular-nums">{{ item.taxRate.toFixed(2) }}%</div>
                </div>
            </div>

            <div class="rounded-2xl bg-white/[0.04] p-3 text-left 2xl:text-right">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--pos-muted)]">Line Total</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-white">{{ store.formatMoney(lineTotal) }}</p>
            </div>
        </div>
    </article>
</template>
