@php
    $general = $siteSettings['general'] ?? [];
    $brandName = $general['brand_name'] ?? 'Ready to Pict';
    $tagline = $general['tagline'] ?? 'Photo booth cepat, estetik, dan anti ribet.';
    
    $navItems = [
        ['label' => 'Home', 'href' => '#home'],
        ['label' => 'Layanan', 'href' => '#services'],
        ['label' => 'Pricelist', 'href' => '#pricelist'],
        ['label' => 'Contact', 'href' => '#contact'],
        ['label' => 'Booking', 'href' => route('booking.customer')],
    ];

    $marqueeRowA = [
        ['src' => asset('images/landing/Basic/IMG_0394.JPG'), 'package' => 'BASIC', 'price' => '35k / sesi'],
        ['src' => asset('images/landing/Mini/IMG_1228.JPG'), 'package' => 'MINI', 'price' => '40k / sesi'],
        ['src' => asset('images/landing/Basic/IMG_0879.JPG'), 'package' => 'BASIC', 'price' => '35k / sesi'],
        ['src' => asset('images/landing/Mini/IMG_1555.JPG'), 'package' => 'MINI', 'price' => '40k / sesi'],
        ['src' => asset('images/landing/Basic/IMG_9825.JPG'), 'package' => 'BASIC', 'price' => '35k / sesi'],
        ['src' => asset('images/landing/Mini/IMG_8011.JPG'), 'package' => 'MINI', 'price' => '40k / sesi'],
        ['src' => asset('images/landing/Mini/IMG_1976.JPG'), 'package' => 'MINI', 'price' => '40k / sesi'],
        ['src' => asset('images/landing/Mini/IMG_9013.JPG'), 'package' => 'MINI', 'price' => '40k / sesi'],
    ];

    $marqueeRowB = [
        ['src' => asset('images/landing/manbol/IMG_0244.JPG'), 'package' => 'MANDI BOLA', 'price' => '45k / sesi'],
        ['src' => asset('images/landing/Vintage/IMG_0032.JPG'), 'package' => 'VINTAGE', 'price' => '45k / sesi'],
        ['src' => asset('images/landing/manbol/IMG_2968.JPG'), 'package' => 'MANDI BOLA', 'price' => '45k / sesi'],
        ['src' => asset('images/landing/Vintage/IMG_3356.JPG'), 'package' => 'VINTAGE', 'price' => '45k / sesi'],
        ['src' => asset('images/landing/manbol/IMG_3096.JPG'), 'package' => 'MANDI BOLA', 'price' => '45k / sesi'],
        ['src' => asset('images/landing/Vintage/IMG_4662.JPG'), 'package' => 'VINTAGE', 'price' => '45k / sesi'],
        ['src' => asset('images/landing/manbol/IMG_5205.JPG'), 'package' => 'MANDI BOLA', 'price' => '45k / sesi'],
        ['src' => asset('images/landing/Vintage/IMG_7475.JPG'), 'package' => 'VINTAGE', 'price' => '45k / sesi'],
    ];

    $marqueeRowC = [
        ['src' => asset('images/landing/source%20tambahan/IMG_0117_20260404_175132_3600.webp'), 'package' => 'SPECIAL', 'price' => '50k / sesi'],
        ['src' => asset('images/landing/source%20tambahan/2025-07-26_180006489.mp4'), 'package' => 'SPECIAL', 'price' => '50k / sesi'],
        ['src' => asset('images/landing/source%20tambahan/IMG_0125_20260404_175402_3600.webp'), 'package' => 'SPECIAL', 'price' => '50k / sesi'],
        ['src' => asset('images/landing/source%20tambahan/20250727_180948_030.mp4'), 'package' => 'SPECIAL', 'price' => '50k / sesi'],
        ['src' => asset('images/landing/source%20tambahan/IMG_0117_20260404_175132_3600.webp'), 'package' => 'SPECIAL', 'price' => '50k / sesi'],
        ['src' => asset('images/landing/source%20tambahan/20250930_120707_461.mp4'), 'package' => 'SPECIAL', 'price' => '50k / sesi'],
        ['src' => asset('images/landing/source%20tambahan/IMG_0125_20260404_175402_3600.webp'), 'package' => 'SPECIAL', 'price' => '50k / sesi'],
        ['src' => asset('images/landing/source%20tambahan/20250930_140526_642.mp4'), 'package' => 'SPECIAL', 'price' => '50k / sesi'],
        ['src' => asset('images/landing/source%20tambahan/IMG_0117_20260404_175132_3600.webp'), 'package' => 'SPECIAL', 'price' => '50k / sesi'],
        ['src' => asset('images/landing/source%20tambahan/20260213_204007_698.mp4'), 'package' => 'SPECIAL', 'price' => '50k / sesi'],
        ['src' => asset('images/landing/source%20tambahan/IMG_0125_20260404_175402_3600.webp'), 'package' => 'SPECIAL', 'price' => '50k / sesi'],
        ['src' => asset('images/landing/source%20tambahan/20260404_175201_597.mp4'), 'package' => 'SPECIAL', 'price' => '50k / sesi'],
        ['src' => asset('images/landing/source%20tambahan/IMG_0117_20260404_175132_3600.webp'), 'package' => 'SPECIAL', 'price' => '50k / sesi'],
        ['src' => asset('images/landing/source%20tambahan/20260404_175452_585.mp4'), 'package' => 'SPECIAL', 'price' => '50k / sesi'],
    ];

    
    // Mapping $packages dari database ke format Memphis UI
    $accents = ['bg-memphis-yellow', 'bg-memphis-blue', 'bg-memphis-orange'];
    $pricing = $packages->map(function($pkg, $index) use ($accents) {
        return [
            'id' => $pkg->id,
            'name' => strtoupper($pkg->name),
            'price' => number_format($pkg->base_price / 1000, 0) . 'k',
            'accent' => $accents[$index % 3], // Mutar warna biar tetep rame
            'badge' => $index == 1 ? 'Terlaris' : ($index == 0 ? 'Promo' : null),
            'features' => [
                $pkg->duration_minutes . ' Menit',
                '1-2 Orang',
                'Cetak 4R',
                'Free All Soft File'
            ],
        ];
    });
