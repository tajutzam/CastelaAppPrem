<?php

namespace App\Imports;

use App\Models\ProductOffering;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class OrderSummaryImport implements ToCollection
{
    public function chunkSize(): int
    {
        return 250;
    }

    public function collection(Collection $rows): void
    {
        $headerIndex = $rows->search(function ($row): bool {
            $headers = collect($row)->map(fn ($value): string => $this->normalizeHeader($value));

            return $headers->contains(fn (string $header): bool => str_contains($header, 'tanggal') && str_contains($header, 'input'))
                && $headers->contains(fn (string $header): bool => str_contains($header, 'durasi') && str_contains($header, 'hari'))
                && $headers->contains(fn (string $header): bool => $header === 'status');
        });

        if ($headerIndex === false) {
            throw new \RuntimeException('Header Rekap Order tidak ditemukan.');
        }

        $headers = collect($rows->get($headerIndex))->map(fn ($value): string => $this->normalizeHeader($value));
        $offeringCache = [];
        $userCache = [];

        foreach ($rows->slice($headerIndex + 1) as $index => $rawRow) {
            $row = collect($rawRow)->mapWithKeys(fn ($value, $column) => [$headers->get($column) => $value]);
            $inputAt = $this->parseDate($row->get('tanggal_input'));
            if (! $inputAt) {
                continue;
            }

            $email = trim((string) ($row->get('email') ?? ''));
            $excelProduct = trim((string) ($row->get('produk') ?? ''));
            if (str_starts_with($excelProduct, '=')) {
                $excelProduct = '';
            }
            $phone = trim((string) ($row->get('no_whatsapp') ?? ''));
            $user = null;
            if ($email !== '') {
                $user = $userCache[$email] ??= User::firstOrCreate(['email' => $email], ['name' => $email, 'password' => bcrypt(str()->random(32)), 'phone_number' => $phone ?: null, 'role' => 'client']);
            }

            $offering = $email !== '' ? ($offeringCache[$email] ??= ProductOffering::query()->with(['product', 'accountType'])->whereRaw('LOWER(email) = ?', [strtolower($email)])->first()) : null;
            if (! $offering) {
                continue;
            }

            $startsAt = $this->parseDate($row->get('waktu_login')) ?: $inputAt;
            $duration = max(1, (int) ($row->get('durasi_hari') ?: 1));
            $expiresAt = $this->parseDate($row->get('jadwal_logout_kick')) ?: $startsAt->copy()->addDays($duration);
            $duration = max(1, (int) ($row->get('durasi_hari') ?: $startsAt->diffInDays($expiresAt)));
            $number = 'IMPORT-'.md5($inputAt->format('c').'|'.$email.'|'.$phone.'|'.$index);
            $totalAmount = $this->priceFor($offering->product?->name, $offering->accountType?->name, $duration);
            if ($totalAmount === null) {
                continue;
            }

            Transaction::updateOrCreate(
                ['number' => $number],
                ['user_id' => $user?->id, 'product_offering_id' => $offering?->id, 'product_name' => $offering?->product?->name ?: ($excelProduct ?: 'Produk import'), 'total_amount' => $totalAmount, 'duration_days' => $duration, 'starts_at' => $startsAt, 'expires_at' => $expiresAt ?: $startsAt->copy()->addDays($duration), 'status' => 'paid', 'paid_at' => $inputAt, 'logged_out_at' => $this->parseDate($row->get('waktu_kick_aktual'))],
            );
        }
    }

    private function priceFor(?string $product, ?string $accountType, int $duration): ?float
    {
        $product = strtolower((string) $product);
        $accountType = strtolower((string) $accountType);
        $prices = str_contains($product, 'chatgpt') || str_contains($product, 'gpt')
            ? ['sharing 8u' => [1 => 5000, 3 => 11000, 7 => 16000, 30 => 35000], 'sharing 5u' => [1 => 8000, 3 => 12000, 7 => 20000, 30 => 48000], 'sharing 3u' => [1 => 12000, 3 => 20000, 7 => 30000, 30 => 75000], 'private' => [1 => 25000, 3 => 50000, 7 => 90000, 30 => 200000]]
            : ['sharing 5u' => [1 => 15000, 3 => 30000, 7 => 50000, 30 => 95000], 'sharing 3u' => [1 => 20000, 3 => 40000, 7 => 60000, 30 => 135000], 'sharing 2u' => [1 => 30000, 3 => 50000, 7 => 80000, 30 => 200000], 'private' => [1 => 40000, 3 => 80000, 7 => 145000, 30 => 350000]];
        foreach ($prices as $type => $durations) {
            if (str_contains($accountType, $type)) {
                return $durations[$duration] ?? null;
            }
        }

        return null;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }
        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
        }
        if (str_starts_with(trim((string) $value), '=')) {
            return null;
        }
        $value = trim((string) $value);
        $months = ['Mei' => 'May', 'Agu' => 'Aug', 'Okt' => 'Oct', 'Des' => 'Dec'];
        $value = str_replace(array_keys($months), array_values($months), $value);

        foreach (['m/d/Y H:i:s', 'm/d/Y H:i', 'd M Y H:i:s', 'd M Y H:i', 'Y-m-d H:i:s', 'Y-m-d H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
                continue;
            }
        }

        return Carbon::parse($value);
    }

    private function normalizeHeader(mixed $value): string
    {
        return str((string) $value)
            ->replace("\xEF\xBB\xBF", '')
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }
}
