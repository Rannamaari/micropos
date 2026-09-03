<?php

namespace App\Filament\Pages;

use App\Filament\Support\AdminSupport;
use App\Services\CsvDataImportService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class DataImport extends Page
{
    use WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Data Import';

    protected static ?string $title = 'CSV Data Import';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected string $view = 'filament.pages.data-import';

    public string $type = 'products';

    public ?string $warehouseId = null;

    public ?TemporaryUploadedFile $csvFile = null;

    public ?string $storedPath = null;

    /** @var array<string,mixed>|null */
    public ?array $preview = null;

    public function mount(): void
    {
        abort_unless(AdminSupport::companyId() && AdminSupport::user()?->can('products.import'), 403);
        $this->warehouseId = AdminSupport::activeWarehouseId();
    }

    public function updatedType(): void
    {
        $this->preview = null;
        $this->storedPath = null;
    }

    public function previewUpload(CsvDataImportService $importer): void
    {
        $this->validate(['csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $this->storedPath = $this->csvFile->store('imports', 'local');
        $this->preview = $importer->preview(AdminSupport::companyId(), $this->type, Storage::disk('local')->path($this->storedPath), $this->type === 'products' ? $this->warehouseId : null);
    }

    public function importRows(CsvDataImportService $importer): void
    {
        abort_unless($this->storedPath && $this->preview, 422, 'Preview a CSV file before importing.');
        $result = $importer->import(AdminSupport::companyId(), $this->type, Storage::disk('local')->path($this->storedPath), $this->type === 'products' ? $this->warehouseId : null);
        Storage::disk('local')->delete($this->storedPath);
        $this->reset('csvFile', 'storedPath', 'preview');
        Notification::make()->title("{$result['created']} rows imported; {$result['skipped']} skipped")->success()->send();
    }

    public function downloadTemplate(CsvDataImportService $importer)
    {
        $template = $importer->template($this->type);
        $contents = implode(',', $template['headers'])."\n".implode(',', $template['example'])."\n";
        return response()->streamDownload(fn () => print($contents), "{$this->type}-import-template.csv", ['Content-Type' => 'text/csv']);
    }
}
