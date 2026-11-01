@extends('layouts.admin', ['heading' => 'Master products'])

@section('content')
<div class="page-heading">
    <div><h2>Data products</h2><p>Kelola nama, slug, deskripsi, dan status produk.</p></div>
    <button class="btn btn-primary px-3 py-2" data-bs-toggle="modal" data-bs-target="#createProduct"><i class="fa-solid fa-plus me-2"></i>Tambah produk</button>
</div>

@if (session('success'))
    <div class="alert alert-success border-0 shadow-sm"><i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger border-0 shadow-sm"><i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><div class="fw-bold mb-1">Periksa kembali data produk</div><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4"><div class="card metric-card"><div class="metric-icon"><i class="fa-solid fa-box"></i></div><div><div class="metric-label">Total produk</div><div class="metric-value">{{ $productStats['total'] }}</div></div></div></div>
    <div class="col-sm-6 col-xl-4"><div class="card metric-card"><div class="metric-icon" style="color:#50cd89;background:#e8fff3"><i class="fa-solid fa-circle-check"></i></div><div><div class="metric-label">Produk aktif</div><div class="metric-value">{{ $productStats['active'] }}</div></div></div></div>
    <div class="col-sm-6 col-xl-4"><div class="card metric-card"><div class="metric-icon" style="color:#dc4266;background:#fff0f3"><i class="fa-solid fa-circle-pause"></i></div><div><div class="metric-label">Produk nonaktif</div><div class="metric-value">{{ $productStats['inactive'] }}</div></div></div></div>
</div>

<section class="card">
    <div class="card-header d-flex align-items-center justify-content-between gap-3"><div><h3 class="card-title">Daftar produk</h3><div class="text-muted small">Tambah, ubah, aktifkan, atau hapus produk.</div></div><span class="badge bg-light text-secondary">{{ $products->total() }} produk</span></div>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="ps-4">Produk</th><th>Slug</th><th>Penawaran</th><th>Status</th><th class="text-end pe-4">Aksi</th></tr></thead><tbody>
        @forelse ($products as $product)
            <tr>
                <td class="ps-4"><div class="fw-bold text-dark">{{ $product->name }}</div>@if ($product->description)<div class="text-muted small mt-1">{{ \Illuminate\Support\Str::limit($product->description, 88) }}</div>@endif</td>
                <td><code>{{ $product->slug }}</code></td>
                <td>{{ $product->offerings_count }} paket</td>
                <td><span class="badge {{ $product->status ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ $product->status ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td class="text-end pe-4"><div class="d-inline-flex align-items-center gap-1">
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editProduct{{ $product->id }}" aria-label="Edit {{ $product->name }}"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" action="{{ route('admin.products.toggle', $product) }}">@csrf @method('PATCH')<button class="btn btn-light btn-sm" title="{{ $product->status ? 'Nonaktifkan' : 'Aktifkan' }}"><i class="fa-solid {{ $product->status ? 'fa-toggle-on text-primary' : 'fa-toggle-off' }}"></i></button></form>
                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Hapus produk {{ $product->name }}?')">@csrf @method('DELETE')<button class="btn btn-light btn-sm text-danger" title="{{ $product->offerings_count ? 'Hapus penawaran sebelum menghapus produk' : 'Hapus produk' }}" @disabled($product->offerings_count > 0)><i class="fa-solid fa-trash"></i></button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="5" class="py-5 text-center"><div class="text-muted mb-2"><i class="fa-regular fa-folder-open fa-2x"></i></div><div class="fw-semibold text-dark">Belum ada produk</div><div class="text-muted small">Tambahkan produk pertama ke katalog.</div></td></tr>
        @endforelse
    </tbody></table></div>
    @if ($products->hasPages())
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 border-top p-3"><div class="small text-muted">Menampilkan {{ $products->firstItem() }} sampai {{ $products->lastItem() }} dari {{ $products->total() }} produk</div><div class="metronic-pagination">{{ $products->links('admin.master.pagination') }}</div></div>
    @endif
</section>

@foreach ($products as $product)
    <div class="modal fade" id="editProduct{{ $product->id }}" tabindex="-1" aria-labelledby="editProductLabel{{ $product->id }}" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('admin.products.update', $product) }}" class="modal-content">@csrf @method('PUT')<input type="hidden" name="status" value="0"><div class="modal-header"><h5 class="modal-title" id="editProductLabel{{ $product->id }}">Edit produk</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><label class="form-label">Nama produk</label><input name="name" class="form-control mb-3" value="{{ $product->name }}" required><label class="form-label">Slug</label><input name="slug" class="form-control mb-3" value="{{ $product->slug }}" required><label class="form-label">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label><textarea name="description" class="form-control mb-3" rows="3">{{ $product->description }}</textarea><label class="form-check"><input type="checkbox" name="status" value="1" class="form-check-input" @checked($product->status)><span class="form-check-label">Produk aktif</span></label></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary px-3">Simpan perubahan</button></div></form></div></div>
@endforeach

<div class="modal fade" id="createProduct" tabindex="-1" aria-labelledby="createProductLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('admin.products.store') }}" class="modal-content">@csrf<input type="hidden" name="status" value="1"><div class="modal-header"><h5 class="modal-title" id="createProductLabel">Tambah produk</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><label class="form-label">Nama produk</label><input name="name" class="form-control mb-3" value="{{ old('name') }}" placeholder="Contoh: Claude Pro" required><label class="form-label">Slug</label><input name="slug" class="form-control mb-3" value="{{ old('slug') }}" placeholder="claude-pro" required><label class="form-label">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label><textarea name="description" class="form-control" rows="3" placeholder="Deskripsi singkat produk">{{ old('description') }}</textarea></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary px-3">Simpan produk</button></div></form></div></div>
@endsection
