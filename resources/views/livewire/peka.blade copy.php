@php
  // ====== NEW: mode bisa 'landing' atau 'form' ======
  $mode = property_exists($this, 'mode') ? $this->mode : 'form';

  // Stage: 1 layanan, 2 loket, 3 staff, 4 rate, 5 komentar (dipakai hanya saat mode=form)
  $stage = ($mode === 'form')
    ? (method_exists($this, 'getStageProperty')
        ? $this->stage
        : (!($serviceId ?? null) ? 1 : (!($counterId ?? null) ? 2 : (!($staffId ?? null) ? 3 : (!($score ?? null) ? 4 : 5)))))
    : 0;

  $emojiLabels = [1=>'Sangat buruk',2=>'Buruk',3=>'Cukup',4=>'Baik',5=>'Sangat baik'];
  $emojis      = [1=>'😡',2=>'☹️',3=>'😐',4=>'🙂',5=>'😍'];

  $selectedService = collect($services ?? [])->firstWhere('id', (int)($serviceId ?? 0));
  $selectedCounter = collect($counters ?? [])->firstWhere('id', (int)($counterId ?? 0));
  $selectedStaff   = collect($staffOptions ?? [])->firstWhere('id', (int)($staffId ?? 0));

  $partnerLogo      = $partnerLogo      ?? asset('images/logokemendagri.png');
  $partnerLogoLink  = $partnerLogoLink  ?? 'images/logokemendagri.png';
  $partnerLogoTitle = $partnerLogoTitle ?? 'Kementerian Dalam Negeri';
@endphp

