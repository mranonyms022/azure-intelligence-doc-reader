<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice DMS — @yield('title', 'Dashboard')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-w: 240px;
            --topbar-h:  56px;
            --navy:  #1B3A5C;
            --teal:  #0D6E56;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; overflow-x: hidden; }
        body { background: #f4f6f9; font-size: .9rem; }

        /* ══ SIDEBAR ══ */
        #sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--navy);
            z-index: 1040;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
            transition: transform .28s cubic-bezier(.4,0,.2,1);
        }

        @media (max-width: 991.98px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.sidebar-open { transform: translateX(0); }
        }

        .sidebar-brand {
            padding: 1rem 1.1rem;
            border-bottom: 1px solid rgba(255,255,255,.1);
            flex-shrink: 0;
        }
        .sidebar-brand-title {
            color: #fff; font-weight: 700;
            font-size: .95rem; letter-spacing: .4px; white-space: nowrap;
        }
        .sidebar-brand-sub { color: rgba(255,255,255,.4); font-size: .67rem; }

        .sidebar-nav { padding: .5rem 0 1.5rem; flex: 1; }

        .sidebar-section {
            color: rgba(255,255,255,.3);
            font-size: .63rem; text-transform: uppercase;
            letter-spacing: 1px; padding: .85rem 1.1rem .2rem;
        }

        .sidebar-link {
            display: flex; align-items: center; gap: .55rem;
            color: rgba(255,255,255,.7);
            padding: .56rem 1.1rem;
            margin: 1px .55rem;
            border-radius: 7px;
            font-size: .83rem;
            text-decoration: none;
            transition: background .14s, color .14s;
            border: none; background: transparent;
            width: calc(100% - 1.1rem);
            cursor: pointer; text-align: left;
        }
        .sidebar-link:hover,
        .sidebar-link.active { background: rgba(255,255,255,.12); color: #fff; }
        .sidebar-link i { font-size: .95rem; flex-shrink: 0; }

        /* ══ OVERLAY ══ */
        #sidebar-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.45); z-index: 1039;
        }
        #sidebar-overlay.show { display: block; }

        /* ══ MAIN WRAP ══ */
        #main-wrap {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex; flex-direction: column;
            transition: margin-left .28s cubic-bezier(.4,0,.2,1);
        }
        @media (max-width: 991.98px) { #main-wrap { margin-left: 0; } }

        /* ══ TOPBAR ══ */
        #topbar {
            height: var(--topbar-h);
            background: #fff;
            border-bottom: 1px solid #e8ecf0;
            padding: 0 1rem;
            position: sticky; top: 0; z-index: 1030;
            display: flex; align-items: center;
            justify-content: space-between; gap: .5rem;
            flex-shrink: 0;
        }
        .topbar-left  { display: flex; align-items: center; gap: .6rem; min-width: 0; overflow: hidden; }
        .topbar-right { display: flex; align-items: center; gap: .5rem; flex-shrink: 0; }

        @media (max-width: 479px) {
            .topbar-breadcrumb { display: none !important; }
            .review-text { display: none; }
        }

        /* ══ PAGE CONTENT ══ */
        #page-content { flex: 1; padding: 1.1rem .85rem; }
        @media (min-width: 576px)  { #page-content { padding: 1.4rem 1.1rem; } }
        @media (min-width: 768px)  { #page-content { padding: 1.6rem 1.4rem; } }
        @media (min-width: 992px)  { #page-content { padding: 1.75rem 1.5rem; } }

        /* ══ STAT CARDS ══ */
        .stat-card {
            border: none; border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
            transition: transform .15s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-icon {
            width: 40px; height: 40px; border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.05rem; flex-shrink: 0;
        }
        .stat-value { font-size: 1.45rem; font-weight: 700; line-height: 1.1; }
        .stat-label { font-size: .73rem; color: #6c757d; margin-top: .15rem; }

        /* ══ TABLE CARD ══ */
        .table-card {
            border: none; border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08); overflow: hidden;
        }
        .table thead th {
            background: #f8f9fa; font-size: .71rem;
            text-transform: uppercase; letter-spacing: .5px;
            color: #6c757d; border-bottom: 2px solid #e8ecf0;
            white-space: nowrap; cursor: pointer; user-select: none;
            padding: .6rem .75rem;
        }
        .table thead th:hover { background: #eef0f3; }
        .table tbody tr:hover { background: #f8f9fb; }
        .table td { vertical-align: middle; font-size: .83rem; padding: .58rem .75rem; }

        @media (max-width: 575.98px) {
            .table td, .table th { font-size: .76rem; padding: .48rem .5rem; }
        }

        /* ══ BADGES ══ */
        .lang-badge-ar { background: #fff3cd; color: #856404; }
        .lang-badge-en { background: #d1e7dd; color: #0f5132; }
        .badge-review  { background: #f8d7da; color: #842029; }

        /* ══ CONFIDENCE BAR ══ */
        .conf-bar { height: 5px; border-radius: 3px; background: #e9ecef; }
        .conf-fill { height: 100%; border-radius: 3px; transition: width .3s; }

        /* ══ DETAIL LABELS ══ */
        .detail-label {
            font-size: .7rem; text-transform: uppercase;
            letter-spacing: .5px; color: #6c757d; margin-bottom: .15rem;
        }
        .detail-value { font-size: .9rem; font-weight: 500; color: #212529; }

        /* ══ USER AVATAR ══ */
        .user-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .78rem; font-weight: 700; flex-shrink: 0;
            background: #1B3A5C; color: #fff;
        }

        /* ══ FILTER CARD ══ */
        .filter-card {
            border: none; border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
        }
    </style>

    @livewireStyles
</head>
<body>

{{-- Overlay --}}
<div id="sidebar-overlay" onclick="closeSidebar()"></div>

{{-- ══ SIDEBAR ══ --}}
<nav id="sidebar">
    <div class="sidebar-brand d-flex align-items-center justify-content-between">
        <div>
            <div class="sidebar-brand-title"><i class="bi bi-receipt me-2"></i>Invoice DMS</div>
            <div class="sidebar-brand-sub">Azure Document Intelligence</div>
        </div>
        <button class="btn btn-sm text-white d-lg-none border-0 p-1"
                style="background:transparent;" onclick="closeSidebar()">
            <i class="bi bi-x-lg" style="font-size:1.1rem;"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section">Main</div>

        <a href="{{ route('dashboard') }}"
           class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           onclick="closeSidebarMobile()">
            <i class="bi bi-speedometer2"></i>Dashboard
        </a>

        <a href="{{ route('invoices.index') }}"
           class="sidebar-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}"
           onclick="closeSidebarMobile()">
            <i class="bi bi-file-earmark-text"></i>Invoices
        </a>

        @if(auth()->user()?->isAdmin())
        <div class="sidebar-section">Admin</div>
        <a href="{{ route('admin.users') }}"
           class="sidebar-link {{ request()->routeIs('admin.*') ? 'active' : '' }}"
           onclick="closeSidebarMobile()">
            <i class="bi bi-people"></i>Users
        </a>
        @endif

        <div class="sidebar-section">Account</div>

        <div class="sidebar-link" style="opacity:.65;pointer-events:none;">
            <i class="bi bi-person-circle"></i>
            <span style="font-size:.78rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                {{ auth()->user()->name }}
            </span>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sidebar-link">
                <i class="bi bi-box-arrow-left"></i>Logout
            </button>
        </form>
    </nav>
</nav>

{{-- ══ MAIN WRAP ══ --}}
<div id="main-wrap">

    {{-- ── TOPBAR ── --}}
    <header id="topbar">
        <div class="topbar-left">
            {{-- Hamburger (mobile/tablet) --}}
            <button class="btn btn-sm btn-light d-lg-none"
                    style="width:36px;height:36px;padding:0;flex-shrink:0;"
                    onclick="openSidebar()">
                <i class="bi bi-list" style="font-size:1.15rem;"></i>
            </button>

            <nav class="topbar-breadcrumb" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0" style="font-size:.8rem;">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}" class="text-decoration-none">Home</a>
                    </li>
                    @yield('breadcrumb')
                </ol>
            </nav>
        </div>

        <div class="topbar-right">
            {{-- Review badge --}}
            @php $reviewCount = \App\Models\Invoice::where('needs_review', true)->count(); @endphp
            @if($reviewCount > 0)
            <a href="{{ route('invoices.index', ['statusFilter' => 'review']) }}"
               class="btn btn-sm btn-warning d-flex align-items-center gap-1 px-2">
                <i class="bi bi-exclamation-triangle" style="font-size:.8rem;"></i>
                <span class="review-text" style="font-size:.78rem;">{{ $reviewCount }}</span>
            </a>
            @endif

            {{-- Avatar --}}
            <div class="user-avatar">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>

            {{-- Name — hidden on mobile --}}
            <div class="d-none d-sm-block lh-sm">
                <div class="fw-semibold" style="font-size:.8rem;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    {{ auth()->user()->name }}
                </div>
                <div class="text-muted" style="font-size:.68rem;">{{ ucfirst(auth()->user()->role) }}</div>
            </div>
        </div>
    </header>

    {{-- ── PAGE CONTENT ── --}}
    <main id="page-content">

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 mb-3 small" role="alert">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size:.75rem;"></button>
        </div>
        @endif

        {{ $slot }}

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@livewireScripts

<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    function openSidebar() {
        sidebar.classList.add('sidebar-open');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar.classList.remove('sidebar-open');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }
    function closeSidebarMobile() {
        if (window.innerWidth < 992) closeSidebar();
    }
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            sidebar.classList.remove('sidebar-open');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    });
</script>
</body>
</html>
