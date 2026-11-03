<?php

namespace App\Imports;

use App\Models\AccountType;
use App\Models\Product;
use App\Models\ProductOffering;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class StockAccountsImport implements ToCollection
{
    public function collection(Collection $rows): void
    {
        $headerIndex = $rows->search(function ($row): bool {
            $values = collect($row)->map(fn ($value): string => $this->normalizeHeader($value));

            return $values->contains('produk') && $values->contains('jenis_akun') && $values->contains('email');
        });

        if ($headerIndex === false) {
            throw new \RuntimeException('Header Stock Akun tidak ditemukan. Pastikan ada kolom Produk, Jenis Akun, dan Email.');
        }

        $headers = collect($rows->get($headerIndex))->map(fn ($value): string => $this->normalizeHeader($value));

        foreach ($rows->slice($headerIndex + 1) as $rawRow) {
            $row = collect($rawRow)->mapWithKeys(fn ($value, $index) => [$headers->get($index) => $value]);
            $productName = trim((string) ($row->get('produk') ?? ''));
            $accountName = trim((string) ($row->get('jenis_akun') ?? ''));
            $email = trim((string) ($row->get('email') ?? ''));
            if ($productName === '' || $accountName === '' || $email === '') {
                continue;
            }

            $productCode = str()->slug($productName);
            $product = Product::query()->whereRaw('LOWER(name) = ?', [strtolower($productName)])->orWhere('slug', $productCode)->first();
            if (! $product) {
                $product = Product::create(['name' => $productName, 'slug' => $productCode, 'status' => true]);
            }

            $accountCode = str()->slug($accountName);
            $accountType = AccountType::query()->whereRaw('LOWER(name) = ?', [strtolower($accountName)])->orWhere('code', $accountCode)->first();
            if (! $accountType) {
                $accountType = AccountType::create(['name' => $accountName, 'code' => $accountCode, 'max_users' => max(1, (int) ($row->get('kapasitas_slot') ?? 1)), 'status' => true]);
            } else {
                $accountType->update(['max_users' => max(1, (int) ($row->get('kapasitas_slot') ?? $accountType->max_users)), 'status' => true]);
            }
            $startsAt = $this->parseDate($row->get('mulai_premium'));
            $expiresAt = $this->parseDate($row->get('berakhir_premium'));
            $durationDays = $startsAt && $expiresAt ? max(1, $startsAt->diffInDays($expiresAt)) : 30;

            ProductOffering::updateOrCreate(
                ['product_id' => $product->id, 'account_type_id' => $accountType->id, 'email' => $email],
                ['created_at' => $startsAt, 'starts_at' => $startsAt, 'expires_at' => $expiresAt, 'status' => match (strtolower(trim((string) ($row->get('status_akun') ?? 'active')))) {
                    'suspend', 'suspended' => 'suspended', 'deactivated', 'dead', 'deadactive' => 'deactivated', default => 'active'
                }, 'notes' => $row->get('catatan') ?? null, 'price' => (float) ($row->get('modal') ?? 0), 'duration_days' => $durationDays],
            );
        }
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
        }

        $value = trim((string) $value);
        $months = ['Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'Mei' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Agu' => 'Aug', 'Sep' => 'Sep', 'Okt' => 'Oct', 'Nov' => 'Nov', 'Des' => 'Dec'];
        $value = str_replace(array_keys($months), array_values($months), $value);

        return Carbon::parse($value);
    }

    private function normalizeHeader(mixed $value): string
    {
        return str($value)->trim()->lower()->replace([' ', '-', '/'], '_')->toString();
    }
}
