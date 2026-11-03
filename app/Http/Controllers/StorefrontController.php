<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductOffering;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(): View
    {
        return view('storefront.home', [
            'products' => Product::query()
                ->with(['offerings' => fn ($query) => $query->where('status', 'active')->with(['accountType', 'product'])->withCount(['subscriptions as active_subscriptions_count' => fn ($subscription) => $subscription->where('status', 'active')->where('expires_at', '>', now())])->orderBy('price')])
                ->where('status', true)
                ->whereHas('offerings', fn ($query) => $query->where('status', 'active'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function customOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'offering_id' => ['required', 'exists:product_offerings,id'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'telegram_username' => ['required', 'string', 'max:33', 'regex:/^@?[A-Za-z0-9_]{5,32}$/'],
        ]);
        $offering = ProductOffering::query()->with(['product', 'accountType'])->withCount(['subscriptions as active_subscriptions_count' => fn ($subscription) => $subscription->where('status', 'active')->where('expires_at', '>', now())])->where('status', 'active')->findOrFail($data['offering_id']);
        abort_if($offering->active_subscriptions_count >= $offering->accountType->max_users, 422, 'Offering ini sudah penuh.');
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! $botToken || ! $chatId) {
            Log::error('Telegram custom order is not configured.');

            return back()->withInput()->with('error', 'Permintaan belum dapat dikirim. Telegram belum dikonfigurasi.');
        }

        $text = implode("\n", [
            'Permintaan custom order baru',
            'Paket: '.$offering->product->name.' '.$offering->accountType->name,
            'Durasi: '.$data['duration_days'].' hari',
            'Nama: '.$data['name'],
            'Email: '.$data['email'],
            'WhatsApp: '.($data['phone'] ?: '-'),
            'Telegram: '.$data['telegram_username'],
        ]);

        try {
            Http::timeout(15)
                ->retry(2, 500)
                ->post('https://api.telegram.org/bot'.$botToken.'/sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $text,
                ])
                ->throw()
                ->json();
        } catch (\Throwable $exception) {
            Log::error('Telegram custom order failed.', ['message' => $exception->getMessage()]);

            return back()->withInput()->with('error', 'Permintaan gagal dikirim ke Telegram. Silakan coba lagi.');
        }

        return back()->with('success', 'Permintaan berhasil dikirim. Cassie akan menghubungi kamu segera.');
    }
}
