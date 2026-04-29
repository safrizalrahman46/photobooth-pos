@php
    $general = $siteSettings['general'] ?? [];
    $brandName = $general['brand_name'] ?? config('app.name', 'Ready To Pict');
    $queueEnabled = (bool) (($siteSettings['booking']['queue_board_enabled'] ?? true) === true);
@endphp

<x-layouts.public :title="'Queue Board - '.$brandName">
    <main class="mx-auto w-full max-w-6xl px-4 pb-16 pt-8 sm:px-6 lg:px-10">
        <header class="mb-8 flex flex-col gap-4 rounded-3xl card-soft p-6 sm:p-8 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="badge inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em]">Queue Board</p>
                <h1 class="display-font mt-4 text-3xl leading-tight sm:text-4xl">Pantau antrean studio secara real-time.</h1>
                <p class="mt-3 max-w-2xl text-sm text-[var(--rtp-muted)] sm:text-base">Tampilan ini bisa dipakai di monitor kasir atau layar antrian agar pelanggan tahu antrean yang sedang diproses.</p>
            </div>

            <form method="get" class="flex flex-col gap-3 sm:min-w-[280px]">
                <label class="space-y-1 text-sm text-[var(--rtp-muted)]">
                    <span class="font-semibold text-[var(--rtp-ink)]">Cabang studio</span>
                    <select name="branch_id" class="w-full rounded-2xl border border-[var(--rtp-outline)] bg-white px-4 py-3 text-sm text-[var(--rtp-ink)] outline-none">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($selectedBranch?->id === $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-[var(--rtp-primary)] px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:brightness-105">Tampilkan</button>
            </form>
        </header>

        @if (! $queueEnabled)
            <section class="rounded-3xl border border-[var(--rtp-outline)] bg-white p-6 text-sm text-[var(--rtp-muted)]">
                Queue board sedang dinonaktifkan oleh admin.
            </section>
        @else
            <section class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                <article class="card-soft rounded-3xl p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--rtp-accent)]">Sedang Diproses</p>
                    @if ($activeTicket)
                        <div class="mt-5 rounded-3xl bg-[var(--rtp-ink)] px-6 py-8 text-white">
                            <p class="text-sm uppercase tracking-[0.18em] text-white/60">Nomor Antrian</p>
                            <p class="display-font mt-3 text-6xl font-bold">{{ str_pad((string) $activeTicket->queue_number, 3, '0', STR_PAD_LEFT) }}</p>
                            <p class="mt-3 text-sm text-white/75">{{ $activeTicket->customer_name }}</p>
                            <p class="mt-1 text-xs uppercase tracking-[0.16em] text-white/50">{{ strtoupper(str_replace('_', ' ', $activeTicket->status->value ?? $activeTicket->status)) }}</p>
                        </div>
                    @else
                        <div class="mt-5 rounded-3xl border border-dashed border-[var(--rtp-outline)] bg-white/80 px-6 py-10 text-center text-[var(--rtp-muted)]">
                            Belum ada antrean aktif untuk hari ini.
                        </div>
                    @endif
                </article>

                <article class="card-soft rounded-3xl p-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--rtp-accent)]">Ringkasan Hari Ini</p>
                            <h2 class="display-font mt-2 text-2xl">{{ $selectedBranch?->name ?? 'Cabang belum dipilih' }}</h2>
                        </div>
                        <div class="rounded-2xl border border-[var(--rtp-outline)] bg-white px-4 py-3 text-right">
                            <p class="text-xs uppercase tracking-[0.16em] text-[var(--rtp-muted)]">Tanggal</p>
                            <p class="mt-1 text-sm font-semibold text-[var(--rtp-ink)]">{{ \Illuminate\Support\Carbon::parse($queueDate)->translatedFormat('d M Y') }}</p>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-[var(--rtp-outline)] bg-white p-4">
                            <p class="text-xs uppercase tracking-[0.16em] text-[var(--rtp-muted)]">Menunggu</p>
                            <p class="mt-2 display-font text-3xl font-bold">{{ $waitingTickets->count() }}</p>
                        </div>
                        <div class="rounded-2xl border border-[var(--rtp-outline)] bg-white p-4">
                            <p class="text-xs uppercase tracking-[0.16em] text-[var(--rtp-muted)]">Berikutnya</p>
                            <p class="mt-2 display-font text-3xl font-bold">{{ $nextTicket ? str_pad((string) $nextTicket->queue_number, 3, '0', STR_PAD_LEFT) : '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-[var(--rtp-outline)] bg-white p-4">
                            <p class="text-xs uppercase tracking-[0.16em] text-[var(--rtp-muted)]">Auto Refresh</p>
                            <p class="mt-2 text-sm font-semibold text-[var(--rtp-ink)]">10 detik</p>
                        </div>
                    </div>

                    <div class="mt-6 rounded-3xl border border-[var(--rtp-outline)] bg-white/80 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-[var(--rtp-ink)]">Daftar menunggu</h3>
                            <p class="text-xs uppercase tracking-[0.16em] text-[var(--rtp-muted)]">{{ $waitingTickets->count() }} orang</p>
                        </div>

                        <div class="mt-4 space-y-2">
                            @forelse ($waitingTickets->take(8) as $ticket)
                                <div class="flex items-center justify-between rounded-2xl border border-[var(--rtp-outline)] bg-white px-4 py-3">
                                    <div>
                                        <p class="font-semibold text-[var(--rtp-ink)]">{{ str_pad((string) $ticket->queue_number, 3, '0', STR_PAD_LEFT) }}</p>
                                        <p class="text-xs text-[var(--rtp-muted)]">{{ $ticket->customer_name }}</p>
                                    </div>
                                    <span class="rounded-full bg-[var(--rtp-primary-soft)] px-3 py-1 text-xs font-semibold text-[var(--rtp-primary)]">Menunggu</span>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-[var(--rtp-outline)] bg-white px-4 py-6 text-center text-sm text-[var(--rtp-muted)]">
                                    Belum ada antrean menunggu.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </article>
            </section>
        @endif
    </main>

    <script>
        window.setTimeout(() => window.location.reload(), 10000);
    </script>
</x-layouts.public>