{{-- ===== SINGLE ROOT WRAPPER ===== --}}
<div id="peka-root" class="relative">

  {{-- ===== BACKGROUND LAYER: fixed full screen ===== --}}
  <div class="pointer-events-none fixed inset-0 -z-10">
    <img
      src="{{ asset('images/barelang.jpg') }}"
      alt=""
      class="absolute inset-0 h-full w-full object-cover object-[50%_30%]" />
    <div class="absolute inset-0 bg-[linear-gradient(to_top,_#1E40AF_0%,_#F97316_50%,_#F59E0B_100%)] opacity-80"></div>
  </div>

  {{-- ===== CONTENT SCROLLER ===== --}}
  <div class="relative z-10 min-h-[100svh] w-full overflow-y-auto text-neutral-900">

    {{-- NAV WRAPPER (sticky + rounded) --}}
    <div class="sticky top-0 z-20 px-3 sm:px-6 pt-3">
      <nav class="rounded-2xl border border-white/30 bg-white/20 backdrop-blur shadow">
        {{-- BARIS ATAS: logo kiri + actions kanan --}}
        <div class="h-16 w-full px-4 sm:px-6 flex items-center justify-between">
          <!-- KIRI: logo utama + teks (JANGAN DIHAPUS) -->
          <div class="flex min-w-0 items-center gap-3">
            <img src="/images/pemkot-batam.png" alt="Logo Instansi" class="h-12 aspect-[3/4] object-contain p-1"/>
            <div class="leading-tight">
              <div class="font-semibold text-white drop-shadow">DUKCAPIL PRIMA</div>
              <div class="text-xs text-white/80">INDONESIA MAJU</div>
            </div>
          </div>

          <!-- KANAN: teks Kemendagri (desktop-only) + partner logo + aksi -->
          <div class="flex items-center gap-3">
            {{-- HIDE on mobile, SHOW from md up --}}
            <div class="leading-tight hidden md:block text-right">
              <div class="font-semibold text-white drop-shadow">KEMENTERIAN DALAM NEGERI</div>
              <div class="text-xs text-white/80">REPUBLIK INDONESIA</div>
            </div>

            @if ($partnerLogo)
              @if ($partnerLogoLink)
                <a href="{{ $partnerLogoLink }}" target="_blank" rel="noopener"
                   class="block h-10 w-auto rounded-lg bg-white/20 hover:bg-white/30 border border-white/30 p-1">
                  <img src="{{ $partnerLogo }}" alt="{{ $partnerLogoTitle }}" class="h-full w-auto object-contain"/>
                </a>
              @else
                <div class="h-10 w-auto rounded-lg bg-white/20 border border-white/30 p-1">
                  <img src="{{ $partnerLogo }}" alt="{{ $partnerLogoTitle }}" class="h-full w-auto object-contain"/>
                </div>
              @endif
            @endif

            <button type="button"
              onclick="(document.documentElement.requestFullscreen?.()||document.documentElement.webkitRequestFullscreen?.()||document.documentElement.msRequestFullscreen?.())"
              class="rounded-lg px-3 py-2 text-sm text-white/90 hover:text-white hover:bg-white/10">
              Layar Penuh
            </button>
          </div>
        </div>
      </nav>
    </div>

    {{-- BODY --}}
    <main class="relative z-20 w-full">
      <div class="w-full px-4 sm:px-6 py-6">

        {{-- ALERT --}}
        @if (session('ok'))
          <div class="mb-4 rounded-2xl border border-white/30 bg-white/80 backdrop-blur px-4 py-3 text-sm text-green-900 shadow">
            {{ session('ok') }}
          </div>
        @endif

        {{-- ====== MODE: LANDING ====== --}}
        @if ($mode === 'landing')
          <section class="rounded-2xl border border-white/30 bg-white/85 backdrop-blur shadow p-6 sm:p-8 grid lg:grid-cols-2 gap-6 lg:gap-8">
            <div class="space-y-4 lg:col-span-2 text-center">
              <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">PEKA (Penilaian Emoji Kinerja Aparatur)</h1>
              <p class="text-neutral-700 max-w-2xl mx-auto">Bantu kami tingkatkan layanan. Proses cepat: pilih layanan, loket, petugas, kasih nilai, komentar opsional.</p>
            
              {{-- HANYA SATU TOMBOL, DI TENGAH --}}
              <div class="flex justify-center pt-1">
                <button type="button"
                  wire:click="start"
                  class="px-8 py-3 rounded-xl text-white font-semibold bg-gradient-to-r from-[#F59E0B] to-[#1E40AF] hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-white/60">
                  Mulai Penilaian
                </button>
              </div>
            </div>
          </section>
        @endif

        {{-- ====== MODE: FORM ====== --}}
        @if ($mode === 'form')
        <div class="grid grid-cols-12 gap-6">
          {{-- KIRI: FORM / STEP --}}
          <section class="col-span-12 lg:col-span-7 xl:col-span-8 flex flex-col">
            {{-- Header + dots --}}
            <div class="rounded-2xl border border-white/30 bg-white/85 backdrop-blur shadow p-5 mb-4">
              <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                  <h1 class="text-xl sm:text-2xl font-bold tracking-tight">PEKA (Penilaian Emoji Kinerja Aparatur)</h1>
                  <p class="text-sm text-neutral-600 mt-1">5 langkah ringkas. Klik dan jalan.</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  @for ($i=1; $i<=5; $i++)
                    <span class="h-2.5 w-2.5 rounded-full {{ $stage >= $i ? 'bg-[linear-gradient(90deg,#F59E0B,#1E40AF)] shadow' : 'bg-white/60' }}"></span>
                  @endfor
                </div>
              </div>
            </div>

            {{-- STAGE 1: PILIH LAYANAN --}}
            @if ($stage === 1)
              <div class="rounded-2xl border border-white/30 bg-white/85 backdrop-blur shadow p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                  <h2 class="text-lg font-semibold">Pilih Layanan</h2>
                  <button type="button" wire:click="backToLanding" class="text-sm underline text-black hover:text-neutral-700">Kembali ke Landing</button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  @foreach (($services ?? []) as $svc)
                    <button type="button"
                      wire:click="selectService({{ (int)$svc['id'] }})"
                      class="group relative w-full p-3 rounded-xl text-left transition border
                             {{ $serviceId==$svc['id']
                                ? 'border-transparent text-white bg-gradient-to-r from-[#F59E0B] to-[#1E40AF] shadow'
                                : 'border-white/40 bg-white/80 hover:bg-white' }}">
                      <span class="relative block font-medium whitespace-normal">{{ $svc['name'] }}</span>
                    </button>
                  @endforeach
                </div>
              </div>
            @endif

            {{-- STAGE 2: PILIH LOKET --}}
            @if ($stage === 2)
              <div class="rounded-2xl border border-white/30 bg-white/85 backdrop-blur shadow p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                  <h2 class="text-lg font-semibold">Pilih Loket</h2>
                  <div class="flex items-center gap-3">
                    <button type="button" wire:click="backToStart" class="text-sm underline text-black hover:text-neutral-700">Ulang dari Layanan</button>
                    <button type="button" wire:click="backToLanding" class="text-sm underline text-black hover:text-neutral-700">Kembali ke Landing</button>
                  </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                  @foreach (collect($counters)->sortBy('id') as $c)
                    <button type="button"
                            wire:click="selectCounter({{ (int)$c['id'] }})"
                            class="relative p-4 rounded-xl transition
                                   {{ $counterId==$c['id']
                                      ? 'text-white bg-gradient-to-r from-[#F59E0B] to-[#1E40AF] border-0 shadow'
                                      : 'border border-white/40 bg-white/80 hover:bg-white text-neutral-900' }}">
                      <span class="relative font-medium truncate">{{ $c['name'] }}</span>
                    </button>
                  @endforeach
                </div>
              </div>
            @endif

            {{-- STAGE 3: PILIH PETUGAS --}}
            @if ($stage === 3)
              <div class="rounded-2xl border border-white/30 bg-white/85 backdrop-blur shadow p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                  <h2 class="text-lg font-semibold">Pilih Petugas</h2>
                  <div class="flex items-center gap-3">
                    <button type="button" wire:click="$set('counterId', null)" class="text-sm underline text-black hover:text-neutral-700">Kembali ke Loket</button>
                    <button type="button" wire:click="backToStart" class="text-sm underline text-black hover:text-neutral-700">Ulang dari Layanan</button>
                    <button type="button" wire:click="backToLanding" class="text-sm underline text-black hover:text-neutral-700">Kembali ke Landing</button>
                  </div>
                </div>

                @if (!empty($staffOptions))
                  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach ($staffOptions as $s)
                      <button type="button"
                              wire:click="selectStaff({{ (int)$s['id'] }})"
                              class="relative p-3 rounded-xl text-left transition flex items-center gap-3
                                     {{ $staffId==$s['id']
                                        ? 'text-white bg-gradient-to-r from-[#F59E0B] to-[#1E40AF] border-0 shadow'
                                        : 'border border-white/40 bg-white/80 hover:bg-white text-neutral-900' }}">
                        <div class="w-20 md:w-24 aspect-[3/4] overflow-hidden rounded-lg bg-neutral-200 shrink-0">
                          <img
                            src="{{ ($s['photo_url'] ?? null) ?: 'https://ui-avatars.com/api/?name=' . urlencode($s['name']) . '&background=f1f5f9&color=0f172a&size=256' }}"
                            alt="Foto {{ $s['name'] }}"
                            class="h-full w-full object-cover"
                            loading="lazy" decoding="async">
                        </div>
                        <div class="min-w-0">
                          <div class="font-medium truncate">{{ $s['name'] }}</div>
                          @if ($selectedCounter)
                            <div class="text-xs text-neutral-600 truncate">{{ $selectedCounter['name'] }}</div>
                          @endif
                        </div>
                      </button>
                    @endforeach
                  </div>
                @else
                  <div class="text-sm text-neutral-700">Belum ada petugas aktif di loket ini.</div>
                @endif
              </div>
            @endif

            {{-- STAGE 4: NILAI --}}
            @if ($stage === 4)
              <div class="rounded-2xl border border-white/30 bg-white/85 backdrop-blur shadow p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                  <h2 class="text-lg font-semibold">Seberapa puas?</h2>
                  <button type="button" wire:click="$set('staffId', null)" class="text-sm underline text-black hover:text-neutral-700">Kembali ke Petugas</button>
                </div>

                <div role="radiogroup" aria-label="Pilih nilai" class="grid grid-cols-5 gap-3">
                  @for ($i=1; $i<=5; $i++)
                    @php $active = ($score === $i); @endphp
                    <button type="button"
                            role="radio"
                            aria-checked="{{ $active ? 'true' : 'false' }}"
                            aria-label="{{ $i }} - {{ $emojiLabels[$i] }}"
                            wire:click="$set('score', {{ $i }})"
                            class="h-24 rounded-xl border flex flex-col items-center justify-center text-center transition
                                   {{ $active
                                      ? 'text-white bg-gradient-to-r from-[#F59E0B] to-[#1E40AF] border-0 shadow'
                                      : 'border border-white/40 bg-white/80 hover:bg-white text-neutral-900' }}">
                      <span class="text-3xl leading-none">{{ $emojis[$i] }}</span>
                      <span class="mt-1 text-[11px]">{{ $emojiLabels[$i] }}</span>
                    </button>
                  @endfor
                </div>
              </div>
            @endif

            {{-- STAGE 5: KOMENTAR + SUBMIT --}}
            @if ($stage === 5)
              <div class="rounded-2xl border border-white/30 bg-white/85 backdrop-blur shadow p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                  <h2 class="text-lg font-semibold">Komentar (opsional)</h2>
                  <button type="button" wire:click="$set('score', null)" class="text-sm underline text-black hover:text-neutral-700">Ubah Nilai</button>
                </div>

                <textarea
                  wire:model="comment"
                  maxlength="200"
                  placeholder="Tulis komentar singkat (maks 200 karakter)"
                  class="w-full border border-white/40 bg-white/80 rounded-xl p-3 text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-white/60 mb-4"></textarea>

                <div class="flex items-center justify-between gap-3">
                  <button type="button" wire:click="backToLanding" class="px-4 py-3 rounded-xl border border-white/40 bg-white/80 hover:bg-white">Batal</button>
                  <button type="button" wire:click="submit" class="px-6 py-3 rounded-xl text-white font-semibold bg-gradient-to-r from-[#F59E0B] to-[#1E40AF] hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-white/60">
                    Kirim
                  </button>
                </div>
              </div>
            @endif
          </section>

          {{-- KANAN: RINGKASAN --}}
          <aside class="col-span-12 lg:col-span-5 xl:col-span-4">
            <div class="rounded-2xl border border-white/30 bg-white/85 backdrop-blur shadow p-5 mb-4">
              <h3 class="font-semibold text-neutral-900 mb-3">Ringkasan</h3>
              <dl class="text-sm space-y-2">
                <div class="flex items-start justify-between gap-3">
                  <dt class="text-neutral-600">Layanan</dt>
                  <dd class="font-medium text-right">{{ $selectedService['name'] ?? '—' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-3">
                  <dt class="text-neutral-600">Loket</dt>
                  <dd class="font-medium text-right">{{ $selectedCounter['name'] ?? '—' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-3">
                  <dt class="text-neutral-600">Petugas</dt>
                  <dd class="font-medium text-right">{{ $selectedStaff['name'] ?? '—' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-3">
                  <dt class="text-neutral-600">Nilai</dt>
                  <dd class="font-medium text-right">
                    @if ($score)
                      {{ $emojis[$score] }} <span class="text-xs text-neutral-600">({{ $emojiLabels[$score] }})</span>
                    @else
                      —
                    @endif
                  </dd>
                </div>
              </dl>
            </div>

            <div class="rounded-2xl border border-white/30 bg-white/85 backdrop-blur shadow p-5">
              <h3 class="font-semibold text-neutral-900 mb-3">Petugas Terpilih</h3>
              @if ($selectedStaff)
                <div class="flex items-start gap-4">
                  <div class="w-28 aspect-[3/4] overflow-hidden rounded-xl bg-neutral-200 shrink-0">
                    <img
                      src="{{ ($selectedStaff['photo_url'] ?? null) ?: 'https://ui-avatars.com/api/?name=' . urlencode($selectedStaff['name']) . '&background=f1f5f9&color=0f172a&size=256' }}"
                      alt="Foto {{ $selectedStaff['name'] }}"
                      class="h-full w-full object-cover"
                      loading="eager" decoding="async">
                  </div>
                  <div class="min-w-0">
                    <div class="font-semibold text-lg">{{ $selectedStaff['name'] }}</div>
                    @if ($selectedCounter)
                      <div class="text-sm text-neutral-600">{{ $selectedCounter['name'] }}</div>
                    @endif
                    @if ($selectedService)
                      <div class="text-xs text-neutral-500 mt-1">Layanan: {{ $selectedService['name'] }}</div>
                    @endif
                  </div>
                </div>
              @else
                <div class="text-sm text-neutral-600">Belum ada petugas terpilih.</div>
              @endif
            </div>
          </aside>
        </div>
        @endif
      </div>
    </main>
  </div>
</div>