@endphp

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brandName }} — Studio Foto Mandiri Premium</title>
    <meta name="description" content="{{ $brandName }} — studio foto self portrait premium. Bebaskan ekspresimu, kamu yang memegang kendali. Tanpa fotografer, tanpa tekanan.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        /* Fallback for Memphis colors if not in Tailwind */
        :root {
            --memphis-yellow: #FFD166;
            --memphis-blue: #118AB2;
            --memphis-blue-soft: #E8F1F5;
            --memphis-orange: #EF476F;
            --memphis-ink: #073B4C;
            --memphis-cream: #FDF9EC;
            
            --background: #ffffff;
            --foreground: #020817;
            --muted-foreground: #64748b;
            --border: #e2e8f0;
            --card: #ffffff;
            --primary-foreground: #ffffff;
        }

        body {
            background-color: var(--background);
            color: var(--foreground);
            font-family: ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
        }

        .bg-memphis-yellow { background-color: var(--memphis-yellow) !important; }
        .text-memphis-yellow { color: var(--memphis-yellow) !important; }
        .bg-memphis-blue { background-color: var(--memphis-blue) !important; }
        .text-memphis-blue { color: var(--memphis-blue) !important; }
        .bg-memphis-blue-soft { background-color: var(--memphis-blue-soft) !important; }
        .bg-memphis-orange { background-color: var(--memphis-orange) !important; }
        .text-memphis-orange { color: var(--memphis-orange) !important; }
        .bg-memphis-ink { background-color: var(--memphis-ink) !important; }
        .text-memphis-ink { color: var(--memphis-ink) !important; }
        .bg-memphis-cream { background-color: var(--memphis-cream) !important; }
        .border-memphis-blue { border-color: rgba(17, 138, 178, 0.3) !important; }
        .border-memphis-yellow { border-color: rgba(255, 209, 102, 0.3) !important; }

        /* Opacity variants for custom Memphis colors */
        .bg-memphis-yellow-light { background-color: rgba(255, 209, 102, 0.2) !important; }
        .bg-memphis-yellow-xlight { background-color: rgba(255, 209, 102, 0.3) !important; }
        .bg-memphis-blue-light { background-color: rgba(17, 138, 178, 0.1) !important; }
        .bg-memphis-blue-xlight { background-color: rgba(17, 138, 178, 0.08) !important; }
        .bg-memphis-orange-light { background-color: rgba(239, 71, 111, 0.1) !important; }
        .border-memphis-blue-light { border-color: rgba(17, 138, 178, 0.15) !important; }
        .bg-memphis-cream-light { background-color: rgba(253, 249, 236, 0.8) !important; }

        .font-display { font-family: ui-sans-serif, system-ui, sans-serif; }
        
        /* Marquee — JS-driven auto-scroll + drag */
        .marquee-container {
            cursor: grab;
            -webkit-user-select: none;
            user-select: none;
        }
        .marquee-container.is-dragging {
            cursor: grabbing;
        }
        .marquee-track {
            will-change: transform;
        }

        .bg-memphis-ink\/95 {
            background-color: rgba(7, 59, 76, 0.95);
        }

        /* Hide scrollbar for a cleaner look */
        ::-webkit-scrollbar {
            display: none;
        }
        body {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }

        /* 5W1H Section Styles */
        .service-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px -12px rgba(7, 59, 76, 0.15);
        }
        .service-card:hover .service-icon {
            transform: scale(1.1) rotate(-5deg);
        }
        .service-icon {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .why-card {
            transition: all 0.3s ease;
        }
        .why-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -8px rgba(7, 59, 76, 0.12);
        }

        .step-connector {
            position: relative;
        }
        .step-connector::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -2rem;
            width: 2rem;
            height: 2px;
            background: linear-gradient(90deg, var(--memphis-blue), transparent);
        }
        .step-connector:last-child::after {
            display: none;
        }

        @keyframes float-gentle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        .animate-float {
            animation: float-gentle 3s ease-in-out infinite;
        }

        .area-badge {
            transition: all 0.3s ease;
        }
        .area-badge:hover {
            transform: scale(1.08);
        }

        /* Scroll reveal */
        .reveal-up {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        .reveal-up.revealed {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body class="min-h-screen bg-background text-foreground overflow-x-hidden">
    
    <!-- HEADER -->
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md shadow-sm">
        <div class="max-w-7xl mx-auto flex items-center justify-between px-6 md:px-12 py-2 md:py-2.5">
            <a href="{{ url('/') }}" class="flex items-center gap-2 md:gap-3">
                <img src="{{ asset('images/logo/logo.png') }}" alt="{{ $brandName }}" class="h-16 md:h-20 w-auto" />
                <span class="font-display text-base md:text-lg font-semibold tracking-tight text-memphis-blue">
                    {{ $brandName }}
                </span>
            </a>
            
            <!-- Mobile Menu Toggle -->
            <button id="mobile-menu-btn" class="md:hidden text-memphis-ink focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu"><line x1="3" x2="21" y1="6" y2="6"/><line x1="3" x2="21" y1="12" y2="12"/><line x1="3" x2="21" y1="18" y2="18"/></svg>
            </button>

            <nav id="nav-menu" class="hidden md:flex items-center gap-8 text-[15px] font-medium text-memphis-ink absolute md:relative top-full md:top-0 left-0 w-full md:w-auto bg-white md:bg-transparent px-6 py-8 md:p-0 flex-col md:flex-row shadow-xl md:shadow-none transition-all duration-300 z-[60]">
                @foreach($navItems as $item)
                    @if($item['label'] === 'Booking')
                        <a href="{{ $item['href'] }}" class="w-full md:w-auto text-center bg-memphis-yellow text-memphis-ink px-6 py-2.5 rounded-full font-bold shadow-md hover:bg-memphis-orange hover:text-white hover:shadow-xl hover:-translate-y-1 hover:scale-105 ring-offset-background transition-all duration-300 active:scale-95">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <a href="{{ $item['href'] }}" class="w-full md:w-auto text-center hover:text-memphis-blue transition-colors py-2 md:py-0">
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
            </nav>
        </div>
    </header>

    <!-- MARQUEE GALLERY -->
    <section id="home" class="w-full bg-background py-6 space-y-4 scroll-mt-24 overflow-hidden">
        <!-- Row A -->
        <div class="marquee-container relative overflow-hidden" data-speed="0.25" data-direction="left">
            <div class="marquee-track flex w-max gap-4">
                @foreach(array_merge($marqueeRowA, $marqueeRowA, $marqueeRowA, $marqueeRowA, $marqueeRowA, $marqueeRowA, $marqueeRowA, $marqueeRowA, $marqueeRowA, $marqueeRowA) as $i => $item)
                    <button type="button" onclick="if(!this.closest('.is-dragging'))openLightbox('{{ $item['src'] }}', '{{ $item['package'] }}', '{{ $item['price'] }}')" class="relative h-[16rem] md:h-[28rem] w-[16rem] md:w-[28rem] shrink-0 overflow-hidden rounded-2xl bg-memphis-blue-soft cursor-zoom-in group">
                        <img src="{{ $item['src'] }}" alt="{{ $item['package'] }} {{ $i + 1 }}" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Row B -->
        <div class="marquee-container relative overflow-hidden" data-speed="0.25" data-direction="right">
            <div class="marquee-track flex w-max gap-4">
                @foreach(array_merge($marqueeRowB, $marqueeRowB, $marqueeRowB, $marqueeRowB, $marqueeRowB, $marqueeRowB, $marqueeRowB, $marqueeRowB, $marqueeRowB, $marqueeRowB) as $i => $item)
                    <button type="button" onclick="if(!this.closest('.is-dragging'))openLightbox('{{ $item['src'] }}', '{{ $item['package'] }}', '{{ $item['price'] }}')" class="relative h-[16rem] md:h-[28rem] w-[16rem] md:w-[28rem] shrink-0 overflow-hidden rounded-2xl bg-memphis-blue-soft cursor-zoom-in group">
                        <img src="{{ $item['src'] }}" alt="{{ $item['package'] }} {{ $i + 1 }}" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                    </button>
                @endforeach
            </div>
        </div>


    </section>


    <!-- SERVICES / WHAT WE OFFER -->
    <section id="services" class="bg-memphis-cream py-16 md:py-24 scroll-mt-24 overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 md:px-12">
            <div class="text-center mb-12 md:mb-16 reveal-up">
                <span class="inline-block bg-memphis-blue text-white px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest mb-4">
                    Layanan Kami
                </span>
                <h2 class="font-display text-3xl md:text-5xl tracking-tight text-memphis-ink leading-tight">
                    Lebih dari Sekadar <span class="text-memphis-blue">Foto.</span>
                </h2>
                <p class="mt-4 text-base md:text-lg text-muted-foreground max-w-2xl mx-auto leading-relaxed">
                    Ready to Pict menghadirkan pengalaman foto yang fun, private, dan memorable dengan berbagai layanan yang bisa kamu pilih.
                </p>
                <!-- Mobile Only Booking CTA -->
                <a href="{{ route('booking.customer') }}" class="mt-8 md:hidden inline-flex items-center justify-center bg-memphis-yellow text-memphis-ink px-8 py-3.5 rounded-full font-bold text-sm hover:bg-white hover:shadow-xl transition-all duration-300 active:scale-95">
                    Booking Sekarang →
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
                <!-- Self Photo Studio -->
                <div class="service-card bg-white rounded-3xl p-8 border border-border/40 relative overflow-hidden reveal-up">
                    <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-memphis-yellow opacity-40"></div>
                    <div class="service-icon h-16 w-16 rounded-2xl bg-memphis-yellow-light flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-memphis-ink" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                            <circle cx="12" cy="13" r="3"/>
                        </svg>
                    </div>
                    <h3 class="font-display text-xl font-bold text-memphis-ink mb-3">Self Photo Studio</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed mb-5">
                        Nikmati pengalaman self photo studio yang nyaman dan private dengan studio aesthetic, pencahayaan profesional, dan aksesoris lengkap.
                    </p>
                    <ul class="space-y-2.5">
                        <li class="flex items-center gap-2.5 text-sm text-foreground/80">
                            <span class="h-5 w-5 rounded-full bg-memphis-yellow-xlight flex items-center justify-center shrink-0">
                                <span class="h-1.5 w-1.5 rounded-full bg-memphis-ink"></span>
                            </span>
                            Studio aesthetic & private
                        </li>
                        <li class="flex items-center gap-2.5 text-sm text-foreground/80">
                            <span class="h-5 w-5 rounded-full bg-memphis-yellow-xlight flex items-center justify-center shrink-0">
                                <span class="h-1.5 w-1.5 rounded-full bg-memphis-ink"></span>
                            </span>
                            Pencahayaan profesional
                        </li>
                        <li class="flex items-center gap-2.5 text-sm text-foreground/80">
                            <span class="h-5 w-5 rounded-full bg-memphis-yellow-xlight flex items-center justify-center shrink-0">
                                <span class="h-1.5 w-1.5 rounded-full bg-memphis-ink"></span>
                            </span>
                            Aksesoris lengkap & variatif
                        </li>
                        <li class="flex items-center gap-2.5 text-sm text-foreground/80">
                            <span class="h-5 w-5 rounded-full bg-memphis-yellow-xlight flex items-center justify-center shrink-0">
                                <span class="h-1.5 w-1.5 rounded-full bg-memphis-ink"></span>
                            </span>
                            Bebas berekspresi tanpa canggung
                        </li>
                    </ul>
                </div>

                <!-- Photobooth & Event -->
                <div class="service-card bg-white rounded-3xl p-8 border border-border/40 relative overflow-hidden reveal-up" style="transition-delay: 0.1s;">
                    <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-memphis-blue opacity-20"></div>
                    <div class="service-icon h-16 w-16 rounded-2xl bg-memphis-blue-light flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-memphis-blue" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                            <polyline points="17 2 12 7 7 2"/>
                        </svg>
                    </div>
                    <h3 class="font-display text-xl font-bold text-memphis-ink mb-3">Photobooth & Event</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed mb-5">
                        Layanan photobooth profesional untuk berbagai acara spesialmu, dari intimate party sampai corporate event.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['Wedding', 'Birthday', 'Graduation', 'Corporate', 'Community', 'Private'] as $event)
                            <span class="px-3 py-1.5 bg-memphis-blue-xlight text-memphis-blue text-xs font-semibold rounded-full border border-memphis-blue-light">
                                {{ $event }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <!-- Custom Frame -->
                <div class="service-card bg-white rounded-3xl p-8 border border-border/40 relative overflow-hidden reveal-up" style="transition-delay: 0.2s;">
                    <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-memphis-orange opacity-20"></div>
                    <div class="service-icon h-16 w-16 rounded-2xl bg-memphis-orange-light flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-memphis-orange" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <rect x="7" y="7" width="10" height="10" rx="1" ry="1"/>
                        </svg>
                    </div>
                    <h3 class="font-display text-xl font-bold text-memphis-ink mb-3">Custom Frame</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed mb-5">
                        Custom frame eksklusif untuk membuat setiap momen terasa lebih personal dan spesial. Desain fleksibel, sesuai style-mu.
                    </p>
                    <div class="bg-memphis-cream-light rounded-2xl p-4 border border-dashed border-memphis-orange/30">
                        <p class="text-xs text-memphis-ink font-medium text-center">
                            ✨ Personalized design sesuai tema acaramu
                        </p>
                    </div>
                </div>
            </div>

            <!-- WHY / USP Strip -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-10 reveal-up">
                <div class="bg-white rounded-2xl p-5 border border-border/40 text-center">
                    <div class="h-10 w-10 rounded-xl bg-memphis-yellow-light flex items-center justify-center mx-auto mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-memphis-ink" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <h4 class="font-bold text-memphis-ink text-xs mb-1">Private & Nyaman</h4>
                    <p class="text-[11px] text-muted-foreground leading-relaxed">Ruang studio private, bebas eksplorasi tanpa canggung</p>
                </div>
                <div class="bg-white rounded-2xl p-5 border border-border/40 text-center">
                    <div class="h-10 w-10 rounded-xl bg-memphis-blue-light flex items-center justify-center mx-auto mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-memphis-blue" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    </div>
                    <h4 class="font-bold text-memphis-ink text-xs mb-1">Aksesoris Terlengkap</h4>
                    <p class="text-[11px] text-muted-foreground leading-relaxed">Glasses, headwear, cute props & seasonal collection</p>
                </div>
                <div class="bg-white rounded-2xl p-5 border border-border/40 text-center">
                    <div class="h-10 w-10 rounded-xl bg-memphis-orange-light flex items-center justify-center mx-auto mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-memphis-orange" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </div>
                    <h4 class="font-bold text-memphis-ink text-xs mb-1">High Quality Result</h4>
                    <p class="text-[11px] text-muted-foreground leading-relaxed">Pencahayaan studio pro & hasil foto berkualitas tinggi</p>
                </div>
                <div class="bg-white rounded-2xl p-5 border border-border/40 text-center">
                    <div class="h-10 w-10 rounded-xl bg-memphis-yellow-light flex items-center justify-center mx-auto mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-memphis-ink" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                    </div>
                    <h4 class="font-bold text-memphis-ink text-xs mb-1">Custom Frame</h4>
                    <p class="text-[11px] text-muted-foreground leading-relaxed">Desain frame personal sesuai tema acaramu</p>
                </div>
            </div>

            <!-- Highlight Banner -->
            <div class="mt-10 bg-memphis-ink rounded-3xl p-8 md:p-10 hidden md:flex flex-col md:flex-row items-center justify-between gap-6 reveal-up">
                <div class="text-center md:text-left">
                    <h3 class="font-display text-2xl md:text-3xl text-white font-bold mb-2">
                        Ready to Capture Your Moment 
                    </h3>
                    <p class="text-sm text-white/70 max-w-md leading-relaxed">
                        Studio + Event Service — private photo experience dengan aksesoris terlengkap dan hasil foto berkualitas tinggi.
                    </p>
                </div>
                <a href="{{ route('booking.customer') }}" class="shrink-0 bg-memphis-yellow text-memphis-ink px-8 py-3.5 rounded-full font-bold text-sm hover:bg-white hover:shadow-xl hover:-translate-y-1 transition-all duration-300 active:scale-95">
                    Booking Sekarang →
                </a>
            </div>
        </div>
    </section>

    <!-- Row C / Gallery Bottom -->
    <section class="bg-background pb-16 pt-12 md:pt-16 space-y-6 overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 md:px-12 mb-8 text-center">
            <span class="inline-block bg-memphis-orange text-white px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest mb-4">
                Real Moments
            </span>
            <h2 class="font-display text-3xl md:text-5xl tracking-tight text-memphis-ink leading-tight">
                Lihat <span class="text-memphis-blue">Momen Seru Mereka.</span>
            </h2>
            <p class="mt-4 text-base md:text-lg text-muted-foreground max-w-2xl mx-auto leading-relaxed">
                Dari sesi studio sampai event photobooth — ini dia momen-momen real dari customer Ready to Pict.
            </p>
        </div>

        <div class="marquee-container relative overflow-hidden w-full" data-speed="0.25" data-direction="left">
            <div class="marquee-track flex w-max gap-4">
                @foreach(array_merge($marqueeRowC, $marqueeRowC, $marqueeRowC, $marqueeRowC, $marqueeRowC, $marqueeRowC, $marqueeRowC, $marqueeRowC, $marqueeRowC, $marqueeRowC) as $i => $item)
                    @php
                        $isVideo = str_ends_with($item['src'], '.mp4') || str_ends_with($item['src'], '.webm');
                    @endphp
                    <button type="button" onclick="openLightbox('{{ $item['src'] }}', '{{ $item['package'] }}', '{{ $item['price'] }}', {{ $isVideo ? 'true' : 'false' }})" class="relative h-[16rem] md:h-[28rem] w-[16rem] md:w-[28rem] shrink-0 overflow-hidden rounded-2xl bg-memphis-blue-soft cursor-zoom-in group">
                        @if($isVideo)
                            <video src="{{ $item['src'] }}" autoplay loop muted playsinline class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"></video>
                        @else
                            <img src="{{ $item['src'] }}" alt="{{ $item['package'] }} {{ $i + 1 }}" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <!-- WHERE WE SERVE + HOW IT WORKS -->
    <section class="bg-memphis-blue-soft/40 py-16 md:py-24 overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 md:px-12">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 md:gap-16">
                <!-- WHERE -->
                <div class="reveal-up">
                    <span class="inline-block bg-memphis-blue text-white px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest mb-4">
                        📍 Area Layanan
                    </span>
                    <h2 class="font-display text-2xl md:text-4xl tracking-tight text-memphis-ink leading-tight mb-4">
                        Kami Ada di <span class="text-memphis-blue">Jawa Timur.</span>
                    </h2>
                    <p class="text-sm text-muted-foreground leading-relaxed mb-6">
                        Studio utama kami berada di Lumajang, dan layanan photobooth siap hadir di berbagai kota.
                    </p>

                    <div class="space-y-4">
                        <!-- Main Location -->
                        <div class="bg-white rounded-2xl p-5 border border-border/40">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="h-12 w-12 rounded-xl bg-memphis-blue flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-memphis-ink text-sm">Studio Utama — Lumajang</h4>
                                    <p class="text-xs text-muted-foreground">Self photo studio & walk-in service</p>
                                </div>
                            </div>
                            
                            <!-- Google Maps Embed -->
                            <div class="w-full h-32 md:h-40 rounded-xl overflow-hidden relative group border border-border/50 mt-4">
                                <iframe 
                                    src="https://maps.google.com/maps?q=Jl.%20DR.%20Sutomo%2C%20Tompokersan%2C%20Kec.%20Lumajang%2C%20Kabupaten%20Lumajang%2C%20Jawa%20Timur%2067316&t=&z=15&ie=UTF8&iwloc=&output=embed" 
                                    width="100%" 
                                    height="100%" 
                                    style="border:0;" 
                                    allowfullscreen="" 
                                    loading="lazy" 
                                    referrerpolicy="no-referrer-when-downgrade"
                                    class="absolute inset-0 grayscale contrast-125 opacity-90 group-hover:grayscale-0 transition-all duration-500">
                                </iframe>
                                <!-- Hover Overlay Button -->
                                <a href="https://maps.app.goo.gl/6ZBauRS5NTHA6PoeA" target="_blank" class="absolute inset-0 z-10 flex items-center justify-center bg-memphis-ink/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <span class="bg-white text-memphis-ink text-xs font-bold px-4 py-2 rounded-full shadow-lg transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                                        Buka di Google Maps ↗
                                    </span>
                                </a>
                            </div>
                        </div>

                        <!-- Service Areas -->
                        <div class="flex flex-wrap gap-3">
                            @foreach(['Malang', 'Surabaya', 'Jember', 'Jawa Timur'] as $i => $area)
                                <div class="area-badge bg-white rounded-full px-5 py-2.5 border border-border/40 flex items-center gap-2 cursor-default">
                                    <span class="h-2 w-2 rounded-full {{ $i === 3 ? 'bg-memphis-orange' : 'bg-memphis-blue' }}"></span>
                                    <span class="text-sm font-semibold text-memphis-ink">{{ $area }}</span>
                                    @if($i === 3)
                                        <span class="text-[10px] text-memphis-orange font-bold">& sekitarnya</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <!-- WHEN -->
                        <div class="bg-white rounded-2xl p-5 border border-border/40 flex items-center gap-4 mt-1">
                            <div class="h-12 w-12 rounded-xl bg-memphis-yellow flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-memphis-ink" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-memphis-ink text-sm">Buka Setiap Hari</h4>
                                <p class="text-xs text-muted-foreground">09.00 – 21.00 WIB · Walk-in & Online Booking</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HOW IT WORKS -->
                <div class="reveal-up" style="transition-delay: 0.15s;">
                    <span class="inline-block bg-memphis-orange text-white px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest mb-4">
                        🚀 Cara Booking
                    </span>
                    <h2 class="font-display text-2xl md:text-4xl tracking-tight text-memphis-ink leading-tight mb-6">
                        Semudah <span class="text-memphis-orange">1-2-3.</span>
                    </h2>

                    <div class="space-y-4">
                        @php
                            $steps = [
                                ['num' => '1', 'title' => 'Pilih Layanan', 'desc' => 'Pilih paket studio atau event photobooth yang kamu mau.', 'color' => 'bg-memphis-yellow', 'text' => 'text-memphis-ink'],
                                ['num' => '2', 'title' => 'Booking & Pilih Konsep', 'desc' => 'Booking jadwal online, pilih konsep dan aksesoris favoritmu.', 'color' => 'bg-memphis-blue', 'text' => 'text-white'],
                                ['num' => '3', 'title' => 'Datang & Foto!', 'desc' => 'Nikmati sesi foto seru, private, dan tanpa tekanan.', 'color' => 'bg-memphis-orange', 'text' => 'text-white'],
                                ['num' => '4', 'title' => 'Dapat Hasil Foto', 'desc' => 'Dapatkan hasil foto high quality dan kenangan tak terlupakan.', 'color' => 'bg-memphis-ink', 'text' => 'text-white'],
                            ];
                        @endphp

                        @foreach($steps as $step)
                            <div class="bg-white rounded-2xl p-5 border border-border/40 flex items-start gap-4 hover:shadow-md transition-shadow duration-300">
                                <span class="h-10 w-10 rounded-xl {{ $step['color'] }} {{ $step['text'] }} flex items-center justify-center font-bold text-sm shrink-0">
                                    {{ $step['num'] }}
                                </span>
                                <div>
                                    <h4 class="font-bold text-memphis-ink text-sm">{{ $step['title'] }}</h4>
                                    <p class="text-xs text-muted-foreground mt-1 leading-relaxed">{{ $step['desc'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <a href="#pricelist" class="mt-6 flex w-full items-center justify-center bg-memphis-ink py-3.5 rounded-full text-sm font-bold text-white hover:bg-memphis-blue hover:shadow-xl hover:-translate-y-1 transition-all duration-300 active:scale-95">
                        Lihat Pricelist ↓
                    </a>
                </div>
            </div>
        </div>
    </section>


    <!-- PRICELIST -->
    <section id="pricelist" class="bg-memphis-blue-soft/60 py-16 md:py-24 scroll-mt-24">
        <div class="max-w-7xl mx-auto px-6 md:px-12">
            <div class="text-center mb-10 md:mb-14">
                <h2 class="font-display text-3xl md:text-5xl tracking-tight">
                    <span class="text-memphis-blue font-bold">PRICELIST</span>
                    <span class="text-memphis-orange font-bold">READY TO PICT</span>
                </h2>
                <p class="mt-3 text-sm text-muted-foreground max-w-md mx-auto">
                    Pilih studio dan konsep yang sesuai dengan gayamu. Semua paket sudah termasuk akses ke semua soft file.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($pricing as $tier)
                    <div class="relative bg-card rounded-2xl p-8 shadow-lg border border-border/50 overflow-hidden transition-all duration-300 ease-out hover:scale-[1.06] hover:shadow-2xl hover:border-memphis-blue/40 hover:z-10">
                        <div class="absolute -top-6 -right-6 h-20 w-20 rounded-full {{ $tier['accent'] }}"></div>
                        @if($tier['badge'])
                            <span class="inline-block bg-memphis-yellow text-memphis-ink text-[10px] font-bold uppercase tracking-widest px-3 py-1 rounded-full mb-3">
                                {{ $tier['badge'] }}
                            </span>
                        @endif
                        <h3 class="text-sm font-bold text-memphis-blue tracking-wider">
                            {{ $tier['name'] }}
                        </h3>
                        <div class="mt-2 flex flex-col">
                            @if(isset($tier['original_price']))
                                <span class="text-sm font-semibold text-muted-foreground line-through decoration-memphis-orange decoration-2">{{ $tier['original_price'] }}</span>
                            @endif
                            <div class="flex items-baseline gap-1">
                                <span class="text-5xl font-bold text-memphis-ink">{{ $tier['price'] }}</span>
                                <span class="text-sm text-muted-foreground">/sesi</span>
                            </div>
                        </div>
                        <ul class="mt-6 space-y-2.5 text-sm">
                            @foreach($tier['features'] as $f)
                                <li class="flex items-center gap-2 text-foreground/80">
                                    <span class="h-1.5 w-1.5 rounded-full bg-memphis-blue"></span>
                                    {{ $f }}
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route('booking.customer', ['package' => $tier['id']]) }}" class="mt-8 flex w-full items-center justify-center bg-memphis-ink py-3 rounded-full text-sm font-bold text-white hover:bg-memphis-blue hover:shadow-xl hover:-translate-y-1 hover:scale-[1.02] transition-all duration-300 active:scale-95">
                            Pilih Paket
                        </a>
                    </div>
                @endforeach
            </div>

        </div>
    </section>

    <!-- CONTACT -->
    <section id="contact" class="bg-background py-16 md:py-24 scroll-mt-24">
        <div class="max-w-7xl mx-auto px-6 md:px-12 grid grid-cols-1 md:grid-cols-2 gap-12 md:gap-16">
            <div class="space-y-6 text-center md:text-left">
                <span class="inline-block bg-memphis-yellow text-memphis-ink px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest">
                    Hubungi Kami
                </span>
                <h2 class="font-display text-3xl md:text-5xl tracking-tight text-memphis-ink leading-tight">
                    Punya pertanyaan? <span class="text-memphis-blue block md:inline">Yuk ngobrol.</span>
                </h2>
                <p class="text-base text-muted-foreground leading-relaxed max-w-md">
                    Tim kami siap menjawab pertanyaanmu seputar paket, konsep, ataupun jadwal booking. Hubungi kami lewat kanal di bawah ini.
                </p>
            </div>

            <div class="bg-memphis-blue-soft/60 rounded-3xl p-6 md:p-10 border border-border/40 flex flex-col items-center justify-center text-center space-y-6">
                <div class="h-16 w-16 md:h-20 md:w-20 rounded-full bg-[#25D366] text-white flex items-center justify-center shadow-lg shadow-[#25D366]/30">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-8 w-8 md:h-10 md:w-10" aria-hidden="true">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                </div>
                <div class="space-y-2 px-2">
                    <h3 class="font-display text-2xl md:text-3xl text-memphis-ink">Chat via WhatsApp</h3>
                    <p class="text-sm text-muted-foreground max-w-xs mx-auto">
                        Klik tombol di bawah buat langsung ngobrol sama admin kami. Cepet, gampang, no ribet.
                    </p>
                </div>
                <a href="https://wa.me/6281234567890?text=Halo%20admin%20Ready%20to%20Pict%2C%20saya%20mau%20tanya-tanya%20soal%20paket%20foto." target="_blank" rel="noopener noreferrer" class="w-[90%] md:w-full bg-[#25D366] text-white py-3 md:py-4 rounded-full text-sm font-bold hover:bg-[#1ebe57] transition-all shadow-xl shadow-[#25D366]/30 hover:-translate-y-0.5 flex items-center justify-center gap-3 mx-auto">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    Hubungi Admin via WhatsApp
                </a>
                <p class="text-xs text-muted-foreground">Online setiap hari · 09.00 – 21.00 WIB</p>
            </div>
        </div>
    </section>

    <!-- LIGHTBOX -->
    <div id="lightbox" class="fixed inset-0 z-[100] bg-memphis-ink/95 backdrop-blur-sm hidden items-center justify-center p-8 transition-opacity duration-200 opacity-0" onclick="closeLightbox()" role="dialog" aria-modal="true">
        <button type="button" onclick="event.stopPropagation(); closeLightbox();" class="absolute top-6 right-6 h-12 w-12 rounded-full bg-memphis-yellow text-memphis-ink flex items-center justify-center hover:bg-memphis-orange hover:text-primary-foreground transition-colors shadow-lg" aria-label="Tutup">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x h-6 w-6"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
        <div class="relative max-w-5xl w-full flex flex-col items-center gap-4 md:gap-6" onclick="event.stopPropagation()">
            <img id="lightbox-img" src="" alt="" class="max-h-[60vh] md:max-h-[70vh] w-auto rounded-2xl shadow-2xl object-contain" />
            <video id="lightbox-video" autoplay loop muted playsinline class="hidden max-h-[60vh] md:max-h-[70vh] w-auto rounded-2xl shadow-2xl object-contain"></video>
            <div class="text-center space-y-2">
                <span class="inline-block bg-memphis-yellow text-memphis-ink px-4 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-widest">
                    Paket
                </span>
                <h3 id="lightbox-package" class="font-display text-2xl md:text-3xl font-bold text-primary-foreground tracking-tight"></h3>
                <p id="lightbox-price" class="text-memphis-yellow font-semibold text-base md:text-lg mb-2"></p>
                <div class="pt-2">
                    <a href="{{ route('booking.customer') }}" class="inline-flex items-center justify-center bg-memphis-blue text-white px-8 py-3.5 rounded-full font-bold text-sm hover:bg-white hover:text-memphis-ink hover:shadow-xl transition-all duration-300 active:scale-95 shadow-memphis border-2 border-transparent hover:border-memphis-ink">
                        Booking Sekarang →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <footer class="py-8 text-center text-xs text-muted-foreground bg-memphis-blue-soft/30 border-t border-border/20">
        © {{ date('Y') }} {{ $brandName }} All Rights Reserved.
    </footer>

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const navMenu = document.getElementById('nav-menu');
        
        if (mobileMenuBtn && navMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                navMenu.classList.toggle('hidden');
                navMenu.classList.toggle('flex');
            });

            // Close menu when a link is clicked
            const navLinks = navMenu.querySelectorAll('a');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 768) {
                        navMenu.classList.add('hidden');
                        navMenu.classList.remove('flex');
                    }
                });
            });
        }

        function openLightbox(src, pkg, price, isVideo = false) {
            const imgContainer = document.getElementById('lightbox-img');
            const videoContainer = document.getElementById('lightbox-video');
            
            if (isVideo) {
                imgContainer.classList.add('hidden');
                videoContainer.classList.remove('hidden');
                videoContainer.src = src;
                videoContainer.play();
            } else {
                videoContainer.classList.add('hidden');
                videoContainer.pause();
                imgContainer.classList.remove('hidden');
                imgContainer.src = src;
            }

            document.getElementById('lightbox-package').textContent = pkg;
            document.getElementById('lightbox-price').textContent = price;
            
            const lb = document.getElementById('lightbox');
            lb.classList.remove('hidden');
            lb.classList.add('flex');
            
            // tiny delay for fade in effect
            setTimeout(() => {
                lb.classList.remove('opacity-0');
            }, 10);
            
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            const lb = document.getElementById('lightbox');
            lb.classList.add('opacity-0');
            
            const videoContainer = document.getElementById('lightbox-video');
            videoContainer.pause();

            setTimeout(() => {
                lb.classList.add('hidden');
                lb.classList.remove('flex');
                document.body.style.overflow = '';
            }, 200);
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('lightbox').classList.contains('hidden')) {
                closeLightbox();
            }
        });

        // Scroll Reveal Animation
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

        document.querySelectorAll('.reveal-up').forEach(el => {
            revealObserver.observe(el);
        });

        // Marquee: auto-scroll + drag-to-slide
        document.querySelectorAll('.marquee-container').forEach(container => {
            const track = container.querySelector('.marquee-track');
            if (!track) return;

            const speed = parseFloat(container.dataset.speed) || 1;
            const dir = container.dataset.direction === 'right' ? -1 : 1;
            let pos = dir === -1 ? track.scrollWidth / 2 : 0;
            let isDragging = false;
            let startX = 0;
            let dragStartPos = 0;
            let dragDelta = 0;
            let isPaused = false;
            let rafId = null;

            function loop() {
                if (!isDragging && !isPaused) {
                    pos += speed * dir;
                }
                const half = track.scrollWidth / 2;
                if (pos >= half) pos -= half;
                if (pos < 0) pos += half;
                track.style.transform = `translate3d(${-pos}px, 0, 0)`;
                rafId = requestAnimationFrame(loop);
            }

            // Start drag
            function onDown(e) {
                isDragging = true;
                dragDelta = 0;
                container.classList.add('is-dragging');
                startX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                dragStartPos = pos;
            }
            // During drag
            function onMove(e) {
                if (!isDragging) return;
                const x = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                dragDelta = x - startX;
                pos = dragStartPos - dragDelta;
            }
            // End drag
            function onUp() {
                if (!isDragging) return;
                isDragging = false;
                // Remove dragging class after a tick so onclick can check it
                setTimeout(() => container.classList.remove('is-dragging'), 50);
            }

            // Mouse events
            container.addEventListener('mousedown', onDown);
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup', onUp);
            // Touch events
            container.addEventListener('touchstart', onDown, { passive: true });
            window.addEventListener('touchmove', onMove, { passive: true });
            window.addEventListener('touchend', onUp);

            // Start auto-scroll
            loop();
        });
    </script>
</body>
</html>
