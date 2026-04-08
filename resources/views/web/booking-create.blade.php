@php
    $prefillPackage = old('package_id') ?: (request()->integer('package') ?: null);
@endphp

<x-layouts.public :title="'Booking Online - READY TO PICT'">
    <main class="mx-auto w-full max-w-5xl px-4 pb-16 pt-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between gap-3">
            <a href="{{ route('landing') }}" class="rounded-xl border border-[var(--rtp-outline)] bg-white/80 px-3 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--rtp-muted)] hover:bg-white">Kembali</a>
            <p class="badge rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em]">Form Booking</p>
        </div>

        <section class="card-soft overflow-hidden rounded-3xl">
            <div class="grid gap-0 lg:grid-cols-[0.95fr_1.05fr]">
                <aside class="bg-[linear-gradient(150deg,#f5e4d1_0%,#fff4e8_45%,#f0f6f2_100%)] p-6 sm:p-8">
                    <h1 class="display-font text-3xl leading-tight">Amankan slot photobooth kamu hari ini.</h1>
                    <p class="mt-3 text-sm text-[var(--rtp-muted)]">Pilih cabang, paket, dan jam yang masih tersedia. Sistem langsung cek ketersediaan secara real-time.</p>

                    <div class="mt-6 space-y-3 text-sm">
                        <div class="rounded-xl border border-white/80 bg-white/70 p-3">
                            <p class="font-semibold">1. Pilih slot</p>
                            <p class="mt-1 text-xs text-[var(--rtp-muted)]">Jam yang penuh otomatis tidak bisa dipilih.</p>
                        </div>
                        <div class="rounded-xl border border-white/80 bg-white/70 p-3">
                            <p class="font-semibold">2. Isi data</p>
                            <p class="mt-1 text-xs text-[var(--rtp-muted)]">Nama dan nomor HP dipakai untuk konfirmasi booking.</p>
                        </div>
                        <div class="rounded-xl border border-white/80 bg-white/70 p-3">
                            <p class="font-semibold">3. Konfirmasi</p>
                            <p class="mt-1 text-xs text-[var(--rtp-muted)]">Setelah submit, kamu dapat kode booking untuk check-in.</p>
                        </div>
                    </div>
                </aside>

                <div class="p-6 sm:p-8">
                    @if ($errors->any())
                        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                            <ul class="space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="booking-form" action="{{ route('booking.store') }}" method="post" class="space-y-4">
                        @csrf

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="space-y-1 text-sm">
                                <span class="font-semibold">Cabang</span>
                                <select id="branch_id" name="branch_id" required class="w-full rounded-xl border border-[var(--rtp-outline)] bg-white px-3 py-2 text-sm focus:border-[var(--rtp-primary)] focus:outline-none">
                                    <option value="">Pilih cabang</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="space-y-1 text-sm">
                                <span class="font-semibold">Paket</span>
                                <select id="package_id" name="package_id" required data-old="{{ $prefillPackage }}" class="w-full rounded-xl border border-[var(--rtp-outline)] bg-white px-3 py-2 text-sm focus:border-[var(--rtp-primary)] focus:outline-none">
                                    <option value="">Pilih paket</option>
                                    @foreach ($packages as $package)
                                        <option value="{{ $package->id }}" @selected((string) $prefillPackage === (string) $package->id)>{{ $package->name }} - Rp {{ number_format((float) $package->base_price, 0, ',', '.') }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="space-y-1 text-sm">
                                <span class="font-semibold">Tanggal Booking</span>
                                <input id="booking_date" type="date" name="booking_date" min="{{ now()->toDateString() }}" value="{{ old('booking_date') }}" required class="w-full rounded-xl border border-[var(--rtp-outline)] bg-white px-3 py-2 text-sm focus:border-[var(--rtp-primary)] focus:outline-none">
                            </label>

                            <label class="space-y-1 text-sm">
                                <span class="font-semibold">Desain (opsional)</span>
                                <select id="design_catalog_id" name="design_catalog_id" data-old="{{ old('design_catalog_id') }}" class="w-full rounded-xl border border-[var(--rtp-outline)] bg-white px-3 py-2 text-sm focus:border-[var(--rtp-primary)] focus:outline-none">
                                    <option value="">Pilih desain</option>
                                    @foreach ($designCatalogs as $design)
                                        <option value="{{ $design->id }}" @selected((string) old('design_catalog_id') === (string) $design->id)>{{ $design->name }}{{ $design->theme ? ' - '.$design->theme : '' }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <div class="space-y-2 rounded-2xl border border-[var(--rtp-outline)] bg-white/80 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <h2 class="font-semibold">Pilih Jam Tersedia</h2>
                                <p id="slot-selected-text" class="text-xs text-[var(--rtp-muted)]">Belum ada slot dipilih</p>
                            </div>
                            <input type="hidden" name="booking_time" id="booking_time" value="{{ old('booking_time') }}">
                            <div id="slot-container" class="grid grid-cols-2 gap-2 sm:grid-cols-3"></div>
                            <p id="slot-help" class="text-xs text-[var(--rtp-muted)]">Pilih cabang, paket, dan tanggal untuk melihat slot.</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="space-y-1 text-sm">
                                <span class="font-semibold">Nama Pemesan</span>
                                <input type="text" name="customer_name" required value="{{ old('customer_name') }}" maxlength="120" class="w-full rounded-xl border border-[var(--rtp-outline)] bg-white px-3 py-2 text-sm focus:border-[var(--rtp-primary)] focus:outline-none">
                            </label>

                            <label class="space-y-1 text-sm">
                                <span class="font-semibold">Nomor HP</span>
                                <input type="text" name="customer_phone" required value="{{ old('customer_phone') }}" maxlength="30" class="w-full rounded-xl border border-[var(--rtp-outline)] bg-white px-3 py-2 text-sm focus:border-[var(--rtp-primary)] focus:outline-none">
                            </label>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="space-y-1 text-sm">
                                <span class="font-semibold">Email (opsional)</span>
                                <input type="email" name="customer_email" value="{{ old('customer_email') }}" class="w-full rounded-xl border border-[var(--rtp-outline)] bg-white px-3 py-2 text-sm focus:border-[var(--rtp-primary)] focus:outline-none">
                            </label>

                            <label class="space-y-1 text-sm">
                                <span class="font-semibold">Catatan (opsional)</span>
                                <input type="text" name="notes" value="{{ old('notes') }}" maxlength="1000" class="w-full rounded-xl border border-[var(--rtp-outline)] bg-white px-3 py-2 text-sm focus:border-[var(--rtp-primary)] focus:outline-none">
                            </label>
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-[var(--rtp-primary)] px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:brightness-105">Submit Booking</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <div id="booking-bootstrap" class="hidden" data-packages='@json($packages->values())' data-design-catalogs='@json($designCatalogs->values())'></div>

    <script>
        (() => {
            const bootstrapData = document.getElementById('booking-bootstrap');
            const packages = JSON.parse(bootstrapData?.dataset.packages || '[]');
            const designCatalogs = JSON.parse(bootstrapData?.dataset.designCatalogs || '[]');

            const branchSelect = document.getElementById('branch_id');
            const packageSelect = document.getElementById('package_id');
            const dateInput = document.getElementById('booking_date');
            const designSelect = document.getElementById('design_catalog_id');
            const slotContainer = document.getElementById('slot-container');
            const slotHelp = document.getElementById('slot-help');
            const bookingTimeInput = document.getElementById('booking_time');
            const slotSelectedText = document.getElementById('slot-selected-text');
            const form = document.getElementById('booking-form');

            const oldPackage = packageSelect.dataset.old || '';
            const oldDesign = designSelect.dataset.old || '';
            let availabilityRequest = null;

            const normalizeTime = (timeValue) => String(timeValue || '').slice(0, 5);

            const setSelectedSlotText = () => {
                const value = bookingTimeInput.value;
                slotSelectedText.textContent = value ? `Slot dipilih: ${value}` : 'Belum ada slot dipilih';
            };

            const createOption = (value, label, selected = false) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                option.selected = selected;
                return option;
            };

            const allowedPackages = () => {
                const selectedBranch = branchSelect.value;

                if (!selectedBranch) {
                    return packages;
                }

                return packages.filter((item) => item.branch_id === null || String(item.branch_id) === String(selectedBranch));
            };

            const renderPackages = () => {
                const current = packageSelect.value || oldPackage;
                const list = allowedPackages();

                packageSelect.innerHTML = '';
                packageSelect.appendChild(createOption('', 'Pilih paket'));

                list.forEach((item) => {
                    const price = Number(item.base_price || 0).toLocaleString('id-ID');
                    const selected = String(item.id) === String(current);
                    packageSelect.appendChild(createOption(String(item.id), `${item.name} - Rp ${price}`, selected));
                });
            };

            const renderDesigns = () => {
                const packageId = packageSelect.value;
                const selected = designSelect.value || oldDesign;

                designSelect.innerHTML = '';
                designSelect.appendChild(createOption('', 'Pilih desain'));

                if (!packageId) {
                    return;
                }

                const filtered = designCatalogs.filter((item) => String(item.package_id) === String(packageId));

                filtered.forEach((item) => {
                    const selectedState = String(item.id) === String(selected);
                    const suffix = item.theme ? ` - ${item.theme}` : '';
                    designSelect.appendChild(createOption(String(item.id), `${item.name}${suffix}`, selectedState));
                });
            };

            const setLoadingSlots = () => {
                slotContainer.innerHTML = '';
                slotHelp.textContent = 'Memuat slot...';
            };

            const renderSlots = (slots) => {
                slotContainer.innerHTML = '';

                if (!slots.length) {
                    slotHelp.textContent = 'Tidak ada slot untuk kombinasi yang dipilih.';
                    bookingTimeInput.value = '';
                    setSelectedSlotText();
                    return;
                }

                slotHelp.textContent = 'Klik salah satu jam untuk memilih slot.';
                const oldValue = bookingTimeInput.value;

                slots.forEach((slot) => {
                    const start = normalizeTime(slot.start_time || slot.start_label);
                    const end = normalizeTime(slot.end_time || slot.end_label);
                    const isAvailable = Boolean(slot.is_available);

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.dataset.time = start;
                    button.className = 'rounded-xl border px-3 py-2 text-left text-xs transition';

                    if (isAvailable) {
                        button.classList.add('border-[var(--rtp-outline)]', 'bg-white', 'hover:border-[var(--rtp-primary)]', 'hover:bg-[var(--rtp-primary-soft)]');
                    } else {
                        button.classList.add('cursor-not-allowed', 'border-gray-200', 'bg-gray-100', 'text-gray-400');
                        button.disabled = true;
                    }

                    const detail = isAvailable
                        ? `${slot.remaining_slots} slot tersisa`
                        : 'Penuh';

                    button.innerHTML = `<span class="block font-semibold">${start} - ${end}</span><span class="block text-[11px] text-[var(--rtp-muted)]">${detail}</span>`;

                    button.addEventListener('click', () => {
                        bookingTimeInput.value = start;
                        document.querySelectorAll('#slot-container button').forEach((node) => {
                            node.classList.remove('ring-2', 'ring-[var(--rtp-primary)]', 'border-[var(--rtp-primary)]', 'bg-[var(--rtp-primary-soft)]');
                        });

                        button.classList.add('ring-2', 'ring-[var(--rtp-primary)]', 'border-[var(--rtp-primary)]', 'bg-[var(--rtp-primary-soft)]');
                        setSelectedSlotText();
                    });

                    slotContainer.appendChild(button);
                });

                const existing = slots.find((slot) => normalizeTime(slot.start_time || slot.start_label) === oldValue && slot.is_available);

                if (!existing) {
                    bookingTimeInput.value = '';
                }

                if (bookingTimeInput.value) {
                    const selectedButton = slotContainer.querySelector(`button[data-time="${bookingTimeInput.value}"]`);
                    selectedButton?.click();
                } else {
                    setSelectedSlotText();
                }
            };

            const loadAvailability = async () => {
                const branchId = branchSelect.value;
                const packageId = packageSelect.value;
                const date = dateInput.value;

                if (!branchId || !packageId || !date) {
                    slotContainer.innerHTML = '';
                    slotHelp.textContent = 'Pilih cabang, paket, dan tanggal untuk melihat slot.';
                    bookingTimeInput.value = '';
                    setSelectedSlotText();
                    return;
                }

                if (availabilityRequest) {
                    availabilityRequest.abort();
                }

                availabilityRequest = new AbortController();
                setLoadingSlots();

                const params = new URLSearchParams({
                    branch_id: branchId,
                    package_id: packageId,
                    date,
                });

                try {
                    const response = await fetch(`{{ route('booking.availability') }}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                        },
                        signal: availabilityRequest.signal,
                    });

                    const json = await response.json();

                    if (!response.ok || !json.success) {
                        slotContainer.innerHTML = '';
                        slotHelp.textContent = json.message || 'Gagal memuat slot.';
                        bookingTimeInput.value = '';
                        setSelectedSlotText();
                        return;
                    }

                    renderSlots(Array.isArray(json.data) ? json.data : []);
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    slotContainer.innerHTML = '';
                    slotHelp.textContent = 'Tidak dapat memuat slot saat ini.';
                    bookingTimeInput.value = '';
                    setSelectedSlotText();
                }
            };

            branchSelect.addEventListener('change', () => {
                renderPackages();
                renderDesigns();
                loadAvailability();
            });

            packageSelect.addEventListener('change', () => {
                renderDesigns();
                loadAvailability();
            });

            designSelect.addEventListener('change', () => {});
            dateInput.addEventListener('change', loadAvailability);

            form.addEventListener('submit', (event) => {
                if (!bookingTimeInput.value) {
                    event.preventDefault();
                    slotHelp.textContent = 'Silakan pilih slot jam terlebih dahulu.';
                    slotHelp.classList.add('text-red-600');
                }
            });

            renderPackages();
            renderDesigns();
            setSelectedSlotText();
            loadAvailability();
        })();
    </script>
</x-layouts.public>
