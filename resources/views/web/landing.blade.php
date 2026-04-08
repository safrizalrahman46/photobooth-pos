<x-layouts.public :title="'READY TO PICT - Booking Photobooth'">
    <main class="mx-auto w-full max-w-6xl px-4 pb-16 pt-8 sm:px-6 lg:px-10">
        <header class="mb-10 flex flex-col gap-4 rounded-3xl card-soft p-6 sm:p-8 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="badge inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em]">READY TO PICT</p>
                <h1 class="display-font mt-4 text-3xl leading-tight sm:text-4xl lg:text-5xl">Photo booth cepat, estetik, dan anti ribet.</h1>
                <p class="mt-3 max-w-2xl text-sm text-[var(--rtp-muted)] sm:text-base">Pilih paket, cek slot yang tersedia, dan booking online dalam beberapa menit. Datang sesuai jam, langsung foto.</p>
            </div>
            <div class="flex shrink-0 flex-col gap-3">
                <a href="{{ route('booking.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-[var(--rtp-primary)] px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:brightness-105">Booking Sekarang</a>
                <p class="text-xs text-[var(--rtp-muted)]">Open daily • Fast queue • Bisa walk-in</p>
            </div>
        </header>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($packages as $package)
                <article class="card-soft rounded-2xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--rtp-accent)]">{{ $package->duration_minutes }} menit</p>
                    <h2 class="mt-2 text-lg font-semibold">{{ $package->name }}</h2>
                    <p class="mt-2 text-sm text-[var(--rtp-muted)]">{{ $package->description ?: 'Paket siap pakai untuk momen seru bareng teman, keluarga, atau event.' }}</p>
                    <div class="mt-4 flex items-center justify-between">
                        <p class="display-font text-2xl font-bold">Rp {{ number_format((float) $package->base_price, 0, ',', '.') }}</p>
                        <a href="{{ route('booking.create', ['package' => $package->id]) }}" class="rounded-xl border border-[var(--rtp-outline)] px-3 py-2 text-xs font-semibold hover:bg-white">Pilih</a>
                    </div>
                </article>
            @empty
                <article class="card-soft rounded-2xl p-6 sm:col-span-2 lg:col-span-3">
                    <h2 class="text-lg font-semibold">Paket belum tersedia</h2>
                    <p class="mt-2 text-sm text-[var(--rtp-muted)]">Admin belum mengaktifkan paket. Coba lagi sebentar lagi.</p>
                </article>
            @endforelse
        </section>

        <section class="mt-10 grid gap-4 lg:grid-cols-[1.2fr_1fr]">
            <article class="card-soft rounded-2xl p-6">
                <h3 class="display-font text-2xl">Alur booking super singkat</h3>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-[var(--rtp-outline)] bg-white/70 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[var(--rtp-accent)]">Step 1</p>
                        <p class="mt-1 text-sm">Pilih cabang, paket, dan tema desain.</p>
                    </div>
                    <div class="rounded-xl border border-[var(--rtp-outline)] bg-white/70 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[var(--rtp-accent)]">Step 2</p>
                        <p class="mt-1 text-sm">Pilih tanggal dan slot jam yang masih kosong.</p>
                    </div>
                    <div class="rounded-xl border border-[var(--rtp-outline)] bg-white/70 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[var(--rtp-accent)]">Step 3</p>
                        <p class="mt-1 text-sm">Isi data kontak, submit, lalu datang tepat waktu.</p>
                    </div>
                </div>
            </article>

            <article class="card-soft rounded-2xl p-6">
                <h3 class="display-font text-2xl">Cabang aktif</h3>
                <ul class="mt-4 space-y-3">
                    @forelse ($branches as $branch)
                        <li class="rounded-xl border border-[var(--rtp-outline)] bg-white/70 p-3">
                            <p class="font-semibold">{{ $branch->name }}</p>
                            <p class="mt-1 text-xs text-[var(--rtp-muted)]">{{ $branch->address ?: 'Alamat segera tersedia' }} • {{ $branch->timezone }}</p>
                        </li>
                    @empty
                        <li class="rounded-xl border border-[var(--rtp-outline)] bg-white/70 p-3 text-sm text-[var(--rtp-muted)]">Belum ada cabang aktif.</li>
                    @endforelse
                </ul>
            </article>
        </section>
    </main>
</x-layouts.public>
