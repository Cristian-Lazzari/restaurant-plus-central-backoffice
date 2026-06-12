<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report 📊</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon-192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon-180.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ─── Design tokens ─── */
        :root {
            color-scheme: light;
            --bg: #f4f6fa;
            --surface: #ffffff;
            --surface-2: #f8fafc;
            --ink: #111827;
            --ink-2: #374151;
            --muted: #667085;
            --border: #d8dee8;
            --border-soft: #edf0f5;
            --brand: #0eb792;
            --brand-hover: #0ca07f;
            --brand-soft: #e6faf7;
            --brand-muted: #7ae8d2;
            --green: #027a48;
            --green-soft: #ecfdf3;
            --green-border: #abefc6;
            --amber: #b54708;
            --amber-soft: #fffaeb;
            --amber-border: #fedf89;
            --red: #b42318;
            --red-soft: #fef3f2;
            --red-border: #fecdca;
            --shadow-sm: 0 1px 3px rgba(17,24,39,0.06), 0 1px 2px rgba(17,24,39,0.04);
            --shadow: 0 4px 16px rgba(17,24,39,0.07), 0 1px 3px rgba(17,24,39,0.05);
            --sidebar-w: 240px;
            --topbar-h: 56px;
            --radius: 8px;
            --radius-sm: 6px;
        }

        /* ─── Reset ─── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            color: var(--ink);
            background: var(--bg);
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        a { color: var(--brand); text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* ─── Sidebar ─── */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: #090333;
            display: flex;
            flex-direction: column;
            z-index: 50;
            overflow-y: auto;
            overflow-x: hidden;
            transition: transform 0.25s ease;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 20px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            flex-shrink: 0;
            text-decoration: none;
        }
        .sidebar-brand:hover { text-decoration: none; opacity: 0.85; }
        .sidebar-brand img {
            height: 32px;
            width: auto;
            max-width: 192px;
            display: block;
            object-fit: contain;
        }

        .brand-icon {
            width: 26px;
            height: 26px;
            border-radius: 5px;
            background: #0eb792;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #fff;
        }

        .sidebar-nav {
            flex: 1;
            padding: 10px 10px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: var(--radius-sm);
            color: rgba(255,255,255,0.65);
            font-size: 13.5px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }
        .nav-item svg { flex-shrink: 0; opacity: 0.8; }
        .nav-item:hover {
            background: rgba(255,255,255,0.07);
            color: #fff;
            text-decoration: none;
        }
        .nav-item:hover svg { opacity: 1; }
        .nav-item.active {
            background: rgba(14,183,146,0.18);
            color: #fff;
        }
        .nav-item.active svg { opacity: 1; }

        .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.07);
            margin: 8px 10px;
        }

        .sidebar-footer {
            padding: 10px 10px 16px;
            flex-shrink: 0;
            border-top: 1px solid rgba(255,255,255,0.07);
        }

        .sidebar-footer form { width: 100%; }

        .sidebar-footer .nav-item {
            width: 100%;
            background: none;
            border: none;
            cursor: pointer;
            font-family: inherit;
            text-align: left;
        }

        /* ─── Topbar (mobile only) ─── */
        .topbar {
            display: none;
            position: sticky;
            top: 0;
            z-index: 40;
            height: var(--topbar-h);
            background: #090333;
            padding: 0 14px;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
        }
        .topbar-brand:hover { text-decoration: none; color: #fff; }

        .hamburger {
            width: 40px;
            height: 40px;
            border: none;
            background: rgba(255,255,255,0.09);
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #fff;
            flex-shrink: 0;
        }
        .hamburger:hover { background: rgba(255,255,255,0.16); }

        /* ─── Sidebar backdrop ─── */
        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 45;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }
        .sidebar-backdrop.visible {
            opacity: 1;
            pointer-events: auto;
        }

        /* ─── Layout ─── */
        .app {
            display: flex;
            min-height: 100vh;
        }

        .main-area {
            flex: 1;
            min-width: 0;
            margin-left: var(--sidebar-w);
            display: flex;
            flex-direction: column;
        }

        .page-content {
            flex: 1;
            max-width: 1200px;
            width: 100%;
            padding: 28px 28px 52px;
        }

        /* ─── Flash messages ─── */
        .flash {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border-radius: var(--radius);
            margin-bottom: 18px;
            border: 1px solid var(--border);
            background: var(--surface);
            font-size: 13.5px;
        }
        .flash svg { flex-shrink: 0; margin-top: 1px; }
        .flash.success {
            border-color: var(--green-border);
            background: var(--green-soft);
            color: var(--green);
        }
        .flash.error {
            border-color: var(--red-border);
            background: var(--red-soft);
            color: var(--red);
        }

        /* ─── Buttons ─── */
        .btn {
            border: 1px solid #c7d0dd;
            background: #fff;
            color: #172033;
            border-radius: var(--radius-sm);
            padding: 8px 13px;
            min-height: 36px;
            font: inherit;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            white-space: nowrap;
            text-decoration: none;
            transition: background 0.12s, border-color 0.12s;
        }
        .btn:hover { background: var(--surface-2); text-decoration: none; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
        .btn svg { width: 15px; height: 15px; flex-shrink: 0; }

        .btn-primary, .btn.primary {
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
        }
        .btn-primary:hover, .btn.primary:hover {
            background: var(--brand-hover);
            border-color: var(--brand-hover);
        }

        .btn-danger, .btn.danger {
            border-color: var(--red-border);
            color: var(--red);
            background: var(--red-soft);
        }
        .btn-danger:hover, .btn.danger:hover {
            background: #fee2e2;
        }

        .btn-icon, .btn.icon-only {
            width: 36px;
            height: 36px;
            padding: 8px;
        }

        .btn-lg {
            padding: 10px 18px;
            min-height: 42px;
            font-size: 14px;
        }

        /* ─── Panel ─── */
        .panel {
            background: var(--surface);
            border: 1px solid var(--border-soft);
            border-radius: var(--radius);
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: var(--shadow-sm);
        }

        /* ─── Badges ─── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: var(--brand-soft);
            color: #066a52;
        }
        .badge-muted, .badge.off { background: #f2f4f7; color: #475467; }
        .badge-green { background: var(--green-soft); color: var(--green); border: 1px solid var(--green-border); }
        .badge-amber { background: var(--amber-soft); color: var(--amber); border: 1px solid var(--amber-border); }
        .badge-red { background: var(--red-soft); color: var(--red); border: 1px solid var(--red-border); }

        /* ─── Typography ─── */
        .page-title { font-size: 26px; font-weight: 780; line-height: 1.2; margin: 0; }
        .page-subtitle { color: var(--muted); font-size: 13.5px; margin-top: 4px; }
        .section-title { font-size: 16px; font-weight: 700; line-height: 1.3; margin: 0; }
        .text-muted, .muted { color: var(--muted); }
        .text-sm { font-size: 12px; }

        /* ─── Grid ─── */
        .grid-4 { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 12px; }
        .grid-auto { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 12px; }

        /* ─── Flex utilities ─── */
        .flex { display: flex; }
        .flex-wrap { flex-wrap: wrap; }
        .items-center { align-items: center; }
        .items-start { align-items: flex-start; }
        .justify-between { justify-content: space-between; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }

        /* ─── Spacing ─── */
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }
        .mb-4 { margin-bottom: 16px; }
        .mb-5 { margin-bottom: 20px; }
        .mb-6 { margin-bottom: 24px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }

        /* ─── Tables ─── */
        .table-wrap {
            overflow-x: auto;
            background: var(--surface);
            border: 1px solid var(--border-soft);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
        }
        table { width: 100%; border-collapse: collapse; }
        .table-wrap table { min-width: 560px; }
        th {
            color: #475467;
            background: #fbfcfe;
            font-size: 11.5px;
            font-weight: 760;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            padding: 11px 14px;
            border-bottom: 1px solid var(--border-soft);
            text-align: left;
            white-space: nowrap;
        }
        td {
            padding: 13px 14px;
            border-bottom: 1px solid var(--border-soft);
            text-align: left;
            vertical-align: top;
            font-size: 13.5px;
        }
        tr:last-child td { border-bottom: 0; }
        tbody tr:hover { background: #fbfcfe; }

        /* ─── Forms ─── */
        .field { margin-bottom: 14px; }
        label {
            display: block;
            font-weight: 600;
            font-size: 13.5px;
            margin-bottom: 6px;
            color: var(--ink-2);
        }
        input[type="text"],
        input[type="url"],
        input[type="password"],
        input[type="email"],
        input[type="date"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 9px 11px;
            font: inherit;
            font-size: 13.5px;
            background: #fff;
            color: var(--ink);
            transition: border-color 0.12s, box-shadow 0.12s;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(14,183,146,0.15);
        }
        input:disabled, textarea:disabled, select:disabled {
            background: var(--surface-2);
            opacity: 0.65;
            cursor: not-allowed;
        }
        textarea { resize: vertical; }

        .check-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        .check-label input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }

        /* ─── Page header ─── */
        .page-header {
            margin-bottom: 24px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 10px;
        }
        .breadcrumb a { color: var(--muted); }
        .breadcrumb a:hover { color: var(--ink); text-decoration: none; }
        .breadcrumb-sep { color: var(--border); }

        /* ─── Metric card ─── */
        .metric-card {
            background: var(--surface);
            border: 1px solid var(--border-soft);
            border-radius: var(--radius);
            padding: 14px 16px;
            box-shadow: var(--shadow-sm);
        }
        .metric-label { color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .metric-value { display: block; font-size: 22px; font-weight: 780; line-height: 1.15; margin-top: 4px; }
        .metric-sub { margin-top: 4px; color: var(--muted); font-size: 12px; }

        /* ─── Alert strip ─── */
        .alert-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border: 1px solid var(--red-border);
            border-radius: var(--radius);
            background: var(--red-soft);
            padding: 13px 14px;
            color: var(--red);
        }
        .alert-strip strong { display: inline-flex; align-items: center; gap: 8px; }

        .alert-strip-amber {
            border-color: var(--amber-border);
            background: var(--amber-soft);
            color: var(--amber);
        }

        /* ─── Empty state ─── */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 40px 20px;
            border: 1px dashed var(--border);
            border-radius: var(--radius);
            background: var(--surface);
            color: var(--muted);
            text-align: center;
        }
        .empty-state svg { opacity: 0.4; }

        /* ─── Icon box ─── */
        .icon-box {
            width: 38px;
            height: 38px;
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: var(--brand-soft);
            color: var(--brand);
        }
        .icon-box-blue { background: var(--brand-soft); color: var(--brand); }
        .icon-box-green { background: var(--green-soft); color: var(--green); }
        .icon-box-amber { background: var(--amber-soft); color: var(--amber); }
        .icon-box-red { background: var(--red-soft); color: var(--red); }

        /* ─── Error list ─── */
        .error-list { margin: 0; padding-left: 18px; color: var(--red); }

        /* ─── Pre ─── */
        pre {
            white-space: pre-wrap;
            word-break: break-word;
            background: #111827;
            color: #f9fafb;
            padding: 16px;
            border-radius: var(--radius);
            overflow-x: auto;
            font-size: 12.5px;
            line-height: 1.6;
        }

        /* ─── Actions ─── */
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        /* ─── Decimali ridotti ─── */
        .dec { font-size: .5em; vertical-align: baseline; opacity: 0.8; }

        /* ─── Section header ─── */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        /* ─── Icon utility ─── */
        .icon { width: 16px; height: 16px; flex-shrink: 0; }

        /* ─── Mobile card-table ─── */
        @media (max-width: 768px) {
            /* Rimuovi bordi del wrapper */
            .card-table .table-wrap {
                border: 0; box-shadow: none; background: transparent;
                overflow-x: visible; border-radius: 0;
            }
            .card-table .table-wrap table { min-width: 0; }

            /* Reset display su tutti gli elementi della tabella */
            .card-table table, .card-table thead,
            .card-table tbody, .card-table tr, .card-table td {
                display: block; width: 100%; min-width: 0;
            }
            .card-table thead { display: none; }

            /* Ogni riga diventa una card a 2 colonne */
            .card-table tr {
                background: var(--surface);
                border: 1px solid var(--border-soft);
                border-radius: var(--radius);
                padding: 12px 10px 6px;
                margin-bottom: 10px;
                box-shadow: var(--shadow-sm);
                display: grid;
                grid-template-columns: 1fr 1fr;
                column-gap: 4px;
                align-items: start;
            }

            /* Ogni cella: etichetta sopra, valore sotto */
            .card-table td {
                border: none;
                padding: 5px 4px 8px;
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 3px;
                font-size: 13px;
                min-width: 0;
                overflow: hidden;
            }
            .card-table td::before {
                content: attr(data-label);
                color: var(--muted);
                font-size: 10px;
                font-weight: 760;
                text-transform: uppercase;
                letter-spacing: 0.03em;
                flex-shrink: 0;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            /* Cella primaria (nome sito, mese, ecc.) → piena larghezza */
            .card-table td.td-primary {
                grid-column: 1 / -1;
                display: block;
                padding: 0 4px 10px;
                border-bottom: 1px solid var(--border-soft);
                margin-bottom: 4px;
            }
            .card-table td.td-primary::before { display: none; }

            /* Cella full-width (risparmio, messaggi lunghi) */
            .card-table td.td-full {
                grid-column: 1 / -1;
            }

            /* Cella azioni → piena larghezza, bottoni 2 colonne */
            .card-table td.td-actions {
                grid-column: 1 / -1;
                display: block;
                padding: 10px 4px 4px;
                border-top: 1px solid var(--border-soft);
                margin-top: 4px;
            }
            .card-table td.td-actions::before { display: none; }
            .card-table td.td-actions .actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }
            .card-table td.td-actions .btn,
            .card-table td.td-actions form { width: 100%; }
            .card-table td.td-actions form .btn { width: 100%; }
        }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            /* iOS Safari zooma la pagina al focus se l'input ha font < 16px:
               su mobile nessun campo deve scendere sotto i 16px.
               !important per vincere anche sugli stili inline delle viste. */
            input[type="text"],
            input[type="url"],
            input[type="password"],
            input[type="email"],
            input[type="date"],
            input[type="number"],
            input[type="search"],
            input[type="tel"],
            input[type="time"],
            textarea,
            select {
                font-size: 16px !important;
            }

            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); box-shadow: 4px 0 24px rgba(0,0,0,0.25); }
            .main-area { margin-left: 0; }
            .topbar { display: flex; }
            .page-content { padding: 16px 14px 36px; }
            .grid-4 { grid-template-columns: repeat(2, 1fr); }
            .grid-3 { grid-template-columns: repeat(2, 1fr); }
            .page-title { font-size: 22px; }
            .alert-strip { flex-direction: column; align-items: flex-start; }
            .hero-copy { max-width: 100%; }
            .actions { gap: 6px; }
        }

        @media (max-width: 480px) {
            .grid-4 { grid-template-columns: 1fr; }
            .grid-3 { grid-template-columns: 1fr; }
            .btn { min-height: 40px; }
        }
    </style>
