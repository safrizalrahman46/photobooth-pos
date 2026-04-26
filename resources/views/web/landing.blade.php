@php
    // --- AMBIL DATA DARI LOGIC TEMAN KAMU ---
    $general = $siteSettings['general'] ?? [];
    $brandName = $general['brand_name'] ?? 'Ready To Pict';
    $tagline = $general['tagline'] ?? 'Studio Foto Mandiri Premium';

    $navItems = [
        ['label' => 'Home', 'href' => '#home'],
        ['label' => 'About', 'href' => '#about'],
        ['label' => 'Pricelist', 'href' => '#pricelist'],
        ['label' => 'Location', 'href' => '#contact'],
        ['label' => 'Booking Sekarang', 'href' => route('booking.create')],
    ];

    // Dummy images for Marquee (Tetap kita pertahankan untuk estetika)
    $allImages = [
        ['src' => asset('images/landing/Basic/IMG_0394.JPG'), 'package' => 'BASIC'],
        ['src' => asset('images/landing/Basic/IMG_0879.JPG'), 'package' => 'BASIC'],
        ['src' => asset('images/landing/manbol/IMG_0244.JPG'), 'package' => 'MANDI BOLA'],
        ['src' => asset('images/landing/Vintage/IMG_3356.JPG'), 'package' => 'VINTAGE'],
        ['src' => asset('images/landing/Mini/IMG_1228.JPG'), 'package' => 'MINIMARKET'],
        ['src' => asset('images/landing/Sofa/IMG_0350.JPG'), 'package' => 'SOFA'],
    ];
    $shuffled = collect($allImages)->shuffle();
    $marqueeRowA = $shuffled->take(6)->all();
    $marqueeRowB = $shuffled->reverse()->take(6)->all();
@endphp

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brandName }} — {{ $tagline }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --memphis-yellow: #FFD166;
            --memphis-blue: #118AB2;
            --memphis-orange: #EF476F;
            --memphis-ink: #073B4C;
            --memphis-cream: #FDF9EC;
        }
        .animate-marquee-left { animation: marquee-left 40s linear infinite; }
        .animate-marquee-right { animation: marquee-right 40s linear infinite; }
        @keyframes marquee-left { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        @keyframes marquee-right { 0% { transform: translateX(-50%); } 100% { transform: translateX(0); } }
    </style>
</head>
<body class="bg-white text-[--memphis-ink] overflow-x-hidden">

    <!-- HEADER -->
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100">
        <div class="max-w-7xl mx-auto flex items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo/logo.png') }}" alt="Logo" class="h-10" />
                <span class="text-xl font-bold text-[--memphis-blue]">{{ $brandName }}</span>
            </div>
            <nav class="hidden md:flex items-center gap-8 font-semibold">
                @foreach($navItems as $item)
                    <a href="{{ $item['href'] }}" class="hover:text-[--memphis-blue] transition-colors {{ $item['label'] == 'Booking Sekarang' ? 'bg-[--memphis-yellow] px-5 py-2 rounded-full' : '' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </header>

    <!-- HERO MARQUEE -->
    <section id="home" class="py-10 space-y-4 overflow-hidden bg-gray-50">
        <div class="flex w-max animate-marquee-left gap-4">
            @foreach(array_merge($marqueeRowA, $marqueeRowA) as $item)
                <div class="h-64 w-64 rounded-2xl overflow-hidden bg-white shadow-md">
                    <img src="{{ $item['src'] }}" class="h-full w-full object-cover" />
                </div>
            @endforeach
        </div>
        <div class="flex w-max animate-marquee-right gap-4">
            @foreach(array_merge($marqueeRowB, $marqueeRowB) as $item)
                <div class="h-64 w-64 rounded-2xl overflow-hidden bg-white shadow-md">
                    <img src="{{ $item['src'] }}" class="h-full w-full object-cover" />
                </div>
            @endforeach
        </div>
    </section>

    <!-- ABOUT DYNAMIC -->
    <section id="about" class="py-20 px-6">
        <div class="max-w-4xl mx-auto text-center border-4 border-dashed border-[--memphis-blue] p-10 rounded-3xl bg-[--memphis-cream]">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">{{ $tagline }}</h1>
            <p class="text-lg text-gray-600 mb-8">Pilih paket, cek slot yang tersedia, dan booking online dalam beberapa menit. Datang sesuai jam, langsung foto.</p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('booking.create') }}" class="bg-[--memphis-blue] text-white px-8 py-4 rounded-2xl font-bold text-lg shadow-lg hover:scale-105 transition-transform">Booking Sekarang</a>
                <a href="{{ route('queue.board') }}" class="border-2 border-[--memphis-blue] px-8 py-4 rounded-2xl font-bold text-lg hover:bg-white transition-colors">Lihat Antrean</a>
            </div>
        </div>
    </section>

    <!-- DYNAMIC PRICELIST (Integrated with Friend's Code) -->
    <section id="pricelist" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-center mb-12">Pilih Paket Kesukaanmu</h2>
            <div class="grid md:grid-cols-3 gap-8">
                @forelse ($packages as $package)
                    <div class="bg-white p-8 rounded-3xl shadow-xl border border-gray-100 hover:border-[--memphis-orange] transition-colors relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4">
                             <span class="bg-[--memphis-yellow] text-xs font-bold px-3 py-1 rounded-full uppercase">{{ $package->duration_minutes }} MENIT</span>
                        </div>
                        <h3 class="text-2xl font-bold mb-2">{{ $package->name }}</h3>
                        <p class="text-gray-500 text-sm mb-6">{{ $package->description ?: 'Paket seru untuk momen terbaikmu.' }}</p>
                        <div class="text-4xl font-black mb-8">Rp {{ number_format((float) $package->base_price, 0, ',', '.') }}</div>
                        <a href="{{ route('booking.create', ['package' => $package->id]) }}" class="block text-center bg-[--memphis-ink] text-white py-4 rounded-xl font-bold group-hover:bg-[--memphis-orange] transition-colors">Pilih Paket</a>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-20 bg-white rounded-3xl border">
                        <p class="text-gray-400">Belum ada paket aktif saat ini.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- CABANG AKTIF (New logic from Friend) -->
    <section class="py-20 px-6">
        <div class="max-w-7xl mx-auto">
            <h3 class="text-2xl font-bold mb-8">Tersedia di Cabang:</h3>
            <div class="grid md:grid-cols-2 gap-6">
                @forelse ($branches as $branch)
                    <div class="bg-[--memphis-cream] p-6 rounded-2xl flex items-center gap-4 border border-[--memphis-blue]/20">
                        <div class="h-12 w-12 bg-[--memphis-blue] rounded-full flex items-center justify-center text-white text-xl">📍</div>
                        <div>
                            <h4 class="font-bold text-lg">{{ $branch->name }}</h4>
                            <p class="text-sm text-gray-500">{{ $branch->address ?: 'Alamat segera tersedia' }} • {{ $branch->timezone }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400">Belum ada cabang aktif.</p>
                @endforelse
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="py-10 text-center border-t border-gray-100 text-sm text-gray-400">
        © {{ date('Y') }} {{ $brandName }}. All rights reserved.
    </footer>

</body>
</html>
