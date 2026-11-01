@extends('layouts.admin', ['heading' => 'Master account types'])

@section('content')
<div class="page-heading">
    <div><h2>Master account types</h2><p>Kelola kapasitas akun yang dapat dipilih pada penawaran produk.</p></div>
    <button class="btn btn-primary px-3 py-2" data-bs-toggle="modal" data-bs-target="#typeModal"><i class="fa-solid fa-plus me-2"></i>Tambah tipe akun</button>
</div>

@if (session('success'))
    <div class="alert alert-success border-0 shadow-sm"><i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><div class="fw-bold mb-1">Periksa kembali data yang dimasukkan</div><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4"><div class="card metric-card"><div class="metric-icon"><i class="fa-solid fa-users"></i></div><div><div class="metric-label">Total tipe akun</div><div class="metric-value">{{ $accountTypeStats['total'] }}</div></div></div></div>
    <div class="col-sm-6 col-xl-4"><div class="card metric-card"><div class="metric-icon" style="color:#50cd89;background:#e8fff3"><i class="fa-solid fa-circle-check"></i></div><div><div class="metric-label">Tipe akun aktif</div><div class="metric-value">{{ $accountTypeStats['active'] }}</div></div></div></div>
    <div class="col-sm-6 col-xl-4"><div class="card metric-card"><div class="metric-icon" style="color:#f6c000;background:#fff8dd"><i class="fa-solid fa-tags"></i></div><div><div class="metric-label">Penawaran terhubung</div><div class="metric-value">{{ $accountTypeStats['offerings'] }}</div></div></div></div>
</div>

<section class="card">
    <div class="card-header d-flex align-items-center justify-content-between gap-3"><div><h3 class="card-title">Daftar tipe akun</h3><div class="text-muted small">Kode, kapasitas, dan status tipe akun.</div></div><span class="badge bg-light text-secondary">{{ $accountTypes->total() }} tipe</span></div>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="ps-4">Tipe akun</th><th>Kode</th><th>Maks. pengguna</th><th>Penawaran</th><th>Status</th><th class="text-end pe-4">Aksi</th></tr></thead><tbody>
        @forelse ($accountTypes as $type)
            <tr><td class="ps-4"><div class="fw-bold text-dark">{{ $type->name }}</div><div class="text-muted small">Dibuat {{ $type->created_at->format('d M Y') }}</div></td><td><code>{{ $type->code }}</code></td><td>{{ number_format($type->max_users) }} pengguna</td><td>{{ $type->offerings_count }}</td><td><span class="badge {{ $type->status ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ $type->status ? 'Aktif' : 'Nonaktif' }}</span></td><td class="text-end pe-4"><form method="POST" action="{{ route('admin.account-types.toggle', $type) }}">@csrf @method('PATCH')<button class="btn btn-light btn-sm">{{ $type->status ? 'Nonaktifkan' : 'Aktifkan' }}</button></form></td></tr>
        @empty
            <tr><td colspan="6" class="py-5 text-center"><div class="text-muted mb-2"><i class="fa-regular fa-folder-open fa-2x"></i></div><div class="fw-semibold text-dark">Belum ada tipe akun</div><div class="text-muted small">Buat tipe akun untuk digunakan pada penawaran.</div></td></tr>
        @endforelse
    </tbody></table></div>
    @if ($accountTypes->hasPages())
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 border-top p-3">
            <div class="small text-muted">Menampilkan {{ $accountTypes->firstItem() }} sampai {{ $accountTypes->lastItem() }} dari {{ $accountTypes->total() }} tipe akun</div>
            <div class="metronic-pagination">{{ $accountTypes->links('admin.master.pagination') }}</div>
        </div>
    @endif
</section>

<div class="modal fade" id="typeModal" tabindex="-1" aria-labelledby="typeModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('admin.account-types.store') }}" class="modal-content">@csrf<input type="hidden" name="status" value="1"><div class="modal-header"><h5 class="modal-title" id="typeModalLabel">Tambah tipe akun</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><label class="form-label">Kode</label><input name="code" class="form-control mb-3" placeholder="users_5" required><label class="form-label">Nama tipe</label><input name="name" class="form-control mb-3" placeholder="5 pengguna" required><label class="form-label">Maksimal pengguna</label><input name="max_users" type="number" min="1" max="10000" class="form-control" placeholder="5" required></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary px-3">Simpan tipe akun</button></div></form></div></div>
@endsection