</head>
<body>
@php
    $logoHorizSrc = file_exists(public_path('images/logo-futureplus.png'))
        ? 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/logo-futureplus.png')))
        : null;
    $faviconSrc = file_exists(public_path('images/favicon.png'))
        ? 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/favicon.png')))
        : null;
@endphp
<div class="app">

    {{-- ─── Sidebar ─── --}}
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="{{ __('Navigazione principale') }}">

        <a class="sidebar-brand" href="{{ route('dashboard') }}">
            @if($logoHorizSrc)
                <img src="{{ $logoHorizSrc }}" alt="Future Plus">
            @else
                <span style="color:#fff;font-weight:700;font-size:15px;letter-spacing:.5px;">FUTURE+</span>
            @endif
        </a>

        @php
            $authUser = auth()->user();
            $isRestaurantUser = $authUser && $authUser->isRestaurant();
        @endphp
        @if(session('backoffice_authenticated'))
            @if($isRestaurantUser)
            <nav class="sidebar-nav">
                @if($authUser->site_id)
                    <a href="{{ route('sites.show', $authUser->site_id) }}"
                       class="nav-item {{ request()->routeIs('sites.show') ? 'active' : '' }}"
                       aria-current="{{ request()->routeIs('sites.show') ? 'page' : 'false' }}">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L8 2.207l6.646 6.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/>
                            <path d="m8 3.293 6 6V13.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5V9.293l6-6Z"/>
                        </svg>
                        {{ __('Il mio ristorante') }}
                    </a>
                    <a href="{{ route('marketing.show', $authUser->site_id) }}"
                       class="nav-item {{ request()->routeIs('marketing.show') ? 'active' : '' }}"
                       aria-current="{{ request()->routeIs('marketing.show') ? 'page' : 'false' }}">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path d="M13 2.5a1.5 1.5 0 0 1 3 0v11a1.5 1.5 0 0 1-3 0v-.214c-2.162-1.241-4.49-1.843-6.912-2.083l.405 2.712A1 1 0 0 1 5.51 15.1h-.548a1 1 0 0 1-.916-.599l-1.85-3.49c-.106-.02-.213-.04-.322-.062A2.01 2.01 0 0 1 0 9V7a2.01 2.01 0 0 1 1.992-2.013 74 74 0 0 0 2.483-.074c3.043-.154 6.148-.85 8.525-2.2V2.5zm1 0v11a.5.5 0 0 0 1 0v-11a.5.5 0 0 0-1 0zm-1 1.35C10.72 4.888 8 5.61 5 5.61H4.5A1.01 1.01 0 0 0 3.5 6.61v2.5c0 .553.448 1 1 1H5c3 0 5.72.722 8 2.25V3.85zm-9.5 6.4v-2.6l-1 .375v1.85l1 .375zm.5 3.065.181-.37-1-.374v.371l.82.373z"/>
                        </svg>
                        {{ __('Marketing') }}
                    </a>
                @endif
            </nav>
            @else
            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}"
                   class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                   aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L8 2.207l6.646 6.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/>
                        <path d="m8 3.293 6 6V13.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5V9.293l6-6Z"/>
                    </svg>
                    {{ __('Dashboard') }}
                </a>

                <a href="{{ route('reports.index') }}"
                   class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                   aria-current="{{ request()->routeIs('reports.*') ? 'page' : 'false' }}">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1V2z"/>
                    </svg>
                    {{ __('Report') }}
                </a>

                <a href="{{ route('todolist.index') }}"
                   class="nav-item {{ request()->routeIs('todolist.*') ? 'active' : '' }}"
                   aria-current="{{ request()->routeIs('todolist.*') ? 'page' : 'false' }}">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M0 0h1v15h15v1H0zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.9l-3.613 4.417a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.681L13.445 4H10.5a.5.5 0 0 1-.5-.5"/>
                    </svg>
                    {{ __('SEO Todolist') }}
                </a>

                <a href="{{ route('pipeline.index') }}"
                   class="nav-item {{ request()->routeIs('pipeline.*') ? 'active' : '' }}"
                   aria-current="{{ request()->routeIs('pipeline.*') ? 'page' : 'false' }}">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M13 2.5a1.5 1.5 0 0 1 3 0v11a1.5 1.5 0 0 1-3 0v-.214c-2.162-1.241-4.49-1.843-6.912-2.083l.405 2.712A1 1 0 0 1 5.51 15.1h-.548a1 1 0 0 1-.916-.599l-1.85-3.49c-.106-.02-.213-.04-.322-.062A2.01 2.01 0 0 1 0 9V7a2.01 2.01 0 0 1 1.992-2.013 74 74 0 0 0 2.483-.074c3.043-.154 6.148-.85 8.525-2.2V2.5zm1 0v11a.5.5 0 0 0 1 0v-11a.5.5 0 0 0-1 0zm-1 1.35C10.72 4.888 8 5.61 5 5.61H4.5A1.01 1.01 0 0 0 3.5 6.61v2.5c0 .553.448 1 1 1H5c3 0 5.72.722 8 2.25V3.85zm-9.5 6.4v-2.6l-1 .375v1.85l1 .375zm.5 3.065.181-.37-1-.374v.371l.82.373z"/>
                    </svg>
                    {{ __('Pipeline CRM') }}
                </a>

                <a href="{{ route('marketing.index') }}"
                   class="nav-item {{ request()->routeIs('marketing.*') ? 'active' : '' }}"
                   aria-current="{{ request()->routeIs('marketing.*') ? 'page' : 'false' }}">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M11.251.068a.5.5 0 0 1 .227.58L9.677 6.5H13a.5.5 0 0 1 .364.843l-8 8.5a.5.5 0 0 1-.842-.49L6.323 9.5H3a.5.5 0 0 1-.364-.843l8-8.5a.5.5 0 0 1 .615-.09z"/>
                    </svg>
                    {{ __('Marketing') }}
                </a>

                <div class="nav-divider"></div>

                <a href="{{ route('users.index') }}"
                   class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}"
                   aria-current="{{ request()->routeIs('users.*') ? 'page' : 'false' }}">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h5.216ZM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                    </svg>
                    {{ __('Utenti') }}
                </a>

                <a href="{{ route('backoffice-settings.edit') }}"
                   class="nav-item {{ request()->routeIs('backoffice-settings.*') ? 'active' : '' }}"
                   aria-current="{{ request()->routeIs('backoffice-settings.*') ? 'page' : 'false' }}">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872l-.1-.34zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"/>
                    </svg>
                    {{ __('Impostazioni') }}
                </a>
            </nav>
            @endif

            <div class="sidebar-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-item" style="width:100%; border:none; background:none; font-family:inherit; cursor:pointer;" aria-label="{{ __('Esci dall\'applicazione') }}">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                            <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                        </svg>
                        {{ __('Logout') }}
                    </button>
                </form>
            </div>
        @else
            <div style="flex:1;"></div>
        @endif

    </aside>

    {{-- ─── Sidebar backdrop (mobile) ─── --}}
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

    {{-- ─── Main area ─── --}}
    <div class="main-area">

        {{-- ─── Topbar (mobile only) ─── --}}
        <div class="topbar" role="banner">
            <a class="topbar-brand" href="{{ route('dashboard') }}">
                @if($faviconSrc)
                    <img src="{{ $faviconSrc }}" alt="Future Plus" style="height:24px;width:auto;display:block;object-fit:contain;">
                @else
                    <span style="color:#fff;font-weight:700;font-size:14px;">FUTURE+</span>
                @endif
            </a>
            <button class="hamburger" id="menuToggle" aria-label="{{ __('Apri menu') }}" aria-expanded="false" aria-controls="sidebar">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                </svg>
            </button>
        </div>

        {{-- ─── Page content ─── --}}
        <main class="page-content" id="main-content">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="flash success" role="alert">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="flash error" role="alert">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="flash error" role="alert">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" style="margin-top: 2px;">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                    </svg>
                    <ul class="error-list">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
