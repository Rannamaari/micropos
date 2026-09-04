<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePosStore } from '../../stores/posStore';

const emit = defineEmits(['shortcuts', 'held-sales', 'sale-lookup', 'sign-out']);
const store = usePosStore();
const now = ref(new Date());
const mobileDetailsOpen = ref(false);

const timer = setInterval(() => {
    now.value = new Date();
}, 1000);

const formattedNow = computed(() => now.value.toLocaleString());

onMounted(() => {});
onBeforeUnmount(() => clearInterval(timer));
</script>

<template>
    <header class="pos-card rounded-[24px] px-4 py-3 md:rounded-[32px] md:px-6 md:py-4">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between xl:gap-4">
            <div class="flex items-center justify-between gap-3 xl:block">
                <div>
                    <div class="mb-1 flex items-center gap-2 md:mb-2 md:gap-3">
                        <span class="rounded-full bg-[var(--pos-accent)]/18 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-[var(--pos-accent-strong)]">Online</span>
                        <span class="text-sm text-[var(--pos-muted)]">Retail Checkout</span>
                    </div>
                    <h1 class="font-[var(--font-display)] text-2xl font-bold tracking-tight text-white sm:text-3xl md:text-4xl">Micro POS</h1>
                </div>
                <button
                    type="button"
                    class="pos-button-secondary min-h-11 shrink-0 px-3 py-2 text-sm md:hidden"
                    :aria-expanded="mobileDetailsOpen"
                    aria-controls="mobile-store-details"
                    @click="mobileDetailsOpen = !mobileDetailsOpen"
                >
                    {{ mobileDetailsOpen ? 'Hide info' : 'Store info' }}
                </button>
            </div>

            <div class="hidden gap-3 text-sm text-[var(--pos-muted)] md:grid md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em]">Company</p>
                    <p class="mt-1 font-semibold text-white">{{ store.bootstrap.company?.name }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-[0.2em]">Branch / Warehouse</p>
                    <p class="mt-1 font-semibold text-white">{{ store.bootstrap.branch?.name }} / {{ store.bootstrap.warehouse?.name }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-[0.2em]">Cashier</p>
                    <p class="mt-1 font-semibold text-white">{{ store.bootstrap.user?.name }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-[0.2em]">Clock</p>
                    <p class="mt-1 font-semibold text-white">{{ formattedNow }}</p>
                </div>
            </div>

            <div
                v-if="mobileDetailsOpen"
                id="mobile-store-details"
                class="grid gap-3 border-t border-white/10 pt-3 text-sm text-[var(--pos-muted)] md:hidden"
            >
                <div>
                    <p class="text-xs uppercase tracking-[0.2em]">Company</p>
                    <p class="mt-1 font-semibold text-white">{{ store.bootstrap.company?.name }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-[0.2em]">Branch / Warehouse</p>
                    <p class="mt-1 font-semibold text-white">{{ store.bootstrap.branch?.name }} / {{ store.bootstrap.warehouse?.name }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em]">Cashier</p>
                        <p class="mt-1 font-semibold text-white">{{ store.bootstrap.user?.name }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em]">Clock</p>
                        <p class="mt-1 font-semibold text-white">{{ formattedNow }}</p>
                    </div>
                </div>
            </div>

            <div class="flex gap-2 overflow-x-auto pb-1 xl:flex-wrap xl:overflow-visible xl:pb-0">
                <button class="pos-button-secondary min-h-11 shrink-0 px-3 py-2 text-sm md:px-4 md:py-3 md:text-base" @click="emit('held-sales')">Held Sales</button>
                <button v-if="store.canViewSalesHistory" class="pos-button-secondary min-h-11 shrink-0 px-3 py-2 text-sm md:px-4 md:py-3 md:text-base" @click="emit('sale-lookup')">Sales History</button>
                <button class="pos-button-secondary min-h-11 shrink-0 px-3 py-2 text-sm md:px-4 md:py-3 md:text-base" @click="emit('shortcuts')">Shortcuts</button>
                <button class="pos-button-secondary min-h-11 shrink-0 px-3 py-2 text-sm md:px-4 md:py-3 md:text-base" @click="emit('sign-out')">Sign Out</button>
            </div>
        </div>
    </header>
</template>
