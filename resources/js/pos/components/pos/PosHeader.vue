<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePosStore } from '../../stores/posStore';

const emit = defineEmits(['shortcuts', 'held-sales', 'sale-lookup', 'sign-out']);
const store = usePosStore();
const now = ref(new Date());

const timer = setInterval(() => {
    now.value = new Date();
}, 1000);

const formattedNow = computed(() => now.value.toLocaleString());

onMounted(() => {});
onBeforeUnmount(() => clearInterval(timer));
</script>

<template>
    <header class="pos-card rounded-[32px] px-5 py-4 md:px-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <div class="mb-2 flex items-center gap-3">
                    <span class="rounded-full bg-[var(--pos-accent)]/18 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-[var(--pos-accent-strong)]">Online</span>
                    <span class="text-sm text-[var(--pos-muted)]">Retail Checkout</span>
                </div>
                <h1 class="font-[var(--font-display)] text-3xl font-bold tracking-tight text-white md:text-4xl">Micro POS</h1>
            </div>

            <div class="grid gap-3 text-sm text-[var(--pos-muted)] sm:grid-cols-2 xl:grid-cols-4">
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

            <div class="flex flex-wrap gap-2">
                <button class="pos-button-secondary" @click="emit('held-sales')">Held Sales</button>
                <button v-if="store.canViewSalesHistory" class="pos-button-secondary" @click="emit('sale-lookup')">Sales History</button>
                <button class="pos-button-secondary" @click="emit('shortcuts')">Shortcuts</button>
                <button class="pos-button-secondary" @click="emit('sign-out')">Sign Out</button>
            </div>
        </div>
    </header>
</template>
