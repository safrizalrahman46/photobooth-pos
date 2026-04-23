@php
    $general = $siteSettings['general'] ?? [];
    $brandName = $general['brand_name'] ?? config('app.name', 'Ready To Pict');
    $statusValue = $booking->status?->value ?? (string) $booking->status;
    $hasTransferProof = filled($booking->transfer_proof_path);

    if ($hasTransferProof) {
        $paymentLabel = 'Bukti transfer sudah diterima. Menunggu verifikasi admin.';
    } elseif ($booking->payment_type === 'dp50') {
        $paymentLabel = 'Menunggu upload bukti transfer DP 50% via QR BRI';
    } else {
        $paymentLabel = 'Menunggu upload bukti transfer pelunasan via QR BRI';
    }
@endphp

<x-layouts.public :title="'Booking Berhasil - '.$brandName">
    <main class="mx-auto flex min-h-screen w-full max-w-3xl items-center px-4 py-10 sm:px-6">
        <section class="card-soft w-full rounded-3xl p-6 sm:p-8">
            <div class="mb-5 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Booking Confirmed</div>

            <h1 class="display-font text-3xl leading-tight sm:text-4xl">Terima kasih, booking kamu sudah tercatat.</h1>
            <p class="mt-3 text-sm text-[var(--rtp-muted)]">Simpan kode booking di bawah ini untuk proses check-in di lokasi.</p>

            @if (session('booking_payment_notice'))
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    {{ session('booking_payment_notice') }}
                </div>
            @endif

            <div class="mt-4 rounded-2xl border border-[var(--rtp-outline)] bg-white p-4 text-sm text-[var(--rtp-muted)]">
                <p class="font-semibold text-[var(--rtp-ink)]">Status pembayaran</p>
                <p class="mt-1">{{ $paymentLabel }}</p>
                @if ($statusValue !== 'paid')
                    <p class="mt-2 text-xs">Admin akan memverifikasi bukti transfer sebelum booking dikonfirmasi.</p>
                @endif
                @if (!empty($booking->payment_reference))
                    <p class="mt-2 text-xs">Referensi transfer: {{ $booking->payment_reference }}</p>
                @endif
            </div>

            <div class="mt-6 rounded-2xl border border-[var(--rtp-outline)] bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--rtp-muted)]">Kode Booking</p>
                <p class="mt-2 display-font text-3xl font-bold text-[var(--rtp-primary)]">{{ $booking->booking_code }}</p>
            </div>

            <dl class="mt-6 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-[var(--rtp-outline)] bg-white/80 p-3">
                    <dt class="text-xs uppercase tracking-[0.16em] text-[var(--rtp-muted)]">Nama</dt>
                    <dd class="mt-1 text-sm font-semibold">{{ $booking->customer_name }}</dd>
                </div>
                <div class="rounded-xl border border-[var(--rtp-outline)] bg-white/80 p-3">
                    <dt class="text-xs uppercase tracking-[0.16em] text-[var(--rtp-muted)]">Nomor HP</dt>
                    <dd class="mt-1 text-sm font-semibold">{{ $booking->customer_phone }}</dd>
                </div>
                <div class="rounded-xl border border-[var(--rtp-outline)] bg-white/80 p-3">
                    <dt class="text-xs uppercase tracking-[0.16em] text-[var(--rtp-muted)]">Cabang</dt>
                    <dd class="mt-1 text-sm font-semibold">{{ $booking->branch?->name ?? '-' }}</dd>
                </div>
                <div class="rounded-xl border border-[var(--rtp-outline)] bg-white/80 p-3">
                    <dt class="text-xs uppercase tracking-[0.16em] text-[var(--rtp-muted)]">Paket</dt>
                    <dd class="mt-1 text-sm font-semibold">{{ $booking->package?->name ?? '-' }}</dd>
                </div>
                <div class="rounded-xl border border-[var(--rtp-outline)] bg-white/80 p-3">
                    <dt class="text-xs uppercase tracking-[0.16em] text-[var(--rtp-muted)]">Tanggal</dt>
                    <dd class="mt-1 text-sm font-semibold">{{ $booking->booking_date?->format('d M Y') }}</dd>
                </div>
                <div class="rounded-xl border border-[var(--rtp-outline)] bg-white/80 p-3">
                    <dt class="text-xs uppercase tracking-[0.16em] text-[var(--rtp-muted)]">Jam</dt>
                    <dd class="mt-1 text-sm font-semibold">{{ $booking->start_at?->format('H:i') }} - {{ $booking->end_at?->format('H:i') }}</dd>
                </div>
            </dl>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                @if ($booking->payment_gateway === 'midtrans' && in_array($booking->payment_type, ['full', 'dp50'], true) && $statusValue !== 'paid' && $booking->payment_url)
                    <a href="{{ $booking->payment_url }}" class="inline-flex items-center justify-center rounded-2xl bg-[var(--rtp-primary)] px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:brightness-105">Lanjutkan Pembayaran</a>
                @endif
                <a href="{{ route('booking.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-[var(--rtp-primary)] px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:brightness-105">Buat Booking Lain</a>
                <a href="{{ route('queue.board') }}" class="inline-flex items-center justify-center rounded-2xl border border-[var(--rtp-outline)] bg-white px-5 py-3 text-sm font-semibold">Lihat Queue Board</a>
                <a href="{{ route('landing') }}" class="inline-flex items-center justify-center rounded-2xl border border-[var(--rtp-outline)] bg-white px-5 py-3 text-sm font-semibold">Kembali ke Beranda</a>
            </div>
        </section>
    </main>
</x-layouts.public>
