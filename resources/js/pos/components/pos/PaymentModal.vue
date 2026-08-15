<script setup>
import { computed, ref } from 'vue';
import { usePosStore } from '../../stores/posStore';

const emit = defineEmits(['close']);
const store = usePosStore();

const form = ref({
    payment_method: 'cash',
    amount: store.paymentTotals.remaining || store.totals.grandTotal,
    amount_tendered: '',
    reference: '',
    notes: '',
});

const projected = computed(() => {
    const payments = [...store.payments, {
        amount: Number(form.value.amount || 0),
    }];

    return store.formatMoney(Math.max(0, store.totals.grandTotal - payments.reduce((sum, entry) => sum + Number(entry.amount || 0), 0)));
});

function addPayment() {
    store.addPayment(form.value);
    form.value = {
        payment_method: 'cash',
        amount: store.paymentTotals.remaining,
        amount_tendered: '',
        reference: '',
        notes: '',
    };
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/55 p-4">
        <div class="pos-card w-full max-w-3xl rounded-[32px] p-6">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.22em] text-[var(--pos-muted)]">Payment</p>
                    <h2 class="mt-1 text-2xl font-bold text-white">Complete Sale</h2>
                </div>
                <button class="pos-button-secondary" @click="emit('close')">Close</button>
            </div>

            <div class="mb-6 grid gap-4 md:grid-cols-3">
                <div class="rounded-[26px] bg-white/[0.03] p-4">
                    <p class="text-xs uppercase tracking-[0.22em] text-[var(--pos-muted)]">Total</p>
                    <p class="mt-2 text-3xl font-bold text-white">{{ store.formatMoney(store.totals.grandTotal) }}</p>
                </div>
                <div class="rounded-[26px] bg-white/[0.03] p-4">
                    <p class="text-xs uppercase tracking-[0.22em] text-[var(--pos-muted)]">Paid</p>
                    <p class="mt-2 text-3xl font-bold text-white">{{ store.formatMoney(store.paymentTotals.paid) }}</p>
                </div>
                <div class="rounded-[26px] bg-white/[0.03] p-4">
                    <p class="text-xs uppercase tracking-[0.22em] text-[var(--pos-muted)]">Remaining</p>
                    <p class="mt-2 text-3xl font-bold text-[var(--pos-accent-strong)]">{{ store.formatMoney(store.paymentTotals.remaining) }}</p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div class="rounded-[28px] border border-white/8 bg-white/[0.03] p-4">
                    <div class="grid gap-3 md:grid-cols-2">
                        <select v-model="form.payment_method" class="pos-input">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Other</option>
                        </select>
                        <input v-model="form.amount" class="pos-input" placeholder="Applied amount">
                        <input v-model="form.amount_tendered" class="pos-input" placeholder="Amount tendered">
                        <input v-model="form.reference" class="pos-input" placeholder="Reference">
                    </div>
                    <textarea v-model="form.notes" class="pos-input mt-3 min-h-28" placeholder="Payment notes"></textarea>
                    <div class="mt-3 flex items-center justify-between text-sm text-[var(--pos-muted)]">
                        <span>Projected remaining: {{ projected }}</span>
                        <button class="pos-button-primary" @click="addPayment">Add Payment</button>
                    </div>
                </div>

                <div class="rounded-[28px] border border-white/8 bg-white/[0.03] p-4">
                    <p class="mb-3 text-xs uppercase tracking-[0.22em] text-[var(--pos-muted)]">Payments</p>
                    <div class="space-y-3">
                        <div v-for="payment in store.payments" :key="payment.id" class="rounded-[22px] border border-white/8 p-3">
                            <div class="flex items-center justify-between">
                                <strong class="uppercase text-white">{{ payment.payment_method }}</strong>
                                <button class="text-sm text-rose-200" @click="store.removePayment(payment.id)">Remove</button>
                            </div>
                            <p class="mt-2 text-lg font-semibold text-[var(--pos-accent-strong)]">{{ store.formatMoney(payment.amount) }}</p>
                            <p v-if="payment.amount_tendered" class="mt-1 text-sm text-[var(--pos-muted)]">Tendered: {{ store.formatMoney(payment.amount_tendered) }}</p>
                            <p v-if="payment.amount_tendered" class="text-sm text-[var(--pos-muted)]">Change: {{ store.formatMoney(Math.max(0, payment.amount_tendered - payment.amount)) }}</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-3">
                        <button class="pos-button-primary w-full" :disabled="store.loading.completingSale || (!store.payments.length && !store.canUseCredit)" @click="store.completeSale()">
                            {{ store.loading.completingSale ? 'Completing…' : 'Complete Sale' }}
                        </button>
                        <p class="text-sm text-[var(--pos-muted)]">
                            Credit checkout is allowed only when the selected customer and your permissions permit it.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
