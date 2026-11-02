<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} | {{ $appSettings->get('app_name', 'CassieRess') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --ink: #181c32;
            --muted: #7e8299;
            --line: #eff0f6;
            --canvas: #f7f8fc;
            --primary: #3e67f6;
            --sidebar: 250px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--canvas);
            color: #3f4254;
            font-family: Inter, system-ui, sans-serif;
            font-size: .9rem;
        }

        a {
            text-decoration: none;
        }

        .app-shell {
            min-height: 100vh;
        }

        .app-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1030;
            width: var(--sidebar);
            overflow-y: auto;
            padding: 27px 18px;
            background: #fff;
            border-right: 1px solid var(--line);
            transition: transform .22s ease;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 0 10px;
            margin-bottom: 42px;
            color: var(--ink);
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: -.04em;
        }

        .brand-mark {
            display: grid;
            width: 35px;
            height: 35px;
            place-items: center;
            border-radius: 10px;
            color: #fff;
            background: var(--primary);
            font-size: .9rem;
        }

        .menu-title {
            display: flex;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            padding: 0 12px;
            margin: 25px 0 9px;
            border: 0;
            background: none;
            color: #a1a5b7;
            font-size: .69rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-align: left;
            text-transform: uppercase;
        }

        button.menu-title {
            cursor: pointer;
            transition: color .15s ease;
        }

        button.menu-title:hover {
            color: var(--primary);
        }

        button.menu-title:focus-visible {
            outline: 0;
            border-radius: 6px;
            box-shadow: 0 0 0 3px rgba(62, 103, 246, .18);
        }

        .menu-arrow {
            font-size: .62rem;
            transition: transform .2s ease;
        }

        .menu-group.collapsed .menu-arrow {
            transform: rotate(-90deg);
        }

        .menu-items {
            display: grid;
            grid-template-rows: 1fr;
            transition: grid-template-rows .2s ease;
        }

        .menu-items>div {
            min-height: 0;
            overflow: hidden;
            transition: visibility .2s;
        }

        .menu-group.collapsed .menu-items {
            grid-template-rows: 0fr;
        }

        .menu-group.collapsed .menu-items>div {
            visibility: hidden;
        }

        .menu-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 12px;
            margin: 3px 0;
            border-radius: 8px;
            color: #7e8299;
            font-size: .87rem;
            font-weight: 600;
            transition: .15s ease;
        }

        .menu-link i {
            width: 17px;
            color: #a1a5b7;
            text-align: center;
        }

        .menu-link:hover {
            color: var(--primary);
            background: #f4f6ff;
        }

        .menu-link.active {
            color: var(--primary);
            background: #eef2ff;
        }

        .menu-link.active i {
            color: var(--primary);
        }

        .menu-link.sub {
            padding-left: 43px;
            font-size: .82rem;
        }

        .app-main {
            min-width: 0;
            min-height: 100vh;
            margin-left: var(--sidebar);
            transition: margin-left .22s ease;
        }

        .sidebar-toggle {
            display: inline-grid;
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            place-items: center;
            border: 1px solid #e8eaf0;
            border-radius: 8px;
            color: #7e8299;
            background: #fff;
        }

        .sidebar-toggle:hover {
            color: var(--primary);
            background: #f7f8ff;
        }

        .topbar-start {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1025;
            display: none;
            background: rgba(24, 28, 50, .35);
        }

        .sidebar-backdrop.show {
            display: block;
        }

        body.sidebar-collapsed .app-sidebar {
            transform: translateX(-100%);
        }

        body.sidebar-collapsed .app-main {
            margin-left: 0;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 1020;
            display: flex;
            min-height: 76px;
            align-items: center;
            justify-content: space-between;
            padding: 0 38px;
            background: rgba(255, 255, 255, .94);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(10px);
        }

        .topbar-kicker {
            margin-bottom: 2px;
            color: var(--muted);
            font-size: .72rem;
        }

        .topbar h1 {
            margin: 0;
            color: var(--ink);
            font-size: 1.05rem;
            font-weight: 700;
        }

        .user-chip {
            display: flex;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            border: 1px solid #dfe5ff;
            border-radius: 50%;
            color: var(--primary);
            background: #eef2ff;
            font-size: .78rem;
            font-weight: 800;
        }

        .topbar-account {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notification-wrap { position: relative; }
        .notification-button { position: relative; display: grid; width: 38px; height: 38px; place-items: center; border: 1px solid #e4e8f1; border-radius: 50%; color: #7e8299; background: #fff; }
        .notification-button:hover { color: var(--primary); border-color: #d8ddf2; background: #f7f8ff; }
        .notification-count { position: absolute; top: -3px; right: -3px; min-width: 17px; height: 17px; padding: 0 4px; border: 2px solid #fff; border-radius: 99px; color: #fff; background: #f1416c; font-size: .62rem; font-weight: 800; line-height: 13px; text-align: center; }
        .notification-menu { width: 340px; padding: 0; border: 0; border-radius: 14px; box-shadow: 0 16px 40px rgba(24, 28, 50, .14); overflow: hidden; }
        .notification-heading { padding: 15px 17px; border-bottom: 1px solid var(--line); color: var(--ink); font-weight: 700; }
        .notification-item { display: block; padding: 12px 17px; border-bottom: 1px solid #f0f2f7; color: #3f4254; }
        .notification-item:hover { background: #f7f9ff; }
        .notification-item strong { display: block; color: var(--ink); font-size: .82rem; }
        .notification-item small { color: var(--muted); }
        .notification-empty { padding: 22px 17px; color: var(--muted); text-align: center; }
        @media (max-width: 575px) { .notification-menu { position: fixed !important; right: 12px !important; left: 12px !important; width: auto; } }

        .logout-button {
            padding: 8px 11px;
            border: 1px solid var(--line);
            border-radius: 7px;
            color: #7e8299;
            background: #fff;
            font-size: .78rem;
            font-weight: 600;
        }

        .logout-button:hover {
            border-color: #d8ddf2;
            color: var(--primary);
            background: #f7f8ff;
        }

        .page-content {
            max-width: 1600px;
            padding: 34px 38px 54px;
            margin: auto;
        }

        .page-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 26px;
        }

        .page-heading h2 {
            margin: 0 0 6px;
            color: var(--ink);
            font-size: 1.45rem;
            font-weight: 700;
            letter-spacing: -.04em;
        }

        .page-heading p {
            margin: 0;
            color: var(--muted);
        }

        .card {
            overflow: hidden;
            border: 0;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 8px 26px rgba(24, 28, 50, .055);
            transition: box-shadow .2s ease, transform .2s ease;
        }

        .card:hover {
            box-shadow: 0 13px 32px rgba(24, 28, 50, .09);
        }

        .card-header {
            padding: 20px 22px;
            border-bottom: 1px solid #edf0f7;
            background: linear-gradient(180deg, #fff, #fcfdff);
        }

        .card-title {
            margin: 0 0 4px;
            color: var(--ink);
            font-size: .98rem;
            font-weight: 700;
        }

        .text-muted {
            color: var(--muted) !important;
        }

        .form-control, .form-select {
            border-color: #e4e8f1;
            border-radius: 9px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(62, 103, 246, .12);
        }

        .table-sort { color: inherit; }
        .table-sort:hover { color: var(--primary); }

        .table thead th {
            color: #8790a8;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .table tbody tr {
            transition: background .15s ease;
        }

        .table tbody tr:hover {
            background: #fbfcff;
        }

        .btn {
            border-radius: 9px;
            font-size: .82rem;
            font-weight: 600;
        }

        .btn-primary {
            border-color: var(--primary);
            background: var(--primary);
        }

        .btn-primary:hover {
            border-color: #2f55df;
            background: #2f55df;
        }

        .btn-light {
            border: 1px solid #e8eaf0;
            background: #fff;
            color: #7e8299;
        }

        .btn-light:hover {
            border-color: #d8ddf2;
            color: var(--primary);
            background: #f7f8ff;
        }

        .form-control,
        .form-select {
            min-height: 41px;
            border-color: #e5e7ee;
            border-radius: 7px;
            font-size: .84rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #aabaff;
            box-shadow: 0 0 0 .2rem rgba(62, 103, 246, .1);
        }

        .form-label {
            margin-bottom: 7px;
            color: #5e6278;
            font-size: .78rem;
            font-weight: 600;
        }

        .table {
            color: #5e6278;
            font-size: .83rem;
        }

        .table thead th {
            padding: 13px 14px;
            border-bottom: 1px solid var(--line);
            color: #a1a5b7;
            background: #fbfbfd;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .035em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 15px 14px;
            border-color: #f1f2f6;
        }

        .badge-soft-success,
        .badge-soft-danger,
        .badge-soft-primary {
            padding: 6px 9px;
            border-radius: 6px;
            font-size: .71rem;
            font-weight: 700;
        }

        .badge-soft-success {
            color: #20a66a;
            background: #e9fbf2;
        }

        .badge-soft-danger {
            color: #dc4266;
            background: #fff0f3;
        }

        .badge-soft-primary {
            color: var(--primary);
            background: #eef2ff;
        }

        .metronic-pagination {
            display: flex;
            justify-content: flex-end;
        }

        .metronic-pagination .pagination {
            gap: 5px;
            margin: 0;
        }

        .metronic-pagination .page-link {
            display: grid;
            min-width: 36px;
            height: 36px;
            place-items: center;
            padding: 0 10px;
            border: 0;
            border-radius: 7px !important;
            color: #7e8299;
            background: transparent;
            font-size: .8rem;
            font-weight: 600;
            transition: .15s ease;
        }

        .metronic-pagination .page-link:hover {
            color: var(--primary);
            background: #eef2ff;
        }

        .metronic-pagination .page-item.active .page-link {
            color: #fff;
            background: var(--primary);
            box-shadow: 0 3px 8px rgba(62, 103, 246, .2);
        }

        .metronic-pagination .page-item.disabled .page-link {
            color: #c4c7d2;
            background: transparent;
        }

        .metronic-pagination .page-link:focus-visible {
            outline: 0;
            box-shadow: 0 0 0 3px rgba(62, 103, 246, .18);
        }

        .metric-card {
            display: flex;
            min-height: 116px;
            align-items: center;
            gap: 15px;
            padding: 20px;
        }

        .metric-icon {
            display: grid;
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            place-items: center;
            border-radius: 10px;
            color: var(--primary);
            background: #eef2ff;
            font-size: 1rem;
        }

        .metric-label {
            color: var(--muted);
            font-size: .79rem;
            font-weight: 500;
        }

        .metric-value {
            margin-top: 4px;
            color: var(--ink);
            font-size: 1.45rem;
            font-weight: 700;
            letter-spacing: -.04em;
        }

        .modal-content {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 15px 60px rgba(24, 28, 50, .15);
        }

        .modal-header,
        .modal-footer {
            border-color: var(--line);
        }

        .modal-title {
            color: var(--ink);
            font-size: 1rem;
            font-weight: 700;
        }

        @media (max-width: 991.98px) {
            :root {
                --sidebar: 220px;
            }

            .topbar {
                padding: 0 24px;
            }

            .page-content {
                padding: 27px 24px 42px;
            }
        }

        @media (max-width: 767.98px) {
            :root {
                --sidebar: 270px;
            }

            .app-sidebar {
                padding: 22px 16px;
                box-shadow: 12px 0 30px rgba(24, 28, 50, .08);
            }

            .brand {
                padding: 0 10px;
                margin-bottom: 35px;
            }

            .brand-name,
            .menu-label {
                display: initial;
            }

            .menu-link {
                justify-content: flex-start;
                padding: 11px 12px;
            }

            .menu-link i {
                width: 17px;
                font-size: .9rem;
            }

            .app-main {
                margin-left: 0;
            }

            body:not(.sidebar-open) .app-sidebar {
                transform: translateX(-100%);
            }

            body.sidebar-open {
                overflow: hidden;
            }

            .topbar {
                min-height: 66px;
                padding: 0 17px;
            }

            .page-content {
                padding: 23px 15px 35px;
            }

            .page-heading {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 575.98px) {
            .metronic-pagination {
                max-width: 100%;
                justify-content: center;
                overflow-x: auto;
            }

            .metronic-pagination .pagination {
                flex-wrap: nowrap;
            }

            .metronic-pagination .page-link {
                min-width: 33px;
                height: 33px;
                padding: 0 8px;
            }
        }
    </style>
</head>

<body>
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
    <div class="app-shell">
        <aside class="app-sidebar" id="adminSidebar" aria-label="Navigasi admin">
            <a class="brand" href="{{ route('admin.catalog.index') }}"><span class="brand-mark"><i
                        class="fa-solid fa-layer-group"></i></span><span
                    class="brand-name">{{ $appSettings->get('app_name', 'CASSIERESS') }}</span></a>

            <div class="menu-group" data-group="workspace-cashier">
                <button type="button" class="menu-title" aria-expanded="true" aria-controls="group-workspace-cashier">
                    <span>Workspace Cashier</span><i class="fa-solid fa-chevron-down menu-arrow"></i>
                </button>
                <div class="menu-items" id="group-workspace-cashier">
                    <div>
                        <a href="{{ route('admin.catalog.index') }}"
                            class="menu-link {{ request()->routeIs('admin.catalog.index') ? 'active' : '' }}"><i
                                class="fa-solid fa-chart-pie"></i><span class="menu-label">Dashboard</span></a>
                        <a href="{{ route('admin.cashier.index') }}"
                            class="menu-link {{ request()->routeIs('admin.cashier.index') ? 'active' : '' }}"><i
                                class="fa-solid fa-cash-register"></i><span class="menu-label">Cashier</span></a>
                    </div>
                </div>
            </div>

            <div class="menu-group" data-group="catalog-management">
                <button type="button" class="menu-title" aria-expanded="true" aria-controls="group-catalog-management">
                    <span>Catalog management</span><i class="fa-solid fa-chevron-down menu-arrow"></i>
                </button>
                <div class="menu-items" id="group-catalog-management">
                    <div>
                        <a href="{{ route('admin.products.index') }}"
                            class="menu-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"><i
                                class="fa-solid fa-box"></i><span class="menu-label">Products</span></a>
                        <a href="{{ route('admin.offerings.index') }}"
                            class="menu-link {{ request()->routeIs('admin.offerings.*') ? 'active' : '' }}"><i
                                class="fa-solid fa-tags"></i><span class="menu-label">Product offerings</span></a>

                    </div>
                </div>
            </div>

            <div class="menu-group" data-group="workspace-reports">
                <button type="button" class="menu-title" aria-expanded="true" aria-controls="group-workspace-reports">
                    <span>Workspace Reports</span><i class="fa-solid fa-chevron-down menu-arrow"></i>
                </button>
                <div class="menu-items" id="group-workspace-reports">
                    <div>
                        <a href="{{ route('admin.transactions.index') }}"
                            class="menu-link {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}"><i
                                class="fa-solid fa-receipt"></i><span class="menu-label">Transactions</span></a>
                        <a href="{{ route('admin.reports.index') }}" class="menu-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i><span
                            class="menu-label">Reports</span></a>
                    </div>
                </div>
            </div>

            <div class="menu-group" data-group="master">
                <button type="button" class="menu-title" aria-expanded="true" aria-controls="group-master">
                    <span>Master</span><i class="fa-solid fa-chevron-down menu-arrow"></i>
                </button>
                <div class="menu-items" id="group-master">
                    <div>
                        <a href="{{ route('admin.users.index') }}"
                            class="menu-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i
                                class="fa-solid fa-user-shield"></i><span class="menu-label">Users &amp; roles</span></a>
                        <a href="{{ route('admin.account-types.index') }}"
                            class="menu-link {{ request()->routeIs('admin.account-types.*') ? 'active' : '' }}"><i
                                class="fa-solid fa-users"></i><span class="menu-label">Account types</span></a>
                    </div>
                </div>
            </div>

            <div class="menu-group" data-group="system">
                <button type="button" class="menu-title" aria-expanded="true" aria-controls="group-system">
                    <span>System</span><i class="fa-solid fa-chevron-down menu-arrow"></i>
                </button>
                <div class="menu-items" id="group-system">
                    <div>
                        <a href="{{ route('admin.settings.index') }}"
                            class="menu-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"><i
                                class="fa-solid fa-gear"></i><span class="menu-label">Settings</span></a>
                    </div>
                </div>
            </div>
        </aside>

        {{-- Dijalankan tepat setelah sidebar agar state group tidak "berkedip" saat halaman dimuat --}}
        <script>
            (() => {
                const storageKey = 'admin-sidebar-groups';
                const groups = document.querySelectorAll('.menu-group');

                const readState = () => {
                    try { return JSON.parse(localStorage.getItem(storageKey)) || {}; } catch { return {}; }
                };
                const saveState = (state) => {
                    try { localStorage.setItem(storageKey, JSON.stringify(state)); } catch { }
                };
                const setCollapsed = (group, collapsed) => {
                    group.classList.toggle('collapsed', collapsed);
                    group.querySelector('.menu-title').setAttribute('aria-expanded', String(!collapsed));
                };

                const state = readState();
                groups.forEach((group) => {
                    const hasActive = group.querySelector('.menu-link.active');
                    // group yang berisi menu aktif selalu terbuka saat halaman dimuat
                    setCollapsed(group, !hasActive && state[group.dataset.group] === true);

                    group.querySelector('.menu-title').addEventListener('click', () => {
                        const collapsed = !group.classList.contains('collapsed');
                        setCollapsed(group, collapsed);
                        const current = readState();
                        current[group.dataset.group] = collapsed;
                        saveState(current);
                    });
                });
            })();
        </script>

        <main class="app-main">
            <header class="topbar">
                <div class="topbar-start">
                    <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Buka navigasi"
                        aria-controls="adminSidebar" aria-expanded="true"><i class="fa-solid fa-bars"></i></button>
                    <div>
                        <div class="topbar-kicker">CassieRess / Admin</div>
                        <h1>{{ $heading ?? 'Catalog' }}</h1>
                    </div>
                </div>
                 <div class="topbar-account">
                     <div class="dropdown notification-wrap">
                         <button class="notification-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi transaksi kedaluwarsa"><i class="fa-regular fa-bell"></i>@if($expiringTransactions->isNotEmpty())<span class="notification-count">{{ $expiringTransactions->count() }}</span>@endif</button>
                         <div class="dropdown-menu dropdown-menu-end notification-menu">
                             <div class="notification-heading"><i class="fa-regular fa-clock me-2 text-warning"></i>Segera expired <small class="text-muted">(5 hari)</small></div>
                             @forelse($expiringTransactions as $expiring)
                                 <a class="notification-item" href="{{ route('admin.transactions.index', ['customer' => $expiring->user_id]) }}"><strong>{{ $expiring->user?->name ?: 'Customer' }} · {{ $expiring->product_name }}</strong><small>Expired {{ $expiring->expires_at->format('d M Y') }} · {{ $formatRemainingTime($expiring->expires_at) }}</small></a>
                             @empty
                                 <div class="notification-empty"><i class="fa-regular fa-circle-check d-block mb-2 text-success"></i>Tidak ada transaksi yang akan expired.</div>
                             @endforelse
                         </div>
                     </div>
                     <div class="user-chip" title="{{ auth()->user()->name }}">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button"><i
                                class="fa-solid fa-arrow-right-from-bracket me-1"></i><span
                                class="logout-label">Keluar</span></button></form>
                </div>
            </header>
            <div class="page-content">@yield('content')</div>
        </main>
        @if(session('success') || session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', () => Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: @json(session('success') ? 'success' : 'error'),
                    title: @json(session('success') ?: session('error')),
                    showConfirmButton: false,
                    timer: 3200,
                    timerProgressBar: true,
                }));
            </script>
        @endif
        <script>
            document.addEventListener('submit', event => {
                const form = event.target;
                if (!form.matches('[data-confirm]')) return;
                event.preventDefault();
                Swal.fire({
                    title: form.dataset.confirmTitle || 'Konfirmasi tindakan',
                    text: form.dataset.confirm,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, lanjutkan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                }).then(result => { if (result.isConfirmed) form.submit(); });
            });
        </script>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (() => {
            const toggle = document.getElementById('sidebarToggle');
            const backdrop = document.getElementById('sidebarBackdrop');
            const mobile = window.matchMedia('(max-width: 767.98px)');

            const setOpen = (open) => {
                document.body.classList.toggle('sidebar-open', open && mobile.matches);
                document.body.classList.toggle('sidebar-collapsed', !open && !mobile.matches);
                backdrop.classList.toggle('show', open && mobile.matches);
                toggle.setAttribute('aria-expanded', String(open));
                toggle.setAttribute('aria-label', open ? 'Tutup navigasi' : 'Buka navigasi');
            };

            setOpen(!mobile.matches);
            toggle.addEventListener('click', () => {
                const open = mobile.matches
                    ? !document.body.classList.contains('sidebar-open')
                    : document.body.classList.contains('sidebar-collapsed');
                setOpen(open);
            });
            backdrop.addEventListener('click', () => setOpen(false));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && mobile.matches) setOpen(false);
            });
            mobile.addEventListener('change', () => setOpen(!mobile.matches));
            document.querySelectorAll('.app-sidebar a[href*="#"]').forEach((link) => {
                link.addEventListener('click', () => {
                    if (mobile.matches) setOpen(false);
                });
            });
        })();
    </script>
</body>

</html>
