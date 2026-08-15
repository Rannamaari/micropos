<script setup>
import { computed, onMounted, ref } from 'vue';
import { usePosStore } from '../../stores/posStore';

const emit = defineEmits(['close']);
const store = usePosStore();
const cancellingSaleId = ref(null);
const cancellationReason = ref('Customer changed mind');
const cancellationNotes = ref('');

const cancellationReasons = [
    'Customer changed mind',
    'Customer did not have payment',
    'Duplicate / accidental cart',
    'Price enquiry only',
    'Item unavailable',
    'Manager instruction',
    'Other',
];

const selectedCancellationSale = computed(() => store.heldSales.find((sale) => sale.id === cancellingSaleId.value) ?? null);

onMounted(() => {
    store.loadHeldSales();
});

function startCancellation(saleId) {
    cancellingSaleId.value = saleId;
    cancellationReason.value = 'Customer changed mind';
    cancellationNotes.value = '';
}

function resetCancellation() {
    cancellingSaleId.value = null;
    cancellationReason.value = 'Customer changed mind';
    cancellationNotes.value = '';
}

async function submitCancellation() {
    if (! selectedCancellationSale.value) return;

    if (cancellationReason.value === 'Other' && ! cancellationNotes.value.trim()) {
        store.setErrors(['Notes are required when the cancellation reason is Other.']);
        return;
    }

    if (! window.confirm(`Cancel held sale ${selectedCancellationSale.value.sale_number}? This will preserve the audit trail.`)) {
        return;
    }

    const success = await store.cancelHeldSale(selectedCancellationSale.value.id, cancellationReason.value, cancellationNotes.value);

    if (success) {
        resetCancellation();
    }
}

async function resumeSale(saleId) {
    await store.resumeHeldSale(saleId);

    if (! store.errors.length) {
        emit('close');
    }
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/55 p-4">
        <div class="pos-card pos-scrollbar max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-[32px] p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-white">Held Sales</h2>
                <button type="button" class="pos-button-secondary" @click="emit('close')">Close</button>
            </div>

            <div class="space-y-3">
                <div
                    v-for="sale in store.heldSales"
                    :key="sale.id"
                    class="rounded-[24px] border border-white/8 bg-white/[0.03] px-4 py-4 transition hover:bg-white/[0.05]"
                >
                    <button
                        type="button"
                        class="w-full text-left"
                        @click="resumeSale(sale.id)"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="font-semibold text-white">{{ sale.sale_number }}</p>
                                <p class="mt-1 text-sm text-[var(--pos-muted)]">{{ sale.customer }} • {{ sale.cashier ?? 'Cashier' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-[var(--pos-accent-strong)]">{{ store.formatMoney(sale.amount) }}</p>
                                <p class="mt-1 text-sm text-[var(--pos-muted)]">{{ sale.item_count }} items</p>
                            </div>
                        </div>
                    </button>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <div>
                            <p class="text-xs uppercase tracking-[0.18em] text-[var(--pos-muted)]">Click card to resume</p>
                        </div>
                        <button
                            v-if="store.canResumeHeldSales"
                            type="button"
                            class="pos-button-primary"
                            @click.stop="resumeSale(sale.id)"
                        >
                            Resume Sale
                        </button>
                        <button
                            v-if="store.canCancelHeldSales"
                            type="button"
                            class="pos-button-secondary"
                            @click.stop="startCancellation(sale.id)"
                        >
                            Cancel Held Sale
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="selectedCancellationSale" class="mt-5 rounded-[28px] border border-amber-300/20 bg-amber-400/10 p-4">
                <h3 class="text-lg font-semibold text-white">Cancel Held Sale</h3>
                <p class="mt-1 text-sm text-[var(--pos-muted)]">
                    {{ selectedCancellationSale.sale_number }} will stay in history as cancelled for audit purposes.
                </p>

                <div class="mt-4 grid gap-3">
                    <select v-model="cancellationReason" class="pos-input">
                        <option v-for="reason in cancellationReasons" :key="reason" :value="reason">{{ reason }}</option>
                    </select>
                    <textarea
                        v-model="cancellationNotes"
                        class="pos-input min-h-28"
                        :placeholder="cancellationReason === 'Other' ? 'Cancellation notes are required.' : 'Optional cancellation notes'"
                    />
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" class="pos-button-primary" @click="submitCancellation">Confirm Cancellation</button>
                    <button type="button" class="pos-button-secondary" @click="resetCancellation">Keep Held Sale</button>
                </div>
            </div>
        </div>
    </div>
</template>
