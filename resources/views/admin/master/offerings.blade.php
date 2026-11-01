@extends('layouts.admin', ['heading' => 'Product offerings'])

@section('content')
<div class="page-heading">
    <div><h2>Product offerings</h2><p>Kelola email akun, masa aktif, harga, dan akses user.</p></div>
    <a href="{{ route('admin.catalog.index') }}" class="btn btn-light px-3 py-2"><i class="fa-solid fa-arrow-left me-2"></i>Dashboard</a>
</div>

@if (session('success'))
    <div class="alert alert-success border-0 shadow-sm"><i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger border-0 shadow-sm"><i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><div class="fw-bold mb-1">Periksa kembali data yang dimasukkan</div><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="row g-4">
    <div class="col-12">
        <section class="card" id="offerings">
            <div class="card-header"><div><h3 class="card-title">Product offerings</h3><div class="text-muted small">Kelola detail akses dan pengguna untuk setiap penawaran.</div></div></div>
            <div class="p-4 border-bottom" style="border-color:var(--line)!important">
                <form method="POST" action="{{ route('admin.offerings.store') }}" class="row g-3">@csrf<input type="hidden" name="status" value="1">
                    <div class="col-md-6"><label class="form-label">Produk</label><select name="product_id" class="form-select" required><option value="">Pilih produk</option>@foreach ($productOptions as $product)<option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Tipe akun</label><select name="account_type_id" class="form-select" required><option value="">Pilih tipe</option>@foreach ($accountTypeOptions as $type)<option value="{{ $type->id }}" @selected(old('account_type_id') == $type->id)>{{ $type->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Email akun</label><input name="email" type="email" class="form-control" value="{{ old('email') }}" placeholder="akun@example.com" required></div>
                    <div class="col-md-3"><label class="form-label">Mulai</label><input name="starts_at" type="datetime-local" class="form-control" value="{{ old('starts_at') }}" required></div>
                    <div class="col-md-3"><label class="form-label">Kedaluwarsa</label><input name="expires_at" type="datetime-local" class="form-control" value="{{ old('expires_at') }}" required></div>
                    <div class="col-md-3"><label class="form-label">Harga</label><input name="price" type="number" min="0" step="0.01" class="form-control" value="{{ old('price') }}" placeholder="0" required></div>
                    <div class="col-md-3"><label class="form-label">Durasi (hari)</label><input name="duration_days" type="number" min="1" class="form-control" value="{{ old('duration_days', 30) }}" required></div>
                    <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary px-4"><i class="fa-solid fa-plus me-2"></i>Tambah offering</button></div>
                </form>
            </div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="ps-4">Produk</th><th>Email</th><th>Mulai</th><th>Kedaluwarsa</th><th>Harga</th><th>Users</th><th class="text-end pe-4">Aksi</th></tr></thead><tbody>
                @forelse ($offerings as $offering)
                    <tr>
                        <td class="ps-4"><div class="fw-semibold text-dark">{{ $offering->product->name }}</div><span class="badge {{ $offering->status ? 'badge-soft-success' : 'badge-soft-danger' }} mt-1">{{ $offering->status ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td>{{ $offering->email ?: '—' }}</td>
                        <td class="text-nowrap">{{ $offering->starts_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="text-nowrap">{{ $offering->expires_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="text-nowrap">{{ config('app.currency', 'IDR') }} {{ number_format($offering->price, config('app.currency', 'IDR') === 'IDR' ? 0 : 2, config('app.currency', 'IDR') === 'IDR' ? ',' : '.', config('app.currency', 'IDR') === 'IDR' ? '.' : ',') }}</td>
                        <td><span class="badge badge-soft-primary">{{ $offering->subscriptions_count }}</span></td>
                        <td class="text-end pe-4 text-nowrap">
                            <a href="{{ route('admin.offerings.show', $offering) }}" class="btn btn-light btn-sm" title="Detail offering"><i class="fa-solid fa-eye"></i></a>
                            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#offering-users-{{ $offering->id }}" title="Tambah user"><i class="fa-solid fa-user-plus"></i></button>
                            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#offering-edit-{{ $offering->id }}" title="Edit"><i class="fa-solid fa-pen"></i></button>
                            <form method="POST" action="{{ route('admin.offerings.destroy', $offering) }}" class="d-inline" onsubmit="return confirm('Hapus offering ini beserta seluruh akses user terkait?')">@csrf @method('DELETE')<button class="btn btn-light btn-sm text-danger" title="Hapus offering"><i class="fa-solid fa-trash"></i></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-4 text-center text-muted">Belum ada penawaran produk.</td></tr>
                @endforelse
            </tbody></table></div>
            @if ($offerings->hasPages())
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 border-top p-3"><div class="small text-muted">Menampilkan {{ $offerings->firstItem() }} sampai {{ $offerings->lastItem() }} dari {{ $offerings->total() }} penawaran</div><div class="metronic-pagination">{{ $offerings->links('admin.master.pagination') }}</div></div>
            @endif
        </section>
    </div>

</div>

@foreach ($offerings as $offering)
    <div class="modal fade" id="offering-users-{{ $offering->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('admin.offerings.users.store', $offering) }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Tambah user ke {{ $offering->product->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><label class="form-label">User</label><select name="user_id" class="form-select" required><option value="">Pilih user</option>@foreach ($userOptions as $user)<option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</option>@endforeach</select>@if ($userOptions->isEmpty())<div class="form-text">Belum ada user dengan role client.</div>@endif<div class="form-text">Akses mengikuti tanggal mulai dan kedaluwarsa pada offering.</div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" @disabled($userOptions->isEmpty())><i class="fa-solid fa-user-plus me-1"></i>Tambah user</button></div></form></div></div>

    <div class="modal fade" id="offering-edit-{{ $offering->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><form method="POST" action="{{ route('admin.offerings.update', $offering) }}" class="modal-content">@csrf @method('PUT')<div class="modal-header"><h5 class="modal-title">Edit offering</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-6"><label class="form-label">Produk</label><select name="product_id" class="form-select" required>@foreach ($productOptions as $product)<option value="{{ $product->id }}" @selected($offering->product_id === $product->id)>{{ $product->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Tipe akun</label><select name="account_type_id" class="form-select" required>@foreach ($accountTypeOptions as $type)<option value="{{ $type->id }}" @selected($offering->account_type_id === $type->id)>{{ $type->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Email akun</label><input name="email" type="email" class="form-control" value="{{ $offering->email }}" required></div><div class="col-md-3"><label class="form-label">Mulai</label><input name="starts_at" type="datetime-local" class="form-control" value="{{ $offering->starts_at?->format('Y-m-d\\TH:i') }}" required></div><div class="col-md-3"><label class="form-label">Kedaluwarsa</label><input name="expires_at" type="datetime-local" class="form-control" value="{{ $offering->expires_at?->format('Y-m-d\\TH:i') }}" required></div><div class="col-md-3"><label class="form-label">Harga</label><input name="price" type="number" min="0" step="0.01" class="form-control" value="{{ $offering->price }}" required></div><div class="col-md-3"><label class="form-label">Durasi (hari)</label><input name="duration_days" type="number" min="1" class="form-control" value="{{ $offering->duration_days }}" required></div><div class="col-12"><input type="hidden" name="status" value="0"><div class="form-check form-switch"><input type="checkbox" name="status" value="1" class="form-check-input" id="offering-status-{{ $offering->id }}" @checked($offering->status)><label class="form-check-label" for="offering-status-{{ $offering->id }}">Offering aktif</label></div></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan perubahan</button></div></form></div></div>
@endforeach
@endsection
