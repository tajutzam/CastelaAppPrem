<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StockWorkbookImport implements Import, WithMultipleSheets
{
    public function sheets(): array
    {
        return ['Stock Akun' => new StockAccountsImport, 'Rekap Order' => new OrderSummaryImport];
    }
}
