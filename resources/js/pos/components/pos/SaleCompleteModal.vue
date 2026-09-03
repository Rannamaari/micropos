<script setup>
import { computed } from 'vue';
import { usePosStore } from '../../stores/posStore';

defineEmits(['close']);
const store = usePosStore();
const changeDue = computed(() => (store.saleCompleteModal?.payments ?? [])
    .reduce((total, payment) => total + Number(payment.change_due ?? 0), 0));
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/65 p-4">
        <div class="pos-card my-auto w-full max-w-2xl rounded-[32px] p-5 text-center sm:p-6">
            <p class="text-xs uppercase tracking-[0.24em] text-[var(--pos-success)]">Sale Complete</p>
            <h2 class="mt-3 break-words text-3xl font-black text-white sm:text-4xl">{{ store.saleCompleteModal.sale_number }}</h2>
            <div class="mt-6 grid gap-3 text-left min-[560px]:grid-cols-3">
                <div class="min-w-0 rounded-2xl bg-white/[0.04] p-4">
                    <p class="text-xs uppercase tracking-[0.18em] text-[var(--pos-muted)]">Total</p>
                    <p class="mt-1 break-words text-lg font-bold tabular-nums text-white">{{ store.formatMoney(store.saleCompleteModal.grand_total) }}</p>
                </div>
                <div class="min-w-0 rounded-2xl bg-white/[0.04] p-4">
                    <p class="text-xs uppercase tracking-[0.18em] text-[var(--pos-muted)]">Paid</p>
                    <p class="mt-1 break-words text-lg font-bold tabular-nums text-white">{{ store.formatMoney(store.saleCompleteModal.paid_total) }}</p>
                </div>
                <div class="min-w-0 rounded-2xl bg-[var(--pos-success)]/12 p-4">
                    <p class="text-xs uppercase tracking-[0.18em] text-[var(--pos-success)]">Change to Return</p>
                    <p class="mt-1 break-words text-lg font-bold tabular-nums text-[var(--pos-success)]">{{ store.formatMoney(changeDue) }}</p>
                </div>
            </div>
            <p v-if="Number(store.saleCompleteModal.balance_due) > 0" class="mt-3 text-sm text-amber-200">Outstanding balance: {{ store.formatMoney(store.saleCompleteModal.balance_due) }}</p>
            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <button class="pos-button-primary min-h-12" @click="$emit('close')">New Sale</button>
                <button class="pos-button-secondary min-h-12" @click="store.printActiveSale(store.saleCompleteModal, 'thermal')">Thermal Receipt</button>
                <button class="pos-button-secondary min-h-12" @click="store.printActiveSale(store.saleCompleteModal, 'a4')">A4 Tax Invoice</button>
            </div>
        </div>
    </div>
</template>
