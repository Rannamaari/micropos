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
    <article class="mb-3 rounded-[26px] border border-white/8 bg-white/[0.03] p-4">
        <div class="mb-3 flex items-start justify-between gap-4">
            <div>
                <h3 class="font-semibold text-white">{{ item.name }}</h3>
                <p class="mt-1 text-sm text-[var(--pos-muted)]">{{ item.sku }}<span v-if="item.barcode"> • {{ item.barcode }}</span></p>
            </div>
            <button type="button" class="text-sm font-semibold text-rose-200 transition hover:text-rose-100" @click="store.removeItem(item.productId)">Remove</button>
        </div>

        <div class="grid gap-3 md:grid-cols-[auto_1fr_auto] md:items-center">
            <div class="flex items-center gap-2">
                <button type="button" class="pos-button-secondary h-11 w-11 rounded-2xl px-0 py-0" @click="store.decrementItem(item.productId)">-</button>
                <input
                    class="pos-input w-24 text-center"
                    :value="item.quantity"
                    @change="store.updateItemQuantity(item.productId, $event.target.value)"
                >
                <button type="button" class="pos-button-secondary h-11 w-11 rounded-2xl px-0 py-0" @click="store.incrementItem(item.productId)">+</button>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <label class="text-sm text-[var(--pos-muted)]">
                    Price
                    <input
                        class="pos-input mt-1"
                        :disabled="!store.canOverridePrice"
                        :value="item.price"
                        @change="store.updateItemPrice(item.productId, $event.target.value)"
                    >
                </label>
                <label class="text-sm text-[var(--pos-muted)]">
                    Discount
                    <input
                        class="pos-input mt-1"
                        :disabled="!store.canDiscount"
                        :value="item.discountAmount"
                        @change="store.updateItemDiscount(item.productId, $event.target.value)"
                    >
                </label>
                <div class="text-sm text-[var(--pos-muted)]">
                    Tax
                    <div class="pos-input mt-1 flex items-center">{{ item.taxRate.toFixed(2) }}%</div>
                </div>
            </div>

            <div class="text-right">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--pos-muted)]">Line Total</p>
                <p class="mt-1 text-xl font-bold text-white">{{ store.formatMoney(lineTotal) }}</p>
            </div>
        </div>
    </article>
</template>
