@php
$navItems = [
    ['label' => 'Home', 'href' => '#home'],
    ['label' => 'About', 'href' => '#about'],
    ['label' => 'Pricelist', 'href' => '#pricelist'],
    ['label' => 'Contact', 'href' => '#contact'],
    ['label' => 'Booking', 'href' => '#pricelist'],
];

$marqueeRowA = [
    ['src' => asset('images/landing/Basic/IMG_0394.JPG'), 'package' => 'BASIC', 'price' => '35k / sesi'],
    ['src' => asset('images/landing/Basic/IMG_0879.JPG'), 'package' => 'BASIC', 'price' => '35k / sesi'],
    ['src' => asset('images/landing/Basic/IMG_9825.JPG'), 'package' => 'BASIC', 'price' => '35k / sesi'],
    ['src' => asset('images/landing/Basic/IMG_0394.JPG'), 'package' => 'BASIC', 'price' => '35k / sesi'],
];

$marqueeRowB = [
    ['src' => asset('images/landing/manbol/IMG_0244.JPG'), 'package' => 'MANDI BOLA / VINTAGE', 'price' => '45k / sesi'],
    ['src' => asset('images/landing/manbol/IMG_2968.JPG'), 'package' => 'MANDI BOLA / VINTAGE', 'price' => '45k / sesi'],
    ['src' => asset('images/landing/Vintage/IMG_0032.JPG'), 'package' => 'MANDI BOLA / VINTAGE', 'price' => '45k / sesi'],
    ['src' => asset('images/landing/Vintage/IMG_3356.JPG'), 'package' => 'MANDI BOLA / VINTAGE', 'price' => '45k / sesi'],
];

$marqueeRowC = [
    ['src' => asset('images/landing/Mini/IMG_1228.JPG'), 'package' => 'MINIMARKET / SOFA', 'price' => '50k / sesi'],
    ['src' => asset('images/landing/Mini/IMG_1254.JPG'), 'package' => 'MINIMARKET / SOFA', 'price' => '50k / sesi'],
    ['src' => asset('images/landing/Sofa/IMG_0350.JPG'), 'package' => 'MINIMARKET / SOFA', 'price' => '50k / sesi'],
    ['src' => asset('images/landing/Sofa/IMG_0689.JPG'), 'package' => 'MINIMARKET / SOFA', 'price' => '50k / sesi'],
];

$pricing = [
    [
        'name' => 'BASIC',
        'price' => '35k',
        'accent' => 'bg-memphis-yellow',
        'badge' => 'Promo',
        'features' => ['1–2 Orang', '10 Menit', '1 Cetak 4R', 'Free All Soft File'],
    ],
    [
        'name' => 'MANDI BOLA / VINTAGE',
        'price' => '45k',
        'accent' => 'bg-memphis-blue',
        'badge' => 'Terlaris',
        'features' => ['1–2 Orang', '10 Menit', '1 Cetak 4R', 'Free All Soft File'],
    ],
    [
        'name' => 'MINIMARKET / SOFA',
        'price' => '50k',
        'accent' => 'bg-memphis-orange',
        'badge' => 'Promo',
        'features' => ['1–2 Orang', '10 Menit', '1 Cetak 4R', 'Free All Soft File'],
    ],
];

