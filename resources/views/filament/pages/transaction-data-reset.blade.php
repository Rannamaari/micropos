<x-filament-panels::page>
    <div class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-danger-200 bg-danger-50 p-5 text-danger-900 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-100">
            <h2 class="text-lg font-semibold">Testing only</h2>
            <p class="mt-1 text-sm">These actions permanently remove transaction data for your current company. Products, categories, suppliers, customers, branches, users, prices, and receipt settings are kept.</p>
        </div>

        <label class="block rounded-xl border border-gray-200 bg-white p-5 text-sm dark:border-white/10 dark:bg-white/5">Type the exact confirmation for the action you need
            <input wire:model="confirmation" type="text" class="mt-2 w-full rounded-lg border-gray-300 dark:border-white/15 dark:bg-gray-900" autocomplete="off">
        </label>

        <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
            <h2 class="font-semibold">Reset Sales Only</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Removes sales, held sales, returns, sale payments, linked customer balances, and sale stock movements. Remaining purchase and opening stock is recalculated correctly. Sale numbers restart at 1.</p>
            <x-filament::button class="mt-4" color="danger" wire:click="resetSales" wire:confirm="This permanently removes all sales for this company. Continue?">Reset Sales</x-filament::button>
            @error('confirmation') <p class="mt-2 text-sm text-danger-600">Type <strong>RESET SALES</strong> to use this option.</p> @enderror
        </section>

        <section class="rounded-xl border border-danger-200 bg-white p-5 dark:border-danger-500/30 dark:bg-white/5">
            <h2 class="font-semibold text-danger-700 dark:text-danger-300">Reset All Transactions</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Also removes purchases, supplier and customer transaction history, stock movements, stock counts, and inventory balances. Product master data and settings remain. Sales and purchase numbers restart at 1.</p>
            <x-filament::button class="mt-4" color="danger" wire:click="resetAllTransactions" wire:confirm="This permanently removes all test transactions for this company. Continue?">Reset All Transactions</x-filament::button>
            @error('confirmation') <p class="mt-2 text-sm text-danger-600">Type <strong>RESET ALL TRANSACTIONS</strong> to use this option.</p> @enderror
        </section>
    </div>
</x-filament-panels::page>
