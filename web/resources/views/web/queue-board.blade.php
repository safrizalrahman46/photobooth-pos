@php
    $general = $siteSettings['general'] ?? [];
    $brandName = $general['brand_name'] ?? config('app.name', 'Ready To Pict');
@endphp

<x-layouts.public :title="'Cek Antrean - '.$brandName">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>

    <main class="min-h-screen px-4 py-5 sm:px-6 lg:px-8">
        <div class="mx-auto flex w-full max-w-2xl flex-col gap-5">
            @if ($queueData)
                {{-- MODE 2: HASIL ANTREAN --}}
                <header class="card-soft rounded-[2rem] p-5 sm:p-6 lg:p-8">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="badge inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.2em]">Cek Antrean</span>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold text-[var(--rtp-accent)]" style="background: #edf7f1;">Auto refresh 10 detik</span>
                    </div>
                    <h1 class="mt-4 text-3xl font-bold leading-tight text-[var(--rtp-ink)] sm:text-4xl">Antrean Kamu</h1>
                    <p class="mt-3 text-sm leading-6 text-[var(--rtp-muted)]">Status dan posisi antrean untuk booking <strong>{{ $queueData['queue_code'] }}</strong>.</p>
                </header>

                {{-- 2 kolom: nomor antrean (kiri) + status/posisi/estimasi (kanan) --}}
                <section class="card-soft rounded-[2rem] p-5 sm:p-6 lg:p-8">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--rtp-muted)]">Nomor Antrean</p>
                            <p class="mt-1 text-[5rem] font-extrabold leading-none tracking-tight text-[var(--rtp-primary)] sm:text-[6rem]">{{ str_pad((string) $queueData['queue_number'], 3, '0', STR_PAD_LEFT) }}</p>
                        </div>
                        <div class="flex flex-col justify-center gap-4">
                            <div class="flex items-center justify-between rounded-xl border border-[var(--rtp-outline)] bg-[var(--rtp-paper)] px-4 py-3">
                                <span class="text-sm font-semibold text-[var(--rtp-muted)]">Status</span>
                                <span class="rounded-full px-3 py-1 text-sm font-bold" style="background: #edf7f1; color: var(--rtp-accent);">{{ $queueData['status_label'] }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-xl border border-[var(--rtp-outline)] bg-[var(--rtp-paper)] px-4 py-3">
                                <span class="text-sm font-semibold text-[var(--rtp-muted)]">Posisi</span>
                                <span class="text-lg font-bold text-[var(--rtp-ink)]">{{ $queueData['position'] }} <span class="font-normal text-[var(--rtp-muted)]">/ {{ $queueData['total_active'] }}</span></span>
                            </div>
                            <div class="flex items-center justify-between rounded-xl border border-[var(--rtp-outline)] bg-[var(--rtp-paper)] px-4 py-3">
                                <span class="text-sm font-semibold text-[var(--rtp-muted)]">Estimasi</span>
                                <span class="text-lg font-bold text-[var(--rtp-ink)]">{{ $queueData['estimated_minutes'] }} <span class="font-normal text-[var(--rtp-muted)]">menit</span></span>
                            </div>
                            <div class="flex items-center justify-between rounded-xl border border-[var(--rtp-outline)] bg-[var(--rtp-paper)] px-4 py-3">
                                <span class="text-sm font-semibold text-[var(--rtp-muted)]">Cabang</span>
                                <span class="text-lg font-bold text-[var(--rtp-ink)]">{{ $queueData['branch_name'] }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card-soft rounded-[2rem] p-5 sm:p-6 lg:p-8">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--rtp-accent)]">Nota Digital</p>
                    <h2 class="mt-2 text-xl font-bold text-[var(--rtp-ink)] sm:text-2xl">Detail Transaksi</h2>

                    <div class="mt-5 space-y-2 text-sm">
                        <div class="flex justify-between border-b border-[var(--rtp-outline)] pb-2">
                            <span class="text-[var(--rtp-muted)]">Kode Booking</span>
                            <span class="font-bold text-[var(--rtp-ink)]">{{ $queueData['queue_code'] }}</span>
                        </div>
                        <div class="flex justify-between border-b border-[var(--rtp-outline)] pb-2">
                            <span class="text-[var(--rtp-muted)]">Pelanggan</span>
                            <span class="font-bold text-[var(--rtp-ink)]">{{ $queueData['customer_name'] }}</span>
                        </div>
                        <div class="flex justify-between border-b border-[var(--rtp-outline)] pb-2">
                            <span class="text-[var(--rtp-muted)]">Tanggal</span>
                            <span class="font-bold text-[var(--rtp-ink)]">{{ $queueData['created_at'] }}</span>
                        </div>
                    </div>

                    <div class="mt-5">
                        <p class="mb-3 text-xs font-bold uppercase tracking-[0.16em] text-[var(--rtp-muted)]">Item</p>
                        <div class="space-y-2">
                            @foreach ($queueData['items'] as $item)
                                <div class="flex items-center justify-between rounded-xl bg-[var(--rtp-bg)] px-4 py-3">
                                    <div>
                                        <p class="text-sm font-bold text-[var(--rtp-ink)]">{{ $item['name'] }}</p>
                                        <p class="text-xs text-[var(--rtp-muted)]">{{ $item['qty'] }} x Rp {{ number_format($item['unit_price'], 0, ',', '.') }}</p>
                                    </div>
                                    <p class="text-sm font-bold text-[var(--rtp-ink)]">Rp {{ number_format($item['line_total'], 0, ',', '.') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-5 space-y-2 border-t border-[var(--rtp-outline)] pt-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-[var(--rtp-muted)]">Total</span>
                            <span class="text-xl font-extrabold text-[var(--rtp-ink)]">Rp {{ number_format($queueData['total_amount'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[var(--rtp-muted)]">Dibayar</span>
                            <span class="font-bold text-[var(--rtp-accent)]">Rp {{ number_format($queueData['paid_amount'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[var(--rtp-muted)]">Status Bayar</span>
                            <span class="font-bold text-[var(--rtp-ink)]">{{ strtoupper($queueData['payment_status']) }} ({{ strtoupper($queueData['payment_method']) }})</span>
                        </div>
                    </div>
                </section>

                <div class="flex justify-center">
                    <a href="{{ route('queue.board', ['reset' => 1]) }}" class="inline-flex items-center justify-center rounded-2xl border border-[var(--rtp-outline)] bg-white px-6 py-3 text-sm font-bold text-[var(--rtp-muted)] shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        Cari Booking Lain
                    </a>
                </div>
            @else
                {{-- MODE 1: FORM INPUT --}}
                <header class="card-soft rounded-[2rem] p-5 sm:p-6 lg:p-8">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="badge inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.2em]">Cek Antrean</span>
                    </div>
                    <h1 class="mt-4 text-3xl font-bold leading-tight text-[var(--rtp-ink)] sm:text-4xl">Cek Posisi Antrean</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-[var(--rtp-muted)]">Masukkan kode booking atau kode walk-in yang tertera di nota kamu untuk melihat posisi antrean dan estimasi waktu tunggu.</p>
                </header>

                <section class="card-soft rounded-[2rem] p-6 sm:p-8">
                    <form method="post" action="{{ route('queue.board') }}" class="space-y-5">
                        @csrf
                        <div>
                            <label for="code" class="mb-2 block text-sm font-bold text-[var(--rtp-ink)]">Kode Booking / Walk-in</label>
                            <input
                                type="text"
                                id="code"
                                name="code"
                                value="{{ old('code') }}"
                                placeholder="contoh: BKG-20260714-001"
                                required
                                maxlength="20"
                                class="w-full rounded-2xl border border-[var(--rtp-outline)] bg-[var(--rtp-paper)] px-5 py-4 text-base font-semibold text-[var(--rtp-ink)] outline-none transition focus:border-[var(--rtp-primary)] focus:ring-4 focus:ring-orange-100"
                            >
                            @error('code')
                                <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-[var(--rtp-primary)] px-6 py-4 text-base font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            Cari Antrean Saya
                        </button>
                    </form>

                    <p class="mt-6 text-center text-xs leading-5 text-[var(--rtp-muted)]">Kode booking / walk-in bisa ditemukan di struk atau nota yang diberikan oleh kasir.</p>
                </section>
            @endif
        </div>
    </main>

    @if ($queueData)
        <script>
            window.setTimeout(() => window.location.reload(), 10000);
        </script>
    @endif
</x-layouts.public>
