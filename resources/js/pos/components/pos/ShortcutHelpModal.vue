<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/55 p-4">
        <div class="pos-card w-full max-w-lg rounded-[32px] p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-white">Keyboard Shortcuts</h2>
                <button class="pos-button-secondary" @click="$emit('close')">Close</button>
            </div>
            <div class="space-y-3 text-sm text-[var(--pos-muted)]">
                <div class="flex justify-between"><span>Focus search</span><strong class="text-white">F2</strong></div>
                <div class="flex justify-between"><span>Customer</span><strong class="text-white">F4</strong></div>
                <div class="flex justify-between"><span>Hold sale</span><strong class="text-white">F6</strong></div>
                <div class="flex justify-between"><span>Payment</span><strong class="text-white">F8</strong></div>
                <div class="flex justify-between"><span>Close modal</span><strong class="text-white">Esc</strong></div>
                <div class="flex justify-between"><span>Scan / confirm</span><strong class="text-white">Enter</strong></div>
            </div>
        </div>
    </div>
</template>
