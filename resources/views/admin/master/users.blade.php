@extends('layouts.admin', ['heading' => 'Master users'])

@section('content')
<div class="page-heading">
    <div><h2>Master users</h2><p>Kelola akun pengguna dan hak akses aplikasi.</p></div>
    <button class="btn btn-primary px-3 py-2" data-bs-toggle="modal" data-bs-target="#userModal"><i class="fa-solid fa-user-plus me-2"></i>Tambah user</button>
</div>

@if (session('success'))
    <div class="alert alert-success border-0 shadow-sm"><i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><div class="fw-bold mb-1">Periksa kembali data yang dimasukkan</div><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="card metric-card"><div class="metric-icon"><i class="fa-solid fa-users"></i></div><div><div class="metric-label">Total user</div><div class="metric-value">{{ $userStats['total'] }}</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card metric-card"><div class="metric-icon" style="color:#f6c000;background:#fff8dd"><i class="fa-solid fa-user-shield"></i></div><div><div class="metric-label">Admin</div><div class="metric-value">{{ $userStats['admin'] }}</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card metric-card"><div class="metric-icon" style="color:#7239ea;background:#f3edff"><i class="fa-solid fa-building-user"></i></div><div><div class="metric-label">Owner</div><div class="metric-value">{{ $userStats['owner'] }}</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card metric-card"><div class="metric-icon" style="color:#50cd89;background:#e8fff3"><i class="fa-solid fa-user"></i></div><div><div class="metric-label">Client</div><div class="metric-value">{{ $userStats['client'] }}</div></div></div></div>
</div>

<section class="card">
    <div class="card-header d-flex align-items-center justify-content-between gap-3"><div><h3 class="card-title">Daftar user</h3><div class="text-muted small">Informasi akun dan role pengguna.</div></div><span class="badge bg-light text-secondary">{{ $users->total() }} user</span></div>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="ps-4">Pengguna</th><th>Nomor telepon</th><th>Role</th><th>Email terverifikasi</th><th>Bergabung</th></tr></thead><tbody>
        @forelse ($users as $user)
            @php($roleStyle = ['admin' => 'badge-soft-danger', 'owner' => 'badge-soft-success', 'client' => 'badge-soft-primary'][$user->role] ?? 'badge-soft-danger')
            <tr><td class="ps-4"><div class="d-flex align-items-center gap-3"><span class="user-chip">{{ strtoupper(substr($user->name, 0, 1)) }}</span><div><div class="fw-bold text-dark">{{ $user->name }}</div><div class="text-muted small">{{ $user->email }}</div></div></div></td><td>{{ $user->phone_number ?: '—' }}</td><td><span class="badge {{ $roleStyle }}">{{ ucfirst($user->role) }}</span></td><td>@if ($user->email_verified_at)<span class="text-success"><i class="fa-solid fa-circle-check me-1"></i>Terverifikasi</span>@else<span class="text-muted">Belum</span>@endif</td><td>{{ $user->created_at->format('d M Y') }}</td></tr>
        @empty
            <tr><td colspan="5" class="py-5 text-center"><div class="text-muted mb-2"><i class="fa-regular fa-user fa-2x"></i></div><div class="fw-semibold text-dark">Belum ada user</div><div class="text-muted small">Tambahkan user untuk memberikan akses ke aplikasi.</div></td></tr>
        @endforelse
    </tbody></table></div>
    @if ($users->hasPages())
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 border-top p-3">
            <div class="small text-muted">Menampilkan {{ $users->firstItem() }} sampai {{ $users->lastItem() }} dari {{ $users->total() }} user</div>
            <div class="metronic-pagination">{{ $users->links('admin.master.pagination') }}</div>
        </div>
    @endif
</section>

<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('admin.users.store') }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title" id="userModalLabel">Tambah user</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><label class="form-label">Nama lengkap</label><input name="name" class="form-control mb-3" value="{{ old('name') }}" placeholder="Nama pengguna" required><label class="form-label">Email</label><input name="email" type="email" class="form-control mb-3" value="{{ old('email') }}" placeholder="nama@email.com" required><label class="form-label">Nomor telepon <span class="text-muted fw-normal">(opsional)</span></label><input name="phone_number" type="tel" class="form-control mb-3" value="{{ old('phone_number') }}" placeholder="08xxxxxxxxxx"><label class="form-label">Role</label><select name="role" class="form-select mb-3" required><option value="client" @selected(old('role', 'client') === 'client')>Client</option><option value="owner" @selected(old('role') === 'owner')>Owner</option><option value="admin" @selected(old('role') === 'admin')>Admin</option></select><label class="form-label">Password</label><input name="password" type="password" class="form-control mb-3" autocomplete="new-password" minlength="8" required><label class="form-label">Konfirmasi password</label><input name="password_confirmation" type="password" class="form-control" autocomplete="new-password" minlength="8" required></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary px-3">Simpan user</button></div></form></div></div>
@endsection
