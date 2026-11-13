@php
    // Halaman publik mengirim prop `seo` (App\Website\Seo\SeoData); halaman admin/auth tidak, sehingga tidak diindeks.
    $seo = $page['props']['seo'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Atribut `inertia` membuat tag ini digantikan <Head> di sisi klien saat navigasi (kunci sama dengan PublicLayout.vue). --}}
        @if ($seo)
            <title inertia>{{ $seo['title'] }}</title>
            @if ($seo['description'] !== '')
                <meta name="description" content="{{ $seo['description'] }}" inertia="description">
                <meta property="og:description" content="{{ $seo['description'] }}" inertia="og:description">
            @endif
            <link rel="canonical" href="{{ $seo['canonical'] }}" inertia="canonical">
            <meta name="robots" content="{{ $seo['noindex'] ? 'noindex,nofollow' : 'index,follow' }}" inertia="robots">
            <meta property="og:type" content="{{ $seo['type'] }}" inertia="og:type">
            <meta property="og:title" content="{{ $seo['title'] }}" inertia="og:title">
            <meta property="og:url" content="{{ $seo['canonical'] }}" inertia="og:url">
            <meta property="og:site_name" content="{{ $seo['siteName'] }}" inertia="og:site_name">
            <meta property="og:locale" content="{{ $seo['locale'] }}" inertia="og:locale">
            <meta name="twitter:card" content="{{ $seo['image'] ? 'summary_large_image' : 'summary' }}" inertia="twitter:card">
            @if ($seo['image'])
                <meta property="og:image" content="{{ $seo['image'] }}" inertia="og:image">
            @endif
            @foreach ($seo['jsonLd'] as $item)
                <script type="application/ld+json" inertia>{!! json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
            @endforeach
        @else
            <title inertia>{{ $siteName }}</title>
            <meta name="robots" content="noindex,nofollow">
        @endif

        @if ($faviconUrl)
            <link rel="icon" href="{{ $faviconUrl }}">
        @endif

        @if ($fontUrl ?? null)
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link rel="stylesheet" href="{{ $fontUrl }}">
        @endif

        {{-- Token warna, font, dan sudut tema aktif (nilai dari config/theme.php, bukan input pengguna). --}}
        <style>{!! $themeCss ?? '' !!}</style>

        {{-- Halaman publik: hanya route non-admin (group "public", lihat WebsiteServiceProvider); lainnya: semua route. --}}
        @routes($ziggyGroup ?? null)
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="min-h-screen bg-surface text-ink antialiased">
        @inertia
    </body>
</html>
