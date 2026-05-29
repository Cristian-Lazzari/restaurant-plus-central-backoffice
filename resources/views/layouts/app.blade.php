<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light; --border: #d9dee7; --muted: #667085; --ink: #101828; --bg: #f6f7f9; --brand: #155eef; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; color: var(--ink); background: var(--bg); }
        a { color: var(--brand); text-decoration: none; }
        header { background: #fff; border-bottom: 1px solid var(--border); }
        nav { max-width: 1180px; margin: 0 auto; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        main { max-width: 1180px; margin: 0 auto; padding: 24px 20px 48px; }
        h1 { margin: 0 0 18px; font-size: 28px; }
        h2 { margin: 28px 0 12px; font-size: 19px; }
        .brand { font-weight: 700; color: var(--ink); }
        .actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .panel { background: #fff; border: 1px solid var(--border); border-radius: 8px; padding: 18px; margin-bottom: 18px; }
        .table-wrap { overflow-x: auto; background: #fff; border: 1px solid var(--border); border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; min-width: 980px; }
        th, td { padding: 11px 12px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: top; font-size: 14px; }
        th { color: #344054; background: #fbfcfe; font-weight: 700; }
        tr:last-child td { border-bottom: 0; }
        label { display: block; font-weight: 700; margin: 0 0 6px; }
        input[type="text"], input[type="url"], input[type="password"], input[type="date"], textarea {
            width: 100%; border: 1px solid var(--border); border-radius: 6px; padding: 10px 11px; font: inherit; background: #fff;
        }
        .field { margin-bottom: 14px; }
        .inline { display: inline-flex; align-items: center; gap: 8px; }
        .btn { border: 1px solid #b8c2d3; background: #fff; color: var(--ink); border-radius: 6px; padding: 8px 11px; font: inherit; cursor: pointer; display: inline-block; }
        .btn.primary { background: var(--brand); border-color: var(--brand); color: #fff; }
        .btn.danger { border-color: #f0b8b8; color: #b42318; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 12px; background: #eef4ff; color: #3538cd; }
        .badge.off { background: #f2f4f7; color: #475467; }
        .muted { color: var(--muted); }
        .flash { padding: 12px 14px; border-radius: 8px; margin-bottom: 16px; border: 1px solid var(--border); background: #fff; }
        .flash.success { border-color: #abefc6; background: #ecfdf3; }
        .flash.error { border-color: #fecdca; background: #fef3f2; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
        .metric { border: 1px solid var(--border); border-radius: 8px; padding: 12px; background: #fff; }
        .metric strong { display: block; font-size: 22px; margin-top: 4px; }
        pre { white-space: pre-wrap; word-break: break-word; background: #111827; color: #f9fafb; padding: 16px; border-radius: 8px; overflow-x: auto; }
        .error-list { margin: 0; padding-left: 18px; color: #b42318; }
    </style>
</head>
<body>
    <header>
        <nav>
            <a class="brand" href="{{ route('dashboard') }}">{{ config('app.name') }}</a>
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
