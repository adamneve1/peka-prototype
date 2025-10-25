<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PEKA — Penilaian Layanan</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  <style>
    /* transisi “seamless” */
    .fade-enter { opacity: 0; }
    .fade-enter-active { opacity: 1; transition: opacity .18s ease-out; }
    .fade-exit { opacity: 1; }
    .fade-exit-active { opacity: 0; transition: opacity .18s ease-in; }
  </style>
</head>
<body class="min-h-screen bg-white text-neutral-900">
  <div id="root" class="fade-enter fade-enter-active">

    {{-- NAVBAR --}}
    <nav class="sticky top-0 z-20 w-full border-b border-neutral-200 bg-white/90 backdrop-blur">
      <div class="max-w-7xl mx-auto h-16 px-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <img src="/images/pemkot-batam.png" alt="Logo Instansi" class="h-12 aspect-[3/4] object-contain">
          <div class="leading-tight">
            <div class="font-semibold text-neutral-900">DUKCAPIL PRIMA</div>
            <div class="text-xs text-neutral-600">INDONESIA MAJU</div>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <a href="{{ route('kiosk') }}" class="rounded-lg px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-100">
            Versi Lama
          </a>
          <button type="button"
            onclick="(document.documentElement.requestFullscreen?.()||document.documentElement.webkitRequestFullscreen?.()||document.documentElement.msRequestFullscreen?.())"
            class="rounded-lg px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-100">Layar Penuh</button>
        </div>
      </div>
    </nav>

    {{-- HERO / HEADER --}}
    <header class="py-16">
      <div class="max-w-7xl mx-auto px-6 grid lg:grid-cols-12 gap-10 items-center">
        <section class="lg:col-span-7">
          <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight">
            PEKA (PENILAIAN EMOJI KINERJA APARATUR)
          </h1>
          <p class="mt-4 text-lg text-neutral-700 max-w-xl">
            Kasih penilaian cepat untuk pengalaman layanan kamu. 5 langkah ringkas: pilih layanan, loket, petugas, skor, komentar.
          </p>

          <div class="mt-8 flex flex-wrap items-center gap-3">
            {{-- CTA ke form baru --}}
            <a href="{{ route('peka.page') }}"
               data-transition
               class="inline-flex items-center justify-center px-6 py-4 rounded-2xl font-semibold text-white bg-neutral-900 hover:bg-neutral-800">
              Mulai Penilaian
            </a>

            <a href="#cara" class="inline-flex items-center justify-center px-5 py-4 rounded-2xl font-semibold border border-neutral-300 hover:bg-neutral-50">
              Lihat Cara Kerja
            </a>
          </div>

          {{-- bullet points singkat --}}
          <dl class="mt-8 grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
            <div class="rounded-xl border border-neutral-200 p-4">
              <dt class="font-semibold">Cepat</dt>
              <dd class="text-neutral-600 mt-1">±30 detik</dd>
            </div>
            <div class="rounded-xl border border-neutral-200 p-4">
              <dt class="font-semibold">Transparan</dt>
              <dd class="text-neutral-600 mt-1">Skor & komentar terekam</dd>
            </div>
            <div class="rounded-xl border border-neutral-200 p-4">
              <dt class="font-semibold">Sederhana</dt>
              <dd class="text-neutral-600 mt-1">5 langkah jelas</dd>
            </div>
          </dl>
        </section>

        <aside class="lg:col-span-5">
          <div class="rounded-2xl border border-neutral-200 p-5">
            <h2 class="font-semibold">Scan QR buat mulai</h2>
            <p class="text-sm text-neutral-600 mt-1">Atau klik “Mulai Penilaian”.</p>
            {{-- pakai QR statis atau ganti dengan simple-qrcode --}}
            <img src="/images/qr-peka.png" alt="QR ke Form PEKA" class="mt-4 w-full rounded-lg object-contain bg-white">
            <div class="mt-3 text-xs text-neutral-500">URL: {{ route('peka.page') }}</div>
          </div>
        </aside>
      </div>
    </header>

    {{-- HOW IT WORKS --}}
    <section id="cara" class="pb-16">
      <div class="max-w-7xl mx-auto px-6">
        <h3 class="text-2xl font-bold">Gimana prosesnya</h3>
        <ol class="mt-6 grid md:grid-cols-5 gap-3">
          <li class="rounded-xl border border-neutral-200 p-4"><span class="text-xs text-neutral-500">Langkah 1</span><div class="font-semibold">Pilih Layanan</div></li>
          <li class="rounded-xl border border-neutral-200 p-4"><span class="text-xs text-neutral-500">Langkah 2</span><div class="font-semibold">Pilih Loket</div></li>
          <li class="rounded-xl border border-neutral-200 p-4"><span class="text-xs text-neutral-500">Langkah 3</span><div class="font-semibold">Pilih Petugas</div></li>
          <li class="rounded-xl border border-neutral-200 p-4"><span class="text-xs text-neutral-500">Langkah 4</span><div class="font-semibold">Kasih Skor</div></li>
          <li class="rounded-xl border border-neutral-200 p-4"><span class="text-xs text-neutral-500">Langkah 5</span><div class="font-semibold">Komentar</div></li>
        </ol>
        <div class="mt-8">
          <a href="{{ route('peka.page') }}" data-transition class="inline-flex items-center justify-center px-6 py-4 rounded-2xl font-semibold text-white bg-neutral-900 hover:bg-neutral-800">
            Mulai Penilaian Sekarang
          </a>
        </div>
      </div>
    </section>

    {{-- FOOTER --}}
    <footer class="mt-auto border-t border-neutral-200 bg-white">
      <div class="max-w-7xl mx-auto px-6 h-14 flex items-center justify-between text-sm">
        <span>© {{ date('Y') }} DUKCAPIL</span>
        <a href="{{ route('peka.page') }}" data-transition class="underline hover:text-neutral-700">Form Penilaian</a>
      </div>
    </footer>
  </div>

  <script>
    // “Seamless” fade-out pas keluar landing
    const root = document.getElementById('root');
    document.querySelectorAll('a[data-transition]').forEach(a => {
      a.addEventListener('click', (e) => {
        e.preventDefault();
        const url = a.getAttribute('href');
        root.classList.remove('fade-enter','fade-enter-active');
        root.classList.add('fade-exit','fade-exit-active');
        setTimeout(() => { window.location.href = url; }, 180);
      });
    });
  </script>
</body>
</html>
