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
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $type === 'products' ? 'Product preview' : ucfirst($type).' preview' }}</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Check the uploaded details carefully before importing. No existing record will be changed.</p>
                    </div>
                    <div class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">{{ $preview['total'] }} row{{ $preview['total'] === 1 ? '' : 's' }} found</div>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg bg-success-50 p-3 text-success-700 dark:bg-success-500/10 dark:text-success-300"><span class="block text-xs font-medium uppercase tracking-wide">Will import</span><span class="text-xl font-semibold">{{ $preview['valid'] }}</span></div>
                    <div class="rounded-lg bg-warning-50 p-3 text-warning-700 dark:bg-warning-500/10 dark:text-warning-300"><span class="block text-xs font-medium uppercase tracking-wide">Already exists</span><span class="text-xl font-semibold">{{ $preview['duplicates'] }}</span></div>
                    <div class="rounded-lg bg-danger-50 p-3 text-danger-700 dark:bg-danger-500/10 dark:text-danger-300"><span class="block text-xs font-medium uppercase tracking-wide">Needs attention</span><span class="text-xl font-semibold">{{ $preview['invalid'] }}</span></div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300">Only rows marked ready will be created. Duplicate and invalid rows are skipped. Showing the first {{ min(100, $preview['total']) }} rows.</p>
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
                    @if ($type === 'products')
                        <table class="min-w-[1100px] w-full text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400"><tr><th class="p-3">Row</th><th class="p-3">Product</th><th class="p-3">SKU</th><th class="p-3">Barcode</th><th class="p-3">Category / Brand</th><th class="p-3">Unit</th><th class="p-3 text-right">Cost</th><th class="p-3 text-right">Selling</th><th class="p-3 text-right">Opening qty</th><th class="p-3">Import result</th></tr></thead>
                            <tbody>
                                @foreach ($preview['rows'] as $row)
                                    @php($statusClass = match ($row['status']) { 'ready' => 'bg-success-50/60 dark:bg-success-500/5', 'duplicate' => 'bg-warning-50/60 dark:bg-warning-500/5', default => 'bg-danger-50/60 dark:bg-danger-500/5' })
                                    <tr class="border-t border-gray-100 align-top dark:border-white/10 {{ $statusClass }}">
                                        <td class="p-3 font-medium">{{ $row['row'] }}</td><td class="p-3 font-medium">{{ data_get($row, 'data.name', '-') }}</td><td class="p-3 font-mono text-xs">{{ data_get($row, 'data.sku', '-') }}</td><td class="p-3 font-mono text-xs">{{ data_get($row, 'data.barcode', '-') }}</td><td class="p-3">{{ data_get($row, 'data.category', '-') }}<span class="text-gray-400"> / </span>{{ data_get($row, 'data.brand', '-') }}</td><td class="p-3">{{ data_get($row, 'data.unit', '-') }}</td><td class="p-3 text-right tabular-nums">{{ data_get($row, 'data.cost_price', '-') }}</td><td class="p-3 text-right tabular-nums font-medium">{{ data_get($row, 'data.selling_price', '-') }}</td><td class="p-3 text-right tabular-nums">{{ data_get($row, 'data.initial_quantity', '-') }}</td><td class="p-3"><span class="font-medium">{{ ucfirst($row['status']) }}</span><p class="mt-1 max-w-xs text-xs text-gray-600 dark:text-gray-300">{{ $row['message'] }}</p></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <table class="min-w-[720px] w-full text-sm"><thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400"><tr><th class="p-3">Row</th><th class="p-3">Code</th><th class="p-3">Name</th><th class="p-3">Contact details</th><th class="p-3">Import result</th></tr></thead><tbody>
                            @foreach ($preview['rows'] as $row)
                                @php($statusClass = match ($row['status']) { 'ready' => 'bg-success-50/60 dark:bg-success-500/5', 'duplicate' => 'bg-warning-50/60 dark:bg-warning-500/5', default => 'bg-danger-50/60 dark:bg-danger-500/5' })
                                <tr class="border-t border-gray-100 align-top dark:border-white/10 {{ $statusClass }}"><td class="p-3 font-medium">{{ $row['row'] }}</td><td class="p-3 font-mono text-xs">{{ data_get($row, 'data.code', '-') }}</td><td class="p-3 font-medium">{{ data_get($row, 'data.name', '-') }}</td><td class="p-3">{{ data_get($row, 'data.phone') ?: data_get($row, 'data.email', '-') }}</td><td class="p-3"><span class="font-medium">{{ ucfirst($row['status']) }}</span><p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $row['message'] }}</p></td></tr>
                            @endforeach
                        </tbody></table>
                    @endif
                </div>
                @if ($preview['valid'] > 0)
                    <x-filament::button wire:click="importRows" wire:confirm="Import the ready rows? Existing records will be skipped.">Import {{ $preview['valid'] }} Ready Row{{ $preview['valid'] === 1 ? '' : 's' }}</x-filament::button>
                @else
                    <p class="rounded-lg bg-warning-50 p-3 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-300">There are no ready rows to import. Correct the CSV and preview it again.</p>
                @endif
            </section>
        @endif
    </div>
</x-filament-panels::page>
