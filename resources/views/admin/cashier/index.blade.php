@extends('layouts.admin', ['heading' => 'Cashier'])

@section('content')
<style>
    .pos-page{--pos-primary:#4169ed;--pos-ink:#20253b;--pos-muted:#8790a3;--pos-border:#e9edf5;color:var(--pos-ink)}
    .pos-heading{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:24px}
    .pos-heading h2{font-weight:800;letter-spacing:-.04em;margin:0 0 4px}
    .pos-heading p{margin:0;color:var(--pos-muted);font-size:.88rem}
    .pos-status{font-size:.76rem;background:#eaf8f1;color:#159968;border-radius:99px;padding:8px 13px;font-weight:700}
    .pos-layout{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(340px,1fr);gap:22px;align-items:start}
    .pos-panel{background:#fff;border:1px solid var(--pos-border);border-radius:18px;box-shadow:0 5px 24px rgba(30,50,90,.035);overflow:hidden}
    .pos-panel-body{padding:22px}.pos-panel-title{font-size:1.05rem;font-weight:800;letter-spacing:-.025em;margin:0}
    .pos-panel-caption{font-size:.79rem;color:var(--pos-muted);margin:5px 0 0}
    .pos-search{position:relative;margin-top:22px}.pos-search i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#a3acc0}
    .pos-search input{padding-left:42px;height:48px;border-radius:11px;border-color:var(--pos-border);background:#fafbfe}
    .pos-product-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px;margin-top:20px}
    .pos-product{border:1.5px solid var(--pos-border);border-radius:14px;padding:16px;text-align:left;background:#fff;min-height:205px;display:flex;flex-direction:column;align-items:flex-start;gap:10px;transition:.15s ease;cursor:pointer;width:100%;color:inherit}
    .pos-product:hover{border-color:#a7b9ff;transform:translateY(-2px);box-shadow:0 8px 18px rgba(65,105,237,.07)}
    .pos-product.selected{border-color:var(--pos-primary);background:#f6f8ff;box-shadow:0 0 0 2px rgba(65,105,237,.08)}
    .pos-product-icon{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;color:var(--pos-primary);background:#edf1ff;font-size:1.1rem}
    .pos-product-name{font-size:.85rem;font-weight:750;line-height:1.4;overflow-wrap:anywhere}
    .pos-product-duration{font-size:.73rem;color:var(--pos-muted)}
    .pos-product-quota{font-size:.73rem;color:#14845c;background:#eaf8f1;border-radius:7px;padding:5px 8px}
    .pos-product-email{display:flex;align-items:center;gap:6px;max-width:100%;font-size:.73rem;color:#596780;background:#f3f6fc;border-radius:7px;padding:6px 8px;overflow-wrap:anywhere;word-break:break-word}
    .pos-product-email i{flex-shrink:0;color:var(--pos-primary)}
    .pos-order-email{display:flex;gap:7px;align-items:flex-start;margin-top:9px;font-size:.76rem;color:#586681;overflow-wrap:anywhere;word-break:break-word}
    .pos-order-email i{margin-top:3px;color:var(--pos-primary)}
    .pos-product-price{font-size:.92rem;font-weight:800;color:var(--pos-primary);margin-top:auto}
    .pos-empty{grid-column:1/-1;text-align:center;color:var(--pos-muted);padding:48px 12px;font-size:.86rem}
    .pos-checkout{position:sticky;top:20px}.pos-divider{height:1px;background:var(--pos-border);margin:20px 0}
    .pos-label{font-size:.8rem;font-weight:700;color:#535d73;margin-bottom:8px}
    .pos-page .form-control,.pos-page .form-select{border-radius:10px;border-color:#e5eaf3;min-height:43px;font-size:.85rem}
    .pos-page .form-control:focus,.pos-page .form-select:focus{border-color:#a8baff;box-shadow:0 0 0 .2rem rgba(65,105,237,.09)}
    .pos-customer-tabs{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:16px 0}
    .pos-customer-tabs input{position:absolute;opacity:0;pointer-events:none}
    .pos-customer-tabs label{display:flex;align-items:center;justify-content:center;gap:7px;cursor:pointer;border:1px solid var(--pos-border);border-radius:10px;padding:11px 7px;font-size:.8rem;font-weight:700;color:#7c879d}
    .pos-customer-tabs input:checked+label{background:#edf2ff;border-color:#b9c8ff;color:var(--pos-primary)}
    .pos-order{background:#f8faff;border:1px solid #eaf0ff;border-radius:13px;padding:16px}
    .pos-order-name{font-size:.88rem;font-weight:800;overflow-wrap:anywhere}.pos-order-sub{font-size:.75rem;color:var(--pos-muted);margin-top:4px}
    .pos-order-row{display:flex;justify-content:space-between;gap:12px;align-items:center;font-size:.82rem;margin-top:14px}
    .pos-order-row span:first-child{color:#808aa0}.pos-order-row strong{text-align:right}
    .pos-total{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:17px;padding-top:15px;border-top:1px dashed #d6def1}
    .pos-total span{font-size:.85rem;font-weight:700}.pos-total strong{font-size:1.3rem;font-weight:850;color:var(--pos-primary);overflow-wrap:anywhere;text-align:right}
    .pos-submit{width:100%;height:51px;border-radius:11px;font-weight:750;background:var(--pos-primary);border-color:var(--pos-primary)}
    .pos-submit:hover{background:#3157d5;border-color:#3157d5}
    .pos-hint{font-size:.74rem;color:var(--pos-muted);line-height:1.6}
    .pos-result{font-size:.78rem;color:var(--pos-muted)}
    @media(max-width:1200px){.pos-product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.pos-layout{grid-template-columns:minmax(0,1.2fr) minmax(320px,1fr)}}
    @media(max-width:900px){.pos-layout{grid-template-columns:1fr}.pos-checkout{position:static}.pos-product-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
    @media(max-width:580px){.pos-product-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.pos-panel-body{padding:16px}.pos-product{padding:13px;min-height:196px}.pos-heading{align-items:flex-start}.pos-status{white-space:nowrap}}
</style>

<div class="pos-page">
    <div class="pos-heading">
        <div><h2>Kasir</h2><p>Pilih produk, tentukan pelanggan, lalu selesaikan transaksi.</p></div>
        <span class="pos-status"><i class="fa-solid fa-circle-check me-1"></i> Siap transaksi</span>
    </div>

    <form id="cashier-form" data-users-url="{{ route('admin.cashier.api.users') }}" data-offerings-url="{{ route('admin.cashier.api.offerings') }}" data-store-url="{{ route('admin.cashier.api.transactions.store') }}" data-currency="{{ config('app.currency', 'IDR') }}">
        @csrf
        <div id="cashier-alert" class="alert d-none mb-3" role="alert" aria-live="polite" style="white-space:pre-line"></div>
        <div class="pos-layout">
            <section class="pos-panel">
                <div class="pos-panel-body">
                    <h3 class="pos-panel-title">Katalog Produk</h3>
                    <p class="pos-panel-caption">Pilih satu paket langganan untuk transaksi ini.</p>
                    <div class="pos-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="product-search" class="form-control" placeholder="Cari nama paket atau email offering..." aria-label="Cari produk"></div>
                    <div class="d-flex justify-content-between align-items-center mt-3"><span class="pos-result" id="product-count">Memuat produk...</span><span class="pos-result"><i class="fa-solid fa-box-open me-1"></i> Product offerings</span></div>
                    <div id="product-grid" class="pos-product-grid" aria-live="polite"><div class="pos-empty">Memuat katalog produk...</div></div>
                </div>
            </section>

            <aside class="pos-panel pos-checkout">
                <div class="pos-panel-body">
                    <h3 class="pos-panel-title"><i class="fa-solid fa-receipt me-2" style="color:var(--pos-primary)"></i>Checkout</h3>
                    <p class="pos-panel-caption">Lengkapi detail pembayaran pelanggan.</p>
                    <div class="pos-divider"></div>
                    <div class="pos-label">Pelanggan</div>
                    <div class="pos-customer-tabs">
                        <div><input type="radio" name="customer_type" id="customer-existing" value="existing" checked><label for="customer-existing"><i class="fa-solid fa-user-check"></i> Client terdaftar</label></div>
                        <div><input type="radio" name="customer_type" id="customer-new" value="new"><label for="customer-new"><i class="fa-solid fa-user-plus"></i> Client baru</label></div>
                    </div>
                    <div id="existing-customer-fields">
                        <label class="pos-label" for="user-search">Cari client</label>
                        <input id="user-search" type="search" class="form-control mb-2" placeholder="Nama, email, atau telepon">
                        <select name="user_id" id="user_id" class="form-select" size="4" aria-label="Pilih client" required><option value="">Memuat client...</option></select>
                        <div class="d-flex justify-content-between align-items-center mt-2"><small class="pos-result" id="user-result-count"></small><button type="button" id="load-more-users" class="btn btn-sm btn-light d-none">Muat lainnya</button></div>
                    </div>
                    <div id="new-customer-fields" class="d-none">
                        <div class="mb-2"><label class="pos-label" for="customer-name">Nama pelanggan</label><input id="customer-name" name="name" class="form-control" autocomplete="name" placeholder="Nama lengkap"></div>
                        <div class="mb-2"><label class="pos-label" for="customer-email">Email</label><input id="customer-email" name="email" type="email" class="form-control" autocomplete="email" placeholder="client@example.com"></div>
                        <div><label class="pos-label" for="customer-phone">Nomor telepon (opsional)</label><input id="customer-phone" name="phone_number" class="form-control" autocomplete="tel" placeholder="08xxxxxxxxxx"></div>
                    </div>
                    <div class="pos-divider"></div>
                    <div class="d-flex justify-content-between align-items-center mb-3"><div class="pos-label mb-0">Ringkasan Pesanan</div><span class="badge bg-light text-secondary">1 produk / transaksi</span></div>
                    <div class="pos-order">
                        <div class="d-flex align-items-center gap-3"><div class="pos-product-icon"><i class="fa-solid fa-cube"></i></div><div><div class="pos-order-name" id="selected-product-name">Belum ada produk</div><div class="pos-order-sub" id="selected-product-description">Pilih produk dari katalog kiri</div><div class="pos-order-email" id="selected-product-email"><i class="fa-regular fa-envelope"></i><span>—</span></div></div></div>
                        <div class="pos-order-row"><span>Harga modal</span><strong id="selected-product-price">-</strong></div>
                        <div class="pos-order-row"><span>Masa langganan</span><strong id="selected-product-duration">—</strong></div>
                        <div class="pos-order-row"><span>Sisa kuota user</span><strong id="selected-product-quota">-</strong></div>
                        <div class="pos-total"><span>Total pembayaran</span><strong id="display-total">—</strong></div>
                    </div>
                    <input type="hidden" id="product_offering_id" name="product_offering_id" required>
                    <div class="row g-3 mt-1">
                        <div class="col-6"><label class="pos-label" for="duration_days">Durasi (hari)</label><input id="duration_days" name="duration_days" type="number" min="1" max="3650" class="form-control" value="30" required></div>
                        <div class="col-6"><label class="pos-label" for="total_amount">Nominal bayar</label><input id="total_amount" name="total_amount" type="number" min="0" step="0.01" class="form-control" placeholder="0" required></div>
                    </div>
                    <p class="pos-hint mt-3 mb-3"><i class="fa-solid fa-circle-info me-1"></i>Client baru akan menerima password sementara setelah transaksi berhasil disimpan.</p>
                    <button id="submit-transaction" type="submit" class="btn btn-primary pos-submit"><i class="fa-solid fa-check-circle me-2"></i>Proses Transaksi</button>
                </div>
            </aside>
        </div>
    </form>
</div>

<script>
(() => {
    const form = document.getElementById('cashier-form');
    const alertBox = document.getElementById('cashier-alert');
    const usersUrl = form.dataset.usersUrl;
    const offeringsUrl = form.dataset.offeringsUrl;
    const storeUrl = form.dataset.storeUrl;
    const currency = form.dataset.currency || 'IDR';
    const userSelect = document.getElementById('user_id');
    const userSearch = document.getElementById('user-search');
    const loadMoreUsers = document.getElementById('load-more-users');
    const userResultCount = document.getElementById('user-result-count');
    const productGrid = document.getElementById('product-grid');
    const productSearch = document.getElementById('product-search');
    const submitButton = document.getElementById('submit-transaction');
    const newCustomerFields = document.getElementById('new-customer-fields');
    const existingCustomerFields = document.getElementById('existing-customer-fields');
    const offeringInput = document.getElementById('product_offering_id');
    const durationInput = document.getElementById('duration_days');
    const amountInput = document.getElementById('total_amount');
    let offerings = [];
    let selectedOffering = null;
    let userPage = 1;
    let userLastPage = 1;
    let searchTimer;
    let usersRequestId = 0;

    const money = (amount) => `${currency} ${Number(amount || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 })}`;
    const showAlert = (message, type = 'danger') => {
        alertBox.className = `alert alert-${type} mb-3`;
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
        if (type === 'success') alertBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
    const clearAlert = () => { alertBox.classList.add('d-none'); alertBox.textContent = ''; };
    const fetchJson = async (url, options = {}) => {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options.body ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value } : {}),
                ...(options.headers || {}),
            },
        });
        const payload = await response.json();
        if (!response.ok) {
            const validationMessage = payload.errors ? Object.values(payload.errors).flat()[0] : null;
            throw new Error(validationMessage || payload.message || 'Permintaan gagal diproses.');
        }
        return payload;
    };

    const updateSummary = () => {
        document.getElementById('selected-product-name').textContent = selectedOffering?.label || 'Belum ada produk';
        document.getElementById('selected-product-description').textContent = selectedOffering ? 'Paket langganan terpilih' : 'Pilih produk dari katalog kiri';
        document.querySelector('#selected-product-email span').textContent = selectedOffering ? (selectedOffering.email || 'Email belum tersedia') : '—';
        document.getElementById('selected-product-price').textContent = selectedOffering ? money(selectedOffering.price) : '—';
        document.getElementById('selected-product-duration').textContent = selectedOffering ? `${durationInput.value || 0} hari` : '—';
        document.getElementById('selected-product-quota').textContent = selectedOffering ? `${selectedOffering.quota} dari ${selectedOffering.max_users} user` : '-';
        document.getElementById('display-total').textContent = selectedOffering && amountInput.value !== '' ? money(amountInput.value) : '—';
    };

    const chooseOffering = (offering) => {
        selectedOffering = offering;
        offeringInput.value = offering.id;
        amountInput.value = '';
        durationInput.value = offering.duration_days ?? 30;
        renderProducts();
        updateSummary();
    };

    const renderProducts = () => {
        const query = productSearch.value.trim().toLowerCase();
        const filtered = offerings.filter(item => [item.label, item.email].some(value => String(value || '').toLowerCase().includes(query)));
        document.getElementById('product-count').textContent = `${filtered.length} produk ditemukan`;
        productGrid.replaceChildren();
        if (!filtered.length) {
            const empty = document.createElement('div');
            empty.className = 'pos-empty';
            empty.textContent = offerings.length ? 'Produk tidak ditemukan.' : 'Belum ada product offering aktif.';
            productGrid.append(empty);
            return;
        }
        filtered.forEach((offering) => {
            const card = document.createElement('button');
            card.type = 'button';
            card.className = `pos-product${String(selectedOffering?.id) === String(offering.id) ? ' selected' : ''}`;
            card.setAttribute('aria-pressed', String(String(selectedOffering?.id) === String(offering.id)));
            const icon = document.createElement('span'); icon.className = 'pos-product-icon';
            icon.innerHTML = '<i class="fa-solid fa-box"></i>';
            const name = document.createElement('span'); name.className = 'pos-product-name'; name.textContent = offering.label;
            const email = document.createElement('span'); email.className = 'pos-product-email';
            const emailIcon = document.createElement('i'); emailIcon.className = 'fa-regular fa-envelope';
            const emailText = document.createElement('span'); emailText.textContent = offering.email || 'Email belum tersedia';
            email.append(emailIcon, emailText);
            const duration = document.createElement('span'); duration.className = 'pos-product-duration'; duration.textContent = `${offering.duration_days ?? 30} hari langganan`;
            const quota = document.createElement('span'); quota.className = 'pos-product-quota'; quota.textContent = `Sisa kuota ${offering.quota} / ${offering.max_users}`;
            const price = document.createElement('span'); price.className = 'pos-product-price'; price.textContent = money(offering.price);
            card.append(icon, name, email, duration, quota, price);
            card.addEventListener('click', () => chooseOffering(offering));
            productGrid.append(card);
        });
    };

    let offeringsSearchTimer;
    const loadOfferings = async (query = '') => {
        try {
            const url = new URL(offeringsUrl, window.location.origin);
            if (query) url.searchParams.set('q', query);
            const payload = await fetchJson(url);
            offerings = payload.data || [];
            renderProducts();
            if (!offerings.length) showAlert('Belum ada product offering aktif. Aktifkan atau buat offering terlebih dahulu.');
        } catch (error) {
            productGrid.innerHTML = '<div class="pos-empty">Gagal memuat produk.</div>';
            showAlert(error.message);
        }
    };

    const loadUsers = async (page = 1, append = false) => {
        const requestId = ++usersRequestId;
        const url = new URL(usersUrl, window.location.origin);
        url.searchParams.set('page', page);
        if (userSearch.value.trim()) url.searchParams.set('q', userSearch.value.trim());
        if (!append) {
            userSelect.replaceChildren();
            userSelect.add(new Option('Pilih client', ''));
        }
        try {
            const payload = await fetchJson(url);
            if (requestId !== usersRequestId) return;
            (payload.data || []).forEach(user => userSelect.add(new Option(`${user.name} — ${user.email}`, user.id)));
            userPage = payload.meta?.current_page ?? page;
            userLastPage = payload.meta?.last_page ?? page;
            userResultCount.textContent = `${payload.meta?.total ?? payload.data?.length ?? 0} client ditemukan`;
            loadMoreUsers.classList.toggle('d-none', userPage >= userLastPage);
            if (!payload.data?.length && !append) userSelect.add(new Option('Client tidak ditemukan', ''));
        } catch (error) {
            if (requestId === usersRequestId) showAlert(error.message);
        }
    };

    const updateCustomerMode = () => {
        const existing = document.getElementById('customer-existing').checked;
        existingCustomerFields.classList.toggle('d-none', !existing);
        newCustomerFields.classList.toggle('d-none', existing);
        userSelect.required = existing;
        document.getElementById('customer-name').required = !existing;
        document.getElementById('customer-email').required = !existing;
    };
    document.querySelectorAll('input[name="customer_type"]').forEach(input => input.addEventListener('change', updateCustomerMode));
    productSearch.addEventListener('input', () => { clearTimeout(offeringsSearchTimer); offeringsSearchTimer = setTimeout(() => loadOfferings(productSearch.value.trim()), 250); });
    userSearch.addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => loadUsers(1), 250); });
    loadMoreUsers.addEventListener('click', () => loadUsers(userPage + 1, true));
    durationInput.addEventListener('input', updateSummary);
    amountInput.addEventListener('input', updateSummary);

    form.addEventListener('submit', async event => {
        event.preventDefault();
        clearAlert();
        if (!selectedOffering) { showAlert('Pilih produk dari katalog terlebih dahulu.'); return; }
        if (!form.reportValidity()) return;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Menyimpan transaksi...';
        const data = Object.fromEntries(new FormData(form).entries());
        delete data._token;
        data.customer_type = document.querySelector('input[name="customer_type"]:checked').value;
        try {
            const payload = await fetchJson(storeUrl, { method: 'POST', body: JSON.stringify(data) });
            const transaction = payload.data.transaction;
            let message = `${payload.message}\nNo. ${transaction.number} | ${transaction.customer} | ${transaction.product} | ${money(transaction.amount)} | Aktif sampai ${transaction.expires_at}`;
            if (payload.data.temporary_password) message += `\nLogin client baru: ${transaction.email}\nPassword sementara: ${payload.data.temporary_password}\nCatat password ini sekarang.`;
            showAlert(message, 'success');
            window.open(transaction.receipt_url, '_blank');
            form.reset();
            document.getElementById('customer-existing').checked = true;
            updateCustomerMode();
            selectedOffering = null;
            offeringInput.value = '';
            productSearch.value = '';
            renderProducts();
            updateSummary();
            await loadUsers(1);
            await loadOfferings();
        } catch (error) {
            showAlert(error.message);
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fa-solid fa-check-circle me-2"></i>Proses Transaksi';
        }
    });

    updateCustomerMode();
    updateSummary();
    loadUsers();
    loadOfferings();
})();
</script>
@endsection
