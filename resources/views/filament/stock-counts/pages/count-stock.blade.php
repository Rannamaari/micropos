<x-filament-panels::page>
    @php
        $record = $this->getRecord();
    @endphp

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Warehouse</div>
            <div class="mt-2 text-base font-semibold text-slate-900">{{ $this->getWarehouseName() }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Status</div>
            <div class="mt-2 text-base font-semibold text-slate-900">{{ $record->status->value }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Counted Items</div>
            <div class="mt-2 text-base font-semibold text-slate-900">{{ $this->getCountedItemsCount() }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Pending Items</div>
            <div class="mt-2 text-base font-semibold text-slate-900">{{ $this->getPendingItemsCount() }}</div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Counting Instructions</h3>
                <p class="mt-1 text-sm text-slate-600">Search by product name, SKU, or barcode. Open an item, enter the physical quantity counted by staff, and complete the stock count when all items are checked.</p>
            </div>
            <div class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                Variance items: <strong>{{ $this->getVarianceItemsCount() }}</strong>
            </div>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
