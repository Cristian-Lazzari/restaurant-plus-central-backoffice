<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f6fa;
            --surface: #ffffff;
            --surface-soft: #f8fafc;
            --ink: #111827;
            --muted: #667085;
            --border: #d8dee8;
            --border-soft: #edf0f5;
            --brand: #155eef;
            --brand-soft: #eef4ff;
            --green: #027a48;
            --green-soft: #ecfdf3;
            --amber: #b54708;
            --amber-soft: #fffaeb;
            --red: #b42318;
            --red-soft: #fef3f2;
            --shadow: 0 12px 32px rgba(17, 24, 39, 0.06);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            color: var(--ink);
            background: var(--bg);
            font-size: 14px;
        }
        a { color: var(--brand); text-decoration: none; }
        a:hover { text-decoration: underline; }
        header {
            position: sticky;
            top: 0;
            z-index: 30;
            background: rgba(255, 255, 255, 0.94);
            border-bottom: 1px solid var(--border-soft);
            backdrop-filter: blur(12px);
        }
        nav {
            max-width: 1240px;
            margin: 0 auto;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        main { max-width: 1240px; margin: 0 auto; padding: 28px 20px 52px; }
        h1 { margin: 0; font-size: 30px; line-height: 1.15; font-weight: 760; }
        h2 { margin: 28px 0 12px; font-size: 18px; line-height: 1.2; font-weight: 720; }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 760;
            color: var(--ink);
        }
        .brand::before {
            content: "";
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: linear-gradient(135deg, #155eef, #039855);
            display: inline-block;
        }
        .actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .panel {
            background: var(--surface);
            border: 1px solid var(--border-soft);
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: var(--shadow);
        }
        .table-wrap {
            overflow-x: auto;
            background: var(--surface);
            border: 1px solid var(--border-soft);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        table { width: 100%; border-collapse: collapse; min-width: 920px; }
        th, td { padding: 13px 14px; border-bottom: 1px solid var(--border-soft); text-align: left; vertical-align: top; font-size: 14px; }
        th { color: #475467; background: #fbfcfe; font-size: 12px; font-weight: 760; text-transform: uppercase; }
        tr:last-child td { border-bottom: 0; }
        label { display: block; font-weight: 700; margin: 0 0 6px; }
        input[type="text"], input[type="url"], input[type="password"], input[type="date"], input[type="number"], textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px 11px;
            font: inherit;
            background: #fff;
            color: var(--ink);
        }
        input:focus, textarea:focus, select:focus {
            outline: 3px solid rgba(21, 94, 239, 0.14);
            border-color: var(--brand);
        }
        .field { margin-bottom: 14px; }
        .inline { display: inline-flex; align-items: center; gap: 8px; }
        .btn {
            border: 1px solid #c7d0dd;
            background: #fff;
            color: #172033;
            border-radius: 6px;
            padding: 8px 12px;
            min-height: 36px;
            font: inherit;
            font-weight: 650;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            white-space: nowrap;
        }
        .btn:hover { background: #f8fafc; text-decoration: none; }
        .btn:disabled { opacity: 0.55; cursor: not-allowed; }
        .btn.primary { background: var(--brand); border-color: var(--brand); color: #fff; }
        .btn.primary:hover { background: #004eeb; }
        .btn.danger { border-color: #f0b8b8; color: var(--red); }
        .btn svg { width: 16px; height: 16px; flex: 0 0 16px; }
        .btn.icon-only { width: 36px; padding: 8px; }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: var(--brand-soft);
            color: #3538cd;
        }
        .badge.off { background: #f2f4f7; color: #475467; }
        .muted { color: var(--muted); }
        .flash { padding: 12px 14px; border-radius: 8px; margin-bottom: 16px; border: 1px solid var(--border); background: #fff; }
        .flash.success { border-color: #abefc6; background: var(--green-soft); }
        .flash.error { border-color: #fecdca; background: var(--red-soft); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
        .metric {
            border: 1px solid var(--border-soft);
            border-radius: 8px;
            padding: 14px;
            background: #fff;
            box-shadow: var(--shadow);
        }
        .metric strong { display: block; font-size: 22px; line-height: 1.15; margin-top: 4px; }
        pre { white-space: pre-wrap; word-break: break-word; background: #111827; color: #f9fafb; padding: 16px; border-radius: 8px; overflow-x: auto; }
        .error-list { margin: 0; padding-left: 18px; color: var(--red); }
        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin: 28px 0 12px;
        }
        .section-head h2 { margin: 0; }
        @media (max-width: 760px) {
            nav { align-items: flex-start; padding: 10px 14px; flex-direction: column; }
            nav > .actions { gap: 6px; }
            nav > .actions, nav form { width: 100%; }
            nav > .actions .brand { width: 100%; }
            nav form .btn { width: 100%; }
            main { padding: 18px 14px 36px; }
            h1 { font-size: 25px; }
            h2 { font-size: 17px; }
            .brand::before { width: 24px; height: 24px; border-radius: 7px; }
            .btn { min-height: 38px; padding: 8px 10px; }
            .panel { padding: 14px; }
            .grid { grid-template-columns: 1fr; }
            table { min-width: 0; }
            .table-wrap { overflow-x: visible; }
            .mobile-full { width: 100%; }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="actions">
                <a class="brand" href="{{ route('dashboard') }}">{{ config('app.name') }}</a>
                @if(session('backoffice_authenticated'))
                    <a class="btn" href="{{ route('dashboard') }}">Dashboard</a>
                    <a class="btn" href="{{ route('backoffice-settings.edit') }}">Impostazioni</a>
                @endif
            </div>
            @if(session('backoffice_authenticated'))
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn" type="submit">Logout</button>
                </form>
            @endif
        </nav>
    </header>
    <main>
        @if(session('success'))
            <div class="flash success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="flash error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="flash error">
                <ul class="error-list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
