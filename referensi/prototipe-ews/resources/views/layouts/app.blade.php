<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Peringatan Dini Longsor') - Desa Lumeneng</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Atkinson Hyperlegible', system-ui, sans-serif; background: #EEF2F4; color: #14232B; }

        /* Warna status. Satu tempat saja, dipakai panel, kartu, dan lencana. */
        .panel { border-radius: 1rem; padding: 1.75rem; color: #fff; background: #6B7780; }
        .panel[data-warna=hijau]  { background: #1E7F55; }
        .panel[data-warna=kuning] { background: #F2B705; color: #1d1600; }
        .panel[data-warna=merah]  { background: #B3261E; }

        .kartu { background: #fff; border-radius: .75rem; border-left: 6px solid #6B7780; padding: 1rem 1.25rem; }
        .kartu[data-warna=hijau]  { border-left-color: #1E7F55; }
        .kartu[data-warna=kuning] { border-left-color: #F2B705; }
        .kartu[data-warna=merah]  { border-left-color: #B3261E; }

        .lencana { display: inline-flex; align-items: center; gap: .35rem; padding: .15rem .6rem; border-radius: 999px; font-weight: 700; font-size: .875rem; background: #E3E8EB; color: #3b464d; }
        .lencana[data-warna=hijau]  { background: #DDF3E8; color: #14573a; }
        .lencana[data-warna=kuning] { background: #FDF1C4; color: #5c4500; }
        .lencana[data-warna=merah]  { background: #FADAD7; color: #7d1914; }

        .blok { background: #fff; border-radius: 1rem; padding: 1.25rem; }
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible { outline: 3px solid #1d4ed8; outline-offset: 2px; }

        @keyframes denyut { 50% { opacity: .4; } }
        @media (prefers-reduced-motion: no-preference) {
            .panel[data-warna=merah] #panel-ikon { animation: denyut 1.4s ease-in-out infinite; }
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen">
    <header class="bg-[#14232B] text-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-lg font-bold">
                <i class="fa-solid fa-mountain-sun"></i> Peringatan Dini Longsor, Desa Lumeneng
            </a>
            <nav class="flex items-center gap-4 text-sm">
                <a class="hover:underline" href="{{ route('dashboard') }}">Dashboard</a>
                <a class="hover:underline" href="{{ route('admin.dashboard') }}">Admin</a>
                <a class="hover:underline" href="{{ route('simulator') }}">Simulator</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl space-y-5 px-4 py-6">
        @yield('content')
    </main>

    <footer class="mx-auto max-w-6xl px-4 pb-8 text-sm text-slate-600">
        Prototipe KKN Aksara, Universitas IVET. Data uji coba, bukan peringatan resmi BPBD.
    </footer>
    @stack('scripts')
</body>
</html>
