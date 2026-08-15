@php
    $trackInventory = $trackInventory ?? false;
    $rows = $rows ?? [];
    $totalQuantity = $totalQuantity ?? '0.0000';
    $inventoryOverviewUrl = $inventoryOverviewUrl ?? null;
@endphp

<div class="space-y-4">
    @if (! $trackInventory)
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            This product is marked as <strong>NON-STOCK</strong>, so warehouse quantities are not tracked.
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Warehouse</th>
                        <th class="px-4 py-3 font-medium">Stock</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Quick Link</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($rows as $row)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $row['name'] }}</div>
                                <div class="text-xs text-slate-500">{{ $row['code'] }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-900">{{ $row['current_quantity'] }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-700' => $row['status_color'] === 'success',
                                    'bg-amber-100 text-amber-700' => $row['status_color'] === 'warning',
                                    'bg-rose-100 text-rose-700' => $row['status_color'] === 'danger',
                                    'bg-slate-100 text-slate-700' => $row['status_color'] === 'gray',
                                ])>
                                    {{ $row['status'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ $row['inventory_url'] }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                                    Open in Inventory Overview
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
            <div>
                <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Total Across Warehouses</div>
                <div class="text-base font-semibold text-slate-900">{{ $totalQuantity }}</div>
            </div>

            @if ($inventoryOverviewUrl)
                <a href="{{ $inventoryOverviewUrl }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                    Open Inventory Overview
                </a>
            @endif
        </div>
    @endif
</div>
