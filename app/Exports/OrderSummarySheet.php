<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class OrderSummarySheet implements FromArray, WithTitle
{
    public function array(): array
    {
        $rows = [['Nomor', 'Tanggal', 'Customer', 'Email', 'Produk', 'Durasi', 'Status', 'Nominal']];
        Transaction::with('user')->latest('paid_at')->get()->each(function (Transaction $transaction) use (&$rows): void {
            $rows[] = [$transaction->number, $transaction->paid_at?->format('Y-m-d H:i:s'), $transaction->user?->name, $transaction->user?->email, $transaction->product_name, $transaction->duration_days, $transaction->status, $transaction->total_amount];
        });

        return $rows;
    }

    public function title(): string
    {
        return 'Rekap Order';
    }
}
