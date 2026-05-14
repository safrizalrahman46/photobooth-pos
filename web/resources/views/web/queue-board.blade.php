@php
    $general = $siteSettings['general'] ?? [];
    $brandName = $general['brand_name'] ?? config('app.name', 'Ready To Pict');
    $queueEnabled = (bool) (($siteSettings['booking']['queue_board_enabled'] ?? true) === true);
    $activeStatus = $activeTicket ? (string) ($activeTicket->status->value ?? $activeTicket->status) : null;
    $activeStatusLabel = $activeStatus ? strtoupper(str_replace('_', ' ', $activeStatus)) : '-';
    $activeNumber = $activeTicket ? str_pad((string) $activeTicket->queue_number, 3, '0', STR_PAD_LEFT) : '---';
    $nextNumber = $nextTicket ? str_pad((string) $nextTicket->queue_number, 3, '0', STR_PAD_LEFT) : '-';
    $waitingCount = $waitingTickets->count();
@endphp

<x-layouts.public :title="'Queue Board - '.$brandName">
    <main class="relative min-h-screen overflow-hidden px-4 py-5 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-24 top-10 h-80 w-80 rounded-full opacity-70 blur-3xl" style="background: #fff2dc;"></div>
            <div class="absolute right-0 top-20 h-72 w-72 rounded-full opacity-60 blur-3xl" style="background: #e3f3eb;"></div>
            <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full opacity-50 blur-3xl" style="background: #f8dfc8;"></div>
        </div>

        <div class="relative mx-auto flex w-full max-w-7xl flex-col gap-5">
            <header class="card-soft overflow-hidden rounded-[2rem] p-5 sm:p-6 lg:p-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="badge inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.2em]">Live Queue</span>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold text-[var(--rtp-accent)]" style="background: #edf7f1;">Auto refresh 10 detik</span>
                        </div>
                        <h1 class="display-font mt-4 text-4xl leading-[0.95] text-[var(--rtp-ink)] sm:text-5xl lg:text-6xl">Queue Board</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-[var(--rtp-muted)] sm:text-base">Pantau antrean studio secara real-time dengan tampilan yang nyaman untuk layar kasir, ruang tunggu, atau display pelanggan.</p>
                    </div>

                    <form method="get" class="rounded-3xl border border-[var(--rtp-outline)] bg-white/75 p-3 shadow-sm backdrop-blur sm:min-w-[340px]">
                        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                            <label class="space-y-1 text-sm text-[var(--rtp-muted)]">
                                <span class="font-semibold text-[var(--rtp-ink)]">Cabang studio</span>
                                <select name="branch_id" class="w-full rounded-2xl border border-[var(--rtp-outline)] bg-[var(--rtp-paper)] px-4 py-3 text-sm font-semibold text-[var(--rtp-ink)] outline-none transition focus:border-[var(--rtp-primary)] focus:ring-4 focus:ring-orange-100">
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected($selectedBranch?->id === $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-[var(--rtp-primary)] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:brightness-105">Tampilkan</button>
                        </div>
                    </form>
                </div>
            </header>

            @if (! $queueEnabled)
                <section class="card-soft rounded-[2rem] p-8 text-center sm:p-12">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[var(--rtp-primary-soft)] text-2xl">!</div>
                    <h2 class="display-font mt-5 text-3xl text-[var(--rtp-ink)]">Queue board sedang dinonaktifkan.</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-[var(--rtp-muted)]">Aktifkan kembali dari pengaturan admin agar layar antrean dapat ditampilkan untuk pelanggan.</p>
                </section>
            @else
                <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_430px]">
                    <article class="relative overflow-hidden rounded-[2rem] p-5 text-white shadow-2xl sm:p-7 lg:min-h-[560px]" style="background: linear-gradient(145deg, #17140f 0%, #263f36 54%, #2f6f61 100%);">
                        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/10 blur-2xl"></div>
                        <div class="pointer-events-none absolute bottom-0 left-0 h-44 w-44 rounded-full bg-[#c06b2a]/25 blur-2xl"></div>

                        <div class="relative flex h-full flex-col justify-between gap-8">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-white/55">Sedang Diproses</p>
                                    <h2 class="display-font mt-2 text-3xl text-white sm:text-4xl">{{ $selectedBranch?->name ?? 'Cabang belum dipilih' }}</h2>
                                    @if ($selectedBranch?->address)
                                        <p class="mt-2 max-w-xl text-sm text-white/55">{{ $selectedBranch->address }}</p>
                                    @endif
                                </div>

                                <div class="rounded-3xl border border-white/15 bg-white/10 px-4 py-3 text-right backdrop-blur">
                                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.18em] text-white/45">Tanggal</p>
                                    <p class="mt-1 text-sm font-bold text-white">{{ \Illuminate\Support\Carbon::parse($queueDate)->translatedFormat('d M Y') }}</p>
                                </div>
                            </div>

                            @if ($activeTicket)
                                <div class="py-5 sm:py-10">
                                    <p class="text-sm font-bold uppercase tracking-[0.28em] text-white/45">Nomor Antrian</p>
                                    <p class="display-font mt-4 text-[7rem] font-bold leading-none tracking-[-0.08em] text-white sm:text-[10rem] lg:text-[12rem]">{{ $activeNumber }}</p>
                                    <div class="mt-6 flex flex-wrap items-center gap-3">
                                        <span class="rounded-full bg-white px-4 py-2 text-sm font-bold text-[var(--rtp-ink)]">{{ $activeTicket->customer_name }}</span>
                                        <span class="rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-white/70">{{ $activeStatusLabel }}</span>
                                    </div>
                                </div>
                            @else
                                <div class="flex min-h-[300px] flex-col items-center justify-center rounded-[1.75rem] border border-dashed border-white/20 bg-white/10 px-6 py-12 text-center">
                                    <p class="display-font text-6xl font-bold text-white/80">---</p>
                                    <p class="mt-4 text-sm font-semibold uppercase tracking-[0.2em] text-white/55">Belum ada antrean aktif</p>
                                    <p class="mt-2 max-w-md text-sm leading-6 text-white/45">Nomor antrean akan tampil otomatis ketika customer dipanggil atau sesi dimulai.</p>
                                </div>
                            @endif

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="rounded-3xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                                    <p class="text-xs uppercase tracking-[0.18em] text-white/45">Berikutnya</p>
                                    <p class="display-font mt-2 text-4xl font-bold">{{ $nextNumber }}</p>
                                </div>
                                <div class="rounded-3xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                                    <p class="text-xs uppercase tracking-[0.18em] text-white/45">Menunggu</p>
                                    <p class="display-font mt-2 text-4xl font-bold">{{ $waitingCount }}</p>
                                </div>
                                <div class="rounded-3xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                                    <p class="text-xs uppercase tracking-[0.18em] text-white/45">Refresh</p>
                                    <p class="mt-3 text-lg font-bold">10 detik</p>
                                </div>
                            </div>
                        </div>
                    </article>

                    <aside class="flex flex-col gap-5">
                        <article class="card-soft rounded-[2rem] p-5 sm:p-6">
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--rtp-accent)]">Ringkasan</p>
                            <h3 class="display-font mt-2 text-3xl text-[var(--rtp-ink)]">Hari Ini</h3>

                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <div class="rounded-3xl border border-[var(--rtp-outline)] bg-white p-4">
                                    <p class="text-xs uppercase tracking-[0.16em] text-[var(--rtp-muted)]">Cabang</p>
                                    <p class="mt-2 text-sm font-bold text-[var(--rtp-ink)]">{{ $selectedBranch?->name ?? '-' }}</p>
                                </div>
                                <div class="rounded-3xl border border-[var(--rtp-outline)] bg-white p-4">
                                    <p class="text-xs uppercase tracking-[0.16em] text-[var(--rtp-muted)]">Berikutnya</p>
                                    <p class="display-font mt-1 text-4xl font-bold text-[var(--rtp-primary)]">{{ $nextNumber }}</p>
                                </div>
                            </div>
                        </article>

                        <article class="card-soft rounded-[2rem] p-5 sm:p-6">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--rtp-accent)]">Daftar Menunggu</p>
                                    <h3 class="display-font mt-2 text-3xl text-[var(--rtp-ink)]">{{ $waitingCount }} antrean</h3>
                                </div>
                                <span class="rounded-full bg-[var(--rtp-primary-soft)] px-3 py-1 text-xs font-bold text-[var(--rtp-primary)]">Live</span>
                            </div>

                            <div class="mt-5 space-y-3">
                                @forelse ($waitingTickets->take(8) as $index => $ticket)
                                    <div class="group flex items-center gap-4 rounded-3xl border border-[var(--rtp-outline)] bg-white px-4 py-3 transition hover:-translate-y-0.5 hover:shadow-sm">
                                        <div class="flex h-14 w-16 shrink-0 items-center justify-center rounded-2xl bg-[var(--rtp-bg)]">
                                            <span class="display-font text-2xl font-bold text-[var(--rtp-ink)]">{{ str_pad((string) $ticket->queue_number, 3, '0', STR_PAD_LEFT) }}</span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-bold text-[var(--rtp-ink)]">{{ $ticket->customer_name }}</p>
                                            <p class="mt-1 text-xs text-[var(--rtp-muted)]">Urutan {{ $index + 1 }} dalam antrean</p>
                                        </div>
                                        <span class="rounded-full bg-[#edf7f1] px-3 py-1 text-[0.68rem] font-bold uppercase tracking-[0.12em] text-[var(--rtp-accent)]">Menunggu</span>
                                    </div>
                                @empty
                                    <div class="rounded-3xl border border-dashed border-[var(--rtp-outline)] bg-white/80 px-5 py-10 text-center">
                                        <p class="display-font text-3xl text-[var(--rtp-ink)]">Kosong</p>
                                        <p class="mt-2 text-sm leading-6 text-[var(--rtp-muted)]">Belum ada antrean menunggu untuk cabang ini.</p>
                                    </div>
                                @endforelse

                                @if ($waitingCount > 8)
                                    <div class="rounded-2xl bg-[var(--rtp-primary-soft)] px-4 py-3 text-center text-xs font-bold text-[var(--rtp-primary)]">
                                        +{{ $waitingCount - 8 }} antrean lainnya menunggu
                                    </div>
                                @endif
                            </div>
                        </article>
                    </aside>
                </section>
            @endif
        </div>
    </main>

    <script>
        window.setTimeout(() => window.location.reload(), 10000);
    </script>
</x-layouts.public>
