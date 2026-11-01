<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramCheckTransactions extends Command
{
    protected $signature = 'telegram:poll {--once : Process one polling request}';

    protected $description = 'Listen for Telegram /cek commands';

    public function handle(): int
    {
        $offset = null;

        do {
            $response = Http::timeout(35)->get('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/getUpdates', array_filter([
                'timeout' => 25,
                'offset' => $offset,
            ]))->throw()->json();

            foreach ($response['result'] ?? [] as $update) {
                $offset = $update['update_id'] + 1;
                $message = $update['message'] ?? [];
                if (trim($message['text'] ?? '') !== '/cek') {
                    continue;
                }

                $this->send($message['chat']['id']);
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }

    private function send(int|string $chatId): void
    {
        $transactions = Transaction::query()
            ->with('user')
            ->where('status', 'paid')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>=', now())
            ->orderBy('expires_at')
            ->limit(5)
            ->get();

        $url = rtrim(config('app.url'), '/').'/admin/transactions';
        $lines = ['*5 transaksi dengan expired terdekat*', ''];
        $buttons = [];

        if ($transactions->isEmpty()) {
            $lines[] = 'Tidak ada transaksi aktif.';
        } else {
            foreach ($transactions as $index => $transaction) {
                $days = max(0, (int) now()->diffInDays($transaction->expires_at, false));
                $lines[] = ($index + 1).'. *'.$transaction->product_name.'*';
                $lines[] = 'Customer: '.($transaction->user?->name ?? '-');
                $lines[] = 'Expired: '.$transaction->expires_at->format('d M Y').' ('.$days.' hari lagi)';
                $lines[] = 'Transaksi: '.$transaction->number;
                $lines[] = '';
                $buttons[] = [['text' => 'Buka '.$transaction->number, 'url' => $url.'/'.$transaction->id]];
            }
        }

        $lines[] = '[Buka halaman transactions]('.$url.')';

        Http::post('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendMessage', [
            'chat_id' => $chatId,
            'text' => implode("\n", $lines),
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true,
            'reply_markup' => ['inline_keyboard' => $buttons],
        ])->throw();
    }
}
