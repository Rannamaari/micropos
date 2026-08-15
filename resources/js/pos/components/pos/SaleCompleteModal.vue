<script setup>
import { usePosStore } from '../../stores/posStore';

defineEmits(['close']);
const store = usePosStore();
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/65 p-4">
        <div class="pos-card w-full max-w-xl rounded-[32px] p-6 text-center">
            <p class="text-xs uppercase tracking-[0.24em] text-[var(--pos-success)]">Sale Complete</p>
            <h2 class="mt-3 text-4xl font-black text-white">{{ store.saleCompleteModal.sale_number }}</h2>
            <p class="mt-4 text-lg text-[var(--pos-muted)]">Total {{ store.formatMoney(store.saleCompleteModal.grand_total) }}</p>
            <p class="mt-2 text-lg text-[var(--pos-muted)]">Paid {{ store.formatMoney(store.saleCompleteModal.paid_total) }}</p>
            <div class="mt-6 flex justify-center gap-3">
                <button class="pos-button-primary" @click="$emit('close')">New Sale</button>
                <button class="pos-button-secondary" @click="window.print()">Print Placeholder</button>
            </div>
        </div>
    </div>
</template>