(function() {
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var toggle = document.getElementById('menuToggle');
    if (!toggle || !sidebar || !backdrop) return;
    function openSidebar() { sidebar.classList.add('open'); backdrop.classList.add('visible'); toggle.setAttribute('aria-expanded','true'); document.body.style.overflow='hidden'; }
    function closeSidebar() { sidebar.classList.remove('open'); backdrop.classList.remove('visible'); toggle.setAttribute('aria-expanded','false'); document.body.style.overflow=''; }
    toggle.addEventListener('click', function() { sidebar.classList.contains('open') ? closeSidebar() : openSidebar(); });
    backdrop.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function(e) { if(e.key==='Escape') closeSidebar(); });
})();
</script>
<script>
(function(){
    // Avvolge i decimali (,XX o .XX a fine stringa) con .dec per ridurne il font
    function wrapDec(el){
        if(!el || el.querySelector('.dec')) return;
        var t = el.textContent.trim();
        if(!/[.,]\d{2}$/.test(t)) return;
        el.innerHTML = t.replace(/([.,])(\d{2})$/, '$1<span class="dec">$2</span>');
    }
    function run(){
        document.querySelectorAll(
            '.kpi-value, .kpi-sub strong, .metric-main, .show-metric strong, .metric-value'
        ).forEach(wrapDec);
    }
    if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
    else run();
})();
</script>
@stack('scripts')
</body>
</html>
