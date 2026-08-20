<!doctype html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'PI Gate Admin' }}</title>
    <style>
        :root {
            --bg: #eef3f8;
            --panel: #ffffff;
            --line: #d7e3ee;
            --text: #152536;
            --muted: #607184;
            --primary: #0b5ea8;
            --primary-dark: #073f75;
            --primary-soft: #e7f2fb;
            --accent: #19a0d8;
            --ok: #187a4a;
            --bad: #a83d3d;
            --warn: #9a6a14;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background:
                linear-gradient(180deg, #f8fbfd 0, var(--bg) 260px);
            color: var(--text);
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 1.45;
        }
        a { color: var(--primary); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .topbar {
            background: #ffffff;
            border-bottom: 4px solid var(--accent);
            box-shadow: 0 10px 30px rgba(7, 63, 117, 0.08);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .topbar-inner {
            align-items: center;
            display: flex;
            gap: 18px;
            margin: 0 auto;
            max-width: 1280px;
            min-height: 72px;
            padding: 0 20px;
        }
        .brand {
            align-items: center;
            color: var(--primary-dark);
            display: flex;
            flex-direction: column;
            font-size: 21px;
            font-weight: 700;
            line-height: 1.05;
            margin-right: 12px;
            white-space: nowrap;
        }
        .brand small {
            color: var(--accent);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .nav {
            align-items: center;
            display: flex;
            flex: 1;
            flex-wrap: wrap;
            gap: 4px;
        }
        .nav a {
            border-radius: 6px;
            color: #244057;
            font-weight: 600;
            padding: 9px 10px;
        }
        .nav a:hover {
            background: #f0f7fc;
            color: var(--primary-dark);
            text-decoration: none;
        }
        .nav a.active {
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-weight: 700;
        }
        .logout {
            background: transparent;
            border: 1px solid #b9ccdc;
            border-radius: 6px;
            color: var(--primary-dark);
            cursor: pointer;
            font-weight: 700;
            padding: 8px 10px;
        }
        .logout:hover { background: #f0f7fc; }
        .action {
            background: linear-gradient(180deg, #126dbb, var(--primary));
            border: 0;
            border-radius: 6px;
            box-shadow: 0 8px 18px rgba(11, 94, 168, 0.20);
            color: #fff;
            display: inline-block;
            font-weight: 700;
            padding: 9px 13px;
        }
        .action:hover { color: #fff; text-decoration: none; }
        .action.secondary {
            background: #edf5fb;
            box-shadow: none;
            color: var(--primary-dark);
        }
        .action.danger {
            background: linear-gradient(180deg, #c64b4b, var(--bad));
            box-shadow: 0 8px 18px rgba(168, 61, 61, 0.20);
        }
        .wrap {
            margin: 0 auto;
            max-width: 1280px;
            padding: 22px 20px 40px;
        }
        .page-head {
            align-items: flex-end;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 18px;
        }
        h1 {
            color: var(--primary-dark);
            font-size: 28px;
            font-weight: 750;
            margin: 0;
        }
        .muted { color: var(--muted); }
        .grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            margin-bottom: 22px;
        }
        .stat, .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(12, 46, 78, 0.06);
        }
        .stat {
            border-top: 3px solid var(--accent);
            padding: 16px;
        }
        .stat .value {
            color: var(--primary-dark);
            font-size: 30px;
            font-weight: 700;
            margin-bottom: 3px;
        }
        .stat .label {
            color: var(--muted);
            font-size: 13px;
        }
        .panel { margin-bottom: 18px; overflow: hidden; }
        .panel.danger {
            border-color: #e8b4b4;
        }
        .panel.danger .panel-title {
            background: #fff6f6;
            color: var(--bad);
        }
        .panel-title {
            background: #fbfdff;
            border-bottom: 1px solid var(--line);
            color: var(--primary-dark);
            font-size: 16px;
            font-weight: 700;
            padding: 13px 16px;
        }
        .table-wrap { overflow-x: auto; }
        table {
            border-collapse: collapse;
            min-width: 860px;
            width: 100%;
        }
        th, td {
            border-bottom: 1px solid var(--line);
            padding: 10px 12px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f4f9fd;
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        tr:last-child td { border-bottom: 0; }
        .badge {
            border-radius: 999px;
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            padding: 3px 8px;
        }
        .badge.ok { background: #e4f5ea; color: var(--ok); }
        .badge.bad { background: #f9e5e5; color: var(--bad); }
        .badge.warn { background: #fff3d7; color: var(--warn); }
        .cards-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .thumb {
            border: 1px solid var(--line);
            border-radius: 6px;
            height: 64px;
            object-fit: cover;
            width: 86px;
        }
        .pager {
            padding: 14px 16px;
        }
        .pager nav > div:first-child { display: none; }
        .notice {
            background: #e4f5ea;
            border: 1px solid #add9bd;
            border-radius: 6px;
            color: var(--ok);
            margin-bottom: 16px;
            padding: 10px 12px;
        }
        .form-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            padding: 16px;
        }
        .form-row {
            display: flex;
            flex-direction: column;
        }
        .form-actions {
            border-top: 1px solid var(--line);
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding: 14px 16px;
        }
        .work-calendar {
            display: grid;
            grid-template-columns: repeat(7, minmax(150px, 1fr));
            overflow-x: auto;
        }
        .calendar-head {
            background: #f4f9fd;
            border-bottom: 1px solid var(--line);
            border-right: 1px solid var(--line);
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 700;
            padding: 9px 10px;
            text-transform: uppercase;
        }
        .calendar-day {
            border-bottom: 1px solid var(--line);
            border-right: 1px solid var(--line);
            min-height: 170px;
            padding: 10px;
        }
        .calendar-day.today {
            background: #e8f5fc;
        }
        .calendar-empty {
            background: #f8fbfd;
        }
        .calendar-date {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .calendar-date strong {
            font-size: 18px;
        }
        .calendar-date span {
            color: var(--primary-dark);
            font-weight: 700;
        }
        .work-entry {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 6px;
            margin-top: 6px;
            padding: 7px;
        }
        .work-entry summary {
            cursor: pointer;
            display: flex;
            gap: 8px;
            justify-content: space-between;
        }
        .work-entry summary span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .pair {
            border-top: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            margin-top: 6px;
            padding-top: 6px;
        }
        pre {
            background: #f4f9fd;
            border: 1px solid var(--line);
            border-radius: 6px;
            margin: 6px 0 10px;
            max-width: 520px;
            overflow: auto;
            padding: 8px;
            white-space: pre-wrap;
        }
        select {
            border: 1px solid var(--line);
            border-radius: 6px;
            font: inherit;
            padding: 10px 12px;
            width: 100%;
        }
        .login-page {
            align-items: center;
            background:
                linear-gradient(135deg, rgba(7, 63, 117, .95), rgba(11, 94, 168, .90)),
                linear-gradient(180deg, #ffffff, #eef3f8);
            display: flex;
            min-height: 100vh;
            padding: 20px;
        }
        .login-box {
            background: var(--panel);
            border: 1px solid rgba(255, 255, 255, .45);
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, .22);
            margin: 0 auto;
            max-width: 420px;
            padding: 26px;
            width: 100%;
        }
        label {
            display: block;
            font-weight: 700;
            margin: 14px 0 6px;
        }
        input[type="date"], input[type="email"], input[type="password"], input[type="text"], input[type="month"] {
            border: 1px solid var(--line);
            border-radius: 6px;
            font: inherit;
            padding: 10px 12px;
            width: 100%;
        }
        input:disabled {
            background: #f4f9fd;
            color: var(--muted);
        }
        input:focus, select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(25, 160, 216, .16);
            outline: none;
        }
        .button {
            background: linear-gradient(180deg, #126dbb, var(--primary));
            border: 0;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            font-weight: 700;
            margin-top: 18px;
            padding: 10px 14px;
            width: 100%;
        }
        .error {
            background: #f9e5e5;
            border: 1px solid #e8b4b4;
            border-radius: 6px;
            color: var(--bad);
            margin: 14px 0;
            padding: 10px 12px;
        }
        @media (max-width: 760px) {
            .topbar-inner { align-items: flex-start; flex-direction: column; gap: 8px; padding: 12px 14px; }
            .page-head { align-items: flex-start; flex-direction: column; }
            .wrap { padding: 18px 14px 32px; }
            table { min-width: 760px; }
            .work-calendar { grid-template-columns: repeat(7, minmax(135px, 1fr)); }
        }
    </style>
</head>
<body>
@isset($loginPage)
    @yield('body')
@else
    <header class="topbar">
        <div class="topbar-inner">
            <div class="brand"><small>Paksi Informatika</small>PI Gate</div>
            <nav class="nav">
                <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
                <a class="{{ request()->routeIs('admin.companies') ? 'active' : '' }}" href="{{ route('admin.companies') }}">Cégek</a>
                <a class="{{ request()->routeIs('admin.employees') ? 'active' : '' }}" href="{{ route('admin.employees') }}">Dolgozók / kártyák</a>
                <a class="{{ request()->routeIs('admin.devices') ? 'active' : '' }}" href="{{ route('admin.devices') }}">Eszközök</a>
                <a class="{{ request()->routeIs('admin.events') ? 'active' : '' }}" href="{{ route('admin.events') }}">Blokkolások</a>
                <a class="{{ request()->routeIs('admin.worktime') ? 'active' : '' }}" href="{{ route('admin.worktime') }}">Munkaidő</a>
                <a class="{{ request()->routeIs('admin.photos') ? 'active' : '' }}" href="{{ route('admin.photos') }}">Fotók</a>
                <a class="{{ request()->routeIs('admin.logs') ? 'active' : '' }}" href="{{ route('admin.logs') }}">Napló</a>
                <a class="{{ request()->routeIs('admin.software-updates*') ? 'active' : '' }}" href="{{ route('admin.software-updates') }}">Frissítések</a>
                @if (auth()->user()?->isAdmin())
                    <a class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}">Felhasználók</a>
                @endif
            </nav>
            <form method="post" action="{{ route('admin.logout') }}">
                @csrf
                <button class="logout" type="submit">Kilépés</button>
            </form>
        </div>
    </header>
    <main class="wrap">
        @if (session('status'))
            <div class="notice">{{ session('status') }}</div>
        @endif
        @yield('body')
    </main>
@endisset
</body>
</html>
