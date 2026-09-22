<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import { usePosStore } from '../../stores/posStore';

const emit = defineEmits(['close']);
const store = usePosStore();
const tenderedInput = ref(null);
const appliedAmountInput = ref(null);
const paymentMethods = [
    { value: 'cash', label: 'Cash' },
    { value: 'card', label: 'Card' },
    { value: 'bank_transfer', label: 'Bank Transfer' },
    { value: 'other', label: 'Other' },
];
const paymentCurrencies = computed(() => [
    { code: store.currency, rate: 1 },
    ...(store.secondaryCurrency ? [{ code: store.secondaryCurrency, rate: Number(store.secondaryCurrencyRate) }] : []),
]);
const form = ref({
    payment_method: 'cash',
    currency: store.currency,
    amount: store.paymentTotals.remaining || store.totals.grandTotal,
    amount_tendered: '',
    reference: '',
    notes: '',
});

const remaining = computed(() => Number(store.paymentTotals.remaining));
const selectedRate = computed(() => paymentCurrencies.value.find((currency) => currency.code === form.value.currency)?.rate ?? 1);
const remainingInPaymentCurrency = computed(() => remaining.value * selectedRate.value);
const tendered = computed(() => Math.max(0, Number(form.value.amount_tendered) || 0));
const appliedCurrencyAmount = computed(() => {
    if (form.value.payment_method === 'cash') {
        return Math.min(tendered.value, remainingInPaymentCurrency.value);
    }

    return Math.min(Math.max(0, Number(form.value.amount) || 0), remainingInPaymentCurrency.value);
});
const appliedAmount = computed(() => appliedCurrencyAmount.value / selectedRate.value);
const changeDue = computed(() => form.value.payment_method === 'cash'
    ? Math.max(0, tendered.value - appliedCurrencyAmount.value)
    : 0);
const canAddPayment = computed(() => appliedAmount.value > 0);
const projected = computed(() => store.formatMoney(Math.max(0, remaining.value - appliedAmount.value)));

function resetForm(paymentMethod = 'cash') {
    form.value = {
        payment_method: paymentMethod,
        currency: form.value.currency ?? store.currency,
        amount: store.paymentTotals.remaining * selectedRate.value,
        amount_tendered: '',
        reference: '',
        notes: '',
    };
}

function selectCurrency(currency) {
    form.value.currency = currency;
    form.value.amount = remaining.value * selectedRate.value;
    form.value.amount_tendered = '';
    focusPaymentInput();
}

function focusPaymentInput() {
    nextTick(() => {
        const input = form.value.payment_method === 'cash'
            ? tenderedInput.value
            : appliedAmountInput.value;

        input?.focus();
        input?.select();
    });
}

function selectPaymentMethod(paymentMethod) {
    resetForm(paymentMethod);
    focusPaymentInput();
}

function addPayment() {
    if (! canAddPayment.value) return;

    store.addPayment({
        ...form.value,
        amount: appliedAmount.value,
        currency_amount: appliedCurrencyAmount.value,
        exchange_rate: selectedRate.value,
        amount_tendered: form.value.payment_method === 'cash' ? tendered.value : null,
    });

    // After any payment, cash is ready for the balance without another click.
    resetForm('cash');
    focusPaymentInput();
}

function onPaymentKeydown(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        addPayment();
    }
}

