@extends('layouts.admin', ['heading' => 'Detail product offering'])

@section('content')
    <div class="page-heading">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge badge-soft-primary">Akses manual</span>
                <span class="badge {{ $offering->status === 'active' ? 'badge-soft-success' : ($offering->status === 'suspended' ? 'badge-soft-warning' : 'badge-soft-danger') }}">{{ ['active' => 'Aktif', 'suspended' => 'Suspend', 'deactivated' => 'Deactivated'][$offering->status] ?? $offering->status }}</span>
            </div>
            <h2>{{ $offering->product->name }} <span class="text-muted fw-normal">/ {{ $offering->accountType->name }}</span></h2>
            <p>Kelola transaksi secara manual. Logout akun provider dilakukan manual melalui website provider.</p>
        </div>
        <a href="{{ route('admin.offerings.index') }}" class="btn btn-light px-3 py-2"><i class="fa-solid fa-arrow-left me-2"></i>Daftar offerings</a>
    </div>

    <section class="card mb-4">
        <div class="card-header"><h3 class="card-title">Informasi offering</h3><div class="small text-muted">Ringkasan akses akun dan transaksi.</div></div>
        <div class="p-4"><div class="row g-4">
            <div class="col-sm-6 col-lg-3"><div class="small text-muted mb-1">Email akun</div><div class="fw-semibold text-dark">{{ $offering->email ?: '—' }}</div></div>
            <div class="col-sm-6 col-lg-3"><div class="small text-muted mb-1">Proses akses</div><div class="fw-semibold text-dark">Manual</div></div>
            <div class="col-sm-6 col-lg-3"><div class="small text-muted mb-1">Durasi</div><div class="fw-semibold text-dark">{{ $offering->duration_days }} hari</div></div>
            <div class="col-sm-6 col-lg-3"><div class="small text-muted mb-1">User berlangganan</div><div class="fw-semibold text-dark">{{ $offering->subscriptions()->count() }} user</div></div>
        </div></div>
    </section>

    <section class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between"><div><h3 class="card-title mb-1">Daftar pelanggan berlangganan</h3><div class="small text-muted">Status transaksi, akses, dan sisa masa aktif pelanggan.</div></div><span class="badge badge-soft-primary">{{ $transactions->count() }} transaksi</span></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th class="ps-4">Pelanggan</th><th>Nomor transaksi</th><th>Status</th><th>Berakhir</th><th>Sisa waktu</th><th class="text-end pe-4">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        @php
                            $isActive = $transaction->status === 'paid' && ($transaction->expires_at === null || $transaction->expires_at->isFuture());
                            $remainingSeconds = $transaction->expires_at ? max(0, now()->diffInSeconds($transaction->expires_at, false)) : null;
                            $remainingDays = $remainingSeconds === null ? null : intdiv($remainingSeconds, 86400);
                            $remainingHours = $remainingSeconds === null ? null : intdiv($remainingSeconds % 86400, 3600);
                        @endphp
                        <tr>
                            <td class="ps-4"><div class="fw-semibold text-dark">{{ $transaction->user?->name ?: '—' }}</div><small class="text-muted">{{ $transaction->user?->phone_number ?: 'Nomor HP tidak tersedia' }}</small></td>
                            <td><a href="{{ route('admin.transactions.show', $transaction) }}" class="text-decoration-none">{{ $transaction->number }}</a></td>
                            <td><span class="badge {{ $isActive ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ $isActive ? 'Aktif' : 'Berakhir' }}</span></td>
                            <td>{{ $transaction->expires_at?->format('d M Y H:i') ?: 'Tidak terbatas' }}</td>
                            <td>{{ $remainingSeconds === null ? 'Tidak terbatas' : ($remainingSeconds > 0 ? $remainingDays.' hari '.$remainingHours.' jam' : 'Sudah berakhir') }}</td>
                            <td class="text-end pe-4 text-nowrap">
                                <a href="{{ route('admin.transactions.receipt', $transaction) }}" class="btn btn-light btn-sm" title="Cetak struk"><i class="fa-solid fa-print"></i></a>
                                <form method="POST" action="{{ route('admin.transactions.logged-out', $transaction) }}" class="d-inline" data-confirm="Tandai transaksi ini sudah logout?"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH"><button class="btn btn-light btn-sm text-warning" title="{{ $transaction->logged_out_at ? 'Catat logout ulang' : 'Logout' }}"><i class="fa-solid fa-right-from-bracket"></i></button></form>
                                <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#renew-{{ $transaction->id }}" title="Renew"><i class="fa-solid fa-rotate"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4 text-center text-muted">Belum ada transaksi pelanggan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @foreach ($transactions as $transaction)
        <div class="modal fade" id="renew-{{ $transaction->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('admin.transactions.renew', $transaction) }}" class="modal-content">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Renew {{ $transaction->user?->name ?: 'customer' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body"><div class="mb-3"><label class="form-label">Nominal</label><input type="number" name="total_amount" min="0" step="0.01" class="form-control" value="{{ $offering->price }}" required></div><div><label class="form-label">Durasi (hari)</label><input type="number" name="duration_days" min="1" max="3650" class="form-control" value="{{ $offering->duration_days }}" required></div></div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Renew</button></div>
                </form>
            </div>
        </div>
    @endforeach

    <section class="card">
        <div class="card-header"><h3 class="card-title">Proses manual</h3></div>
        <div class="p-4">
            <ol class="mb-0">
                <li>Catat pembayaran melalui menu Cashier.</li>
                <li>Kirim akses akun/provider secara manual kepada customer.</li>
                <li>Lakukan invite, kick, dan logout device secara manual melalui website provider.</li>
                <li>Pantau transaksi dan laporan melalui menu Workspace Reports.</li>
            </ol>
        </div>
    </section>
@endsection
