<?php

namespace App\Filament\Resources\StockCounts\Pages;

use App\Enums\StockCountStatus;
use App\Filament\Resources\StockCounts\StockCountResource;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Services\InventoryService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CountStock extends Page implements HasTable
{
    use InteractsWithRecord;
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = StockCountResource::class;

    protected string $view = 'filament.stock-counts.pages.count-stock';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();
        $this->mountInteractsWithTable();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canView($this->getRecord()), 403);
    }

    public function hydrate(): void
    {
        $this->authorizeAccess();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Count Stock';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewSummary')
                ->label('View Summary')
                ->url(fn (): string => StockCountResource::getUrl('view', ['record' => $this->getRecord()])),
            Action::make('completeCount')
                ->label('Complete Count')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('This will reconcile the counted quantities against current stock and create the necessary stock movements.')
                ->visible(fn (): bool => $this->getRecord()->status === StockCountStatus::InProgress)
                ->action(function (): void {
                    app(InventoryService::class)->completeStockCount($this->getRecord()->id, auth()->id());
                    $this->record = $this->getRecord()->fresh(['warehouse', 'creator', 'completer']);

                    Notification::make()
                        ->title('Stock count completed.')
                        ->success()
                        ->send();
                }),
            Action::make('cancelCount')
                ->label('Cancel Count')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status === StockCountStatus::InProgress)
                ->action(function (): void {
                    app(InventoryService::class)->cancelStockCount($this->getRecord()->id);
                    $this->record = $this->getRecord()->fresh(['warehouse', 'creator', 'completer']);

                    Notification::make()
                        ->title('Stock count cancelled.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->description(fn (StockCountItem $record): string => 'SKU: '.$record->product->sku.($record->product->primaryBarcode?->barcode ? ' | Barcode: '.$record->product->primaryBarcode->barcode : ''))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';

                        return $query->where(function (Builder $nested) use ($like): void {
                            $nested->whereHas('product', function (Builder $productQuery) use ($like): void {
                                $productQuery->whereLike('name', $like, caseSensitive: false)
                                    ->orWhereLike('sku', $like, caseSensitive: false)
                                    ->orWhereHas('barcodes', fn (Builder $barcodeQuery): Builder => $barcodeQuery->whereLike('barcode', $like, caseSensitive: false));
                            });
                        });
                    })
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->join('products', 'products.id', '=', 'stock_count_items.product_id')
                        ->orderBy('products.name', $direction)
                        ->select('stock_count_items.*')),
                Tables\Columns\TextColumn::make('system_quantity')
                    ->label('System Qty')
                    ->sortable(),
                Tables\Columns\TextColumn::make('counted_quantity')
                    ->label('Counted Qty')
                    ->formatStateUsing(fn (mixed $state): string => $state === null ? 'Not entered' : number_format((float) $state, 4, '.', ''))
                    ->sortable(),
                Tables\Columns\TextColumn::make('difference')
                    ->label('Difference')
                    ->formatStateUsing(fn (mixed $state): string => $state === null ? 'Pending' : number_format((float) $state, 4, '.', ''))
                    ->color(function (mixed $state): string {
                        if ($state === null) {
                            return 'gray';
                        }

                        return (float) $state === 0.0 ? 'success' : 'warning';
                    }),
                Tables\Columns\TextColumn::make('count_status')
                    ->label('Status')
                    ->state(fn (StockCountItem $record): string => $record->counted_quantity === null ? 'PENDING' : 'COUNTED')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'COUNTED' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('counted')
                    ->label('Count Status')
                    ->options([
                        'pending' => 'Pending',
                        'counted' => 'Counted',
                        'variance' => 'Variance Only',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'pending' => $query->whereNull('counted_quantity'),
                            'counted' => $query->whereNotNull('counted_quantity'),
                            'variance' => $query->whereNotNull('counted_quantity')->where('difference', '!=', 0),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('enterCount')
                    ->label('Enter Count')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (): bool => $this->getRecord()->status === StockCountStatus::InProgress)
                    ->schema([
                        Placeholder::make('product')
                            ->content(fn (StockCountItem $record): string => $record->product->name),
                        Placeholder::make('system_quantity')
                            ->label('System Quantity')
                            ->content(fn (StockCountItem $record): string => number_format((float) $record->system_quantity, 4, '.', '')),
                        TextInput::make('counted_quantity')
                            ->label('Physical Count')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ])
                    ->fillForm(fn (StockCountItem $record): array => [
                        'counted_quantity' => $record->counted_quantity,
                    ])
                    ->action(function (StockCountItem $record, array $data): void {
                        app(InventoryService::class)->setCountedQuantity($record->id, $data['counted_quantity']);
                        $this->record = $this->getRecord()->fresh(['warehouse', 'creator', 'completer']);

                        Notification::make()
                            ->title('Counted quantity saved.')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordAction('enterCount')
            ->defaultSort('product.name')
            ->paginated([25, 50, 100]);
    }

    protected function getTableQuery(): Builder
    {
        return StockCountItem::query()
            ->where('stock_count_id', $this->getRecord()->id)
            ->with(['product.primaryBarcode']);
    }

    public function getPendingItemsCount(): int
    {
        return $this->getRecord()->items()->whereNull('counted_quantity')->count();
    }

    public function getCountedItemsCount(): int
    {
        return $this->getRecord()->items()->whereNotNull('counted_quantity')->count();
    }

    public function getVarianceItemsCount(): int
    {
        return $this->getRecord()->items()->whereNotNull('counted_quantity')->where('difference', '!=', 0)->count();
    }

    public function getWarehouseName(): string
    {
        return $this->getRecord()->warehouse?->name ?? 'Unknown Warehouse';
    }

    protected function getTableModel(): ?string
    {
        return StockCountItem::class;
    }

    protected function getTableHeading(): string|Htmlable|null
    {
        return 'Count Worksheet';
    }
}
