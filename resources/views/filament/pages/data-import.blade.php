<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold">Safe CSV import</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Download a template, upload it, review every issue, then import only rows marked ready. Existing records are never overwritten.</p>
        </div>

        <form wire:submit="previewUpload" class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block text-sm font-medium">Import type
                    <select wire:model.live="type" class="mt-1 w-full rounded-lg border-gray-300 dark:border-white/15 dark:bg-gray-900">
                        <option value="products">Products</option><option value="categories">Categories</option><option value="suppliers">Suppliers</option><option value="customers">Customers</option>
                    </select>
                </label>
                @if ($type === 'products')
                    <label class="block text-sm font-medium">Warehouse for optional opening quantity
                        <select wire:model="warehouseId" class="mt-1 w-full rounded-lg border-gray-300 dark:border-white/15 dark:bg-gray-900">
                            <option value="">No opening quantity</option>
                            @foreach (\App\Filament\Support\AdminSupport::authorizedWarehouses() as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
            </div>
            <label class="block text-sm font-medium">CSV file
                <input wire:model="csvFile" type="file" accept=".csv,text/csv" class="mt-1 block w-full text-sm">
            </label>
            @error('csvFile') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
            <div class="flex flex-wrap gap-3">
                <x-filament::button type="button" color="gray" wire:click="downloadTemplate">Download {{ ucfirst($type) }} Template</x-filament::button>
                <x-filament::button type="submit" wire:loading.attr="disabled">Preview CSV</x-filament::button>
            </div>
        </form>

        @if ($preview)
            <section class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg bg-success-50 p-3 text-success-700 dark:bg-success-500/10 dark:text-success-300">Ready: {{ $preview['valid'] }}</div>
                    <div class="rounded-lg bg-warning-50 p-3 text-warning-700 dark:bg-warning-500/10 dark:text-warning-300">Duplicates skipped: {{ $preview['duplicates'] }}</div>
                    <div class="rounded-lg bg-danger-50 p-3 text-danger-700 dark:bg-danger-500/10 dark:text-danger-300">Invalid skipped: {{ $preview['invalid'] }}</div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300">Only ready rows will be created. Duplicates and invalid rows are skipped; nothing existing is deleted or overwritten.</p>
                <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left"><th class="p-2">Row</th><th class="p-2">Status</th><th class="p-2">Result</th></tr></thead><tbody>
                    @foreach ($preview['rows'] as $row)<tr class="border-b border-gray-100 dark:border-white/10"><td class="p-2">{{ $row['row'] }}</td><td class="p-2 font-medium">{{ ucfirst($row['status']) }}</td><td class="p-2">{{ $row['message'] }}</td></tr>@endforeach
                </tbody></table></div>
                <x-filament::button wire:click="importRows" wire:confirm="Import the ready rows? Existing records will be skipped.">Import {{ $preview['valid'] }} Ready Rows</x-filament::button>
            </section>
        @endif
    </div>
</x-filament-panels::page>
