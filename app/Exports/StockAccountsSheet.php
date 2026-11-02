<?php

namespace App\Exports;

use App\Models\ProductOffering;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class StockAccountsSheet implements FromArray, WithTitle
{
    public function array(): array
    {
        $rows = [['Produk', 'Jenis Akun', 'Email', 'Mulai Premium', 'Berakhir Premium', 'Kapasitas Slot', 'Slot Terpakai', 'Stock Tersedia', 'Sisa Hari', 'Status Akun', 'Catatan', 'Modal']];
        ProductOffering::with(['product', 'accountType'])->withCount(['subscriptions as used_slots' => fn ($query) => $query->where('status', 'active')->where('expires_at', '>', now())])->get()->each(function (ProductOffering $offering) use (&$rows): void {
            $rows[] = [$offering->product->name, $offering->accountType->name, $offering->email, $offering->starts_at?->format('Y-m-d H:i:s'), $offering->expires_at?->format('Y-m-d H:i:s'), $offering->accountType->max_users, $offering->used_slots, max(0, $offering->accountType->max_users - $offering->used_slots), $offering->expires_at ? max(0, now()->diffInDays($offering->expires_at, false)) : null, $offering->status, $offering->notes, $offering->price];
        });

        return $rows;
    }

    public function title(): string
    {
        return 'Stock Akun';
    }
}
