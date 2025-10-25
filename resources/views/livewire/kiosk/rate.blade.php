@php
  $orgName = config('app.org_name') ?? 'Dinas Kependudukan dan Pencatatan Sipil Kota Batam';
  $orgUnit = config('app.org_unit') ?? 'Disdukcapil Prima';
  $orgLogo = config('app.org_logo') ?? 'images/pemkot-batam.png';
@endphp

<div class="fixed inset-0 w-screen h-screen bg-white text-neutral-900 flex flex-col">
  {{-- NAVBAR: konteks instansi --}}
  <nav class="sticky top-0 z-20 w-full border-b border-neutral-200 bg-white/95 backdrop-blur">
    <div class="max-w-screen-2xl mx-auto h-16 px-4 sm:px-6 flex items-center justify-between">
      <div class="flex items-center gap-3 min-w-0">
        @if ($orgLogo)
          <img src="{{ asset($orgLogo) }}"
               alt="Logo {{ $orgName }}"
               class="h-9 w-9 rounded-lg object-contain"/>
        @else
          <div class="h-9 w-9 rounded-lg bg-emerald-500 text-white flex items-center justify-center font-bold">
            {{ \Illuminate\Support\Str::of($orgName)->trim()->substr(0,1)->upper() }}
          </div>
        @endif
        <div class="truncate">
          <div class="font-semibold leading-tight truncate">{{ $orgName }}</div>
          <div class="text-xs text-neutral-500 leading-tight truncate">{{ $orgUnit }} • Penilaian Emoji Kinerja Aparatur</div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button type="button" onclick="document.documentElement.requestFullscreen && document.documentElement.requestFullscreen()"
                class="hidden sm:inline-flex items-center rounded-lg border border-neutral-300 px-3 py-2 text-sm hover:bg-neutral-100 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          Layar penuh
        </button>
        <a href="#" class="rounded-lg border border-neutral-300 px-3 py-2 text-sm hover:bg-neutral-100 focus:outline-none focus:ring-2 focus:ring-emerald-500">Bantuan</a>
      </div>
    </div>
  </nav>

  {{-- MAIN CONTENT --}}
  <main class="flex-1 w-full flex items-center justify-center p-4">
    <div class="w-full max-w-xl">

      {{-- alert sukses --}}
      @if (session('ok'))
        <div class="mb-4 rounded-xl border border-green-500/30 bg-green-50 px-4 py-3 text-sm text-green-700" role="status" aria-live="polite">
          {{ session('ok') }}
        </div>
      @endif

      {{-- HEADER minimal --}}
      <div class="mb-4 text-center">
        <h1 class="text-3xl font-bold tracking-tight">Penilaian Layanan</h1>
        <p class="text-sm text-neutral-600 mt-1">Butuh ±10 detik. 3 langkah ringkas.</p>
        <p class="text-xs text-neutral-400 mt-2 italic">Respon Anda bersifat rahasia dan digunakan hanya untuk memperbaiki kualitas pelayanan kami.</p>
      </div>

      {{-- STEP 1: pilih loket --}}
      @if ($step === 1)
        <div class="rounded-2xl border border-neutral-200 bg-white shadow-sm p-5">
          <div class="text-sm text-neutral-600 mb-2">Silakan pilih loket yang baru saja melayani Anda</div>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            @foreach ($counters as $c)
              <button wire:click="selectCounter({{ $c['id'] }})"
                      class="rounded-2xl border border-neutral-300 bg-white px-4 py-4 text-left
                             transition-all duration-200
                             hover:border-emerald-500 hover:bg-emerald-50 hover:shadow-md
                             focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <div class="flex items-center gap-4">
                  <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 text-xl font-bold">
                    {{ preg_replace('/[^0-9]/','', $c['name']) ?: substr($c['name'],-2) }}
                  </div>
                  <div class="min-w-0">
                    <div class="font-medium truncate">{{ $c['name'] }}</div>
                    <div class="text-xs text-neutral-500">Pilih untuk lanjut</div>
                  </div>
                </div>
              </button>
            @endforeach
          </div>
        </div>
      @endif

      {{-- STEP 2: alur satu per satu --}}
      @if ($step === 2)
        @php $stage = !$serviceId ? 1 : (!$score ? 2 : 3); @endphp

        {{-- step dots --}}
        <div class="flex items-center justify-center gap-2 mb-3">
          @for ($i=1; $i<=3; $i++)
            <span class="h-2 w-2 rounded-full {{ $stage >= $i ? 'bg-emerald-500' : 'bg-neutral-300' }}"></span>
          @endfor
        </div>

        {{-- kartu petugas --}}
       <div class="rounded-2xl border border-neutral-200 bg-white shadow-sm p-5 mb-4">
  <div class="text-sm text-neutral-600 mb-2">Petugas yang Melayani</div>
  @if ($activeStaff)
    <div class="flex items-center gap-4">
      <div class="w-20 md:w-24 aspect-[3/4] overflow-hidden rounded-xl">
  <img
    src="{{ $activeStaff->photo_url ?: 'https://ui-avatars.com/api/?name=' . urlencode($activeStaff->name) . '&background=ffffff&color=000000&size=256' }}"
    alt="Foto Petugas"
    class="h-full w-full object-cover"
    loading="eager" decoding="async">