onMounted(focusPaymentInput);
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/55 p-4">
        <div class="pos-card my-auto w-full max-w-3xl rounded-[32px] p-5 sm:p-6">
            <div class="mb-5 flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.22em] text-[var(--pos-muted)]">{{ store.t('payment') }}</p>
                    <h2 class="mt-1 text-2xl font-bold text-white">{{ store.t('complete_sale') }}</h2>
                </div>
                <button class="pos-button-secondary min-h-12" @click="emit('close')">Close</button>
            </div>

            <div class="mb-6 grid gap-3 min-[560px]:grid-cols-3">
                <div class="rounded-[26px] bg-white/[0.03] p-4">
                    <p class="text-xs uppercase tracking-[0.22em] text-[var(--pos-muted)]">Total</p>
                    <p class="mt-2 break-words text-2xl font-bold tabular-nums text-white">{{ store.formatMoney(store.totals.grandTotal) }}</p>
                    <p v-if="store.formatSecondaryMoney(store.totals.grandTotal)" class="mt-1 text-sm font-semibold tabular-nums text-[var(--pos-accent-strong)]">≈ {{ store.formatSecondaryMoney(store.totals.grandTotal) }}</p>
                </div>
                <div class="rounded-[26px] bg-white/[0.03] p-4">
                    <p class="text-xs uppercase tracking-[0.22em] text-[var(--pos-muted)]">Paid</p>
                    <p class="mt-2 break-words text-2xl font-bold tabular-nums text-white">{{ store.formatMoney(store.paymentTotals.paid) }}</p>
                    <p v-if="store.formatSecondaryMoney(store.paymentTotals.paid)" class="mt-1 text-sm font-semibold tabular-nums text-[var(--pos-accent-strong)]">≈ {{ store.formatSecondaryMoney(store.paymentTotals.paid) }}</p>
                </div>
                <div class="rounded-[26px] bg-white/[0.03] p-4">
                    <p class="text-xs uppercase tracking-[0.22em] text-[var(--pos-muted)]">Balance</p>
                    <p class="mt-2 break-words text-2xl font-bold tabular-nums text-[var(--pos-accent-strong)]">{{ store.formatMoney(store.paymentTotals.remaining) }}</p>
                    <p v-if="store.formatSecondaryMoney(store.paymentTotals.remaining)" class="mt-1 text-sm font-semibold tabular-nums text-[var(--pos-accent-strong)]">≈ {{ store.formatSecondaryMoney(store.paymentTotals.remaining) }}</p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="rounded-[28px] border border-white/8 bg-white/[0.03] p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--pos-muted)]">Payment Method</p>
                    <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <button
                            v-for="method in paymentMethods"
                            :key="method.value"
                            type="button"
                            class="min-h-12 rounded-2xl border px-3 text-sm font-semibold transition"
                            :class="form.payment_method === method.value
                                ? 'border-[var(--pos-accent)] bg-[var(--pos-accent)]/15 text-[var(--pos-accent-strong)]'
                                : 'border-white/10 bg-white/[0.03] text-[var(--pos-paper)] hover:bg-white/[0.08]'"
                            @click="selectPaymentMethod(method.value)"
                        >
                            {{ method.label }}
                        </button>
                    </div>

                    <div v-if="paymentCurrencies.length > 1" class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--pos-muted)]">Payment Currency</p>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <button
                                v-for="paymentCurrency in paymentCurrencies"
                                :key="paymentCurrency.code"
                                type="button"
                                class="min-h-12 rounded-2xl border px-3 font-bold transition"
                                :class="form.currency === paymentCurrency.code
                                    ? 'border-[var(--pos-accent)] bg-[var(--pos-accent)]/15 text-[var(--pos-accent-strong)]'
                                    : 'border-white/10 bg-white/[0.03] text-[var(--pos-paper)]'"
                                @click="selectCurrency(paymentCurrency.code)"
                            >
                                {{ paymentCurrency.code }}
                            </button>
                        </div>
                        <p v-if="form.currency !== store.currency" class="mt-2 text-sm text-[var(--pos-muted)]">
                            1 {{ store.currency }} = {{ selectedRate.toFixed(4) }} {{ form.currency }} · Balance {{ store.formatCurrency(remainingInPaymentCurrency, form.currency) }}
                        </p>
                    </div>

                    <div v-if="form.payment_method === 'cash'" class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="text-sm text-[var(--pos-muted)]">
                            {{ store.t('cash_tendered') }}
                            <input
                                ref="tenderedInput"
                                v-model="form.amount_tendered"
                                class="pos-input mt-1 text-lg font-semibold tabular-nums"
                                inputmode="decimal"
                                placeholder="Type amount received"
                                aria-label="Cash tendered"
                                @keydown="onPaymentKeydown"
                            >
                        </label>
                        <div class="rounded-2xl bg-[var(--pos-success)]/10 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-[var(--pos-success)]">{{ store.t('change_to_return') }}</p>
                            <p class="mt-1 text-2xl font-bold tabular-nums text-[var(--pos-success)]">{{ store.formatCurrency(changeDue, form.currency) }}</p>
                        </div>
                    </div>

                    <div v-else class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="text-sm text-[var(--pos-muted)]">
                            {{ form.payment_method === 'card' ? 'Card Amount' : 'Payment Amount' }}
                            <input
                                ref="appliedAmountInput"
                                v-model="form.amount"
                                class="pos-input mt-1 text-lg font-semibold tabular-nums"
                                inputmode="decimal"
                                aria-label="Payment amount"
                                @keydown="onPaymentKeydown"
                            >
                        </label>
                        <label class="text-sm text-[var(--pos-muted)]">
                            Reference (optional)
                            <input v-model="form.reference" class="pos-input mt-1" placeholder="Card slip or transfer reference" @keydown="onPaymentKeydown">
                        </label>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 rounded-2xl bg-white/[0.03] p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.18em] text-[var(--pos-muted)]">Applied to Sale</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-white">{{ store.formatCurrency(appliedCurrencyAmount, form.currency) }}</p>
                            <p v-if="form.currency !== store.currency" class="mt-1 text-sm text-[var(--pos-muted)]">Applied as {{ store.formatMoney(appliedAmount) }}</p>
                            <p class="mt-1 text-sm text-[var(--pos-muted)]">Balance after this payment: {{ projected }}</p>
                        </div>
                        <button class="pos-button-primary min-h-12" :disabled="!canAddPayment" @click="addPayment">Add Payment</button>
                    </div>
                </div>

                <div class="rounded-[28px] border border-white/8 bg-white/[0.03] p-4">
                    <p class="mb-3 text-xs uppercase tracking-[0.22em] text-[var(--pos-muted)]">Added Payments</p>
                    <div v-if="store.payments.length" class="space-y-3">
                        <div v-for="payment in store.payments" :key="payment.id" class="rounded-[22px] border border-white/8 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <strong class="uppercase text-white">{{ payment.payment_method.replace('_', ' ') }}</strong>
                                <button class="min-h-11 rounded-xl px-2 text-sm text-rose-200" @click="store.removePayment(payment.id)">Remove</button>
                            </div>
                            <p class="mt-2 text-lg font-semibold tabular-nums text-[var(--pos-accent-strong)]">{{ store.formatCurrency(payment.currency_amount, payment.currency) }}</p>
                            <p v-if="payment.currency !== store.currency" class="mt-1 text-sm text-[var(--pos-muted)]">Applied: {{ store.formatMoney(payment.amount) }} at {{ payment.exchange_rate }}</p>
                            <p v-if="payment.amount_tendered !== null" class="mt-1 text-sm text-[var(--pos-muted)]">Tendered: {{ store.formatCurrency(payment.amount_tendered, payment.currency) }}</p>
                        </div>
                    </div>
                    <p v-else class="rounded-2xl border border-dashed border-white/10 p-4 text-sm text-[var(--pos-muted)]">Add a card payment, cash payment, or both.</p>
                    <button class="pos-button-primary mt-4 min-h-12 w-full" :disabled="store.loading.completingSale || (!store.payments.length && !store.canUseCredit)" @click="store.completeSale()">
                        {{ store.loading.completingSale ? '...' : store.t('complete_sale') }}
                    </button>
                    <p class="mt-3 text-sm text-[var(--pos-muted)]">For a split payment, add the card amount first, then enter the cash tendered for the remaining balance.</p>
                </div>
            </div>
        </div>
    </div>
</template>
