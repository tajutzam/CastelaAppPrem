@extends('layouts.admin', ['heading' => 'Settings'])

@section('content')
<div class="page-heading">
    <div><h2>Pengaturan aplikasi</h2><p>Atur identitas aplikasi dan preferensi operasional.</p></div>
</div>

@if (session('success'))
    <div class="alert alert-success border-0 shadow-sm"><i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><div class="fw-bold mb-1">Pengaturan belum tersimpan</div><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    @method('PUT')
    <div class="row g-4">
        <div class="col-xl-8">
            <section class="card mb-4">
                <div class="card-header"><h3 class="card-title"><i class="fa-solid fa-building me-2 text-primary"></i>Identitas aplikasi</h3><div class="text-muted small">Informasi ini digunakan pada header admin dan informasi dukungan.</div></div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-12"><label for="app_name" class="form-label">Nama aplikasi</label><input id="app_name" name="app_name" class="form-control" value="{{ old('app_name', $settings->get('app_name', $defaults['app_name'])) }}" maxlength="100" required><div class="form-text">Nama ini tampil di sidebar dan judul halaman admin.</div></div>
                        <div class="col-md-6"><label for="support_email" class="form-label">Email dukungan</label><input id="support_email" name="support_email" type="email" class="form-control" value="{{ old('support_email', $settings->get('support_email', $defaults['support_email'])) }}" placeholder="support@example.com"><div class="form-text">Boleh dikosongkan jika belum tersedia.</div></div>
                        <div class="col-md-6"><label for="support_phone" class="form-label">Nomor dukungan</label><input id="support_phone" name="support_phone" type="tel" class="form-control" value="{{ old('support_phone', $settings->get('support_phone', $defaults['support_phone'])) }}" maxlength="30" placeholder="+62 812 3456 7890"></div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h3 class="card-title"><i class="fa-solid fa-sliders me-2 text-primary"></i>Preferensi</h3><div class="text-muted small">Zona waktu dan mata uang yang digunakan aplikasi.</div></div>
                <div class="p-4"><div class="row g-3">
                    <div class="col-md-6"><label for="timezone" class="form-label">Zona waktu</label><select id="timezone" name="timezone" class="form-select" required>@foreach (['Asia/Jakarta' => 'WIB · Jakarta', 'Asia/Makassar' => 'WITA · Makassar', 'Asia/Jayapura' => 'WIT · Jayapura', 'Asia/Singapore' => 'Singapore', 'UTC' => 'UTC'] as $value => $label)<option value="{{ $value }}" @selected(old('timezone', $settings->get('timezone', $defaults['timezone'])) === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label for="currency" class="form-label">Mata uang</label><select id="currency" name="currency" class="form-select" required>@foreach (['IDR' => 'IDR · Rupiah Indonesia', 'USD' => 'USD · US Dollar'] as $value => $label)<option value="{{ $value }}" @selected(old('currency', $settings->get('currency', $defaults['currency'])) === $value)>{{ $label }}</option>@endforeach</select></div>
                </div></div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="card">
                <div class="card-header"><h3 class="card-title">Simpan perubahan</h3></div>
                <div class="p-4"><p class="small text-muted mb-4">Pengaturan akan langsung diterapkan pada tampilan admin setelah disimpan.</p><button class="btn btn-primary w-100 py-2"><i class="fa-solid fa-floppy-disk me-2"></i>Simpan pengaturan</button></div>
            </section>
        </div>
    </div>
</form>
@endsection
