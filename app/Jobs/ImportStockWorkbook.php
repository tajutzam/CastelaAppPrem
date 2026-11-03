<?php

namespace App\Jobs;

use App\Imports\StockWorkbookImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportStockWorkbook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $path) {}

    public function handle(): void
    {
        Excel::import(new StockWorkbookImport, Storage::disk('local')->path($this->path));
        Storage::disk('local')->delete($this->path);
    }

    public function failed(): void
    {
        Storage::disk('local')->delete($this->path);
    }
}