$addons = [
    ['label' => '+1 Orang (include cetak 1 4R)', 'price' => '15k'],
    ['label' => '+1 Cetak 4R', 'price' => '15k'],
    ['label' => '+5 Menit Durasi Foto', 'price' => '20k'],
    ['label' => 'Sewa 1 Kostum', 'price' => '10k'],
];
@endphp

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ready to Pict — Studio Foto Mandiri Premium</title>
    <meta name="description" content="Ready to Pict — studio foto self portrait premium. Bebaskan ekspresimu, kamu yang memegang kendali. Tanpa fotografer, tanpa tekanan.">
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
        .border-memphis-blue { border-color: var(--memphis-blue) !important; }
        .border-memphis-yellow { border-color: var(--memphis-yellow) !important; }

        .font-display { font-family: ui-sans-serif, system-ui, sans-serif; }
        
        .animate-marquee-left { 
            animation: marquee-left 30s linear infinite; 
            will-change: transform;
        }
        .animate-marquee-right { 
            animation: marquee-right 30s linear infinite; 
            will-change: transform;
        }
        
        @keyframes marquee-left {
            0% { transform: translate3d(0, 0, 0); }
            100% { transform: translate3d(-50%, 0, 0); }
        }
        @keyframes marquee-right {
            0% { transform: translate3d(-50%, 0, 0); }
            100% { transform: translate3d(0, 0, 0); }
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
    </style>
</head>
<body class="min-h-screen bg-background text-foreground overflow-x-hidden">
    
    <!-- HEADER -->
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md shadow-sm">
        <div class="max-w-7xl mx-auto flex items-center justify-between px-6 md:px-12 py-4 md:py-6">
            <a href="#home" class="flex items-center gap-2 md:gap-3">
                <img src="{{ asset('images/logo/logo.png') }}" alt="Ready to Pict" class="h-8 md:h-12 w-auto" />
                <span class="font-display text-lg md:text-2xl font-semibold tracking-tight text-memphis-blue">
                    Ready to Pict
                </span>
            </a>
            
            <!-- Mobile Menu Toggle -->
            <button id="mobile-menu-btn" class="md:hidden text-memphis-ink focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu"><line x1="3" x2="21" y1="6" y2="6"/><line x1="3" x2="21" y1="12" y2="12"/><line x1="3" x2="21" y1="18" y2="18"/></svg>
            </button>

            <nav id="nav-menu" class="hidden md:flex items-center gap-8 text-[15px] font-medium text-memphis-ink absolute md:relative top-16 md:top-0 left-0 w-full md:w-auto bg-white md:bg-transparent px-6 py-8 md:p-0 flex-col md:flex-row shadow-xl md:shadow-none transition-all duration-300 z-[60]">
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
    <section id="home" class="bg-background py-6 space-y-4 scroll-mt-24 overflow-hidden">
        <!-- Row A -->
        <div class="relative overflow-hidden">
            <div class="flex w-max animate-marquee-left gap-4">
                @foreach(array_merge($marqueeRowA, $marqueeRowA) as $i => $item)
                    <button type="button" onclick="openLightbox('{{ $item['src'] }}', '{{ $item['package'] }}', '{{ $item['price'] }}')" class="relative h-[16rem] md:h-[28rem] w-[16rem] md:w-[28rem] shrink-0 overflow-hidden rounded-2xl bg-memphis-blue-soft cursor-zoom-in group">
                        <img src="{{ $item['src'] }}" alt="{{ $item['package'] }} {{ $i + 1 }}" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Row B -->
        <div class="relative overflow-hidden">
            <div class="flex w-max animate-marquee-right gap-4">
                @foreach(array_merge($marqueeRowB, $marqueeRowB) as $i => $item)
                    <button type="button" onclick="openLightbox('{{ $item['src'] }}', '{{ $item['package'] }}', '{{ $item['price'] }}')" class="relative h-[16rem] md:h-[28rem] w-[16rem] md:w-[28rem] shrink-0 overflow-hidden rounded-2xl bg-memphis-blue-soft cursor-zoom-in group">
                        <img src="{{ $item['src'] }}" alt="{{ $item['package'] }} {{ $i + 1 }}" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Row C -->
        <div class="relative overflow-hidden">
            <div class="flex w-max animate-marquee-left gap-4">
                @foreach(array_merge($marqueeRowC, $marqueeRowC) as $i => $item)
                    <button type="button" onclick="openLightbox('{{ $item['src'] }}', '{{ $item['package'] }}', '{{ $item['price'] }}')" class="relative h-[16rem] md:h-[28rem] w-[16rem] md:w-[28rem] shrink-0 overflow-hidden rounded-2xl bg-memphis-blue-soft cursor-zoom-in group">
                        <img src="{{ $item['src'] }}" alt="{{ $item['package'] }} {{ $i + 1 }}" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ABOUT US -->
    <section id="about" class="bg-background py-16 md:py-24 scroll-mt-24">
        <div class="max-w-7xl mx-auto px-6 md:px-12">
            <div class="bg-memphis-cream border-2 border-dashed border-memphis-blue rounded-3xl p-8 md:p-12 relative overflow-hidden flex flex-col items-center text-center">
                <!-- Decorative element -->
                <div class="absolute -top-12 -right-12 h-40 w-40 rounded-full bg-memphis-yellow mix-blend-multiply opacity-50"></div>
                <div class="absolute -bottom-12 -left-12 h-40 w-40 rounded-full bg-memphis-blue mix-blend-multiply opacity-30"></div>
                
                <div class="relative z-10 max-w-3xl space-y-6">
                    <span class="inline-block bg-memphis-orange text-white px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest">
                        Kenalan Yuk!
                    </span>
                    <h2 class="font-display text-3xl md:text-5xl tracking-tight text-memphis-ink leading-tight">
                        Kamu yang jadi <span class="text-memphis-blue">Sutradara.</span>
                    </h2>
                    <p class="text-base md:text-lg text-muted-foreground leading-relaxed">
                        Ready to Pict adalah studio foto self-portrait premium di mana kamu bebas berekspresi tanpa rasa canggung. Nggak ada fotografer yang ngarahin gaya—kamu yang pegang remote, atur timing, dan ciptakan karya terbaik versimu sendiri.
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-6 pt-6">
                        <div class="flex items-center gap-3 bg-white px-5 py-3 rounded-full border border-border shadow-sm">
                            <span class="h-8 w-8 rounded-full bg-memphis-yellow flex items-center justify-center font-bold text-sm text-memphis-ink">1</span>
                            <span class="text-sm font-semibold text-memphis-ink">Private & Bebas Canggung</span>
                        </div>
                        <div class="flex items-center gap-3 bg-white px-5 py-3 rounded-full border border-border shadow-sm">
                            <span class="h-8 w-8 rounded-full bg-memphis-blue text-white flex items-center justify-center font-bold text-sm">2</span>
                            <span class="text-sm font-semibold text-memphis-ink">Konsep Studio Unik</span>
                        </div>
                        <div class="flex items-center gap-3 bg-white px-5 py-3 rounded-full border border-border shadow-sm">
                            <span class="h-8 w-8 rounded-full bg-memphis-orange text-white flex items-center justify-center font-bold text-sm">3</span>
                            <span class="text-sm font-semibold text-memphis-ink">Cetak High Quality</span>
                        </div>
                    </div>
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
                        <button class="mt-8 w-full bg-memphis-ink text-white py-3 rounded-full text-sm font-bold hover:bg-memphis-blue hover:shadow-xl hover:-translate-y-1 hover:scale-[1.02] transition-all duration-300 active:scale-95">
                            Pilih Paket
                        </button>
                    </div>
                @endforeach
            </div>

            <!-- ADD ON -->
            <div class="mt-10 bg-memphis-cream border-2 border-dashed border-memphis-yellow rounded-2xl p-6">
                <p class="text-center text-sm font-bold uppercase tracking-widest text-memphis-blue mb-5">
                    Add On (Tambahan)
                </p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($addons as $a)
                        <div class="flex items-center justify-between bg-background rounded-full px-4 md:px-5 py-3 shadow-sm">
                            <span class="text-[10px] md:text-xs font-medium text-foreground/80 leading-tight">
                                {{ $a['label'] }}
                            </span>
                            <span class="text-xs md:text-sm font-bold text-memphis-blue shrink-0">
                                {{ $a['price'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
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
                <div class="flex flex-col items-center md:items-start gap-4 pt-6 px-4">
                    <div class="flex items-center gap-4 w-full max-w-[280px] md:max-w-none">
                        <div class="h-10 w-10 md:h-12 md:w-12 rounded-full bg-memphis-blue text-white flex items-center justify-center font-bold shrink-0 shadow-sm">📍</div>
                        <div class="text-left">
                            <p class="text-[10px] uppercase tracking-widest text-muted-foreground font-bold">Lokasi</p>
                            <p class="text-sm font-semibold text-memphis-ink">Jl. Studio Foto No. 12, Jakarta</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 w-full max-w-[280px] md:max-w-none">
                        <div class="h-10 w-10 md:h-12 md:w-12 rounded-full bg-memphis-yellow text-memphis-ink flex items-center justify-center font-bold shrink-0 shadow-sm">📱</div>
                        <div class="text-left">
                            <p class="text-[10px] uppercase tracking-widest text-muted-foreground font-bold">WhatsApp</p>
                            <p class="text-sm font-semibold text-memphis-ink">+62 812 3456 7890</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 w-full max-w-[280px] md:max-w-none">
                        <div class="h-10 w-10 md:h-12 md:w-12 rounded-full bg-memphis-orange text-white flex items-center justify-center font-bold shrink-0 shadow-sm">✉️</div>
                        <div class="text-left">
                            <p class="text-[10px] uppercase tracking-widest text-muted-foreground font-bold">Email</p>
                            <p class="text-sm font-semibold text-memphis-ink text-wrap break-all">hello@readytopict.com</p>
                        </div>
                    </div>
                </div>
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
        <div class="relative max-w-5xl w-full flex flex-col items-center gap-6" onclick="event.stopPropagation()">
            <img id="lightbox-img" src="" alt="" class="max-h-[80vh] w-auto rounded-2xl shadow-2xl object-contain" />
            <div class="text-center space-y-2">
                <span class="inline-block bg-memphis-yellow text-memphis-ink px-4 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-widest">
                    Paket
                </span>
                <h3 id="lightbox-package" class="font-display text-3xl font-bold text-primary-foreground tracking-tight"></h3>
                <p id="lightbox-price" class="text-memphis-yellow font-semibold text-lg"></p>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="bg-background">
        <div class="max-w-7xl mx-auto px-6 md:px-12 py-12 flex flex-col md:flex-row justify-between gap-10">
            <div class="text-center md:text-left">
                <h3 class="font-display text-2xl text-memphis-blue font-semibold">
                    Ready to Pict
                </h3>
                <p class="mt-3 text-sm text-muted-foreground max-w-sm mx-auto md:mx-0 leading-relaxed">
                    Foto self portrait mandiri, di mana kamu adalah sang sutradara, aktor, sekaligus penontonnya.
                </p>
            </div>
            <div class="flex flex-wrap items-center justify-center md:justify-end gap-x-8 gap-y-4 text-sm font-bold text-memphis-ink/80">
                <a href="#home" class="hover:text-memphis-blue transition-colors">Home</a>
                <a href="#about" class="hover:text-memphis-blue transition-colors">About</a>
                <a href="#pricelist" class="hover:text-memphis-blue transition-colors">Pricelist</a>
                <a href="#contact" class="hover:text-memphis-blue transition-colors">Contact</a>
            </div>
        </div>
        <div class="py-5 text-center text-xs text-muted-foreground">
            © {{ date('Y') }} Ready to Pict. All rights reserved.
        </div>
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

        function openLightbox(src, pkg, price) {
            document.getElementById('lightbox-img').src = src;
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
    </script>
</body>
</html>