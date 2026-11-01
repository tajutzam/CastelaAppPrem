@extends('layouts.admin', ['heading' => 'Detail product offering'])

@section('content')
    <div class="page-heading">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge badge-soft-primary">{{ $providerLabel }}</span>
                <span class="badge {{ $offering->status ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ $offering->status ? 'Aktif' : 'Nonaktif' }}</span>
            </div>
            <h2>{{ $offering->product->name }} <span class="text-muted fw-normal">/ {{ $offering->accountType->name }}</span></h2>
            <p>Kelola transaksi secara manual. Logout akun provider dilakukan manual melalui website provider.</p>
        </div>
        <a href="{{ route('admin.offerings.index') }}" class="btn btn-light px-3 py-2"><i class="fa-solid fa-arrow-left me-2"></i>Daftar offerings</a>
    </div>

    @if ($connectionError)
        <div class="alert alert-warning border-0 shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i>{{ $connectionError }}</div>
    @endif

    <section class="card mb-4">
        <div class="card-header"><h3 class="card-title">Informasi offering</h3><div class="small text-muted">Ringkasan akses akun dan transaksi.</div></div>
        <div class="p-4"><div class="row g-4">
            <div class="col-sm-6 col-lg-3"><div class="small text-muted mb-1">Email akun</div><div class="fw-semibold text-dark">{{ $offering->email ?: '—' }}</div></div>
            <div class="col-sm-6 col-lg-3"><div class="small text-muted mb-1">Organization lokal</div><div class="fw-semibold text-dark">{{ $offering->organization?->id ? '#'.$offering->organization->id : 'Belum dibuat' }}</div></div>
            <div class="col-sm-6 col-lg-3"><div class="small text-muted mb-1">ID organization provider</div><div class="fw-semibold text-dark">{{ $offering->organization?->external_id ?: '—' }}</div></div>
            <div class="col-sm-6 col-lg-3"><div class="small text-muted mb-1">User berlangganan</div><div class="fw-semibold text-dark">{{ $offering->subscriptions()->count() }} user</div></div>
        </div></div>
    </section>

    <section class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between"><div><h3 class="card-title mb-1">Daftar pelanggan berlangganan</h3><div class="small text-muted">Status dan sisa masa aktif setiap subscription.</div></div><span class="badge badge-soft-primary">{{ $subscriptions->count() }} pelanggan</span></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th class="ps-4">Pelanggan</th><th>Status</th><th>Mulai</th><th>Berakhir</th><th>Sisa waktu</th></tr></thead>
                <tbody>
                    @forelse ($subscriptions as $subscription)
                        @php
                            $isActive = $subscription->status === 'active' && ($subscription->expires_at === null || $subscription->expires_at->isFuture());
                            $remainingSeconds = $subscription->expires_at ? max(0, now()->diffInSeconds($subscription->expires_at, false)) : null;
                            $remainingDays = $remainingSeconds === null ? null : intdiv($remainingSeconds, 86400);
                            $remainingHours = $remainingSeconds === null ? null : intdiv($remainingSeconds % 86400, 3600);
                        @endphp
                        <tr>
                            <td class="ps-4"><div class="fw-semibold text-dark">{{ $subscription->user?->name ?: '—' }}</div><small class="text-muted">{{ $subscription->user?->email ?: 'Email tidak tersedia' }}</small></td>
                            <td><span class="badge {{ $isActive ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ $isActive ? 'Aktif' : 'Berakhir' }}</span></td>
                            <td>{{ $subscription->starts_at?->format('d M Y H:i') ?: '—' }}</td>
                            <td>{{ $subscription->expires_at?->format('d M Y H:i') ?: 'Tidak terbatas' }}</td>
                            <td>{{ $remainingSeconds === null ? 'Tidak terbatas' : ($remainingSeconds > 0 ? $remainingDays.' hari '.$remainingHours.' jam' : 'Sudah berakhir') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-center text-muted">Belum ada pelanggan berlangganan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="card-header"><h3 class="card-title">Proses manual</h3></div>
        <div class="p-4">
            <ol class="mb-0">
                <li>Catat pembayaran melalui menu Cashier.</li>
                <li>Kirim akses akun/provider secara manual kepada customer.</li>
                <li>Lakukan logout semua device secara manual melalui website {{ $providerLabel }}.</li>
                <li>Pantau transaksi dan laporan melalui menu Workspace Reports.</li>
            </ol>
        </div>
    </section>
@endsection
