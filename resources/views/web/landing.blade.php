@extends('layouts.public')

@section('main-content')
    <nav class="sticky top-0 z-50 w-full bg-white/90 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
            <span class="text-xl font-extrabold tracking-tight text-blue-700">Ready to Pict</span>
            <div class="hidden md:flex items-center gap-10 text-[11px] font-bold text-gray-400 uppercase tracking-widest">
                <a href="#studio" class="text-blue-700">Utama</a>
                <a href="#harga" class="hover:text-blue-700">Harga</a>
                <a href="#proses" class="hover:text-blue-700">Proses</a>
                <a href="#galeri" class="hover:text-blue-700">Galeri</a>
            </div>
            <a href="{{ route('booking.create') }}"
                class="rounded-full bg-[#005bb7] px-7 py-3 text-[11px] font-bold text-white shadow-lg uppercase transition hover:bg-blue-800">
                Booking Sekarang
            </a>
        </div>
    </nav>

    <section id="studio" class="max-w-7xl mx-auto px-6 pt-10 pb-20 grid lg:grid-cols-2 gap-8 items-center">
        <div>
            <div
                class="inline-block rounded-md bg-[#e3b23c] px-3 py-1 text-[9px] font-bold tracking-widest text-white uppercase mb-6">
                100% PRIVATE STUDIO FOTO
            </div>
            <h1 class="text-5xl md:text-7xl font-extrabold text-[#001a3d] leading-[1.1] mb-6">
                Ekspresi Diri,<br><span class="text-[#8b6b23]">Definisi Baru.</span>
            </h1>
            <p class="text-gray-500 text-sm max-w-md mb-8 leading-relaxed">
                Bebaskan kreativitasmu di studio kelas profesional di mana kamu memegang kendali. Tanpa fotografer, tanpa
                tekanan—hanya kamu dan momen terbaikmu.
            </p>
            <div class="flex gap-4">
                <button class="rounded-full bg-[#005bb7] px-8 py-4 text-xs font-bold text-white shadow-xl">Booking
                    Sekarang</button>
                <button class="rounded-full bg-white border border-gray-200 px-8 py-4 text-xs font-bold text-gray-500">Lihat
                    Paket</button>
            </div>
        </div>
        <div class="relative">
            <div class="rounded-[3rem] overflow-hidden shadow-2xl aspect-square bg-[#001021]">
                <img src="https://images.unsplash.com/photo-1516035069371-29a1b244cc32?q=80&w=1200"
                    class="w-full h-full object-cover opacity-80">
            </div>
            <div
                class="absolute -bottom-4 -left-4 w-28 h-28 bg-[#a83a0f] rounded-full flex items-center justify-center p-4 text-center shadow-2xl">
                <p class="text-white text-[9px] font-bold uppercase leading-tight">Ready To Pict, Your Studio.</p>
            </div>
        </div>
    </section>

    <section id="harga" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="mb-12">
                <h3 class="text-blue-500 font-bold text-xl uppercase tracking-tighter">Pricelist</h3>
                <h2 class="text-3xl font-black text-[#8b6b23] uppercase">Ready To Pict</h2>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                @foreach($packages as $package)
                    <div
                        class="bg-white rounded-[2.5rem] p-10 border border-gray-50 shadow-[0_10px_40px_rgba(0,0,0,0.04)] relative">
                        <div class="absolute top-0 right-10 w-12 h-6 bg-[#e3b23c] rounded-b-xl"></div>

                        <h4 class="text-[#005bb7] font-extrabold text-sm uppercase mb-1">{{ $package->name }}</h4>
                        <div class="text-3xl font-black text-[#001a3d] mb-6">
                            {{ number_format($package->base_price / 1000, 0) }}k <span
                                class="text-xs font-medium text-gray-400">/ Sesi</span>
                        </div>

                        <ul class="space-y-4 text-xs font-bold text-gray-500 mb-8 uppercase tracking-wide">
                            <li class="flex items-center gap-3">👥 1-2 Orang</li>
                            <li class="flex items-center gap-3">⏱️ {{ $package->duration_minutes }} Menit</li>
                            <li class="flex items-center gap-3">🖼️ 1 Cetak 4R</li>
                            <li class="flex items-center gap-3">☁️ Free All Soft File</li>
                        </ul>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 bg-[#f9f3d9] rounded-2xl p-6 border border-[#e3b23c]/30">
                <p class="text-center text-[10px] font-black text-[#8b6b23] uppercase tracking-[0.2em] mb-4">Add On
                    (Tambahan)</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-full px-5 py-2 flex justify-between text-[10px] font-bold">
                        <span>+1 Orang (+Cetak)</span> <span class="text-blue-600">15k</span>
                    </div>
                    <div class="bg-white rounded-full px-5 py-2 flex justify-between text-[10px] font-bold">
                        <span>+1 Cetak 4R</span> <span class="text-blue-600">15k</span>
                    </div>
                    <div class="bg-white rounded-full px-5 py-2 flex justify-between text-[10px] font-bold">
                        <span>+5 Menit Durasi</span> <span class="text-blue-600 text-right">20k</span>
                    </div>
                    <div class="bg-white rounded-full px-5 py-2 flex justify-between text-[10px] font-bold">
                        <span>Sewa 1 Kostum</span> <span class="text-blue-600">10k</span>
                    </div>
                </div>
            </div>

            <div class="text-center mt-12">
                <button
                    class="bg-[#005bb7] text-white px-10 py-3 rounded-xl font-bold text-xs shadow-xl shadow-blue-200">Booking
                    Sekarang</button>
            </div>
        </div>
    </section>

    <section id="proses" class="py-24 bg-gray-50/50">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <h2 class="text-3xl font-black text-[#005bb7] uppercase mb-16 tracking-tight">Tiga Langkah Menuju Keajaiban</h2>
            <div class="grid md:grid-cols-3 gap-12 relative">
                <div
                    class="hidden md:block absolute top-10 left-0 w-full h-[1px] border-t border-dashed border-gray-300 -z-0">
                </div>

                <div class="relative z-10 flex flex-col items-center">
                    <div
                        class="w-20 h-20 bg-[#005bb7] rounded-full flex items-center justify-center text-white text-2xl font-bold mb-6 shadow-xl">
                        1</div>
                    <h4 class="font-bold text-[#001a3d] mb-2 uppercase text-sm">Booking</h4>
                    <p class="text-[10px] text-gray-400 max-w-[150px] leading-relaxed uppercase">Pesan slotmu secara online
                        dan pilih gaya estetika yang kamu inginkan.</p>
                </div>
                <div class="relative z-10 flex flex-col items-center">
                    <div
                        class="w-20 h-20 bg-[#e3b23c] rounded-full flex items-center justify-center text-[#001a3d] text-2xl font-bold mb-6 shadow-xl">
                        2</div>
                    <h4 class="font-bold text-[#001a3d] mb-2 uppercase text-sm">Potret</h4>
                    <p class="text-[10px] text-gray-400 max-w-[150px] leading-relaxed uppercase">Datang ke studio dan mulai
                        memotret dengan remote nirkabel kami.</p>
                </div>
                <div class="relative z-10 flex flex-col items-center">
                    <div
                        class="w-20 h-20 bg-[#a83a0f] rounded-full flex items-center justify-center text-white text-2xl font-bold mb-6 shadow-xl">
                        3</div>
                    <h4 class="font-bold text-[#001a3d] mb-2 uppercase text-sm">Cetak</h4>
                    <p class="text-[10px] text-gray-400 max-w-[150px] leading-relaxed uppercase">Terima semua file digital
                        secara instan dan pilih cetakan favoritmu.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="galeri" class="py-24">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-black text-[#005bb7] uppercase tracking-tight">Momen Terbaik di Ready To Pict</h2>
                <p class="text-xs text-gray-400 mt-2 uppercase tracking-widest">Hasil karya nyata dari komunitas kami.</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="space-y-4">
                    <div class="rounded-3xl overflow-hidden aspect-square"><img
                            src="https://images.unsplash.com/photo-1542038784456-1ea8e935640e?q=80&w=400"
                            class="w-full h-full object-cover"></div>
                    <div class="rounded-3xl overflow-hidden aspect-[3/4]"><img
                            src="https://images.unsplash.com/photo-1516035069371-29a1b244cc32?q=80&w=400"
                            class="w-full h-full object-cover"></div>
                </div>
                <div class="pt-12">
                    <div class="rounded-3xl overflow-hidden aspect-[3/5]"><img
                            src="https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?q=80&w=400"
                            class="w-full h-full object-cover"></div>
                </div>
                <div class="space-y-4">
                    <div class="rounded-3xl overflow-hidden aspect-[4/5]"><img
                            src="https://images.unsplash.com/photo-1533158326339-7f3cf2404354?q=80&w=400"
                            class="w-full h-full object-cover"></div>
                    <div class="rounded-3xl overflow-hidden aspect-square"><img
                            src="https://images.unsplash.com/photo-1554048612-b6a482bc67e5?q=80&w=400"
                            class="w-full h-full object-cover"></div>
                </div>
                <div class="pt-8">
                    <div class="rounded-3xl overflow-hidden aspect-[3/4]"><img
                            src="https://images.unsplash.com/photo-1542038784456-1ea8e935640e?q=80&w=400"
                            class="w-full h-full object-cover"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 py-10">
        <div class="bg-[#000d1a] rounded-[3rem] p-16 text-center relative overflow-hidden">
            <div class="absolute top-0 left-0 w-24 h-24 bg-[#a83a0f]/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-32 h-32 bg-[#e3b23c]/20 rounded-full blur-3xl"></div>

            <h2 class="text-4xl md:text-5xl font-black text-white uppercase mb-4">Siap Untuk Beraksi?</h2>
            <p class="text-gray-400 text-xs uppercase tracking-widest max-w-sm mx-auto mb-10 leading-relaxed">
                Kanvasmu sudah menanti. Booking sesimu sekarang dan ciptakan sesuatu yang tak terlupakan.
            </p>
            <button class="bg-[#005bb7] text-white px-12 py-4 rounded-full font-bold text-xs uppercase shadow-2xl">Booking
                Sekarang</button>
        </div>
    </section>
@endsection