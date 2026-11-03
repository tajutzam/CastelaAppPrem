<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $transaction->number }}</title>
    <style>
        @page { size: 80mm auto; margin: 4mm; }
        * { box-sizing: border-box; }
        body { width: 72mm; margin: 0 auto; color: #111; font: 12px/1.45 Arial, sans-serif; }
        .center { text-align: center; }
        h1 { margin: 0 0 2px; font-size: 20px; }
        .muted { color: #666; }
        .line { border-top: 1px dashed #222; margin: 10px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; }
        .row span:last-child { text-align: right; }
        .total { font-size: 16px; font-weight: 700; }
        .actions { display: flex; justify-content: center; gap: 8px; margin: 16px 0; }
        button { border: 1px solid #222; background: #fff; padding: 7px 14px; cursor: pointer; }
        @media print { .actions { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="actions"><button type="button" onclick="window.print()">Cetak struk</button><button type="button" onclick="window.close()">Tutup</button></div>
    <div class="center">
        <h1>Castela </h1>
        <div class="muted">Bukti transaksi</div>
    </div>
    <div class="line"></div>
    <div class="row"><span>No.</span><span>{{ $transaction->number }}</span></div>
    <div class="row"><span>Tanggal</span><span>{{ $transaction->paid_at?->format('d/m/Y H:i') ?: '-' }}</span></div>
    <div class="row"><span>Kasir</span><span>{{ $transaction->cashier?->name ?: '-' }}</span></div>
    <div class="line"></div>
    <strong>{{ $transaction->product_name }}</strong>
    <div class="muted">{{ $transaction->productOffering?->accountType?->name ?: 'Paket langganan' }}</div>
    <div class="row"><span>Customer</span><span>{{ $transaction->user?->name ?: '-' }}</span></div>
    <div class="row"><span>Email akun</span><span>{{ $transaction->productOffering?->email ?: '-' }}</span></div>
    <div class="row"><span>Durasi</span><span>{{ $transaction->duration_days }} hari</span></div>
    <div class="row"><span>Berlaku sampai</span><span>{{ $transaction->expires_at?->format('d/m/Y') ?: '-' }}</span></div>
    <div class="line"></div>
    <div class="row total"><span>Total</span><span>{{ config('app.currency', 'IDR') }} {{ number_format($transaction->total_amount, 0, ',', '.') }}</span></div>
    <div class="line"></div>
    <div class="center muted">Terima kasih</div>
</body>
</html>