</div>

      <div>
        <div class="font-semibold text-xl">{{ $activeStaff->name }}</div>
        <div class="text-xs text-neutral-500">{{ collect($counters)->firstWhere('id', (int)$counterId)['name'] ?? '' }}</div>
      </div>
    </div>
  @else
    <div class="text-sm text-neutral-700">Belum ada petugas aktif di loket ini.</div>
  @endif
</div>


        {{-- STAGE 1: layanan --}}
        @if ($stage === 1)
          <div class="rounded-2xl border border-neutral-200 bg-white shadow-sm p-5">
            <div class="text-sm text-neutral-600 mb-3">Pilih jenis layanan</div>
            <div class="grid grid-cols-1 gap-3">
              @foreach ($services as $s)
                <button type="button" wire:click="$set('serviceId','{{ $s['id'] }}')"
                        class="w-full h-14 rounded-xl border text-base font-medium transition focus:outline-none focus:ring-2 focus:ring-emerald-500
                               {{ $serviceId == $s['id']
                                    ? 'border-emerald-500 bg-emerald-50 text-emerald-800'
                                    : 'border-neutral-300 bg-white hover:bg-emerald-50 hover:border-emerald-500 hover:shadow-sm' }}">
                  {{ $s['name'] }}
                </button>
              @endforeach
            </div>
            <div class="mt-4 flex items-center justify-between">
              <button type="button" wire:click="backToCounters"
                      class="h-12 rounded-xl border border-neutral-300 px-5 hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                Kembali
              </button>
              <button type="button" @if(!$serviceId) disabled @endif
                      class="h-12 rounded-xl bg-emerald-500 text-white px-6 font-semibold disabled:opacity-40">
                Lanjut
              </button>
            </div>
          </div>
        @endif

        {{-- STAGE 2: rating --}}
        @if ($stage === 2)
          <div class="rounded-2xl border border-neutral-200 bg-white shadow-sm p-5">
            <div class="text-sm text-neutral-600 mb-3">Seberapa puas?</div>
            @php
              $labels = [1=>'Sangat buruk',2=>'Buruk',3=>'Cukup',4=>'Baik',5=>'Sangat baik'];
              $emojis = [1=>'😡',2=>'☹️',3=>'😐',4=>'🙂',5=>'😍'];
            @endphp
            <div role="radiogroup" aria-label="Pilih nilai" class="grid grid-cols-5 gap-2">
              @for ($i=1; $i<=5; $i++)
                @php $active = ($score === $i); @endphp
                <button type="button"
                        role="radio"
                        aria-checked="{{ $active ? 'true' : 'false' }}"
                        aria-label="{{ $i }} - {{ $labels[$i] }}"
                        wire:click="$set('score', {{ $i }})"
                        class="h-20 rounded-xl border flex flex-col items-center justify-center text-center transition focus:outline-none focus:ring-2 focus:ring-emerald-500
                               {{ $active
                                    ? 'border-emerald-500 bg-emerald-50'
                                    : 'border-neutral-300 bg-white hover:bg-emerald-50 hover:border-emerald-500 hover:shadow-sm' }}">
                  <span class="text-3xl leading-none">{{ $emojis[$i] }}</span>
                  <span class="mt-1 text-[11px]">{{ $labels[$i] }}</span>
                </button>
              @endfor
            </div>
            <div class="mt-4 flex items-center justify-between">
              <button type="button" wire:click="$set('serviceId', null)"
                      class="h-12 rounded-xl border border-neutral-300 px-5 hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                Kembali
              </button>
              <button type="button" @if(!$score) disabled @endif
                      class="h-12 rounded-xl bg-emerald-500 text-white px-6 font-semibold disabled:opacity-40">
                Lanjut
              </button>
            </div>
          </div>
        @endif

        {{-- STAGE 3: komentar + submit --}}
        @if ($stage === 3)
          <div class="rounded-2xl border border-neutral-200 bg-white shadow-sm p-5">
            <div class="text-sm text-neutral-600 mb-2">Komentar (opsional)</div>
            <textarea wire:model="comment" maxlength="200"
                      placeholder="Tulis masukan singkat (maks 200 karakter)"
                      class="w-full rounded-xl border border-neutral-300 bg-white p-3 text-base placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            <div class="mt-2 flex items-center justify-between text-xs text-neutral-500">
              <button type="button" wire:click="$set('score', null)" class="underline-offset-4 hover:underline">Ubah nilai</button>
              <div>{{ 200 - mb_strlen($comment ?? '') }} sisa</div>
            </div>
            <div class="mt-4 flex items-center justify-end gap-3">
              <button wire:click="backToCounters"
                      class="h-12 rounded-xl border border-neutral-300 px-5 hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                Kembali
              </button>
              <button wire:click="submit"
                      class="h-12 rounded-xl bg-emerald-500 text-white px-6 font-semibold">
                Kirim
              </button>
            </div>
          </div>
        @endif

        {{-- Footer kecil opsional --}}
        <div class="mt-4 text-center text-xs text-neutral-400">© {{ date('Y') }} {{ $orgName }}. Semua hak dilindungi.</div>
      @endif

    </div>
  </main>
</div>
