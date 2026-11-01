<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramExpiringTransactions extends Command
{
    protected $signature = 'telegram:expiring-transactions';

    protected $description = 'Send Telegram reminders for transactions expiring today and tomorrow';

    public function handle(): int
    {
        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $end = $tomorrow->copy()->endOfDay();
        $transactions = Transaction::query()->with('user')->where('status', 'paid')->whereBetween('expires_at', [$today, $end])->orderBy('expires_at')->get();
        $lines = ['*Pengingat expiry transaksi*', ''];

        if ($transactions->isEmpty()) {
            $lines[] = 'Tidak ada transaksi yang expired hari ini atau besok.';
        } else {
            foreach ($transactions as $transaction) {
                $label = $transaction->expires_at->isSameDay($today) ? 'HARI INI' : 'BESOK';
                $lines[] = '*'.$label.'* · '.$transaction->product_name;
                $lines[] = 'Customer: '.($transaction->user?->name ?? '-');
                $lines[] = 'Expired: '.$transaction->expires_at->format('d M Y');
                $lines[] = '[Buka detail]('.rtrim(config('app.url'), '/').'/admin/transactions/'.$transaction->id.')';
                $lines[] = '';
            }
        }

        Http::post('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendMessage', [
            'chat_id' => config('services.telegram.chat_id'),
            'text' => implode("\n", $lines),
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true,
        ])->throw();

        $this->info('Telegram expiry reminder sent.');

        return self::SUCCESS;
    }
}
